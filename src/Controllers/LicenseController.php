<?php
/**
 * License Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SoftwareLicense;
use App\Models\LicenseAssignment;
use App\Models\Department;
use App\Models\User;
use App\Models\Asset;
use App\Services\AuditService;

class LicenseController extends Controller
{
    private AuditService $auditService;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole('admin', 'viewer');
        $this->auditService = new AuditService();
    }

    /**
     * List all licenses
     */
    public function index(): void
    {
        $page = (int) ($this->request->query('page', 1));
        $search = $this->request->query('search', '');
        $type = $this->request->query('type', '');
        $status = $this->request->query('status', '');
        $departmentId = (int) $this->request->query('department_id', 0);

        // Update statuses based on expiration dates
        SoftwareLicense::updateStatuses();

        if ($search || $type || $status || $departmentId) {
            $licenses = SoftwareLicense::searchLicenses($search, $type, $status, $departmentId);
            $total = count($licenses);
            $totalPages = 1;
        } else {
            $result = SoftwareLicense::paginate($page, PER_PAGE);
            $licenses = $result['data'];
            $total = $result['total'];
            $totalPages = $result['total_pages'];
        }

        $departments = Department::getOptions();
        $stats = SoftwareLicense::getDashboardStats();

        $this->render('licenses/index', [
            'title' => 'Software Licenses',
            'licenses' => $licenses,
            'departments' => $departments,
            'stats' => $stats,
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
     * Show license creation form
     */
    public function create(): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $departments = Department::getOptions();
        $users = User::all('full_name', 'ASC');
        $assets = Asset::all('asset_tag', 'ASC');

        $this->render('licenses/create', [
            'title' => 'Add New License',
            'departments' => $departments,
            'users' => $users,
            'assets' => $assets,
        ]);
    }

    /**
     * Store a new license
     */
    public function store(): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $data = $this->request->only([
            'license_name', 'software_name', 'vendor', 'license_key', 'license_type',
            'version', 'purchase_date', 'start_date', 'expiration_date',
            'purchased_seats', 'cost', 'currency',
            'department_id', 'assigned_user_id', 'assigned_name',
            'assigned_asset_id', 'assigned_asset_tag', 'notes'
        ]);

        // Validate
        $errors = [];
        if (empty($data['license_name'])) $errors[] = 'License name is required.';
        if (empty($data['software_name'])) $errors[] = 'Software name is required.';
        if (empty($data['license_type'])) $errors[] = 'License type is required.';
        if (isset($data['purchased_seats']) && (!is_numeric($data['purchased_seats']) || $data['purchased_seats'] <= 0)) {
            $errors[] = 'Purchased seats must be a positive number.';
        }

        // Resolve manually typed assigned user / asset happens below (after validation)

        if (!empty($errors)) {
            $this->session->setFlash('message', implode('<br>', $errors));
            $this->session->setFlash('message_type', 'danger');
            $this->redirect('/licenses/create');
            return;
        }

        // Convert empty foreign keys to null
        if (empty($data['department_id'])) $data['department_id'] = null;
        if (empty($data['purchased_seats'])) $data['purchased_seats'] = 1;
        if (empty($data['currency'])) $data['currency'] = 'PHP';

        // Perpetual licenses never expire
        if (($data['license_type'] ?? '') === 'perpetual') {
            $data['expiration_date'] = null;
        }

        // Save typed "assigned user" text verbatim and resolve to a user ID when possible
        $assignedName = trim((string) ($data['assigned_name'] ?? ''));
        $data['assigned_name'] = $assignedName !== '' ? $assignedName : null;
        $data['assigned_user_id'] = $this->resolveAssignedUser($assignedName);

        // Save typed asset tag verbatim and resolve to an asset ID when possible
        $assetTag = trim((string) ($data['assigned_asset_tag'] ?? ''));
        if (empty($assetTag) && !empty($data['assigned_asset_id'])) {
            $asset = Asset::find((int) $data['assigned_asset_id']);
            $assetTag = $asset['asset_tag'] ?? '';
        }
        $data['assigned_asset_tag'] = $assetTag !== '' ? $assetTag : null;
        $data['assigned_asset_id'] = $this->resolveAssetTag($assetTag);

        // Set created_by and initial status
        $data['created_by'] = $this->currentUser['id'];
        $data['used_seats'] = 0;
        $data['status'] = SoftwareLicense::determineStatus($data['expiration_date'] ?? null);

        $licenseId = SoftwareLicense::create($data);

        if ($licenseId) {
            $this->auditService->logAction('license_created', 'License #' . $licenseId, 'Created license: ' . ($data['license_name'] ?? ''));
            $this->redirectWith('/licenses/' . $licenseId, 'License created successfully.', 'success');
        } else {
            $this->redirectWith('/licenses/create', 'Failed to create license.', 'danger');
        }
    }

    /**
     * Show license details
     */
    public function show(int $id): void
    {
        $license = SoftwareLicense::findWithRelations($id);
        if (!$license) {
            $this->redirectWith('/licenses', 'License not found.', 'danger');
            return;
        }

        $assignments = SoftwareLicense::getAllAssignments($id);
        $activeAssignments = SoftwareLicense::getActiveAssignments($id);

        $this->render('licenses/show', [
            'title' => 'License: ' . ($license['license_name'] ?? ''),
            'license' => $license,
            'assignments' => $assignments,
            'activeAssignments' => $activeAssignments,
        ]);
    }

    /**
     * Show license edit form
     */
    public function edit(int $id): void
    {
        $license = SoftwareLicense::findWithRelations($id);
        if (!$license) {
            $this->redirectWith('/licenses', 'License not found.', 'danger');
            return;
        }

        $departments = Department::getOptions();
        $users = User::all('full_name', 'ASC');
        $assets = Asset::all('asset_tag', 'ASC');

        $this->render('licenses/edit', [
            'title' => 'Edit License: ' . $license['license_name'],
            'license' => $license,
            'departments' => $departments,
            'users' => $users,
            'assets' => $assets,
        ]);
    }

    /**
     * Update a license
     */
    public function update(int $id): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $license = SoftwareLicense::find($id);
        if (!$license) {
            $this->redirectWith('/licenses', 'License not found.', 'danger');
            return;
        }

        $data = $this->request->only([
            'license_name', 'software_name', 'vendor', 'license_key', 'license_type',
            'version', 'purchase_date', 'start_date', 'expiration_date',
            'purchased_seats', 'cost', 'currency',
            'department_id', 'assigned_user_id', 'assigned_name',
            'assigned_asset_id', 'assigned_asset_tag', 'status', 'notes'
        ]);

        // Validate
        $errors = [];
        if (empty($data['license_name'])) $errors[] = 'License name is required.';
        if (empty($data['software_name'])) $errors[] = 'Software name is required.';

        if (!empty($errors)) {
            $this->session->setFlash('message', implode('<br>', $errors));
            $this->session->setFlash('message_type', 'danger');
            $this->redirect('/licenses/' . $id . '/edit');
            return;
        }

        // Convert empty foreign keys to null
        if (empty($data['department_id'])) $data['department_id'] = null;

        // Perpetual licenses never expire
        if (($data['license_type'] ?? '') === 'perpetual') {
            $data['expiration_date'] = null;
        }

        // Save typed "assigned user" text verbatim and resolve to a user ID when possible
        $assignedName = trim((string) ($data['assigned_name'] ?? ''));
        $data['assigned_name'] = $assignedName !== '' ? $assignedName : null;
        $data['assigned_user_id'] = $this->resolveAssignedUser($assignedName);

        // Save typed asset tag verbatim and resolve to an asset ID when possible
        $assetTag = trim((string) ($data['assigned_asset_tag'] ?? ''));
        $data['assigned_asset_tag'] = $assetTag !== '' ? $assetTag : null;
        $data['assigned_asset_id'] = $this->resolveAssetTag($assetTag);

        // Track changes for audit
        foreach ($data as $field => $newValue) {
            if ($field === 'created_by') continue;
            if (isset($license[$field]) && (string) $license[$field] !== (string) $newValue) {
                $this->auditService->logAction('license_updated', 'License #' . $id, "Changed {$field} from '{$license[$field]}' to '{$newValue}'");
            }
        }

        SoftwareLicense::update($id, $data);
        $this->redirectWith('/licenses/' . $id, 'License updated successfully.', 'success');
    }

    /**
     * Delete a license
     */
    public function destroy(int $id): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            if ($this->request->isAjax()) {
                $this->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
            }
            return;
        }
        $license = SoftwareLicense::find($id);
        if (!$license) {
            if ($this->request->isAjax()) {
                $this->json(['success' => false, 'message' => 'License not found.'], 404);
            }
            $this->redirectWith('/licenses', 'License not found.', 'danger');
            return;
        }

        $this->auditService->logAction('license_deleted', 'License #' . $id, 'Deleted license: ' . ($license['license_name'] ?? ''));
        SoftwareLicense::delete($id);

        // The licenses table deletes rows via fetch() and removes the row from
        // the DOM, so it needs a JSON response — a redirect would be followed
        // by fetch() and the row would stay on screen until a manual refresh.
        if ($this->request->isAjax()) {
            $this->json([
                'success' => true,
                'message' => 'License "' . ($license['license_name'] ?? 'License') . '" deleted successfully.',
                'stats' => SoftwareLicense::getDashboardStats(),
            ]);
        }

        $this->redirectWith('/licenses', 'License deleted successfully.', 'success');
    }

    /**
     * Assign license to user
     */
    public function assignToUser(int $id): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $license = SoftwareLicense::find($id);
        if (!$license) {
            $this->redirectWith('/licenses', 'License not found.', 'danger');
            return;
        }

        $userId = (int) $this->request->input('user_id', 0);
        $typedName = trim((string) $this->request->input('user_name', ''));

        if ($userId <= 0) {
            // No suggestion was picked — fall back to the text that was typed
            // into the field. It may still resolve to an existing user.
            if ($typedName === '') {
                $this->redirectWith('/licenses/' . $id, 'Please select or type a user.', 'danger');
                return;
            }
            $userId = $this->resolveAssignedUser($typedName) ?? 0;
        } else {
            // Validate the user exists (IDs arrive from a typed autocomplete field,
            // so they must not be trusted blindly).
            if (!User::find($userId)) {
                $this->redirectWith('/licenses/' . $id, 'Selected user does not exist.', 'danger');
                return;
            }
            $typedName = '';
        }

        // Check capacity
        $activeCount = LicenseAssignment::countActiveForLicense($id);
        if ($activeCount >= (int) $license['purchased_seats']) {
            $this->redirectWith('/licenses/' . $id, 'License is at full capacity.', 'danger');
            return;
        }

        if ($userId > 0) {
            // Check if already assigned
            if (LicenseAssignment::isAssignedToUser($id, $userId)) {
                $this->redirectWith('/licenses/' . $id, 'License is already assigned to this user.', 'warning');
                return;
            }
            $assignmentId = LicenseAssignment::assignToUser($id, $userId, $this->currentUser['id']);
            $target = "user #{$userId}";
        } else {
            // Free-text name with no matching user record — store it verbatim.
            if (LicenseAssignment::isAssignedToManualName($id, $typedName)) {
                $this->redirectWith('/licenses/' . $id, 'License is already assigned to "' . $typedName . '".', 'warning');
                return;
            }
            $assignmentId = LicenseAssignment::assignToManualName($id, $typedName, $this->currentUser['id']);
            $target = '"' . $typedName . '" (manual entry)';
        }

        if ($assignmentId) {
            // Update used seats
            $newUsed = $activeCount + 1;
            SoftwareLicense::update($id, ['used_seats' => $newUsed]);
            $this->auditService->logAction('license_assigned', 'License #' . $id, "Assigned to {$target}");
            $this->redirectWith('/licenses/' . $id, 'License assigned successfully.', 'success');
        } else {
            $this->redirectWith('/licenses/' . $id, 'Failed to assign license.', 'danger');
        }
    }

    /**
     * Assign license to asset
     */
    public function assignToAsset(int $id): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $license = SoftwareLicense::find($id);
        if (!$license) {
            $this->redirectWith('/licenses', 'License not found.', 'danger');
            return;
        }

        $assetId = (int) $this->request->input('asset_id', 0);
        $typedTag = trim((string) $this->request->input('asset_tag', ''));

        if ($assetId <= 0) {
            // No suggestion was picked — fall back to the text that was typed
            // into the field. It may still resolve to an existing asset.
            if ($typedTag === '') {
                $this->redirectWith('/licenses/' . $id, 'Please select or type an asset.', 'danger');
                return;
            }
            $assetId = $this->resolveAssetTag($typedTag) ?? 0;
        } else {
            // Validate the asset exists (IDs arrive from a typed autocomplete field,
            // so they must not be trusted blindly).
            if (!Asset::find($assetId)) {
                $this->redirectWith('/licenses/' . $id, 'Selected asset does not exist.', 'danger');
                return;
            }
            $typedTag = '';
        }

        // Check capacity
        $activeCount = LicenseAssignment::countActiveForLicense($id);
        if ($activeCount >= (int) $license['purchased_seats']) {
            $this->redirectWith('/licenses/' . $id, 'License is at full capacity.', 'danger');
            return;
        }

        if ($assetId > 0) {
            // Check if already assigned
            if (LicenseAssignment::isAssignedToAsset($id, $assetId)) {
                $this->redirectWith('/licenses/' . $id, 'License is already assigned to this asset.', 'warning');
                return;
            }
            $assignmentId = LicenseAssignment::assignToAsset($id, $assetId, $this->currentUser['id']);
            $target = "asset #{$assetId}";
        } else {
            // Free-text tag with no matching asset record — store it verbatim.
            if (LicenseAssignment::isAssignedToManualTag($id, $typedTag)) {
                $this->redirectWith('/licenses/' . $id, 'License is already assigned to "' . $typedTag . '".', 'warning');
                return;
            }
            $assignmentId = LicenseAssignment::assignToManualTag($id, $typedTag, $this->currentUser['id']);
            $target = '"' . $typedTag . '" (manual entry)';
        }

        if ($assignmentId) {
            // Update used seats
            $newUsed = $activeCount + 1;
            SoftwareLicense::update($id, ['used_seats' => $newUsed]);
            $this->auditService->logAction('license_assigned', 'License #' . $id, "Assigned to {$target}");
            $this->redirectWith('/licenses/' . $id, 'License assigned successfully.', 'success');
        } else {
            $this->redirectWith('/licenses/' . $id, 'Failed to assign license.', 'danger');
        }
    }

    /**
     * Remove license assignment
     */
    public function removeAssignment(int $id, int $assignmentId): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $license = SoftwareLicense::find($id);
        if (!$license) {
            $this->redirectWith('/licenses', 'License not found.', 'danger');
            return;
        }

        $assignment = LicenseAssignment::find($assignmentId);
        if (!$assignment || (int) $assignment['license_id'] !== $id) {
            $this->redirectWith('/licenses/' . $id, 'Assignment not found.', 'danger');
            return;
        }

        LicenseAssignment::removeAssignment($assignmentId);

        // Update used seats
        $activeCount = LicenseAssignment::countActiveForLicense($id);
        SoftwareLicense::update($id, ['used_seats' => $activeCount]);

        $this->auditService->logAction('license_assignment_removed', 'License #' . $id, "Removed assignment #{$assignmentId}");
        $this->redirectWith('/licenses/' . $id, 'License assignment removed.', 'success');
    }

    /**
     * Resolve a manually typed user name into a user ID.
     * Tries exact full_name, exact username, partial full_name, partial username.
     */
    private function resolveAssignedUser(?string $name): ?int
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        $db = \App\Core\Database::getInstance();
        $trimmed = trim($name);

        $user = $db->fetch("SELECT id FROM users WHERE full_name = ? LIMIT 1", [$trimmed]);
        if ($user) return (int) $user['id'];

        $user = $db->fetch("SELECT id FROM users WHERE username = ? LIMIT 1", [$trimmed]);
        if ($user) return (int) $user['id'];

        $user = $db->fetch(
            "SELECT id FROM users WHERE LOWER(full_name) LIKE LOWER(?) ORDER BY full_name LIMIT 1",
            ["%{$trimmed}%"]
        );
        if ($user) return (int) $user['id'];

        $user = $db->fetch(
            "SELECT id FROM users WHERE LOWER(username) LIKE LOWER(?) ORDER BY username LIMIT 1",
            ["%{$trimmed}%"]
        );
        if ($user) return (int) $user['id'];

        return null;
    }

    /**
     * Resolve a manually typed asset tag into an asset ID.
     * Tries exact match first, then a partial (LIKE) match.
     */
    private function resolveAssetTag(?string $tag): ?int
    {
        if ($tag === null || trim($tag) === '') {
            return null;
        }

        $db = \App\Core\Database::getInstance();
        $trimmed = trim($tag);

        $asset = $db->fetch("SELECT id FROM assets WHERE asset_tag = ? LIMIT 1", [$trimmed]);
        if ($asset) return (int) $asset['id'];

        $asset = $db->fetch(
            "SELECT id FROM assets WHERE LOWER(asset_tag) LIKE LOWER(?) ORDER BY asset_tag LIMIT 1",
            ["%{$trimmed}%"]
        );
        if ($asset) return (int) $asset['id'];

        // Also try matching by hostname as a convenience
        $asset = $db->fetch(
            "SELECT id FROM assets WHERE hostname IS NOT NULL AND LOWER(hostname) LIKE LOWER(?) ORDER BY hostname LIMIT 1",
            ["%{$trimmed}%"]
        );
        if ($asset) return (int) $asset['id'];

        return null;
    }
}