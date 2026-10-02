<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\DiagramService;
use App\Services\AuditService;
use App\Models\NetworkLink;
use App\Models\Asset;

class TopologyController extends Controller
{
    private DiagramService $diagramService;
    private AuditService $auditService;

public function __construct()
    {
        parent::__construct();
        $this->requireRole('admin', 'viewer');
        $this->diagramService = new DiagramService();
        $this->auditService = new AuditService();
    }

    public function index(): void
    {
        $assets = Asset::getTopologyAssets();
        $links = NetworkLink::getAllWithDevices();

        // Build topology data for the view's embedded JSON
        $topologyData = $this->diagramService->getTopologyData();

        $this->render('topology/index', [
            'title' => 'Network Topology',
            'assets' => $assets,
            'links' => $links,
            'topologyData' => $topologyData,
        ]);
    }

    public function data(): void
    {
        $data = $this->diagramService->getTopologyData();
        $this->json($data);
    }

    public function savePositions(): void
    {
        // Write access is admin-only; viewers are read-only.
        if (!$this->requireRole('admin')) {
            return;
        }
        if (!$this->request->isPost()) {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $positions = $this->request->json();
        if (!$positions) {
            $this->json(['error' => 'Invalid data'], 400);
            return;
        }

        $this->diagramService->savePositions($positions);

        // ONE entry per save — never one per moved node.
        $this->auditService->log('topology_saved', 'topology', null, 'Network topology', 'positions', null, count($positions), 'Node positions saved (' . count($positions) . ' nodes)');

        $this->json(['success' => true]);
    }

    public function saveNode(): void
    {
        if (!$this->guardAdminWrite()) {
            return;
        }
        if (!$this->request->isPost()) {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $data = $this->request->json();
        if (!$data || empty($data['id'])) {
            $this->json(['error' => 'Node id is required'], 400);
            return;
        }

        $nodeId = (int) $data['id'];
        $update = [];

        if (isset($data['x'])) $update['pos_x'] = (float) $data['x'];
        if (isset($data['y'])) $update['pos_y'] = (float) $data['y'];
        if (isset($data['size'])) $update['pos_size'] = (float) $data['size'];

        if ($update) {
            \App\Core\Database::getInstance()->update('assets', $update, 'id = ?', [$nodeId]);
        }

        // Deliberately not audited per node: this is the per-node drag callback
        // and would create one entry per node move. savePositions() records the
        // save as a single 'topology_saved' entry.

        $this->json(['success' => true]);
    }

    public function createLink(): void
    {
        if (!$this->guardAdminWrite()) {
            return;
        }
        if (!$this->request->isPost()) {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $data = $this->request->json();
        $validated = $this->validate($data ?? [], [
            'source_id' => 'required|integer',
            'target_id' => 'required|integer',
            'link_type' => 'required|in:ethernet,fiber,wifi,virtual',
        ]);

        if ($this->validationFails()) {
            $this->json(['error' => 'Validation failed', 'errors' => $this->validationErrors()], 422);
            return;
        }

        if (NetworkLink::linkExists($validated['source_id'], $validated['target_id'])) {
            $this->json(['error' => 'Link already exists between these devices'], 409);
            return;
        }

        // Optional: branch link taps into an existing junction
        if (!empty($data['tap_id'])) {
            $junction = NetworkLink::findJunction((int) $data['tap_id']);
            if (!$junction) {
                $this->json(['error' => 'Junction not found'], 404);
                return;
            }
            $validated['tap_id'] = (int) $data['tap_id'];
            $validated['tap_side'] = !empty($data['tap_side']) ? substr((string) $data['tap_side'], 0, 10) : null;
        }

        $linkId = NetworkLink::create($validated);
        $this->auditService->log('link_created', 'topology', $linkId, 'Link #' . $linkId, 'link_type', null, $validated['link_type'], 'Topology link created (' . $validated['source_id'] . ' → ' . $validated['target_id'] . ')');

        $this->json(['success' => true, 'id' => $linkId]);
    }

    public function addJunction(): void
    {
        if (!$this->guardAdminWrite()) {
            return;
        }
        if (!$this->request->isPost()) {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $data = $this->request->json();
        $validated = $this->validate($data ?? [], [
            'edge_id' => 'required|integer',
            't' => 'required|numeric',
        ]);

        if ($this->validationFails()) {
            $this->json(['error' => 'Validation failed', 'errors' => $this->validationErrors()], 422);
            return;
        }

        $junctionId = $this->diagramService->saveJunction($validated);
        $this->json(['success' => true, 'id' => $junctionId]);
    }

    public function deleteLink(int $id): void
    {
        if (!$this->guardAdminWrite()) {
            return;
        }
        NetworkLink::delete($id);
        $this->auditService->log('link_deleted', 'topology', $id, 'Link #' . $id, null, null, null, 'Topology link deleted');
        $this->json(['success' => true]);
    }

    public function deleteLinkById(int $id): void
    {
        if (!$this->guardAdminWrite()) {
            return;
        }
        NetworkLink::delete($id);
        $this->auditService->log('link_deleted', 'topology', $id, 'Link #' . $id, null, null, null, 'Topology link deleted');
        $this->json(['success' => true]);
    }

    public function deleteLinks(int $assetId): void
    {
        if (!$this->guardAdminWrite()) {
            return;
        }
        NetworkLink::deleteByDevice($assetId);
        $this->auditService->log('link_deleted', 'topology', $assetId, 'Asset #' . $assetId, null, null, null, 'All topology links removed for asset #' . $assetId);
        $this->json(['success' => true]);
    }

    // ─── Labels API ───

    public function getLabels(): void
    {
        $labels = $this->diagramService->getLabels();
        $this->json($labels);
    }

    public function saveLabel(): void
    {
        if (!$this->guardAdminWrite()) {
            return;
        }
        if (!$this->request->isPost()) {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $data = $this->request->json();
        if (!$data || !isset($data['text']) || trim((string) $data['text']) === '') {
            $this->json(['error' => 'Label text is required'], 400);
            return;
        }
        // x/y are NOT NULL columns — reject malformed input with 422 instead
        // of letting the database throw a 500.
        if (!isset($data['x']) || !isset($data['y']) || !is_numeric($data['x']) || !is_numeric($data['y'])) {
            $this->json(['error' => 'Label x and y numeric coordinates are required'], 422);
            return;
        }
        if (mb_strlen((string) $data['text']) > 255) {
            $this->json(['error' => 'Label text is too long (255 characters max).'], 422);
            return;
        }

        $id = $this->diagramService->saveLabel($data);
        $this->auditService->log('topology_saved', 'topology', $id, 'Label #' . $id, 'text', null, $data['text'], 'Topology label added');
        $this->json(['success' => true, 'id' => $id]);
    }

public function deleteLabel(int $id): void
    {
        if (!$this->guardAdminWrite()) {
            return;
        }
        $this->diagramService->deleteLabel($id);
        $this->auditService->log('topology_saved', 'topology', $id, 'Label #' . $id, null, null, null, 'Topology label deleted');
        $this->json(['success' => true]);
    }

    public function saveLabelPositions(): void
    {
        if (!$this->guardAdminWrite()) {
            return;
        }
        if (!$this->request->isPost()) {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $labels = $this->request->json();
        if (!$labels || !is_array($labels)) {
            $this->json(['error' => 'Invalid data'], 400);
            return;
        }

        $this->diagramService->saveLabelPositions($labels);
        // One entry per save, not per label.
        $this->auditService->log('topology_saved', 'topology', null, 'Network topology', 'label_positions', null, count($labels), 'Label positions saved (' . count($labels) . ' labels)');
        $this->json(['success' => true]);
    }

    public function info(int $id): void
    {
        $asset = Asset::findWithRelations($id);
        if (!$asset) {
            $this->json(['error' => 'Asset not found'], 404);
            return;
        }
        $this->json($asset);
    }

    /**
     * Topology write operations are admin-only (viewers are read-only).
     * Returns a machine-readable 403 for AJAX clients.
     */
    private function guardAdminWrite(): bool
    {
        if (($this->currentUser['role'] ?? '') === 'admin') {
            return true;
        }
        $this->json(['error' => 'Insufficient permissions.'], 403);
        return false;
    }
}
