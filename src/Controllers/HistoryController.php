<?php
/**
 * History/Audit Controller
 *
 * The unified audit trail: `audit_log` is the single source of truth.
 * `asset_history` is still read by asset() only, for the per-asset detail
 * history dialog on the asset page.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\AssetHistory;

class HistoryController extends Controller
{
    private const PER_PAGE = 50;

    /**
     * Whitelisted ORDER BY columns. Request data is never interpolated into
     * SQL (SECURITY_REMEDIATION_PROMPT.md F6).
     */
    private const SORTABLE = [
        'time'   => 'a.created_at',
        'user'   => 'a.user_id',
        'action' => 'a.action',
        'entity' => 'a.entity_type',
        'ip'     => 'a.ip_address',
    ];

    /**
     * Display the unified audit log
     */
    public function index(): void
    {
        if (!$this->requireRole('admin', 'viewer')) {
            return;
        }

        $filters = $this->filters();

        // CSV export is admin-only — viewers never get the export link.
        if ($this->request->query('export') === 'csv') {
            if (!$this->requireRole('admin')) {
                return;
            }
            $this->exportCsv($filters);
            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = self::PER_PAGE;
        $offset = ($page - 1) * $perPage;

        [$where, $params] = $this->buildWhere($filters);

        $db = Database::getInstance();
        $total = (int) ($db->fetch("SELECT COUNT(*) AS count FROM audit_log a{$where}", $params)['count'] ?? 0);

        // Every filter value is bound; only the (whitelisted) ORDER BY is built
        // by string concatenation.
        $entries = $db->fetchAll(
            "SELECT a.*, u.full_name AS user_name, u.username AS user_username, u.avatar AS user_avatar
             FROM audit_log a
             LEFT JOIN users u ON u.id = a.user_id
             {$where}
             " . $this->orderSql($filters) . "
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        $this->render('history/index', [
            'title' => 'Audit Log',
            'entries' => $this->decorate($entries),
            'total' => $total,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
            'filters' => $filters,
            'entityTypes' => $this->distinctValues('entity_type'),
            'actions' => $this->distinctValues('action'),
            'auditUsers' => $db->fetchAll("SELECT id, full_name, username FROM users ORDER BY full_name ASC"),
            'canExport' => ($this->currentUser['role'] ?? '') === 'admin',
        ]);
    }

    /**
     * Get history for a specific asset (AJAX) — per-asset detail page.
     */
    public function asset(int $id): void
    {
        if (!$this->requireRole('admin', 'viewer')) {
            return;
        }
        $history = AssetHistory::getByAsset($id);
        $this->json(['success' => true, 'data' => $history]);
    }

    /**
     * Read + normalise the GET filters. Unknown sort keys fall back to 'time'.
     */
    private function filters(): array
    {
        $sort = (string) $this->request->query('sort', 'time');
        $dir = strtolower((string) $this->request->query('dir', 'desc'));

        return [
            'entity_type' => trim((string) $this->request->query('entity_type', '')),
            'action'      => trim((string) $this->request->query('action', '')),
            'user_id'     => max(0, (int) $this->request->query('user_id', 0)),
            'from'        => $this->validDate($this->request->query('from')),
            'to'          => $this->validDate($this->request->query('to')),
            'sort'        => array_key_exists($sort, self::SORTABLE) ? $sort : 'time',
            'dir'         => $dir === 'asc' ? 'asc' : 'desc',
        ];
    }

    /**
     * Only ISO dates pass through; anything else is ignored, not interpolated.
     */
    private function validDate(mixed $value): string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : '';
    }

    /**
     * Build the WHERE clause with bound parameters only.
     *
     * @return array{0:string,1:array}
     */
    private function buildWhere(array $filters): array
    {
        $conditions = [];
        $params = [];

        if ($filters['entity_type'] !== '') {
            $conditions[] = 'a.entity_type = ?';
            $params[] = $filters['entity_type'];
        }
        if ($filters['action'] !== '') {
            $conditions[] = 'a.action = ?';
            $params[] = $filters['action'];
        }
        if ($filters['user_id'] > 0) {
            $conditions[] = 'a.user_id = ?';
            $params[] = $filters['user_id'];
        }
        if ($filters['from'] !== '') {
            $conditions[] = 'a.created_at >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if ($filters['to'] !== '') {
            $conditions[] = 'a.created_at <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }

        return [$conditions ? ' WHERE ' . implode(' AND ', $conditions) : '', $params];
    }

    /**
     * ORDER BY built from the whitelist — never from raw request input (F6).
     */
    private function orderSql(array $filters): string
    {
        $column = self::SORTABLE[$filters['sort']] ?? self::SORTABLE['time'];
        $direction = $filters['dir'] === 'asc' ? 'ASC' : 'DESC';

        return ' ORDER BY ' . $column . ' ' . $direction . ', a.id ' . $direction;
    }

    /**
     * Distinct values for the filter dropdowns (fixed internal column names).
     */
    private function distinctValues(string $column): array
    {
        if (!in_array($column, ['entity_type', 'action'], true)) {
            return [];
        }

        $rows = Database::getInstance()->fetchAll(
            "SELECT DISTINCT {$column} AS value FROM audit_log
             WHERE {$column} IS NOT NULL AND {$column} <> ''
             ORDER BY {$column} ASC"
        );

        return array_column($rows, 'value');
    }

    /**
     * Add the entity label + link for each row (view stays presentation-only).
     */
    private function decorate(array $entries): array
    {
        foreach ($entries as &$entry) {
            $type = (string) ($entry['entity_type'] ?? '');
            $ref = (string) ($entry['entity_ref'] ?? '');
            $id = isset($entry['entity_id']) && $entry['entity_id'] !== null ? (int) $entry['entity_id'] : null;

            $parts = [];
            if ($type !== '') {
                $parts[] = ucfirst($type);
            }
            if ($ref !== '') {
                $parts[] = $ref;
            }
            $entry['entity_label'] = $parts ? implode(' · ', $parts) : '—';
            $entry['entity_url'] = $this->entityLink($type, $id);
        }
        unset($entry);

        return $entries;
    }

    /**
     * Where the audited record lives, when the app can show it.
     */
    private function entityLink(string $type, ?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        return match ($type) {
            'asset'      => url('/assets/' . $id),
            'ticket'     => url('/tickets/' . $id . '/edit'),
            'license'    => url('/licenses/' . $id),
            'user'       => url('/users/' . $id . '/edit'),
            'department' => url('/departments/' . $id . '/assets'),
            'disposal'   => url('/disposals/' . $id),
            'topology'   => url('/topology'),
            'setting'    => url('/settings'),
            'message'    => url('/messages'),
            'backup'     => url('/tickets/backup'),
            default      => null,
        };
    }

    /**
     * Stream the (filtered) audit log as CSV. Admin-only, capped at 10k rows.
     */
    private function exportCsv(array $filters): void
    {
        [$where, $params] = $this->buildWhere($filters);

        $rows = Database::getInstance()->fetchAll(
            "SELECT a.created_at, a.action, a.entity_type, a.entity_ref, a.field_changed,
                    a.old_value, a.new_value, a.description, a.ip_address,
                    COALESCE(u.full_name, u.username, 'System') AS actor
             FROM audit_log a
             LEFT JOIN users u ON u.id = a.user_id
             {$where}
             " . $this->orderSql($filters) . "
             LIMIT 10000",
            $params
        );

        $this->response->header('Content-Type', 'text/csv; charset=utf-8');
        $this->response->header('Content-Disposition', 'attachment; filename="audit_log_' . date('Y-m-d_His') . '.csv"');
        $this->response->header('Cache-Control', 'no-cache');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Time', 'Actor', 'Action', 'Entity type', 'Entity ref', 'Field', 'Old value', 'New value', 'Details', 'IP'], ',', '"', '');

        foreach ($rows as $row) {
            fputcsv($out, array_map([$this, 'csvCell'], [
                $row['created_at'],
                $row['actor'],
                $row['action'],
                $row['entity_type'],
                $row['entity_ref'],
                $row['field_changed'],
                $row['old_value'],
                $row['new_value'],
                $row['description'],
                $row['ip_address'],
            ]), ',', '"', '');
        }

        fclose($out);
        exit;
    }

    /**
     * Neutralise CSV formula injection: cells starting with = + - @ (or a
     * control char) are prefixed with an apostrophe, and newlines are folded
     * so one audit row stays on one CSV line.
     */
    private function csvCell(mixed $value): string
    {
        $value = str_replace(["\r\n", "\r", "\n"], ' ', (string) ($value ?? ''));

        if ($value !== '' && (str_contains('=+-@', $value[0]) || ord($value[0]) < 32)) {
            return "'" . $value;
        }

        return $value;
    }
}

