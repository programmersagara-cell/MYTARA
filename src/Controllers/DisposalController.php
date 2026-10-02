<?php
/**
 * Disposal Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\Department;
use App\Services\AuditService;
use App\Helpers\Security;

class DisposalController extends Controller
{
    private AuditService $auditService;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole('admin', 'viewer');
        $this->auditService = new AuditService();
    }

    /**
     * List all disposals
     */
    public function index(): void
    {
        $page = (int) ($this->request->query('page', 1));
        $search = $this->request->query('search', '');
        $status = $this->request->query('status', '');
        $type = $this->request->query('type', '');
        $departmentId = (int) $this->request->query('department_id', 0);

        $conditions = [];
        $params = [];

        if ($search) {
            $searchTerm = "%{$search}%";
            $conditions[] = "(a.asset_tag LIKE ? OR a.hostname LIKE ? OR a.serial_number LIKE ? OR d.certificate_number LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if ($status) {
            $conditions[] = "d.disposal_status = ?";
            $params[] = $status;
        }

        if ($type) {
            $conditions[] = "a.type = ?";
            $params[] = $type;
        }

        if ($departmentId > 0) {
            $conditions[] = "a.department_id = ?";
            $params[] = $departmentId;
        }

        $where = $conditions ? implode(' AND ', $conditions) : '1=1';
        $disposals = AssetDisposal::getAllWithRelations($where, $params);
        $total = count($disposals);
        $totalPages = 1;

        $departments = Department::getOptions();
        $stats = AssetDisposal::getDashboardStats();

        $this->render('disposals/index', [
            'title' => 'Asset Disposals',
            'disposals' => $disposals,
            'departments' => $departments,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'filterStatus' => $status,
            'filterType' => $type,
            'filterDepartment' => $departmentId,
        ]);
    }

    /**
     * Show retired assets page
     */
    public function retired(): void
    {
        $search = $this->request->query('search', '');
        $type = $this->request->query('type', '');
        $departmentId = (int) $this->request->query('department_id', 0);
        $reason = $this->request->query('reason', '');
        $disposalStatus = $this->request->query('disposal_status', '');

        $conditions = [];
        $params = [];

        if ($search) {
            $searchTerm = "%{$search}%";
            $conditions[] = "(a.asset_tag LIKE ? OR a.hostname LIKE ? OR a.serial_number LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }

        if ($type) {
            $conditions[] = "a.type = ?";
            $params[] = $type;
        }

        if ($departmentId > 0) {
            $conditions[] = "a.department_id = ?";
            $params[] = $departmentId;
        }

        if ($reason) {
            $conditions[] = "ad.retirement_reason = ?";
            $params[] = $reason;
        }

        if ($disposalStatus) {
            $conditions[] = "ad.disposal_status = ?";
            $params[] = $disposalStatus;
        }

        $where = $conditions ? implode(' AND ', $conditions) : '1=1';
        $retiredAssets = AssetDisposal::getRetiredAssets($where, $params);

        $departments = Department::getOptions();

        $this->render('disposals/retired', [
            'title' => 'Retired Assets',
            'retiredAssets' => $retiredAssets,
            'departments' => $departments,
            'search' => $search,
            'filterType' => $type,
            'filterDepartment' => $departmentId,
            'filterReason' => $reason,
            'filterDisposalStatus' => $disposalStatus,
        ]);
    }

    /**
     * Show disposal details
     */
    public function show(int $id): void
    {
        $disposal = AssetDisposal::findWithRelations($id);
        if (!$disposal) {
            $this->redirectWith('/disposals', 'Disposal record not found.', 'danger');
            return;
        }

        $attachments = AssetDisposal::getAttachments($id);

        $this->render('disposals/show', [
            'title' => 'Disposal: ' . ($disposal['asset_tag'] ?? ''),
            'disposal' => $disposal,
            'attachments' => $attachments,
        ]);
    }

    /**
     * Create disposal request for a retired asset
     */
    public function create(int $assetId): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $asset = Asset::findWithRelations($assetId);
        if (!$asset) {
            $this->redirectWith('/assets/retired', 'Asset not found.', 'danger');
            return;
        }

        if ($asset['status'] !== 'retired') {
            $this->redirectWith('/assets/' . $assetId, 'Asset is not retired.', 'warning');
            return;
        }

        // Check if disposal already exists
        if (AssetDisposal::hasActiveDisposal($assetId)) {
            $this->redirectWith('/assets/retired', 'A disposal record already exists for this asset.', 'warning');
            return;
        }

        $this->render('disposals/create', [
            'title' => 'Create Disposal Request',
            'asset' => $asset,
        ]);
    }

    /**
     * Store a new disposal record
     */
    public function store(int $assetId): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $asset = Asset::find($assetId);
        if (!$asset) {
            $this->redirectWith('/assets/retired', 'Asset not found.', 'danger');
            return;
        }

        if ($asset['status'] !== 'retired') {
            $this->redirectWith('/assets/' . $assetId, 'Asset is not retired.', 'warning');
            return;
        }

        // Check if disposal already exists
        if (AssetDisposal::hasActiveDisposal($assetId)) {
            $this->redirectWith('/assets/retired', 'A disposal record already exists for this asset.', 'warning');
            return;
        }

        $data = $this->request->only([
            'retirement_reason', 'disposal_method', 'disposal_location',
            'disposal_vendor', 'data_destruction_required', 'data_destruction_method',
            'resale_value', 'disposal_cost', 'notes'
        ]);

        // Validate
        $errors = [];
        if (empty($data['retirement_reason'])) $errors[] = 'Retirement reason is required.';

        if (!empty($errors)) {
            $this->session->setFlash('message', implode('<br>', $errors));
            $this->session->setFlash('message_type', 'danger');
            $this->redirect('/disposals/create/' . $assetId);
            return;
        }

        // Set defaults
        $data['asset_id'] = $assetId;
        $data['retirement_date'] = $asset['retired_at'] ?? date('Y-m-d H:i:s');
        $data['disposal_status'] = 'pending_disposal';
        $data['data_destruction_required'] = isset($data['data_destruction_required']) ? 1 : 0;
        $data['created_by'] = $this->currentUser['id'];

        // Convert empty values to null
        if (empty($data['disposal_method'])) $data['disposal_method'] = null;
        if (empty($data['disposal_location'])) $data['disposal_location'] = null;
        if (empty($data['disposal_vendor'])) $data['disposal_vendor'] = null;
        if (empty($data['data_destruction_method'])) $data['data_destruction_method'] = null;
        if (empty($data['resale_value'])) $data['resale_value'] = null;
        if (empty($data['disposal_cost'])) $data['disposal_cost'] = null;

        $disposalId = AssetDisposal::create($data);

        if ($disposalId) {
            // Update asset retirement reason
            Asset::update($assetId, ['retirement_reason' => $data['retirement_reason']]);

            $this->auditService->logAction('disposal_created', 'Disposal #' . $disposalId, 'Created disposal request for asset: ' . $asset['asset_tag']);
            $this->redirectWith('/disposals/' . $disposalId, 'Disposal request created successfully.', 'success');
        } else {
            $this->redirectWith('/disposals/create/' . $assetId, 'Failed to create disposal request.', 'danger');
        }
    }

    /**
     * Submit disposal for approval
     */
    public function submit(int $id): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $disposal = AssetDisposal::find($id);
        if (!$disposal) {
            $this->redirectWith('/disposals', 'Disposal record not found.', 'danger');
            return;
        }

        if ($disposal['disposal_status'] !== 'pending_disposal') {
            $this->redirectWith('/disposals/' . $id, 'Disposal is not in pending status.', 'warning');
            return;
        }

        AssetDisposal::update($id, ['disposal_status' => 'awaiting_approval']);
        $this->auditService->logAction('disposal_submitted', 'Disposal #' . $id, 'Disposal submitted for approval');
        $this->redirectWith('/disposals/' . $id, 'Disposal submitted for approval.', 'success');
    }

    /**
     * Approve disposal (admin only)
     */
    public function approve(int $id): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $disposal = AssetDisposal::find($id);
        if (!$disposal) {
            $this->redirectWith('/disposals', 'Disposal record not found.', 'danger');
            return;
        }

        if (!in_array($disposal['disposal_status'], ['pending_disposal', 'awaiting_approval'])) {
            $this->redirectWith('/disposals/' . $id, 'Disposal cannot be approved in current status.', 'warning');
            return;
        }

        AssetDisposal::update($id, [
            'disposal_status' => 'approved',
            'approved_by' => $this->currentUser['id'],
            'approval_date' => date('Y-m-d H:i:s'),
        ]);

        $this->auditService->logAction('disposal_approved', 'Disposal #' . $id, 'Disposal approved');
        $this->redirectWith('/disposals/' . $id, 'Disposal approved successfully.', 'success');
    }

    /**
     * Reject disposal (admin only)
     */
    public function reject(int $id): void
    {
        if (!$this->requireRole('admin')) {
            return;
        }

        $disposal = AssetDisposal::find($id);
        if (!$disposal) {
            $this->redirectWith('/disposals', 'Disposal record not found.', 'danger');
            return;
        }

        if (!in_array($disposal['disposal_status'], ['pending_disposal', 'awaiting_approval'])) {
            $this->redirectWith('/disposals/' . $id, 'Disposal cannot be rejected in current status.', 'warning');
            return;
        }

        $notes = $this->request->input('notes', '');
        $updateData = ['disposal_status' => 'rejected'];
        if ($notes) {
            $updateData['notes'] = $notes;
        }

        AssetDisposal::update($id, $updateData);
        $this->auditService->logAction('disposal_rejected', 'Disposal #' . $id, 'Disposal rejected');
        $this->redirectWith('/disposals/' . $id, 'Disposal rejected.', 'success');
    }

    /**
     * Schedule disposal
     */
    public function schedule(int $id): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $disposal = AssetDisposal::find($id);
        if (!$disposal) {
            $this->redirectWith('/disposals', 'Disposal record not found.', 'danger');
            return;
        }

        if ($disposal['disposal_status'] !== 'approved') {
            $this->redirectWith('/disposals/' . $id, 'Disposal must be approved before scheduling.', 'warning');
            return;
        }

        $data = $this->request->only([
            'disposal_date', 'disposal_location', 'disposal_vendor', 'disposal_method'
        ]);

        $data['disposal_status'] = 'scheduled';

        if (empty($data['disposal_date'])) {
            $this->redirectWith('/disposals/' . $id, 'Disposal date is required.', 'danger');
            return;
        }

        if (empty($data['disposal_method'])) {
            $this->redirectWith('/disposals/' . $id, 'Disposal method is required.', 'danger');
            return;
        }

        AssetDisposal::update($id, $data);
        $this->auditService->logAction('disposal_scheduled', 'Disposal #' . $id, 'Disposal scheduled');
        $this->redirectWith('/disposals/' . $id, 'Disposal scheduled successfully.', 'success');
    }

    /**
     * Record data destruction
     */
    public function recordDataDestruction(int $id): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $disposal = AssetDisposal::find($id);
        if (!$disposal) {
            $this->redirectWith('/disposals', 'Disposal record not found.', 'danger');
            return;
        }

        $data = $this->request->only([
            'data_destruction_method', 'data_destruction_date', 'data_destruction_verified'
        ]);

        if (empty($data['data_destruction_method'])) {
            $this->redirectWith('/disposals/' . $id, 'Data destruction method is required.', 'danger');
            return;
        }

        if (empty($data['data_destruction_date'])) {
            $this->redirectWith('/disposals/' . $id, 'Data destruction date is required.', 'danger');
            return;
        }

        $data['data_destruction_by'] = $this->currentUser['id'];
        $data['data_destruction_verified'] = isset($data['data_destruction_verified']) ? 1 : 0;

        AssetDisposal::update($id, $data);
        $this->auditService->logAction('data_destruction_performed', 'Disposal #' . $id, 'Data destruction recorded');
        $this->redirectWith('/disposals/' . $id, 'Data destruction recorded successfully.', 'success');
    }

    /**
     * Complete disposal (mark as disposed)
     */
    public function complete(int $id): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $disposal = AssetDisposal::find($id);
        if (!$disposal) {
            $this->redirectWith('/disposals', 'Disposal record not found.', 'danger');
            return;
        }

        if ($disposal['disposal_status'] !== 'scheduled') {
            $this->redirectWith('/disposals/' . $id, 'Disposal must be scheduled before completing.', 'warning');
            return;
        }

        // Check if data destruction is required and completed
        if ($disposal['data_destruction_required'] && empty($disposal['data_destruction_method'])) {
            $this->redirectWith('/disposals/' . $id, 'Data destruction must be recorded before completing disposal.', 'danger');
            return;
        }

        $data = $this->request->only([
            'certificate_number', 'disposal_date'
        ]);

        $data['disposal_status'] = 'disposed';
        $data['disposed_by'] = $this->currentUser['id'];
        $data['disposal_date'] = $data['disposal_date'] ?? date('Y-m-d H:i:s');

        if (empty($data['certificate_number'])) {
            $data['certificate_number'] = 'DSP-' . strtoupper(substr(md5($id . time()), 0, 8));
        }

        AssetDisposal::update($id, $data);

        // Update asset status to disposed
        Asset::update($disposal['asset_id'], ['status' => 'disposed']);

        $this->auditService->logAction('asset_disposed', 'Disposal #' . $id, 'Asset disposed');
        $this->redirectWith('/disposals/' . $id, 'Disposal completed successfully.', 'success');
    }

    /**
     * Cancel disposal
     */
    public function cancel(int $id): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $disposal = AssetDisposal::find($id);
        if (!$disposal) {
            $this->redirectWith('/disposals', 'Disposal record not found.', 'danger');
            return;
        }

        if (in_array($disposal['disposal_status'], ['disposed', 'cancelled', 'rejected', 'archived'])) {
            $this->redirectWith('/disposals/' . $id, 'Disposal cannot be cancelled in current status.', 'warning');
            return;
        }

        AssetDisposal::update($id, ['disposal_status' => 'cancelled']);
        $this->auditService->logAction('disposal_cancelled', 'Disposal #' . $id, 'Disposal cancelled');
        $this->redirectWith('/disposals/' . $id, 'Disposal cancelled.', 'success');
    }

    /**
     * Return asset to service (from retired)
     */
    public function returnToService(int $assetId): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $asset = Asset::find($assetId);
        if (!$asset) {
            $this->redirectWith('/assets/retired', 'Asset not found.', 'danger');
            return;
        }

        if ($asset['status'] !== 'retired') {
            $this->redirectWith('/assets/' . $assetId, 'Asset is not retired.', 'warning');
            return;
        }

        // Check if there's an active disposal
        $disposal = AssetDisposal::findByAssetId($assetId);
        if ($disposal && !in_array($disposal['disposal_status'], ['rejected', 'cancelled'])) {
            $this->redirectWith('/assets/retired', 'Cannot return to service while disposal is in progress.', 'danger');
            return;
        }

        Asset::update($assetId, [
            'status' => 'active',
            'retirement_reason' => null,
            'retired_at' => null,
        ]);

        $this->auditService->logAction('asset_returned_to_service', 'Asset #' . $assetId, 'Asset returned to service');
        $this->redirectWith('/assets/' . $assetId, 'Asset returned to service.', 'success');
    }

    /**
     * Generate disposal certificate
     */
    public function certificate(int $id): void
    {
        $disposal = AssetDisposal::findWithRelations($id);
        if (!$disposal) {
            $this->redirectWith('/disposals', 'Disposal record not found.', 'danger');
            return;
        }

        $companyName = \App\Models\Setting::get('company_name', 'IT Services and Asset Management');

        $this->render('disposals/certificate', [
            'title' => 'Disposal Certificate',
            'disposal' => $disposal,
            'companyName' => $companyName,
        ]);
    }

    /**
     * Upload attachment for disposal
     */
    public function uploadAttachment(int $id): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        $disposal = AssetDisposal::find($id);
        if (!$disposal) {
            $this->redirectWith('/disposals', 'Disposal record not found.', 'danger');
            return;
        }

        if (!$this->request->hasFile('attachment')) {
            $this->redirectWith('/disposals/' . $id, 'Please select a file to upload.', 'warning');
            return;
        }

        $file = $this->request->file('attachment');
        $validation = Security::validateFileUpload($file, ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'], 10 * 1024 * 1024);

        if (!$validation['valid']) {
            $this->redirectWith('/disposals/' . $id, $validation['error'], 'danger');
            return;
        }

        $uploadDir = PUBLIC_PATH . '/uploads/disposals/' . $id;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = uniqid('disposal_') . '_' . basename($file['name']);
        $destination = $uploadDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            AssetDisposal::addAttachment($id, [
                'filename' => $filename,
                'original_name' => $file['name'],
                'file_type' => $file['type'],
                'file_size' => $file['size'],
            ], $this->currentUser['id']);

            $this->auditService->logAction('disposal_attachment_uploaded', 'Disposal #' . $id, 'Attachment uploaded');
            $this->redirectWith('/disposals/' . $id, 'Attachment uploaded successfully.', 'success');
        } else {
            $this->redirectWith('/disposals/' . $id, 'Failed to upload attachment.', 'danger');
        }
    }
}