<?php
/**
 * Audit Service
 * Logs all significant actions for compliance and tracking.
 *
 * `audit_log` is the SINGLE source of truth for the audit trail. Every write
 * funnels through log(); the historic logCreated/logUpdated/... methods are
 * kept as thin wrappers so existing call sites do not change, and they still
 * mirror asset events into `asset_history` (the per-asset detail pages read
 * that table directly).
 *
 * Auditing can be switched off with config/app.php `audit.enabled => false`.
 */

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\AssetHistory;

class AuditService
{
    private Session $session;
    private Request $request;

    /** Cached config/app.php contents (null = not loaded yet). */
    private static ?array $config = null;
    /** Cached asset tags used as `entity_ref`, keyed by asset id. */
    private static array $assetTags = [];

    public function __construct()
    {
        $this->session = Session::getInstance();
        $this->request = new Request();
    }

    /**
     * The one canonical audit write. Persists to `audit_log` only.
     *
     * @param string      $action      e.g. 'created', 'user_updated', 'login_failed'
     * @param string      $entityType  asset|ticket|license|user|department|setting|
     *                                 topology|message|disposal|backup|auth|mail|
     *                                 instruction ('' = no entity, stored as NULL)
     * @param int|null    $entityId    Numeric id of the affected record
     * @param string|null $entityRef   Human reference (asset_tag, ticket_number, 'License #4')
     * @param string|null $field       Changed field, when the event is a field edit
     * @param mixed       $old         Previous value (cast to string, NULL-safe)
     * @param mixed       $new         New value (cast to string, NULL-safe)
     * @param string|null $description Free-text explanation
     */
    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $entityRef = null,
        ?string $field = null,
        mixed $old = null,
        mixed $new = null,
        ?string $description = null
    ): void {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $db = Database::getInstance();
            $db->insert('audit_log', [
                'user_id'       => $this->getUserId(),
                'action'        => substr($action, 0, 50),
                'description'   => $description,
                'ip_address'    => $this->request->ip(),
                'created_at'    => date('Y-m-d H:i:s'),
                // '' means "no entity" -> NULL keeps filters and joins clean.
                'entity_type'   => $entityType !== '' ? substr($entityType, 0, 30) : null,
                'entity_id'     => $entityId,
                'entity_ref'    => $entityRef !== null ? substr($entityRef, 0, 80) : null,
                'field_changed' => $field !== null ? substr($field, 0, 50) : null,
                'old_value'     => $this->toText($old),
                'new_value'     => $this->toText($new),
            ]);
        } catch (\Throwable $e) {
            // Fail-soft: an audit failure must never break the user's action.
            error_log('AuditService::log failed [' . $action . ']: ' . $e->getMessage());
        }
    }

    /**
     * Log an asset creation
     */
    public function logCreated(int $assetId, array $data): void
    {
        $ref = $this->assetRef($assetId, $data['asset_tag'] ?? null);

        AssetHistory::log(
            $assetId,
            $this->getUserId(),
            'created',
            null,
            null,
            json_encode($data)
        );

        $this->log('created', 'asset', $assetId, $ref, null, null, $data, 'Asset ' . ($ref ?? '#' . $assetId) . ' created');
    }

    /**
     * Log an asset update
     */
    public function logUpdated(int $assetId, string $field, mixed $oldValue, mixed $newValue): void
    {
        AssetHistory::log(
            $assetId,
            $this->getUserId(),
            'updated',
            $field,
            (string) $oldValue,
            (string) $newValue
        );

        $this->log('updated', 'asset', $assetId, $this->assetRef($assetId), $field, $oldValue, $newValue);
    }

    /**
     * Log an asset deletion
     */
    public function logDeleted(int $assetId, string $assetTag): void
    {
        AssetHistory::log(
            $assetId,
            $this->getUserId(),
            'deleted',
            null,
            $assetTag,
            null
        );

        $this->log('deleted', 'asset', $assetId, $this->assetRef($assetId, $assetTag), null, $assetTag, null, 'Asset ' . $assetTag . ' deleted');
    }

    /**
     * Log asset assignment change
     */
    public function logAssignment(int $assetId, ?int $oldUserId, ?int $newUserId): void
    {
        AssetHistory::log(
            $assetId,
            $this->getUserId(),
            'assigned',
            'assigned_to',
            (string) $oldUserId,
            (string) $newUserId
        );

        $this->log('assigned', 'asset', $assetId, $this->assetRef($assetId), 'assigned_to', $oldUserId, $newUserId);
    }

    /**
     * Log asset status change
     */
    public function logStatusChange(int $assetId, string $oldStatus, string $newStatus): void
    {
        AssetHistory::log(
            $assetId,
            $this->getUserId(),
            'updated',
            'status',
            $oldStatus,
            $newStatus
        );

        $this->log('updated', 'asset', $assetId, $this->assetRef($assetId), 'status', $oldStatus, $newStatus);
    }

    /**
     * Log bulk action
     */
    public function logBulkAction(string $action, array $assetIds, ?string $details = null): void
    {
        foreach ($assetIds as $assetId) {
            $assetId = (int) $assetId;

            AssetHistory::log(
                $assetId,
                $this->getUserId(),
                'updated',
                'bulk_' . $action,
                null,
                $details
            );

            $this->log('updated', 'asset', $assetId, $this->assetRef($assetId), 'bulk_' . $action, null, $details, 'Bulk ' . $action);
        }
    }

    /**
     * Log a generic action (ticket_created, license_assigned, disposal_approved,
     * ticket_backup, login, ...).
     *
     * Existing call sites pass a human reference only, so the entity type is
     * inferred from the action name and the entity id from the reference
     * ('Disposal #7', 'License #4', '12') — that keeps /history linkable
     * without touching those controllers.
     */
    public function logAction(string $action, ?string $reference, ?string $description = null): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $entityType = $this->inferEntityType($action);
        $entityId = $this->extractId($reference);

        // Tickets sometimes log the number, sometimes the row id — resolve the
        // id from the number so the audit UI can link to the ticket.
        if ($entityType === 'ticket' && $entityId === null && $reference !== null && $reference !== '') {
            $entityId = $this->resolveTicketId($reference);
        }

        // Legacy description shape is preserved for backwards compatibility.
        $fullDescription = $description;
        if ($reference !== null && $reference !== '') {
            $fullDescription = ($fullDescription ? $fullDescription . ' — ' : '') . 'ref: ' . $reference;
        }

        $this->log($action, (string) $entityType, $entityId, $reference, null, null, null, $fullDescription);
    }

    /**
     * Map an action name onto an entity type.
     */
    private function inferEntityType(string $action): ?string
    {
        // Most specific prefixes first ('asset_disposed' is a disposal event).
        if (str_starts_with($action, 'disposal_') || $action === 'asset_disposed' || $action === 'data_destruction_performed') {
            return 'disposal';
        }
        if (str_starts_with($action, 'asset_')) {
            return 'asset';
        }
        if ($action === 'ticket_backup' || str_starts_with($action, 'backup')) {
            return 'backup';
        }
        if (str_starts_with($action, 'ticket_')) {
            return 'ticket';
        }
        if (str_starts_with($action, 'instruction_')) {
            return 'instruction';
        }
        if (str_starts_with($action, 'license_')) {
            return 'license';
        }
        if (str_starts_with($action, 'user_')) {
            return 'user';
        }
        if (str_starts_with($action, 'department_')) {
            return 'department';
        }
        if (str_starts_with($action, 'setting')) {
            return 'setting';
        }
        if (str_starts_with($action, 'topology_') || str_starts_with($action, 'link_')) {
            return 'topology';
        }
        if (str_starts_with($action, 'message_')) {
            return 'message';
        }
        if (str_starts_with($action, 'mail_')) {
            return 'mail';
        }
        if (in_array($action, [
            'login', 'logout', 'login_failed', 'account_locked',
            'profile_update', 'password_change', 'session_restored',
        ], true)) {
            return 'auth';
        }

        return null;
    }

    /**
     * Pull a numeric record id out of a human reference
     * ('7', '#7', 'Disposal #7' => 7; 'TKT-2026-0001' => null).
     */
    private function extractId(?string $reference): ?int
    {
        if ($reference === null) {
            return null;
        }
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }
        if (preg_match('/^#?(\d+)$/', $reference, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/#\s*(\d+)\s*$/', $reference, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Resolve a concern (ticket) row id from its ticket_number.
     */
    private function resolveTicketId(string $ticketNumber): ?int
    {
        try {
            $row = Database::getInstance()->fetch(
                'SELECT id FROM concerns WHERE ticket_number = ? LIMIT 1',
                [$ticketNumber]
            );

            return $row ? (int) $row['id'] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Asset tag used as `entity_ref` (cached per request).
     */
    private function assetRef(int $assetId, ?string $knownTag = null): ?string
    {
        if ($knownTag !== null && $knownTag !== '') {
            return $knownTag;
        }
        if (array_key_exists($assetId, self::$assetTags)) {
            return self::$assetTags[$assetId];
        }

        $tag = null;
        try {
            $row = Database::getInstance()->fetch('SELECT asset_tag FROM assets WHERE id = ? LIMIT 1', [$assetId]);
            $tag = $row['asset_tag'] ?? null;
        } catch (\Throwable $e) {
            $tag = null;
        }

        self::$assetTags[$assetId] = $tag;

        return $tag;
    }

    /**
     * NULL-safe value casting for the old_value / new_value TEXT columns.
     */
    private function toText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value) || is_object($value)) {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $json === false ? null : $json;
        }

        return (string) $value;
    }

    /**
     * Is auditing enabled? (config/app.php 'audit' => ['enabled' => bool])
     */
    public function isEnabled(): bool
    {
        if (self::$config === null) {
            try {
                self::$config = require CONFIG_PATH . '/app.php';
            } catch (\Throwable $e) {
                self::$config = [];
            }
        }

        return (bool) (self::$config['audit']['enabled'] ?? true);
    }

    /**
     * Get current user ID, or NULL when the request has no session user
     * (CLI worker, failed login, ...). Never 0 — that would violate the FK.
     */
    private function getUserId(): ?int
    {
        $user = $this->session->getUser();
        $id = $user['id'] ?? null;

        return $id === null ? null : (int) $id;
    }
}
