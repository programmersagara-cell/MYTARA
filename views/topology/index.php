    <?php $layout = 'layouts/main'; ?>
<?php
$extraStyles = ['topology.css'];
$extraScripts = ['topology.js'];
?>

<div class="topology-container">
    <!-- Topology Toolbar -->
    <div class="topology-toolbar">
        <div class="toolbar-left">
            <h2><i class="fas fa-project-diagram"></i> Network Topology</h2>
        </div>
        <div class="toolbar-right">
            <div class="filter-buttons">
<button class="btn btn-sm btn-primary active" data-filter="all" onclick="filterTopology('all')">
                    <i class="fas fa-th"></i> All
                </button>
                <button class="btn btn-sm" data-filter="pc" onclick="filterTopology('pc')">
                    <img src="<?= BASE_URL ?>/public/assets/img/devices/pc.png" alt="PC" class="device-icon-sm"> PCs
                </button>
                <button class="btn btn-sm" data-filter="laptop" onclick="filterTopology('laptop')">
                    <img src="<?= BASE_URL ?>/public/assets/img/devices/laptop.png" alt="Laptop" class="device-icon-sm"> Laptops
                </button>
                <button class="btn btn-sm" data-filter="switch" onclick="filterTopology('switch')">
                    <img src="<?= BASE_URL ?>/public/assets/img/devices/switch.svg" alt="Switch" class="device-icon-sm"> Switches
                </button>
<button class="btn btn-sm" data-filter="server" onclick="filterTopology('server')">
                    <img src="<?= BASE_URL ?>/public/assets/img/devices/server.png" alt="Server" class="device-icon-sm"> Servers
                </button>
                <button class="btn btn-sm" data-filter="printer" onclick="filterTopology('printer')">
                    <img src="<?= BASE_URL ?>/public/assets/img/devices/printer.png" alt="Printer" class="device-icon-sm"> Printers
                </button>
                <button class="btn btn-sm" data-filter="nas" onclick="filterTopology('nas')">
                    <img src="<?= BASE_URL ?>/public/assets/img/devices/nas.png" alt="NAS" class="device-icon-sm"> NAS
                </button>
<button class="btn btn-sm" data-filter="nvr" onclick="filterTopology('nvr')">
                    <img src="<?= BASE_URL ?>/public/assets/img/devices/nvr.png" alt="NVR" class="device-icon-sm"> NVR
                </button>
                <button class="btn btn-sm" data-filter="vm" onclick="filterTopology('vm')">
                    <img src="<?= BASE_URL ?>/public/assets/img/devices/vm.png" alt="VM" class="device-icon-sm"> VMs
                </button>
            </div>
<div class="topology-actions">
                <button class="btn btn-sm btn-secondary" onclick="fitToScreen()" title="Fit to screen">
                    <i class="fas fa-expand"></i>
                </button>
                <button class="btn btn-sm btn-secondary" onclick="zoomIn()" title="Zoom in">
                    <i class="fas fa-plus"></i>
                </button>
                <button class="btn btn-sm btn-secondary" onclick="zoomOut()" title="Zoom out">
                    <i class="fas fa-minus"></i>
                </button>
                <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
                <button class="btn btn-sm btn-success" onclick="savePositions()" title="Save positions">
                    <i class="fas fa-save"></i> Save
                </button>
                <?php endif; ?>
                <button class="btn btn-sm btn-secondary" onclick="undoAction()" title="Undo (Ctrl+Z)" id="undoBtn" disabled>
                    <i class="fas fa-undo"></i>
                </button>
                <button class="btn btn-sm btn-secondary" onclick="redoAction()" title="Redo (Ctrl+Shift+Z)" id="redoBtn" disabled>
                    <i class="fas fa-redo"></i>
                </button>
                <button class="btn btn-sm btn-secondary" onclick="exportDiagram()" title="Export diagram as PNG">
                    <i class="fas fa-download"></i> Export
                </button>
            <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
            <div class="connection-actions">
