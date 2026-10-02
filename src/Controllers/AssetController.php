<?php
/**
 * Asset Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\Department;
use App\Models\AssetHistory;
use App\Services\AuditService;
use App\Services\ExportService;
use App\Helpers\Security;

class AssetController extends Controller
{
    private AuditService $auditService;

public function __construct()
    {
        parent::__construct();
        $this->requireRole('admin', 'viewer');
        $this->auditService = new AuditService();
    }

    /**
     * List all assets
     */
    public function index(): void
    {
        $page = (int) ($this->request->query('page', 1));
        $search = $this->request->query('search', '');
        $type = $this->request->query('type', '');
        $status = $this->request->query('status', '');
        $departmentId = (int) $this->request->query('department_id', 0);

        if ($search || $type || $status || $departmentId) {
            $assets = Asset::searchAssets($search, $type, $status, $departmentId);
            $total = count($assets);
            $totalPages = 1;
        } else {
            $result = Asset::paginate($page, PER_PAGE);
            $assets = $result['data'];
            $total = $result['total'];
            $totalPages = $result['total_pages'];
        }

        $departments = Department::getOptions();
        $typeCounts = Asset::getCountByType();

        $this->render('assets/index', [
            'title' => 'Asset Ledger',
            'assets' => $assets,
            'departments' => $departments,
            'typeCounts' => $typeCounts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'filterType' => $type,
            'filterStatus' => $status,
            'filterDepartment' => $departmentId,
        ]);
    }

    /**
     * Show asset creation form
     */
    public function create(): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $departments = Department::getOptions();
        $nextTag = Asset::generateAssetTag();

        $this->render('assets/create', [
            'title' => 'Add New Asset',
            'departments' => $departments,
            'nextTag' => $nextTag,
        ]);
    }

    /**
     * JSON endpoint for the create form: a fresh org-format asset tag
     * (ORG-YYYY-DEPT-NNNN) for the chosen department / acquisition year.
     * GET /assets/next-tag?department_id=&year=
     */
    public function nextTag(): void
    {
        // Part of the admin-only create flow; viewers must not call it.
        if (!$this->requireRole('admin')) {
            return;
        }

        $year = trim((string) $this->request->query('year', ''));
        if ($year !== '' && !preg_match('/^(19|20)\d{2}$/', $year)) {
            $this->json(['success' => false, 'error' => 'Invalid year.'], 422);
            return;
        }

        $departmentId = (int) $this->request->query('department_id', 0);

        $tag = Asset::generateAssetTag(
            $departmentId > 0 ? $departmentId : null,
            $year !== '' ? $year : null
        );

        $this->json(['success' => true, 'tag' => $tag]);
    }

    /**
     * Printable asset label sheet (QR code + tag) for physical stickers.
     * GET /assets/labels?department_id=&status=&ids=1,2,3
     */
    public function labels(): void
    {
        // Label sheets get revisited after edits (sizes, new tags). Without
        // this, Chrome/Edge may serve a stale copy (with stale JS) from the
        // back/forward cache.
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        $departmentId = (int) $this->request->query('department_id', 0);
        $status = strtolower(trim((string) $this->request->query('status', '')));
        $validStatuses = ['active', 'inactive', 'maintenance', 'retired', 'lost', 'reserved'];

        $where = "a.asset_tag IS NOT NULL AND a.asset_tag <> ''";
        $params = [];

        // Explicit selection, e.g. from an asset's "Print Label" button.
        $idsRaw = trim((string) $this->request->query('ids', ''));
        if ($idsRaw !== '') {
            $ids = array_values(array_filter(array_map('intval', explode(',', $idsRaw))));
            if ($ids) {
                $where .= ' AND a.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
                $params = array_merge($params, $ids);
            }
        }

        if ($departmentId > 0) {
            $where .= ' AND a.department_id = ?';
            $params[] = $departmentId;
        }
        if (in_array($status, $validStatuses, true)) {
            $where .= ' AND a.status = ?';
            $params[] = $status;
        }

        $assets = Asset::getAllWithRelations($where, $params, 'a.asset_tag', 'ASC');

        // The QR carries the asset data itself (tag/model/host/IP), so the
        // sheet no longer needs a request-host dependent URL.
        $this->render('assets/labels', [
            'assets' => $assets,
            'departments' => Department::getOptions(),
            'deptFilter' => $departmentId,
            'statusFilter' => $status,
            'pageTitle' => 'Print Asset Labels',
            'layout' => 'layouts/print',
        ]);
    }

    /**
     * Store a new asset
     */
    public function store(): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $data = $this->request->only([
            'asset_tag', 'type', 'hostname', 'ip_address', 'mac_address',
            'vendor', 'model', 'serial_number', 'os', 'cpu', 'ram_gb', 'storage_gb',
            'status', 'purchase_date', 'warranty_end', 'location', 'floor',
            'department_id', 'section', 'assigned_to', 'notes'
        ]);

        // Validate
        $errors = [];
        if (empty($data['asset_tag'])) $errors[] = 'Asset tag is required.';
        if (empty($data['type'])) $errors[] = 'Asset type is required.';
        
        if (!empty($data['ip_address']) && !filter_var($data['ip_address'], FILTER_VALIDATE_IP)) {
            $errors[] = 'Invalid IP address format.';
        }
        if (!empty($data['mac_address']) && !Security::sanitizeMac($data['mac_address'])) {
            $errors[] = 'Invalid MAC address format.';
        }
        if (!empty($data['ram_gb']) && (!is_numeric($data['ram_gb']) || $data['ram_gb'] <= 0)) {
            $errors[] = 'RAM must be a positive number.';
        }
        if (!empty($data['storage_gb']) && (!is_numeric($data['storage_gb']) || $data['storage_gb'] <= 0)) {
            $errors[] = 'Storage must be a positive number.';
        }

        if (!empty($errors)) {
            $this->session->setFlash('message', implode('<br>', $errors));
            $this->session->setFlash('message_type', 'danger');
            $this->redirect('/assets/create');
            return;
        }

        // Convert empty foreign keys to null
        if (empty($data['department_id'])) $data['department_id'] = null;

        // Save typed "assigned to" text verbatim, and resolve user ID when possible
        $assignedName = isset($data['assigned_to']) ? trim((string) $data['assigned_to']) : '';
        $data['assigned_name'] = $assignedName !== '' ? $assignedName : null;
        $data['assigned_to'] = $this->resolveAssignedUser($assignedName);

        // Set created_by
        $data['created_by'] = $this->currentUser['id'];

        // Sanitize MAC
        if (!empty($data['mac_address'])) {
            $data['mac_address'] = Security::sanitizeMac($data['mac_address']);
        }

        // Convert empty strings to null for nullable fields with UNIQUE constraints
        if (empty($data['serial_number'])) $data['serial_number'] = null;

        $assetId = null;
        try {
            $assetId = Asset::create($data);
        } catch (\PDOException $e) {
            // Final safety net: two admins on the create form can race and end up
            // with the same generated tag. On an asset_tag UNIQUE violation only,
            // regenerate the tag once and retry; anything else is a real error.
            if (!$this->isDuplicateAssetTagError($e)) {
                throw $e;
            }

            $year = null;
            if (preg_match('/^(19|20)\d{2}/', (string) ($data['purchase_date'] ?? ''), $m)) {
                $year = $m[0]; // $m[1] is only the century; $m[0] is the full year
            }
            $data['asset_tag'] = Asset::generateAssetTag(
                !empty($data['department_id']) ? (int) $data['department_id'] : null,
                $year
            );
            $assetId = Asset::create($data);
        }

        if ($assetId) {
            $this->auditService->logCreated($assetId, $data);
            $this->redirectWith('/assets/' . $assetId, 'Asset created successfully.', 'success');
        } else {
            $this->redirectWith('/assets/create', 'Failed to create asset.', 'danger');
        }
    }

    /**
     * Show asset details
     */
    public function show(int $id): void
    {
        $asset = Asset::findWithRelations($id);
        if (!$asset) {
            $this->redirectWith('/assets', 'Asset not found.', 'danger');
            return;
        }

        $history = AssetHistory::getByAsset($id);
        $disposal = null;
        
        // Check if asset has a disposal record
        if ($asset['status'] === 'retired' || $asset['status'] === 'disposed') {
            $disposal = AssetDisposal::findByAssetId($id);
        }

        $this->render('assets/show', [
            'title' => 'Asset: ' . ($asset['asset_tag'] ?? ''),
            'asset' => $asset,
            'history' => $history,
            'disposal' => $disposal,
        ]);
    }

    /**
     * Retire an asset - automatically create a disposal record
     */
    public function retire(int $id): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $asset = Asset::find($id);
        if (!$asset) {
            $this->redirectWith('/assets', 'Asset not found.', 'danger');
            return;
        }

        // Prevent retiring disposed assets
        if ($asset['status'] === 'disposed') {
            $this->redirectWith('/assets/' . $id, 'Asset is already disposed and cannot be retired.', 'warning');
            return;
        }

        // Check if already retired
        if ($asset['status'] === 'retired') {
            $existingDisposal = AssetDisposal::findByAssetId($id);
            if ($existingDisposal) {
                $this->redirectWith('/disposals/' . $existingDisposal['id'], 'Asset is already retired with an existing disposal record.', 'info');
            } else {
                $this->redirectWith('/disposals/create/' . $id, 'Asset is already retired. Create a disposal request.', 'info');
            }
            return;
        }

        $retirementReason = $this->request->input('retirement_reason', 'end_of_life');
        $notes = $this->request->input('notes', '');

        // Update asset status to retired
        Asset::update($id, [
            'status' => 'retired',
            'retirement_reason' => $retirementReason,
            'retired_at' => date('Y-m-d H:i:s'),
        ]);

        // Record the action in asset history
        AssetHistory::create([
            'asset_id' => $id,
            'user_id' => $this->currentUser['id'],
            'action' => 'retired',
            'field_changed' => 'status',
            'old_value' => $asset['status'],
            'new_value' => 'retired',
        ]);

        // Audit log
        $this->auditService->logAction('asset_retired', 'Asset ' . $asset['asset_tag'], 'Asset retired - reason: ' . $retirementReason);

        // Check if a disposal record already exists
        $existingDisposal = AssetDisposal::findByAssetId($id);
        if (!$existingDisposal) {
            // Create disposal record automatically
            $disposalId = AssetDisposal::create([
                'asset_id' => $id,
                'retirement_date' => $asset['retired_at'],
                'retirement_reason' => $retirementReason,
                'disposal_status' => 'pending_disposal',
                'data_destruction_required' => 1,
                'created_by' => $this->currentUser['id'],
            ]);

            if ($notes) {
                AssetDisposal::update($disposalId, ['notes' => $notes]);
            }

            $this->auditService->logAction('disposal_created', 'Disposal #' . $disposalId, 'Automatic disposal record created for retired asset');
            $this->redirectWith('/disposals/' . $disposalId, 'Asset retired and disposal record created successfully.', 'success');
        } else {
            $this->redirectWith('/disposals/' . $existingDisposal['id'], 'Asset retired successfully.', 'success');
        }
    }

    /**
     * Show asset edit form
     */
    public function edit(int $id): void
    {
        $asset = Asset::findWithRelations($id);
        if (!$asset) {
            $this->redirectWith('/assets', 'Asset not found.', 'danger');
            return;
        }

        $departments = Department::getOptions();

        $this->render('assets/edit', [
            'title' => 'Edit Asset: ' . $asset['asset_tag'],
            'asset' => $asset,
            'departments' => $departments,
        ]);
    }

    /**
     * Update an asset
     */
    public function update(int $id): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $asset = Asset::find($id);
        if (!$asset) {
            $this->redirectWith('/assets', 'Asset not found.', 'danger');
            return;
        }

        $data = $this->request->only([
            'asset_tag', 'type', 'hostname', 'ip_address', 'mac_address',
            'vendor', 'model', 'serial_number', 'os', 'cpu', 'ram_gb', 'storage_gb',
            'status', 'purchase_date', 'warranty_end', 'location', 'floor',
            'department_id', 'section', 'assigned_to', 'notes'
        ]);

        // Validate
        $errors = [];
        if (empty($data['asset_tag'])) $errors[] = 'Asset tag is required.';
        if (empty($data['type'])) $errors[] = 'Asset type is required.';
        
        if (!empty($data['ip_address']) && !filter_var($data['ip_address'], FILTER_VALIDATE_IP)) {
            $errors[] = 'Invalid IP address format.';
        }
        if (!empty($data['mac_address']) && !Security::sanitizeMac($data['mac_address'])) {
            $errors[] = 'Invalid MAC address format.';
        }
        if (!empty($data['ram_gb']) && (!is_numeric($data['ram_gb']) || $data['ram_gb'] <= 0)) {
            $errors[] = 'RAM must be a positive number.';
        }
        if (!empty($data['storage_gb']) && (!is_numeric($data['storage_gb']) || $data['storage_gb'] <= 0)) {
            $errors[] = 'Storage must be a positive number.';
        }

        if (!empty($errors)) {
            $this->session->setFlash('message', implode('<br>', $errors));
            $this->session->setFlash('message_type', 'danger');
            $this->redirect('/assets/' . $id . '/edit');
            return;
        }

        // Convert empty foreign keys to null
        if (empty($data['department_id'])) $data['department_id'] = null;

        // Save typed "assigned to" text verbatim, and resolve user ID when possible
        $assignedName = isset($data['assigned_to']) ? trim((string) $data['assigned_to']) : '';
        $data['assigned_name'] = $assignedName !== '' ? $assignedName : null;
        $data['assigned_to'] = $this->resolveAssignedUser($assignedName);

        if (!empty($data['mac_address'])) {
            $data['mac_address'] = Security::sanitizeMac($data['mac_address']);
        }

        // Convert empty strings to null for nullable fields with UNIQUE constraints
        if (empty($data['serial_number'])) $data['serial_number'] = null;

        // Track changes for audit
        foreach ($data as $field => $newValue) {
            if ($field === 'created_by') continue;
            if (isset($asset[$field]) && (string) $asset[$field] !== (string) $newValue) {
                $this->auditService->logUpdated($id, $field, $asset[$field], $newValue);
            }
        }

        Asset::update($id, $data);
        $this->redirectWith('/assets/' . $id, 'Asset updated successfully.', 'success');
    }

    /**
     * Delete an asset
     */
    public function destroy(int $id): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $asset = Asset::find($id);
        if (!$asset) {
            $this->redirectWith('/assets', 'Asset not found.', 'danger');
            return;
        }

        $this->auditService->logDeleted($id, $asset['asset_tag']);
        Asset::delete($id);
        $this->redirectWith('/assets', 'Asset deleted successfully.', 'success');
    }

    /**
     * Export assets to CSV
     */
    public function exportCsv(): void
    {
        $exportService = new ExportService();
        $csv = $exportService->exportAssetsCsv([
            'search' => $this->request->query('search', ''),
            'type' => $this->request->query('type', ''),
            'status' => $this->request->query('status', ''),
            'department_id' => $this->request->query('department_id', 0),
        ]);

        $filename = 'assets_export_' . date('Y-m-d') . '.csv';
        $this->response->header('Content-Type', 'text/csv; charset=utf-8');
        $this->response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->response->header('Cache-Control', 'no-cache');
        echo $csv;
        exit;
    }

    /**
     * Show import form
     */
    public function showImport(): void
    {
        $this->render('assets/import', [
            'title' => 'Import Assets from CSV',
        ]);
    }

    /**
     * Download sample CSV template for import
     */
    public function sampleCsv(): void
    {
        $exportService = new ExportService();
        $csv = $exportService->generateSampleCsv();

        $filename = 'import_sample.csv';
        $this->response->header('Content-Type', 'text/csv; charset=utf-8');
        $this->response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->response->header('Cache-Control', 'no-cache');
        echo $csv;
        exit;
    }

    /**
     * Import assets from CSV
     */
    public function import(): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        if (!$this->request->hasFile('csv_file')) {
            $this->redirectWith('/assets/import', 'Please select a CSV file to upload.', 'warning');
            return;
        }

        $file = $this->request->file('csv_file');
        $validation = Security::validateFileUpload($file, ['csv'], 5 * 1024 * 1024);

        if (!$validation['valid']) {
            $this->redirectWith('/assets/import', $validation['error'], 'danger');
            return;
        }

        $exportService = new ExportService();
        $result = $exportService->importFromCsv($file['tmp_name'], $this->currentUser['id']);

        if ($result['success']) {
            $this->redirectWith('/assets', $result['message'], 'success');
        } else {
            $errorMsg = $result['message'] ?? 'Import failed.';
            if (!empty($result['errors'])) {
                $errorMsg .= '<br>' . implode('<br>', array_slice($result['errors'], 0, 10));
            }
            $this->redirectWith('/assets/import', $errorMsg, 'warning');
        }
    }

    /**
     * Was this PDOException an `assets.asset_tag` UNIQUE constraint violation?
     * (SQLSTATE 23000 integrity constraint violation — other unique columns
     * like serial_number must keep failing loudly.)
     */
    private function isDuplicateAssetTagError(\PDOException $e): bool
    {
        return (string) $e->getCode() === '23000'
            && stripos($e->getMessage(), 'asset_tag') !== false;
    }

    /**
     * Resolve an "assigned to" name typed by the user into a user ID.
     *
     * Attempts, in order:
     *  1. Exact match on full_name (case-insensitive, trimmed)
     *  2. Exact match on username (case-insensitive, trimmed)
     *  3. Partial match on full_name (case-insensitive)
     *  4. Partial match on username (case-insensitive)
     *
     * Returns the matched user's ID, or null if no user was found.
     */
    private function resolveAssignedUser(?string $name): ?int
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        $db = \App\Core\Database::getInstance();
        $trimmed = trim($name);

        // 1. Exact full_name match (case-insensitive)
        $user = $db->fetch(
            "SELECT id FROM users WHERE full_name = ? LIMIT 1",
            [$trimmed]
        );
        if ($user) {
            return (int) $user['id'];
        }

        // 2. Exact username match (case-insensitive)
        $user = $db->fetch(
            "SELECT id FROM users WHERE username = ? LIMIT 1",
            [$trimmed]
        );
        if ($user) {
            return (int) $user['id'];
        }

        // 3. Partial full_name match (case-insensitive)
        $user = $db->fetch(
            "SELECT id FROM users WHERE LOWER(full_name) LIKE LOWER(?) LIMIT 1",
            ["%{$trimmed}%"]
        );
        if ($user) {
            return (int) $user['id'];
        }

        // 4. Partial username match (case-insensitive)
        $user = $db->fetch(
            "SELECT id FROM users WHERE LOWER(username) LIKE LOWER(?) LIMIT 1",
            ["%{$trimmed}%"]
        );
        if ($user) {
            return (int) $user['id'];
        }

        return null;
    }
}