<button class="btn btn-sm btn-secondary" id="connectBtn" onclick="enableConnectMode('ethernet')" title="Connect devices - click first device, then second">
                        <i class="fas fa-link"></i> Connect
                    </button>
                    <button class="btn btn-sm btn-secondary" id="disconnectBtn" onclick="enableDisconnectMode()" title="Disconnect devices - click on any connection line">
                        <i class="fas fa-unlink"></i> Disconnect
                    </button>
                    <button class="btn btn-sm btn-secondary" id="labelBtn" onclick="enableLabelMode()" title="Add text label on canvas">
                        <i class="fas fa-font"></i> Label
                    </button>
                    <button class="btn btn-sm btn-secondary" id="boxSelectBtn" onclick="toggleBoxSelect()" title="Box select - drag on canvas to select multiple devices">
                        <i class="fas fa-object-group"></i> Box Select
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="cancelModes()" title="Cancel current mode" id="cancelBtn" style="display:none;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="topology-search">
        <i class="fas fa-search"></i>
        <input type="text" id="topologySearch" placeholder="Search devices..." oninput="searchTopology(this.value)">
    </div>

    <!-- Canvas -->
    <div class="topology-canvas" id="topologyCanvas">
        <div class="loading-overlay" id="loadingOverlay">
            <div class="spinner"></div>
            <p>Loading topology...</p>
        </div>
        <canvas id="networkCanvas"></canvas>
        
        <!-- Info Popup -->
        <div class="device-popup" id="devicePopup" style="display: none;">
            <div class="popup-header">
                <div class="popup-device-icon" id="popupIcon">
                    <i class="fas fa-desktop"></i>
                </div>
                <div class="popup-title">
                    <h4 id="popupHostname">Hostname</h4>
                    <span class="badge" id="popupTag">PC-001</span>
                </div>
                <button class="popup-close" onclick="closePopup()">&times;</button>
            </div>
            <div class="popup-body">
                <div class="popup-detail-row">
                    <span class="info-label"><i class="fas fa-network-wired"></i> IP Address</span>
                    <code id="popupIP">—</code>
                </div>
                <div class="popup-detail-row">
                    <span class="info-label"><i class="fas fa-qrcode"></i> MAC Address</span>
                    <code id="popupMAC">—</code>
                </div>
                <div class="popup-detail-row">
                    <span class="info-label"><i class="fas fa-user"></i> Assigned To</span>
                    <span id="popupUser">—</span>
                </div>
                <div class="popup-detail-row">
                    <span class="info-label"><i class="fas fa-flag"></i> Status</span>
                    <span id="popupStatus">—</span>
                </div>
                <div class="popup-detail-row">
                    <span class="info-label"><i class="fas fa-tag"></i> Asset Type</span>
                    <span id="popupType">—</span>
                </div>
                <div class="popup-detail-row">
                    <span class="info-label"><i class="fas fa-tag"></i> Asset Tag</span>
                    <span id="popupAssetTag">—</span>
                </div>
            </div>
<div class="popup-actions">
                <a href="#" id="popupViewLink" class="btn btn-primary btn-sm">
                    <i class="fas fa-eye"></i> View Details
                </a>
                <?php if (isset($user) && $user && $user['role'] !== 'viewer'): ?>
                <a href="#" id="popupEditLink" class="btn btn-secondary btn-sm">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

<!-- Legend -->
    <div class="topology-legend">
        <div class="legend-item" data-type="pc" style="color:#3B82F6">
            <img src="<?= IMG_URL ?>/devices/pc.png" alt="PC" class="device-icon-sm" > PC
        </div>
        <div class="legend-item" data-type="laptop" style="color: #8B5CF6;">
            <img src="<?= IMG_URL ?>/devices/laptop.png" alt="Laptop" class="device-icon-sm"> Laptop
        </div>
        <div class="legend-item" data-type="switch">
            <img src="<?= IMG_URL ?>/devices/switch.svg" alt="Switch" class="device-icon-sm"> Switch
        </div>
<div class="legend-item" data-type="server" style="color: #F59E0B;">
            <img src="<?= IMG_URL ?>/devices/server.png" alt="Server" class="device-icon-sm"> Server
        </div>
        <div class="legend-item" data-type="printer" style="color: #EC4899;">
            <img src="<?= IMG_URL ?>/devices/printer.png" alt="Printer" class="device-icon-sm"> Printer
        </div>
        <div class="legend-item" data-type="nas" style="color: #14B8A6;">
            <img src="<?= IMG_URL ?>/devices/nas.png" alt="NAS" class="device-icon-sm"> NAS
        </div>
<div class="legend-item" data-type="nvr" style="color: #b97b49;">
            <img src="<?= IMG_URL ?>/devices/nvr.png" alt="NVR" class="device-icon-sm"> NVR
        </div>
        <div class="legend-item" data-type="vm" style="color: #93C5FD;">
            <img src="<?= IMG_URL ?>/devices/vm.png" alt="VM" class="device-icon-sm"> VM
        </div>
        <div class="legend-item">
            <span class="connection-line"></span> Connection
        </div>
<div class="legend-item">
            <span class="junction-dot"></span> Junction / Tap
        </div>
<div class="legend-item">
            <span class="straight-badge straight-h">H </span> Horizontal
        </div>
        <div class="legend-item">
            <span class="straight-badge straight-v">V </span> Vertical
        </div>
    </div>

    <!-- Position Data -->
    <div id="topologyData" style="display:none;"><?= htmlspecialchars(json_encode($topologyData ?? [])) ?></div>
</div>
