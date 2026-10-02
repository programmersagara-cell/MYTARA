/**
 * Network Topology Engine
 * Interactive canvas-based network diagram with drag, zoom, resize, popup info,
 * connect/disconnect features, and undo/redo
 */

(function() {
    'use strict';

    class TopologyEngine {
        constructor(canvasId) {
            this.canvas = document.getElementById(canvasId);
            if (!this.canvas) return;

            this.ctx = this.canvas.getContext('2d');
            this.nodes = [];
            this.edges = [];
this.selectedNode = null;
            this.draggingNode = null;
            this.dragOffset = { x: 0, y: 0 };
            // Pre-change state capture for undo/redo correctness
            this._preActionState = null;
            this.offset = { x: 0, y: 0 };
            this.scale = 1;
            this.isPanning = false;
            this.panStart = { x: 0, y: 0 };
            this.minScale = 0.3;
            this.maxScale = 3;
            this.hoveredNode = null;
            this.hoveredEdge = null;
            this.originalNodes = [];
            this.searchQuery = '';

            // Resize state
            this.resizingNode = null;
            this.resizeStart = { x: 0, y: 0 };
            this.resizeStartSize = 1;
            this.resizeStartDistance = 0;
            this.hoveringResizeHandle = false;
            this.minNodeSize = 0.4;
            this.maxNodeSize = 3.0;

            // Box select state
            this.boxSelectMode = false;
            this.isBoxSelecting = false;
            this.boxSelectStart = { x: 0, y: 0 };
            this.boxSelectEnd = { x: 0, y: 0 };
            this.selectedNodes = [];

// Junctions (tap points on edges)
            this.junctions = [];

// Recently-aligned edges: edge id -> timestamp (ms) when the edge
            // became straight. Used to show the straight-line indicator for a
            // short time after alignment, then hide it automatically.
            this.alignedEdgeTimestamps = {};
            // Previous-frame alignment state per edge (edge id -> boolean) so we
            // only start the 5s timer on a fresh transition into alignment.
            this.lastAlignedState = {};
            // How long the straight-line indicator stays visible (ms).
            this.ALIGN_INDICATOR_DURATION = 5000;
            // Timer handle used to re-render once the indicator expires.
            this._alignTimer = null;

            // Undo/Redo state
            this.undoStack = [];
            this.redoStack = [];
            this.maxHistory = 50;
            this.isRestoring = false;

            // Connect/Disconnect mode state
            this.connectMode = false;
            this.disconnectMode = false;
            this.labelMode = false;
            this.connectSource = null;
            this.connectType = 'ethernet';
            this.mouseWorldPos = { x: 0, y: 0 };

            // Labels
            this.labels = [];
            this.draggingLabel = null;
            this.draggingLabelOffset = { x: 0, y: 0 };
            this.editingLabel = null;

            // Performance: render throttle
            this._renderPending = false;
            this._nodePosMap = {};

            // Export mode flag - increases text size and always shows all
            // node label attributes (asset tag, IP) when true.
            this._exportMode = false;

// Device type colors
            this.colors = {
                pc: '#3B82F6',
                laptop: '#8B5CF6',
                switch: '#10B981',
                server: '#F59E0B',
                printer: '#EC4899',
                nas: '#14B8A6',
                nvr: '#b97b49',
                vm: '#93C5FD',
                default: '#6B7280',
            };

            // Device type icons (PNG images). Place your PNG files in
            // public/assets/img/devices/ and update the paths below.
            // Falls back to emoji if the image has not loaded yet.
            this.iconImages = {
                pc: null,
                laptop: null,
                switch: null,
                server: null,
                printer: null,
                nas: null,
                nvr: null,
                vm: null,
                default: null,
            };

            // Map each device type to its image path (SVG or PNG).
            // Place your image files in public/assets/img/devices/ and update the paths below.
            // The canvas can draw both SVG and PNG images via drawImage().
            const base = (window.BASE_PATH || '') + '/public/assets/img/devices/';
            const iconPaths = {
                pc: 'pc.png',
                laptop: 'laptop.png',
                switch: 'switch.svg',
                server: 'server.png',
                printer: 'printer.png',
                nas: 'nas.png',
                nvr: 'nvr.png',
                vm: 'vm.png',
                default: 'device.svg',
            };
this.preloadIcons(base, iconPaths);

            this.edgeColors = {
                ethernet: '#10B981',
                fiber: '#F59E0B',
                wifi: '#3B82F6',
                virtual: '#8B5CF6',
            };

this.init();
        }

        preloadIcons(base, paths) {
            for (const type in paths) {
                const img = new Image();
                img.onload = () => {
                    this.iconImages[type] = img;
                    this.render();
                };
                img.src = base + paths[type];
            }
        }

        init() {
            this.resize();
            this.bindEvents();
            this.loadData();
            this.loadLabels();
            this.setupPopupClose();

            if (window.ResizeObserver) {
                try {
                    const observer = new ResizeObserver(() => this.resize());
                    observer.observe(this.canvas.parentElement);
                } catch (e) {}
            }
        }

        resize() {
            const parent = this.canvas.parentElement;
            if (!parent) return;
            const rect = parent.getBoundingClientRect();
            if (rect.width === 0 || rect.height === 0) return;
            this.canvas.width = rect.width;
            this.canvas.height = rect.height;
            this.centerX = this.canvas.width / 2;
            this.centerY = this.canvas.height / 2;
            this.render();
        }

        setupPopupClose() {
            const popup = document.getElementById('devicePopup');
            if (popup) {
                const closeBtn = popup.querySelector('.popup-close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => this.hidePopup());
                }
            }
        }

        bindEvents() {
            this.canvas.addEventListener('mousedown', (e) => this.onMouseDown(e));
            this.canvas.addEventListener('mousemove', (e) => this.onMouseMove(e));
            this.canvas.addEventListener('mouseup', (e) => this.onMouseUp(e));
            this.canvas.addEventListener('mouseleave', (e) => this.onMouseUp(e));
            this.canvas.addEventListener('wheel', (e) => this.onWheel(e), { passive: false });
            this.canvas.addEventListener('dblclick', (e) => this.onDoubleClick(e));
            this.canvas.addEventListener('contextmenu', (e) => this.onContextMenu(e));

            this.canvas.addEventListener('touchstart', (e) => this.onTouchStart(e), { passive: false });
            this.canvas.addEventListener('touchmove', (e) => this.onTouchMove(e), { passive: false });
            this.canvas.addEventListener('touchend', (e) => this.onTouchEnd(e));

            window.addEventListener('resize', () => this.resize());

            // Keyboard shortcuts: Ctrl+Z undo, Ctrl+Shift+Z / Ctrl+Y redo
            document.addEventListener('keydown', (e) => this.onKeyDown(e));
        }

onKeyDown(e) {
            // Only handle when not typing in an input
            const tag = (e.target && e.target.tagName) || '';
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || e.target.isContentEditable) return;

            // Arrow keys: move the currently selected node(s) precisely.
            // Works with box-select multi-selection and single selection.
            const arrowKeys = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
            if (arrowKeys.includes(e.key)) {
                // Only move if there is a selection. If no nodes are selected,
                // fall through to normal behavior (e.g. nothing happens).
                let targets = this.selectedNodes && this.selectedNodes.length > 0
                    ? this.selectedNodes
                    : (this.selectedNode ? [this.selectedNode] : null);
                if (!targets || targets.length === 0) return;

                e.preventDefault();

                // Precision step: 1px by default, 10px with Shift held.
                const step = e.shiftKey ? 10 : 1;
                let dx = 0, dy = 0;
                if (e.key === 'ArrowLeft') dx = -step;
                else if (e.key === 'ArrowRight') dx = step;
                else if (e.key === 'ArrowUp') dy = -step;
                else if (e.key === 'ArrowDown') dy = step;

                // Capture the state BEFORE the first move so undo goes back
                // to the position before the arrow-key session began.
                if (!this._preActionState) {
                    this._preActionState = this.captureState();
                }

targets.forEach(n => {
                    n.x += dx;
                    n.y += dy;
                });

                this.detectAlignment();
                this.render();
                return;
            }

            const ctrl = e.ctrlKey || e.metaKey;
            if (!ctrl) return;

            const key = e.key.toLowerCase();
            if (key === 'z') {
                e.preventDefault();
                if (e.shiftKey) {
                    this.redo();
                } else {
                    this.undo();
                }
            } else if (key === 'y') {
                e.preventDefault();
                this.redo();
            }
        }

        async loadData() {
            const base = window.BASE_PATH || '';
            const loading = document.getElementById('loadingOverlay');
            try {
                const response = await fetch(base + '/topology/data');
                const data = await response.json();
                this.nodes = data.nodes || [];
                this.edges = data.edges || [];
                this.junctions = data.junctions || [];
                // Ensure every node has a size property
                this.nodes.forEach(n => {
                    n.size = (typeof n.size === 'number' && n.size > 0) ? n.size : 1;
                });
                this.originalNodes = this.nodes.map(n => ({ ...n }));
                // Reset history when fresh data loads
                this.undoStack = [];
                this.redoStack = [];
                this.updateHistoryButtons();
                if (loading) loading.style.display = 'none';
                this.fitAll();
            } catch (err) {
                console.error('Failed to load topology data:', err);
                if (loading) {
                    loading.innerHTML = '<p style="color:#EF4444;">Failed to load topology data. Check console for details.</p>';
                }
            }
        }

        // ─── Coordinate Transformation ───
        screenToWorld(sx, sy) {
            return {
                x: (sx - this.centerX - this.offset.x) / this.scale,
                y: (sy - this.centerY - this.offset.y) / this.scale,
            };
        }

        worldToScreen(wx, wy) {
            return {
                x: wx * this.scale + this.centerX + this.offset.x,
                y: wy * this.scale + this.centerY + this.offset.y,
            };
        }

        // ─── Node Geometry Helpers ───
        getNodeRadius(node) {
            return 28 * this.scale * (node.size || 1);
        }

        getResizeHandleAt(sx, sy) {
            if (!this.selectedNode) return null;
            const pos = this.worldToScreen(this.selectedNode.x, this.selectedNode.y);
            const radius = this.getNodeRadius(this.selectedNode);
            const handleSize = 12 * this.scale;
            // Handle positioned at bottom-right corner of the node bounding box
            const hx = pos.x + radius * 0.7;
            const hy = pos.y + radius * 0.7;
            if (sx >= hx - handleSize && sx <= hx + handleSize &&
                sy >= hy - handleSize && sy <= hy + handleSize) {
                return this.selectedNode;
            }
            return null;
        }

        // ─── Hit Testing ───
        getNodeAt(sx, sy) {
            // Check nodes in reverse so topmost (drawn last) is hit first
            for (let i = this.nodes.length - 1; i >= 0; i--) {
                const node = this.nodes[i];
                const pos = this.worldToScreen(node.x, node.y);
                const radius = this.getNodeRadius(node);
                const dx = sx - pos.x;
                const dy = sy - pos.y;
                if (dx * dx + dy * dy <= radius * radius) {
                    return node;
                }
            }
            return null;
        }

        getEdgeAt(sx, sy) {
            const threshold = 8 * this.scale;
            for (const edge of this.edges) {
                const source = this.nodes.find(n => n.id === edge.source);
                const target = this.nodes.find(n => n.id === edge.target);
                if (!source || !target) continue;

                const from = this.worldToScreen(source.x, source.y);
                const to = this.worldToScreen(target.x, target.y);

                const dx = to.x - from.x;
                const dy = to.y - from.y;
                const lenSq = dx * dx + dy * dy;
                if (lenSq === 0) continue;

                let t = ((sx - from.x) * dx + (sy - from.y) * dy) / lenSq;
                t = Math.max(0, Math.min(1, t));
                const projX = from.x + t * dx;
                const projY = from.y + t * dy;
                const dist = Math.sqrt((sx - projX) * (sx - projX) + (sy - projY) * (sy - projY));

                if (dist <= threshold) {
                    return edge;
                }
            }
            return null;
        }

        // ─── History (Undo/Redo) ───
        captureState() {
            return {
                nodes: this.nodes.map(n => ({ ...n })),
                edges: this.edges.map(e => ({ ...e })),
            };
        }

pushHistory() {
            // Push the pre-action state (captured at start of drag/resize)
            // This ensures undo goes back to the state BEFORE the change
            if (!this._preActionState) {
                this._preActionState = this.captureState();
            }
            this.undoStack.push(this._preActionState);
            this._preActionState = null;
            // Limit history size
            if (this.undoStack.length > this.maxHistory) {
                this.undoStack.shift();
            }
            // Clear redo stack on new action
            this.redoStack = [];
            this.updateHistoryButtons();
        }

        undo() {
            if (this.undoStack.length === 0) {
                this.showToast('Nothing to undo', 'info');
                return;
            }
            if (this.isRestoring) return;
            this.isRestoring = true;

            const prev = this.undoStack.pop();
            this.redoStack.push(this.captureState());
            this.restoreState(prev);
            this.isRestoring = false;
            this.updateHistoryButtons();
            this.showToast('Undo', 'success');
        }

        redo() {
            if (this.redoStack.length === 0) {
                this.showToast('Nothing to redo', 'info');
                return;
            }
            if (this.isRestoring) return;
            this.isRestoring = true;

            const next = this.redoStack.pop();
            this.undoStack.push(this.captureState());
            this.restoreState(next);
            this.isRestoring = false;
            this.updateHistoryButtons();
            this.showToast('Redo', 'success');
        }

        restoreState(state) {
            this.nodes = state.nodes.map(n => ({ ...n }));
            this.edges = state.edges.map(e => ({ ...e }));
            this.originalNodes = this.nodes.map(n => ({ ...n }));
            this.selectedNode = null;
            this.draggingNode = null;
            this.resizingNode = null;
            this.hoveredNode = null;
            this.hoveredEdge = null;
            this.hidePopup();
            this.render();
        }

        updateHistoryButtons() {
            const undoBtn = document.getElementById('undoBtn');
            const redoBtn = document.getElementById('redoBtn');
            if (undoBtn) {
                undoBtn.disabled = this.undoStack.length === 0;
                undoBtn.classList.toggle('disabled', this.undoStack.length === 0);
                undoBtn.title = this.undoStack.length === 0 ? 'Nothing to undo' : 'Undo (Ctrl+Z)';
            }
            if (redoBtn) {
                redoBtn.disabled = this.redoStack.length === 0;
                redoBtn.classList.toggle('disabled', this.redoStack.length === 0);
                redoBtn.title = this.redoStack.length === 0 ? 'Nothing to redo' : 'Redo (Ctrl+Shift+Z)';
            }
        }

        // ─── Mouse Handlers ───
        onMouseDown(e) {
            const rect = this.canvas.getBoundingClientRect();
            const sx = e.clientX - rect.left;
            const sy = e.clientY - rect.top;

            // If in disconnect mode and clicking an edge, delete it
            if (this.disconnectMode) {
                const edge = this.getEdgeAt(sx, sy);
                if (edge) {
                    this.deleteEdge(edge);
                    return;
                }
                this._preActionState = null;
            }

// If in connect mode, handle node selection
            if (this.connectMode) {
                const node = this.getNodeAt(sx, sy);
                if (node) {
                    if (!this.connectSource) {
                        // First node selected
                        this.connectSource = node;
                        this.selectedNode = node;
                        this.render();
                    } else if (node.id !== this.connectSource.id) {
                        // Second node selected - create link
                        this.createLink(this.connectSource, node);
                        this.connectSource = null;
                        this.selectedNode = null;
                        this.render();
                    }
                    return;
                }
                // Clicking empty space cancels connect source and allows panning
                // (same behavior as disconnect mode)
                this.connectSource = null;
                this.selectedNode = null;
                this._preActionState = null;
                this.isPanning = true;
                this.panStart = { x: sx - this.offset.x, y: sy - this.offset.y };
                this.canvas.style.cursor = 'grabbing';
                this.hidePopup();
                this.selectedNodes = [];
                this.render();
                return;
            }

            // Check label hit first - labels are draggable in ALL modes
            const hitLabel = this.getLabelAt(sx, sy);
            if (hitLabel) {
this.draggingLabel = hitLabel;
                this.labelDragStartPos = { x: sx, y: sy };
                const pos = this.worldToScreen(hitLabel.x, hitLabel.y);
                this.draggingLabelOffset = { x: sx - pos.x, y: sy - pos.y };
                this._preActionState = null;
                return;
            }

            // If in label mode and clicking empty space - add a new label
            if (this.labelMode) {
                const world = this.screenToWorld(sx, sy);
                this.showAddLabelModal(world.x, world.y);
                this._preActionState = null;
                return;
            }

// Box select mode: click node to toggle its selection, drag empty space to box-select
            if (this.boxSelectMode) {
                const node = this.getNodeAt(sx, sy);
                if (node) {
                    // Toggle node membership in the selection (additive selection)
                    const idx = this.selectedNodes.findIndex(n => n.id === node.id);
                    if (idx >= 0) {
                        this.selectedNodes.splice(idx, 1);
                    } else {
                        this.selectedNodes.push(node);
                    }
                    this.selectedNode = this.selectedNodes.length > 0
                        ? this.selectedNodes[this.selectedNodes.length - 1]
                        : null;
                    this.hidePopup();
                    this.render();
                } else {
                    this.isBoxSelecting = true;
                    this.boxSelectStart = { x: sx, y: sy };
                    this.boxSelectEnd = { x: sx, y: sy };
                    this.selectedNode = null;
                    this.selectedNodes = [];
                    this.hidePopup();
                    this.canvas.style.cursor = 'crosshair';
                }
                return;
            }

// Normal mode: check resize handle first (if a node is selected)
            const resizeHandle = this.getResizeHandleAt(sx, sy);
            if (resizeHandle) {
                // Capture state BEFORE the resize starts
                this._preActionState = this.captureState();
                this.resizingNode = resizeHandle;
                this.resizeStart = { x: sx, y: sy };
                this.resizeStartSize = resizeHandle.size || 1;
                // Distance from node center to mouse start, to determine direction
                const pos = this.worldToScreen(resizeHandle.x, resizeHandle.y);
                const radius = this.getNodeRadius(resizeHandle);
                this.resizeStartDistance = Math.max(10, Math.sqrt(
                    (sx - pos.x) * (sx - pos.x) + (sy - pos.y) * (sy - pos.y)
                ));
                this.canvas.style.cursor = 'nwse-resize';
                this.hidePopup();
                return;
            }

// Normal mode: drag or pan
            const node = this.getNodeAt(sx, sy);
            if (node) {
                // Capture state BEFORE the drag starts
                this._preActionState = this.captureState();
                this.draggingNode = node;
                this.dragStartPos = { x: sx, y: sy };
                const pos = this.worldToScreen(node.x, node.y);
                this.dragOffset = { x: sx - pos.x, y: sy - pos.y };
                this.selectedNode = node;
                // If clicked node is NOT already part of a multi-selection, reset to single selection
                if (!this.selectedNodes || !this.selectedNodes.some(n => n.id === node.id)) {
                    this.selectedNodes = [node];
                }
                this.render();
            } else {
                this.isPanning = true;
                this.panStart = { x: sx - this.offset.x, y: sy - this.offset.y };
                this.canvas.style.cursor = 'grabbing';
                this.hidePopup();
                this.selectedNode = null;
                this.selectedNodes = [];
                this.render();
            }
        }

        onMouseMove(e) {
            const rect = this.canvas.getBoundingClientRect();
            const sx = e.clientX - rect.left;
            const sy = e.clientY - rect.top;

            // Store mouse world position for connect mode preview line
            this.mouseWorldPos = this.screenToWorld(sx, sy);

            // Handle node resizing
            if (this.resizingNode) {
                const pos = this.worldToScreen(this.resizingNode.x, this.resizingNode.y);
                const dx = sx - pos.x;
                const dy = sy - pos.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                const ratio = dist / this.resizeStartDistance;
                let newSize = this.resizeStartSize * ratio;
                newSize = Math.max(this.minNodeSize, Math.min(this.maxNodeSize, newSize));
                this.resizingNode.size = Math.round(newSize * 10) / 10;
                this.render();
                return;
            }

            // Box select dragging
            if (this.isBoxSelecting) {
                this.boxSelectEnd = { x: sx, y: sy };
                this.render();
                return;
            }

            if (this.draggingNode) {
                const world = this.screenToWorld(sx - this.dragOffset.x, sy - this.dragOffset.y);
                // If multiple nodes are selected, drag them all together
                if (this.selectedNodes && this.selectedNodes.length > 1 &&
                    this.selectedNodes.some(n => n.id === this.draggingNode.id)) {
                    const dxWorld = world.x - this.draggingNode.x;
                    const dyWorld = world.y - this.draggingNode.y;
                    this.selectedNodes.forEach(n => {
                        if (n.id !== this.draggingNode.id) {
                            n.x += dxWorld;
                            n.y += dyWorld;
                        }
                    });
                    this.draggingNode.x = Math.round(world.x);
                    this.draggingNode.y = Math.round(world.y);
} else {
                    this.draggingNode.x = Math.round(world.x);
                    this.draggingNode.y = Math.round(world.y);
                }
                this.detectAlignment();
                this.render();
                return;
            }

            if (this.isPanning) {
                this.offset.x = sx - this.panStart.x;
                this.offset.y = sy - this.panStart.y;
                this.render();
                return;
            }

            // Label dragging
            if (this.draggingLabel) {
                const world = this.screenToWorld(sx - this.draggingLabelOffset.x, sy - this.draggingLabelOffset.y);
                this.draggingLabel.x = Math.round(world.x);
                this.draggingLabel.y = Math.round(world.y);
                this.render();
                return;
            }

            // Hover effects
            if (this.disconnectMode) {
                const edge = this.getEdgeAt(sx, sy);
                if (edge !== this.hoveredEdge) {
                    this.hoveredEdge = edge;
                    this.canvas.style.cursor = edge ? 'pointer' : 'default';
                    this.render();
                }
                return;
            }

            if (this.connectMode) {
                const node = this.getNodeAt(sx, sy);
                this.canvas.style.cursor = node ? 'crosshair' : 'default';
                if (node !== this.hoveredNode) {
                    this.hoveredNode = node;
                    this.render();
                }
                return;
            }

            if (this.labelMode) {
                const label = this.getLabelAt(sx, sy);
                this.canvas.style.cursor = label ? 'move' : 'text';
                return;
            }

            // Normal mode: check resize handle hover
            const resizeHandle = this.getResizeHandleAt(sx, sy);
            if (resizeHandle) {
                this.hoveringResizeHandle = true;
                this.canvas.style.cursor = 'nwse-resize';
                this.render();
                return;
            }
            if (this.hoveringResizeHandle) {
                this.hoveringResizeHandle = false;
                this.render();
            }

            const node = this.getNodeAt(sx, sy);
            this.canvas.style.cursor = node ? 'pointer' : 'grab';
            if (node !== this.hoveredNode) {
                this.hoveredNode = node;
                this.render();
            }
        }

onMouseUp(e) {
            // Finish box selection
            if (this.isBoxSelecting) {
                const rect = this.canvas.getBoundingClientRect();
                const sx = e.clientX - rect.left;
                const sy = e.clientY - rect.top;
                this.boxSelectEnd = { x: sx, y: sy };
                this.completeBoxSelect();
                this.isBoxSelecting = false;
                this.canvas.style.cursor = 'grab';
                this.render();
                return;
            }
            if (this.draggingNode) {
                // Check if this was a click (no significant movement) vs a drag
                if (this.dragStartPos) {
                    const rect = this.canvas.getBoundingClientRect();
                    const sx = e.clientX - rect.left;
                    const sy = e.clientY - rect.top;
                    const dx = sx - this.dragStartPos.x;
                    const dy = sy - this.dragStartPos.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    // If movement is less than 5px, treat as a click → show popup
                    if (dist < 5 && this.selectedNode) {
                        this.showPopup(this.selectedNode);
                    } else {
                        // A real drag happened - record history
                        this.pushHistory();
                    }
                }
                this.draggingNode = null;
                this.dragStartPos = null;
            }
            if (this.resizingNode) {
                // Record resize in history
                this.pushHistory();
                this.resizingNode = null;
                this.resizeStart = { x: 0, y: 0 };
                this.canvas.style.cursor = 'grab';
            }
            if (this.draggingLabel) {
                this.draggingLabel = null;
            }
            if (this.editingLabel) {
                this.finishEditingLabel();
            }
            this.isPanning = false;
            if (!this.connectMode && !this.disconnectMode && !this.labelMode && !this.resizingNode) {
                this.canvas.style.cursor = 'grab';
            }
        }

        onWheel(e) {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            this.zoom(delta, e.clientX, e.clientY);
        }

        onDoubleClick(e) {
            if (this.connectMode || this.disconnectMode || this.labelMode) return;
            const rect = this.canvas.getBoundingClientRect();
            const sx = e.clientX - rect.left;
            const sy = e.clientY - rect.top;
            // Check if double-click on a label → edit it
            const label = this.getLabelAt(sx, sy);
            if (label) {
                this.editLabelText(label);
                return;
            }
            const node = this.getNodeAt(sx, sy);
            if (node) {
                const base = window.BASE_PATH || '';
                window.open(base + '/assets/' + node.id, '_blank');
            }
        }

        onContextMenu(e) {
            e.preventDefault();
            const rect = this.canvas.getBoundingClientRect();
            const sx = e.clientX - rect.left;
            const sy = e.clientY - rect.top;
            const label = this.getLabelAt(sx, sy);
            if (label) {
                this.showLabelContextMenu(e.clientX, e.clientY, label);
            }
        }

        showLabelContextMenu(mx, my, label) {
            // Remove existing context menu
            const old = document.getElementById('labelContextMenu');
            if (old) old.remove();

            const menu = document.createElement('div');
            menu.id = 'labelContextMenu';
            menu.className = 'label-context-menu';
            menu.style.cssText = 'position:fixed;left:' + mx + 'px;top:' + my + 'px;z-index:10000;';
            menu.innerHTML =
                '<div class="context-menu-item" data-action="edit"><i class="fas fa-edit"></i> Edit Label</div>' +
                '<div class="context-menu-item" data-action="delete"><i class="fas fa-trash"></i> Delete Label</div>' +
                '<div class="context-menu-item" data-action="cancel"><i class="fas fa-times"></i> Cancel</div>';
            document.body.appendChild(menu);

            const self = this;
            menu.querySelectorAll('.context-menu-item').forEach(item => {
                item.addEventListener('click', function() {
                    const action = this.dataset.action;
                    if (action === 'edit') {
                        self.editLabelText(label);
                    } else if (action === 'delete') {
                        self.deleteLabel(label);
                    }
                    menu.remove();
                });
            });

            // Close on outside click
            setTimeout(() => {
                document.addEventListener('click', function closeMenu(e) {
                    if (!menu.contains(e.target)) {
                        menu.remove();
                        document.removeEventListener('click', closeMenu);
                    }
                });
            }, 10);
        }

        editLabelText(label) {
            const newText = prompt('Edit label text:', label.text);
            if (newText && newText.trim() !== '' && newText.trim() !== label.text) {
                const base = window.BASE_PATH || '';
                fetch(base + '/topology/labels/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        id: label.id,
                        text: newText.trim(),
                        x: label.x,
                        y: label.y,
                        font_size: label.font_size || 16,
                        color: label.color || '#64748B',
                    }),
                })
                .then(r => r.json())
                .then(result => {
                    if (result.success) {
                        this.loadLabels();
                        this.showToast('Label updated', 'success');
                    }
                })
                .catch(err => console.error('Failed to update label:', err));
            }
        }

        showAddLabelModal(worldX, worldY) {
            const text = prompt('Enter label text:', 'New Label');
            if (text && text.trim() !== '') {
                this.addLabelAt(worldX, worldY, text.trim());
            }
        }

        async addLabelAt(worldX, worldY, text) {
            if (!text) {
                text = prompt('Enter label text:', 'New Label');
                if (!text || text.trim() === '') return;
            }
            const base = window.BASE_PATH || '';
            try {
                const response = await fetch(base + '/topology/labels/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        text: text.trim(),
                        x: worldX,
                        y: worldY,
                        font_size: 24,
                        color: '#475569',
                    }),
                });
                const result = await response.json();
                if (result.success) {
                    await this.loadLabels();
                    this.showToast('Label added', 'success');
                }
            } catch (err) {
                console.error('Failed to save label:', err);
            }
        }

        // ─── Touch Handlers ───
        onTouchStart(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const rect = this.canvas.getBoundingClientRect();
            const sx = touch.clientX - rect.left;
            const sy = touch.clientY - rect.top;

            if (this.disconnectMode) {
                const edge = this.getEdgeAt(sx, sy);
                if (edge) {
                    this.deleteEdge(edge);
                    return;
                }
            }

            if (this.connectMode) {
                const node = this.getNodeAt(sx, sy);
                if (node) {
                    if (!this.connectSource) {
                        this.connectSource = node;
                        this.selectedNode = node;
                        this.render();
                    } else if (node.id !== this.connectSource.id) {
                        this.createLink(this.connectSource, node);
                        this.connectSource = null;
                        this.selectedNode = null;
                        this.render();
                    }
} else {
                    this.connectSource = null;
                    this.selectedNode = null;
                    this.isPanning = true;
                    this.panStart = { x: sx - this.offset.x, y: sy - this.offset.y };
                    this._preActionState = null;
                    this.render();
                }
                return;
            }

            // Resize handle on touch
            const resizeHandle = this.getResizeHandleAt(sx, sy);
            if (resizeHandle) {
                // Capture state BEFORE the resize starts
                this._preActionState = this.captureState();
                this.resizingNode = resizeHandle;
                this.resizeStart = { x: sx, y: sy };
                this.resizeStartSize = resizeHandle.size || 1;
                const pos = this.worldToScreen(resizeHandle.x, resizeHandle.y);
                this.resizeStartDistance = Math.max(10, Math.sqrt(
                    (sx - pos.x) * (sx - pos.x) + (sy - pos.y) * (sy - pos.y)
                ));
                return;
            }

            const node = this.getNodeAt(sx, sy);
            if (node) {
                // Capture state BEFORE the drag starts
                this._preActionState = this.captureState();
                this.draggingNode = node;
                this.touchStartPos = { x: sx, y: sy };
                const pos = this.worldToScreen(node.x, node.y);
                this.dragOffset = { x: sx - pos.x, y: sy - pos.y };
                this.selectedNode = node;
                this.render();
            } else {
                this.isPanning = true;
                this.panStart = { x: sx - this.offset.x, y: sy - this.offset.y };
                this._preActionState = null;
            }
        }

        onTouchMove(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const rect = this.canvas.getBoundingClientRect();
            const sx = touch.clientX - rect.left;
            const sy = touch.clientY - rect.top;

            if (this.resizingNode) {
                const pos = this.worldToScreen(this.resizingNode.x, this.resizingNode.y);
                const dx = sx - pos.x;
                const dy = sy - pos.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                const ratio = dist / this.resizeStartDistance;
                let newSize = this.resizeStartSize * ratio;
                newSize = Math.max(this.minNodeSize, Math.min(this.maxNodeSize, newSize));
                this.resizingNode.size = Math.round(newSize * 10) / 10;
                this.render();
} else if (this.draggingNode) {
                const world = this.screenToWorld(sx - this.dragOffset.x, sy - this.dragOffset.y);
                this.draggingNode.x = Math.round(world.x);
                this.draggingNode.y = Math.round(world.y);
                this.detectAlignment();
                this.render();
            } else if (this.isPanning) {
                this.offset.x = sx - this.panStart.x;
                this.offset.y = sy - this.panStart.y;
                this.render();
            }
        }

        onTouchEnd(e) {
            if (this.resizingNode) {
                this.pushHistory();
                this.resizingNode = null;
            }
            if (this.draggingNode) {
                // Check if this was a tap (no significant movement) vs a drag
                if (this.touchStartPos) {
                    const rect = this.canvas.getBoundingClientRect();
                    const touch = e.changedTouches[0];
                    const sx = touch.clientX - rect.left;
                    const sy = touch.clientY - rect.top;
                    const dx = sx - this.touchStartPos.x;
                    const dy = sy - this.touchStartPos.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    // If movement is less than 5px, treat as a tap → show popup
                    if (dist < 5 && this.selectedNode) {
                        this.showPopup(this.selectedNode);
                    } else {
                        this.pushHistory();
                    }
                }
                this.draggingNode = null;
                this.touchStartPos = null;
            }
            if (this.draggingLabel) {
                this.draggingLabel = null;
            }
            this.isPanning = false;
        }

        // ─── Zoom ───
        zoom(delta, cx, cy) {
            const oldScale = this.scale;
            this.scale = Math.max(this.minScale, Math.min(this.maxScale, this.scale + delta));
            if (cx !== undefined && cy !== undefined) {
                const rect = this.canvas.getBoundingClientRect();
                const sx = cx - rect.left;
                const sy = cy - rect.top;
                this.offset.x = sx - (sx - this.offset.x) * (this.scale / oldScale);
                this.offset.y = sy - (sy - this.offset.y) * (this.scale / oldScale);
            }
            this.render();
        }

        // ─── View Controls ───
        resetView() {
            this.offset = { x: 0, y: 0 };
            this.scale = 1;
            this.render();
        }

        fitAll() {
            if (this.nodes.length === 0) return;
            const padding = 60;
            let minX = Infinity, maxX = -Infinity;
            let minY = Infinity, maxY = -Infinity;
            this.nodes.forEach(n => {
                minX = Math.min(minX, n.x);
                maxX = Math.max(maxX, n.x);
                minY = Math.min(minY, n.y);
                maxY = Math.max(maxY, n.y);
            });
            const width = maxX - minX || 200;
            const height = maxY - minY || 200;
            const scaleX = (this.canvas.width - padding * 2) / width;
            const scaleY = (this.canvas.height - padding * 2) / height;
            this.scale = Math.min(scaleX, scaleY, 1.5);
            this.offset.x = -((minX + maxX) / 2) * this.scale + this.canvas.width / 2 - this.centerX;
            this.offset.y = -((minY + maxY) / 2) * this.scale + this.canvas.height / 2 - this.centerY;
            this.render();
        }

        autoLayout() {
            if (this.nodes.length === 0) return;
            const centerX = 0;
            const centerY = 0;
            const radius = 200;
            const types = { switch: 0, server: 1, printer: 2, pc: 3, laptop: 4, nas: 5, nvr: 6, default: 7 };
            const sorted = [...this.nodes].sort((a, b) => (types[a.type] || 7) - (types[b.type] || 7));
            sorted.forEach((node, i) => {
                const angle = (2 * Math.PI * i) / sorted.length - Math.PI / 2;
                const r = radius + (types[node.type] || 0) * 40;
                node.x = Math.round(centerX + r * Math.cos(angle));
                node.y = Math.round(centerY + r * Math.sin(angle));
            });
            this.savePositions();
            this.fitAll();
        }

        // ─── Connect / Disconnect Mode ───
        setConnectMode(enabled, linkType) {
            this.connectMode = enabled;
            this.disconnectMode = false;
            this.connectSource = null;
            this.selectedNode = null;
            this.hoveredNode = null;
            this.hoveredEdge = null;
            if (linkType) {
                this.connectType = linkType;
            }
            this.canvas.style.cursor = enabled ? 'crosshair' : 'grab';
            this.render();
        }

        setDisconnectMode(enabled) {
            this.disconnectMode = enabled;
            this.connectMode = false;
            this.connectSource = null;
            this.selectedNode = null;
            this.hoveredNode = null;
            this.hoveredEdge = null;
            this.canvas.style.cursor = enabled ? 'default' : 'grab';
            this.render();
        }

        cancelModes() {
            this.connectMode = false;
            this.disconnectMode = false;
            this.labelMode = false;
            this.boxSelectMode = false;
            this.isBoxSelecting = false;
            this.connectSource = null;
            this.selectedNode = null;
            this.hoveredNode = null;
            this.hoveredEdge = null;
            this.canvas.style.cursor = 'grab';
            this.render();
            // Update button states
            document.querySelectorAll('.topology-actions .btn').forEach(btn => {
                btn.classList.remove('active', 'btn-primary');
            });
            document.querySelectorAll('.connection-actions .btn').forEach(btn => {
                btn.classList.remove('active', 'btn-primary');
            });
            // Hide cancel button
            const cancelBtn = document.getElementById('cancelBtn');
            if (cancelBtn) cancelBtn.style.display = 'none';
        }

async createLink(sourceNode, targetNode) {
            const base = window.BASE_PATH || '';
            try {
                const response = await fetch(base + '/topology/add-link', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        source_id: sourceNode.id,
                        target_id: targetNode.id,
                        link_type: this.connectType,
                    }),
                });
                const result = await response.json();
if (result.success) {
                    // Capture the pre-change state (before the new edge is added)
                    const preState = this.captureState();
                    const savedUndo = [...this.undoStack];
                    const savedRedo = [...this.redoStack];
                    // Preserve the current view (zoom/pan) so the canvas does not
                    // zoom out after creating a link (same behavior as disconnect)
                    const savedScale = this.scale;
                    const savedOffset = { x: this.offset.x, y: this.offset.y };
                    // Reload data to get the new edge with its ID
                    await this.loadData();
                    // Restore the view after loadData's fitAll()
                    this.scale = savedScale;
                    this.offset.x = savedOffset.x;
                    this.offset.y = savedOffset.y;
                    // Restore history stacks (loadData resets them) and set the
                    // pre-action state so undo reverts to before the link existed
                    this.undoStack = savedUndo;
                    this.redoStack = savedRedo;
                    this._preActionState = preState;
                    this.pushHistory();
                    this.updateHistoryButtons();
                    this.render();
                    this.showToast('Link created successfully', 'success');
                } else {
                    this.showToast(result.error || 'Failed to create link', 'danger');
                }
            } catch (err) {
                console.error('Failed to create link:', err);
                this.showToast('Failed to create link. Check console.', 'danger');
            }
        }

        async deleteEdge(edge) {
            const base = window.BASE_PATH || '';
            try {
                // Capture state before deletion for undo
                this.pushHistory();
                const response = await fetch(base + '/topology/remove-link/' + edge.id, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const result = await response.json();
                if (result.success) {
                    // Remove edge from local array
                    this.edges = this.edges.filter(e => e.id !== edge.id);
                    this.hoveredEdge = null;
                    this.render();
                    this.showToast('Link removed', 'success');
                } else {
                    this.showToast(result.error || 'Failed to remove link', 'danger');
                }
            } catch (err) {
                console.error('Failed to delete edge:', err);
                this.showToast('Failed to remove link. Check console.', 'danger');
            }
        }

        showToast(message, type) {
            const container = document.querySelector('.page-content');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = 'alert alert-' + type + ' alert-dismissible';
            toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;max-width:400px;animation:slideIn 0.3s ease;';
            toast.innerHTML = '<span>' + message + '</span><button class="alert-close" onclick="this.parentElement.remove()">&times;</button>';
            container.appendChild(toast);
            setTimeout(() => { if (toast.parentElement) toast.remove(); }, 4000);
        }

        // ─── Filter ───
        filterByType(type) {
            if (type === 'all') {
                this.nodes = this.originalNodes.map(n => ({ ...n }));
            } else {
                this.nodes = this.originalNodes.map(n => ({ ...n })).filter(n => n.type === type);
            }
            this.render();
        }

        // ─── Search ───
        search(query) {
            this.searchQuery = query ? query.toLowerCase().trim() : '';
            this.render();
        }

        // ─── Popup ───
        showPopup(node) {
            const popup = document.getElementById('devicePopup');
            if (!popup) return;
const typeIcons = { pc: 'fa-desktop', laptop: 'fa-laptop', switch: 'fa-network-wired', server: 'fa-server', printer: 'fa-print', nas: 'fa-database', nvr: 'fa-video', vm: 'fa-cloud', default: 'fa-microchip' };
            const iconEl = popup.querySelector('.popup-device-icon i');
            if (iconEl) {
                iconEl.className = 'fas ' + (typeIcons[node.type] || typeIcons.default);
                iconEl.style.color = this.colors[node.type] || this.colors.default;
            }
            const titleEl = document.getElementById('popupHostname');
            if (titleEl) titleEl.textContent = node.label || 'Unknown Device';
            const badgeEl = document.getElementById('popupTag');
            if (badgeEl) {
                badgeEl.textContent = node.status || 'unknown';
                const statusColors = { active: 'success', inactive: 'secondary', maintenance: 'warning', retired: 'danger', lost: 'danger', reserved: 'info' };
                badgeEl.className = 'badge badge-sm badge-' + (statusColors[node.status] || 'secondary');
            }
            this.setPopupText('popupIP', node.ip || '—');
            this.setPopupText('popupMAC', node.mac || '—');
            this.setPopupText('popupUser', node.user || '—');
            this.setPopupText('popupStatus', node.status || '—');
            this.setPopupText('popupType', node.type || '—');
            this.setPopupText('popupAssetTag', node.asset_tag || '—');
            const base = window.BASE_PATH || '';
            const viewLink = document.getElementById('popupViewLink');
            const editLink = document.getElementById('popupEditLink');
            if (viewLink) viewLink.href = base + '/assets/' + node.id;
            if (editLink) editLink.href = base + '/assets/' + node.id + '/edit';
            const screenPos = this.worldToScreen(node.x, node.y);
            popup.style.left = Math.min(screenPos.x + 40, this.canvas.width - 280) + 'px';
            popup.style.top = Math.max(10, screenPos.y - 40) + 'px';
            popup.style.display = 'block';
            popup.classList.add('active');
        }

        setPopupText(id, text) {
            const el = document.getElementById(id);
            if (el) el.textContent = text;
        }

        hidePopup() {
            const popup = document.getElementById('devicePopup');
            if (popup) {
                popup.style.display = 'none';
                popup.classList.remove('active');
            }
        }

        // ─── Save Positions & Labels ───
        async savePositions() {
            const positions = {};
            this.nodes.forEach(node => {
                positions[node.id] = { x: node.x, y: node.y, size: node.size || 1 };
            });
            try {
                const base = window.BASE_PATH || '';
                // Save node positions (including sizes)
                await fetch(base + '/topology/save-positions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(positions),
                });
                // Save label positions
                await fetch(base + '/topology/save-label-positions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(this.labels),
                });
                this.showToast('Positions saved successfully', 'success');
            } catch (err) {
                console.error('Failed to save positions:', err);
            }
        }

        // ─── Labels ───
        async loadLabels() {
            const base = window.BASE_PATH || '';
            try {
                const response = await fetch(base + '/topology/labels');
                this.labels = await response.json();
                this.render();
            } catch (err) {
                console.error('Failed to load labels:', err);
            }
        }

        setLabelMode(enabled) {
            this.labelMode = enabled;
            this.connectMode = false;
            this.disconnectMode = false;
            this.connectSource = null;
            this.selectedNode = null;
            this.hoveredNode = null;
            this.hoveredEdge = null;
            this.canvas.style.cursor = enabled ? 'text' : 'grab';
            this.render();
        }

        getLabelAt(sx, sy) {
            // Check in reverse order so topmost label is selected first
            for (let i = this.labels.length - 1; i >= 0; i--) {
                const label = this.labels[i];
                const pos = this.worldToScreen(label.x, label.y);
                const fontSize = (label.font_size || 16) * this.scale;
                const textWidth = label.text.length * fontSize * 0.6;
                const textHeight = fontSize * 1.4;
                const halfW = textWidth / 2;
                const halfH = textHeight / 2;
                if (sx >= pos.x - halfW && sx <= pos.x + halfW &&
                    sy >= pos.y - halfH && sy <= pos.y + halfH) {
                    return label;
                }
            }
            return null;
        }

        async deleteLabel(label) {
            if (!confirm('Delete this label?')) return;
            const base = window.BASE_PATH || '';
            try {
                await fetch(base + '/topology/labels/delete/' + label.id, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                this.labels = this.labels.filter(l => l.id !== label.id);
                this.render();
                this.showToast('Label deleted', 'success');
            } catch (err) {
                console.error('Failed to delete label:', err);
            }
        }

        // ─── Render ───
        render() {
            // Throttle render via requestAnimationFrame to prevent lag during drag
            if (this._renderPending) return;
            this._renderPending = true;
            requestAnimationFrame(() => {
                this._renderPending = false;
                this._doRender();
            });
        }

        _doRender() {
            const ctx = this.ctx;
            const w = this.canvas.width;
            const h = this.canvas.height;
            ctx.clearRect(0, 0, w, h);
            this.drawGrid(ctx, w, h);
            this.edges.forEach(edge => this.drawEdge(ctx, edge));
            this.nodes.forEach(node => this.drawNode(ctx, node));
            this.nodes.forEach(node => this.drawNodeLabel(ctx, node));

            // Draw annotation labels
            this.labels.forEach(label => this.drawCanvasLabel(ctx, label));

            // Draw junctions (edge tap points)
            this.drawJunctions(ctx);

            // Draw resize handle on selected node
            if (this.selectedNode && !this.connectMode && !this.disconnectMode && !this.labelMode) {
                this.drawResizeHandle(ctx);
            }

            // Draw box selection rectangle
            if (this.isBoxSelecting) {
                this.drawBoxSelection(ctx);
            }

            // Draw connect mode preview line
            if (this.connectMode && this.connectSource) {
                this.drawPreviewLine(ctx);
            }
        }

        drawBoxSelection(ctx) {
            const start = this.boxSelectStart;
            const end = this.boxSelectEnd;
            if (!start || !end) return;
            const x = Math.min(start.x, end.x);
            const y = Math.min(start.y, end.y);
            const w = Math.abs(end.x - start.x);
            const h = Math.abs(end.y - start.y);
            if (w < 2 && h < 2) return;
            ctx.save();
            ctx.fillStyle = 'rgba(59, 130, 246, 0.12)';
            ctx.strokeStyle = '#3B82F6';
            ctx.lineWidth = 1.5;
            ctx.setLineDash([5, 3]);
            ctx.fillRect(x, y, w, h);
            ctx.strokeRect(x, y, w, h);
            ctx.setLineDash([]);
            ctx.restore();
        }

        drawJunctions(ctx) {
            if (!this.junctions || this.junctions.length === 0) return;
            const radius = 4 * this.scale;
            ctx.save();
            this.junctions.forEach(j => {
                const pos = this.worldToScreen(j.x, j.y);
                ctx.beginPath();
                ctx.arc(pos.x, pos.y, radius, 0, Math.PI * 2);
                ctx.fillStyle = '#64748B';
                ctx.fill();
                ctx.strokeStyle = '#FFFFFF';
                ctx.lineWidth = 1.5;
                ctx.stroke();
            });
            ctx.restore();
        }

        completeBoxSelect() {
            const start = this.boxSelectStart;
            const end = this.boxSelectEnd;
            if (!start || !end) return;
            const x1 = Math.min(start.x, end.x);
            const y1 = Math.min(start.y, end.y);
            const x2 = Math.max(start.x, end.x);
            const y2 = Math.max(start.y, end.y);
            // Only treat as box select if the drag was large enough
            if (x2 - x1 < 5 && y2 - y1 < 5) {
                this.selectedNodes = [];
                return;
            }
            const found = this.nodes.filter(node => {
                const pos = this.worldToScreen(node.x, node.y);
                return pos.x >= x1 && pos.x <= x2 && pos.y >= y1 && pos.y <= y2;
            });
            this.selectedNodes = found;
            this.selectedNode = found.length > 0 ? found[0] : null;
        }

        toggleBoxSelect() {
            this.boxSelectMode = !this.boxSelectMode;
            this.connectMode = false;
            this.disconnectMode = false;
            this.labelMode = false;
            this.isBoxSelecting = false;
            this.selectedNodes = [];
            this.selectedNode = null;
            this.hoveredNode = null;
            this.hoveredEdge = null;
            this.canvas.style.cursor = this.boxSelectMode ? 'crosshair' : 'grab';
            this.render();
            // Update button states
            const btn = document.getElementById('boxSelectBtn');
            if (btn) {
                btn.classList.toggle('active', this.boxSelectMode);
                btn.classList.toggle('btn-primary', this.boxSelectMode);
            }
            return this.boxSelectMode;
        }

        drawResizeHandle(ctx) {
            const node = this.selectedNode;
            if (!node) return;
            const pos = this.worldToScreen(node.x, node.y);
            const radius = this.getNodeRadius(node);
            const size = 14 * this.scale;
            const hx = pos.x + radius * 0.7;
            const hy = pos.y + radius * 0.7;

            ctx.save();
            // Handle background
            ctx.beginPath();
            ctx.roundRect(hx - size / 2, hy - size / 2, size, size, 3 * this.scale);
            ctx.fillStyle = '#FFFFFF';
            ctx.fill();
            ctx.strokeStyle = this.hoveringResizeHandle ? '#2563EB' : '#3B82F6';
            ctx.lineWidth = 2;
            ctx.stroke();

            // Diagonal grip lines
            ctx.strokeStyle = this.hoveringResizeHandle ? '#2563EB' : '#64748B';
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.moveTo(hx - size * 0.25, hy + size * 0.2);
            ctx.lineTo(hx + size * 0.2, hy - size * 0.25);
            ctx.moveTo(hx - size * 0.05, hy + size * 0.25);
            ctx.lineTo(hx + size * 0.25, hy - size * 0.05);
            ctx.stroke();

            // Label tooltip when hovering
            if (this.hoveringResizeHandle) {
                ctx.font = `${10 * this.scale}px sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                ctx.fillStyle = '#2563EB';
                ctx.fillText('Drag to resize', hx, hy - size);
            }
            ctx.restore();
        }

        drawPreviewLine(ctx) {
            const from = this.worldToScreen(this.connectSource.x, this.connectSource.y);
            const to = this.worldToScreen(this.mouseWorldPos.x, this.mouseWorldPos.y);
            ctx.beginPath();
            ctx.moveTo(from.x, from.y);
            ctx.lineTo(to.x, to.y);
            ctx.strokeStyle = this.edgeColors[this.connectType] || '#94A3B8';
            ctx.lineWidth = 2 * this.scale;
            ctx.setLineDash([6, 4]);
            ctx.stroke();
            ctx.setLineDash([]);
        }

        drawGrid(ctx, w, h) {
            const gridSize = 50 * this.scale;
            ctx.strokeStyle = 'rgba(0,0,0,0.03)';
            ctx.lineWidth = 1;
            for (let x = gridSize; x < w; x += gridSize) {
                ctx.beginPath();
                ctx.moveTo(x, 0);
                ctx.lineTo(x, h);
                ctx.stroke();
            }
            for (let y = gridSize; y < h; y += gridSize) {
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(w, y);
                ctx.stroke();
            }
        }

        drawNode(ctx, node) {
            const pos = this.worldToScreen(node.x, node.y);
            const radius = this.getNodeRadius(node);
            const color = this.colors[node.type] || this.colors.default;
            const isSelected = this.selectedNode && this.selectedNode.id === node.id;
            const isHovered = this.hoveredNode && this.hoveredNode.id === node.id;
            const isConnectSource = this.connectSource && this.connectSource.id === node.id;
            const isGroupSelected = this.selectedNodes && this.selectedNodes.length > 0 &&
                this.selectedNodes.some(n => n.id === node.id) && !isSelected;

// Search highlight logic: dim non-matching nodes
            const hasSearch = this.searchQuery !== '';
            const matchesSearch = hasSearch && (
                (node.label && node.label.toLowerCase().includes(this.searchQuery)) ||
                (node.ip && node.ip.toLowerCase().includes(this.searchQuery)) ||
                (node.type && node.type.toLowerCase().includes(this.searchQuery))
            );
            const isDimmed = hasSearch && !matchesSearch;

            const alpha = isDimmed ? 0.15 : 1;

            // Active concern highlight: draw a red pulse ring + count badge
            const concernCount = node.concernCount || 0;
            if (concernCount > 0) {
                // Red ring (pulsing) around the node
                const pulse = 0.5 + 0.5 * Math.sin(Date.now() / 400);
                const ringRadius = radius + (10 + 3 * pulse) * this.scale;
                ctx.beginPath();
                ctx.arc(pos.x, pos.y, ringRadius, 0, Math.PI * 2);
                ctx.strokeStyle = '#EF4444';
                ctx.lineWidth = ((isSelected || isConnectSource) ? 4 : 3) * this.scale;
                ctx.globalAlpha = 0.55 + 0.25 * pulse;
                ctx.stroke();
                ctx.globalAlpha = 1;

                // Count badge (top-right corner)
                const badgeRadius = 11 * this.scale * (node.size || 1);
                const bx = pos.x + radius * 0.7;
                const by = pos.y - radius * 0.7;
                ctx.beginPath();
                ctx.arc(bx, by, badgeRadius, 0, Math.PI * 2);
                ctx.fillStyle = '#DC2626';
                ctx.fill();
                ctx.strokeStyle = '#FFFFFF';
                ctx.lineWidth = 2;
                ctx.stroke();
                ctx.fillStyle = '#FFFFFF';
                ctx.font = `bold ${Math.max(10, 11 * this.scale * (node.size || 1))}px sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(concernCount > 99 ? '99+' : concernCount, bx, by + 0.5);
            }

if (isSelected || isConnectSource) {
                ctx.beginPath();
                ctx.arc(pos.x, pos.y, radius + 8 * this.scale, 0, Math.PI * 2);
                ctx.fillStyle = color + '30';
                ctx.fill();
            }

            ctx.save();
            ctx.globalAlpha = alpha;

            ctx.beginPath();
            ctx.arc(pos.x, pos.y, radius, 0, Math.PI * 2);
            ctx.fillStyle = color + '20';
            ctx.fill();
            ctx.strokeStyle = color;
            ctx.lineWidth = (isSelected || isConnectSource || matchesSearch) ? 3 : 2;
            ctx.stroke();

            // Highlight group-selected nodes (part of a multi-selection but not the primary)
            if (isGroupSelected) {
                ctx.beginPath();
                ctx.arc(pos.x, pos.y, radius + 5 * this.scale, 0, Math.PI * 2);
                ctx.strokeStyle = '#3B82F6';
                ctx.lineWidth = 2;
                ctx.setLineDash([5, 3]);
                ctx.stroke();
                ctx.setLineDash([]);
            }

const img = this.iconImages[node.type] || this.iconImages.default;
            if (img) {
                // Draw PNG image icon scaled to the node size
                const iconSize = radius * 0.75;
                ctx.drawImage(img, pos.x - iconSize / 2, pos.y - iconSize / 2, iconSize, iconSize);
            } else {
                // Fallback to emoji while the image loads (or if the file is missing)
                const icons = { pc: '🖥', laptop: '💻', switch: '🔀', server: '🖧', printer: '🖨', nas: '🗄', nvr: '📹', vm: '☁', default: '🔌' };
                const icon = icons[node.type] || icons.default;
                // Icon scales with node size
                ctx.font = `${(20 * this.scale * (node.size || 1))}px sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(icon, pos.x, pos.y);
            }

            ctx.restore();

            // Draw "pin" icon for matched search
            if (matchesSearch) {
                ctx.font = `${16 * this.scale}px sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                ctx.fillStyle = '#F59E0B';
                ctx.fillText('📍', pos.x, pos.y - radius - 4 * this.scale);
            }

            // Show size indicator on selected node
            if (isSelected && node.size && node.size !== 1) {
                ctx.font = `bold ${10 * this.scale}px sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';
                ctx.fillStyle = '#2563EB';
                ctx.fillText((node.size).toFixed(1) + 'x', pos.x + radius, pos.y + radius);
            }
        }

        drawNodeLabel(ctx, node) {
            const pos = this.worldToScreen(node.x, node.y);
            const radius = this.getNodeRadius(node);
            const isSelected = this.selectedNode && this.selectedNode.id === node.id;
            const labelScale = node.size || 1;

            // Theme-aware colors so labels stay readable in both light and dark mode.
            const isDark = (document.documentElement.getAttribute('data-theme') === 'dark');
            const mainColor = isSelected ? '#3B82F6' : (isDark ? '#E2E8F0' : '#64748B');
            const subColor = isDark ? '#94A3B8' : '#94A3B8';
            // Halo should contrast with the canvas background: dark halo in dark mode,
            // white halo in light mode.
            const haloColor = isDark ? 'rgba(7, 10, 19, 0.9)' : 'rgba(255, 255, 255, 0.9)';

// Keep text sharp and readable even when zoomed out: clamp the font
            // size with a minimum scale so it never shrinks below a legible size.
            // In export mode we use a larger minimum so the exported image is readable.
            const textScale = Math.max(this.scale, this._exportMode ? 1.0 : 0.67);
            const lineHeight = (this._exportMode ? 11 : 10) * textScale * labelScale;

            // Collect label lines. To avoid overlapping when zoomed out, only the
            // primary hostname is shown below a zoom threshold; the asset tag and
            // IP are shown again once the user zooms in far enough.
            // In export mode we always show all lines so every attribute is captured.
            const lines = [];
            const mainFont = `bold ${(10.8 * textScale * labelScale)}px -apple-system, sans-serif`;
            const subFont = `${(10 * textScale * labelScale)}px monospace`;
            lines.push({ text: node.label, font: mainFont, color: mainColor });
            if (this._exportMode || this.scale >= 0.6) {
                if (node.asset_tag && node.asset_tag !== node.label) {
                    lines.push({ text: node.asset_tag, font: subFont, color: subColor });
                }
                if (node.ip) {
                    lines.push({ text: node.ip, font: subFont, color: subColor });
                }
            }

            // Draw the text lines with a halo outline around each glyph.
            // This keeps the text readable over connection lines WITHOUT drawing a
            // solid background block that would hide the line. The line stays
            // visible between the letters.
            ctx.textAlign = 'center';
            ctx.textBaseline = 'top';
            ctx.lineJoin = 'round';
            ctx.lineWidth = Math.max(2, 2 * this.scale * labelScale);
            let lineY = pos.y + radius + 2;
            for (const line of lines) {
                ctx.font = line.font;
                // Stroke (halo) first, then fill with the label color
                ctx.strokeStyle = haloColor;
                ctx.strokeText(line.text, pos.x, lineY);
ctx.fillStyle = line.color;
                ctx.fillText(line.text, pos.x, lineY);
                lineY += lineHeight + 2;
            }
        }

        drawCanvasLabel(ctx, label) {
            const pos = this.worldToScreen(label.x, label.y);
            // Keep annotation text readable even when zoomed out.
            // In export mode we use a much larger minimum so the exported image is readable.
            const fontSize = (label.font_size || 12) * Math.max(this.scale, this._exportMode ? 1.0 : 0.5);
            // Theme-aware default color so annotation labels stay visible in dark mode.
            const isDark = (document.documentElement.getAttribute('data-theme') === 'dark');
            const color = label.color || (isDark ? '#CBD5E1' : '#64748B');
            const isDragging = this.draggingLabel && this.draggingLabel.id === label.id;

            // Background highlight for dragging label
            if (isDragging) {
                const textWidth = label.text.length * fontSize * 0.6;
                const textHeight = fontSize * 1.4;
                ctx.fillStyle = 'rgba(59, 130, 246, 0.1)';
                ctx.roundRect(pos.x - textWidth/2 - 8, pos.y - textHeight/2 - 4, textWidth + 16, textHeight + 8, 8);
                ctx.fill();
            }

            ctx.font = `bold ${fontSize}px -apple-system, sans-serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = isDragging ? '#2563EB' : color;
            ctx.fillText(label.text, pos.x, pos.y);

            // Draw underline decoration
            const textWidth = label.text.length * fontSize * 0.6;
            ctx.beginPath();
            ctx.moveTo(pos.x - textWidth/2, pos.y + fontSize/2 + 2);
            ctx.lineTo(pos.x + textWidth/2, pos.y + fontSize/2 + 2);
            ctx.strokeStyle = isDragging ? '#2563EB' : color + '60';
            ctx.lineWidth = 2;
            ctx.stroke();
        }

        drawEdge(ctx, edge) {
            const source = this.nodes.find(n => n.id === edge.source);
            const target = this.nodes.find(n => n.id === edge.target);
            if (!source || !target) return;

            const from = this.worldToScreen(source.x, source.y);
            const to = this.worldToScreen(target.x, target.y);
            const dx = to.x - from.x;
            const dy = to.y - from.y;
            const len = Math.sqrt(dx * dx + dy * dy);
            const nx = len > 0 ? dx / len : 0;
            const ny = len > 0 ? dy / len : 0;
            const sourceRadius = this.getNodeRadius(source);
            const targetRadius = this.getNodeRadius(target);
            const startX = from.x + nx * sourceRadius;
            const startY = from.y + ny * sourceRadius;
            const endX = to.x - nx * targetRadius;
            const endY = to.y - ny * targetRadius;

            const isHovered = this.hoveredEdge && this.hoveredEdge.id === edge.id;

            ctx.beginPath();
            ctx.moveTo(startX, startY);
            ctx.lineTo(endX, endY);
            ctx.strokeStyle = this.edgeColors[edge.type] || '#94A3B8';
            ctx.lineWidth = isHovered ? 4 * this.scale : 2 * this.scale;
            ctx.setLineDash(edge.type === 'wifi' ? [6, 4] : []);
            ctx.stroke();
            ctx.setLineDash([]);

            // Draw midpoint dot
            const midX = (startX + endX) / 2;
            const midY = (startY + endY) / 2;
            ctx.beginPath();
            ctx.arc(midX, midY, isHovered ? 5 * this.scale : 3 * this.scale, 0, Math.PI * 2);
            ctx.fillStyle = isHovered ? '#EF4444' : '#94A3B8';
            ctx.fill();

// Draw edge type label at midpoint
            if (isHovered) {
                ctx.font = `bold ${10 * this.scale}px sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                ctx.fillStyle = '#EF4444';
                ctx.fillText('Click to disconnect', midX, midY - 8 * this.scale);
} else {
                // ─── Straight-line indication ───
                // The badge is only shown for a short time AFTER the two connected
                // nodes become aligned (a fresh transition into alignment), then it
                // auto-hides after ALIGN_INDICATOR_DURATION ms.
                const alignedAt = this.alignedEdgeTimestamps[edge.id];
                if (alignedAt && (Date.now() - alignedAt) <= this.ALIGN_INDICATOR_DURATION) {
                    // Also verify the edge is CURRENTLY straight (the user may have
                    // moved a node out of alignment while the badge was still visible).
                    const tolerance = 1; // world units
                    const sameX = Math.abs(source.x - target.x) <= tolerance;
                    const sameY = Math.abs(source.y - target.y) <= tolerance;
                    if (sameX || sameY) {
                        const isHorizontal = sameY;
                        const isDark = (document.documentElement.getAttribute('data-theme') === 'dark');
                        // Distinct colors: horizontal = blue, vertical = orange
                        const badgeColor = isHorizontal ? '#3B82F6' : '#F59E0B';
                        const badgeLabel = isHorizontal ? 'H' : 'V';
                        const badgeArrow = isHorizontal ? '⬌' : '⬍';
                        const badgeW = 40 * this.scale;
                        const badgeH = 24 * this.scale;

                        // Draw a small rounded badge background so the indicator stays
                        // readable over the line regardless of theme.
                        ctx.save();
                        ctx.beginPath();
                        ctx.roundRect(midX - badgeW / 2, midY - badgeH / 2, badgeW, badgeH, 6 * this.scale);
                        ctx.fillStyle = isDark ? 'rgba(15, 23, 42, 0.85)' : 'rgba(255, 255, 255, 0.9)';
                        ctx.fill();
                        ctx.strokeStyle = badgeColor;
                        ctx.lineWidth = 1.5;
                        ctx.stroke();

                        // Orientation letter (H / V) followed by direction arrows.
                        ctx.font = `bold ${11 * this.scale}px sans-serif`;
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillStyle = badgeColor;
                        ctx.fillText(badgeLabel, midX - 9 * this.scale, midY + 1 * this.scale);
ctx.font = `${11 * this.scale}px sans-serif`;
                        ctx.fillText(badgeArrow, midX + 9 * this.scale, midY + 1 * this.scale);
                        ctx.restore();
                    }
                }
            }
        }

        // Detect edges that have (just) become straight and record the timestamp.
        // Called when node positions change (drag, arrow keys).
        detectAlignment() {
            const now = Date.now();
            let changed = false;
            const tolerance = 1; // world units

            this.edges.forEach(edge => {
                const source = this.nodes.find(n => n.id === edge.source);
                const target = this.nodes.find(n => n.id === edge.target);
                if (!source || !target) {
                    delete this.alignedEdgeTimestamps[edge.id];
                    delete this.lastAlignedState[edge.id];
                    return;
                }
                const isAligned = Math.abs(source.x - target.x) <= tolerance ||
                                  Math.abs(source.y - target.y) <= tolerance;
                const wasAligned = !!this.lastAlignedState[edge.id];
                if (isAligned && !wasAligned) {
                    // Fresh transition into alignment → start the 5s visibility window.
                    this.alignedEdgeTimestamps[edge.id] = now;
                    changed = true;
                } else if (!isAligned) {
                    // No longer aligned → don't show the badge even if the timer is running.
                    delete this.alignedEdgeTimestamps[edge.id];
                }
                this.lastAlignedState[edge.id] = isAligned;
            });

            if (changed) this.scheduleAlignHide();
            return changed;
        }

        // Schedule a re-render (and state cleanup) once the last indicator expires.
        scheduleAlignHide() {
            if (this._alignTimer) {
                clearTimeout(this._alignTimer);
                this._alignTimer = null;
            }
            this._alignTimer = setTimeout(() => {
                this._alignTimer = null;
                const now = Date.now();
                let anyActive = false;
                for (const id in this.alignedEdgeTimestamps) {
                    if ((now - this.alignedEdgeTimestamps[id]) <= this.ALIGN_INDICATOR_DURATION) {
                        anyActive = true;
                    } else {
                        delete this.alignedEdgeTimestamps[id];
                    }
                }
                if (anyActive) {
                    this.scheduleAlignHide();
                }
                this.render();
            }, this.ALIGN_INDICATOR_DURATION);
        }
    }

    // ─── Global Instance ───
    let engine = null;

    // ─── Global Helper Functions ───
    window.filterTopology = function(type) {
        if (!engine) return;
        document.querySelectorAll('.filter-buttons .btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.filter === type);
        });
        engine.cancelModes();
        engine.filterByType(type);
    };

    window.searchTopology = function(query) {
        if (!engine) return;
        engine.search(query);
    };

    window.fitToScreen = function() {
        if (!engine) return;
        engine.cancelModes();
        engine.fitAll();
    };

    window.zoomIn = function() {
        if (!engine) return;
        engine.zoom(0.2);
    };

    window.zoomOut = function() {
        if (!engine) return;
        engine.zoom(-0.2);
    };

    window.savePositions = function() {
        if (!engine) return;
        engine.savePositions();
    };

    window.undoAction = function() {
        if (!engine) return;
        engine.undo();
    };

    window.redoAction = function() {
        if (!engine) return;
        engine.redo();
    };

window.exportDiagram = function() {
        if (!engine) return;
        if (engine.nodes.length === 0) return;

        // Compute the bounding box of the whole topology in world coordinates.
        const LEGEND_HEIGHT = 170; // reserved space at the bottom for the legend
        const BUFFER = 120; // extra padding around the diagram
        const MAX_DIM = 2200; // max export dimension in logical px
        let minX = Infinity, maxX = -Infinity;
        let minY = Infinity, maxY = -Infinity;
        engine.nodes.forEach(n => {
            minX = Math.min(minX, n.x);
            maxX = Math.max(maxX, n.x);
            minY = Math.min(minY, n.y);
            maxY = Math.max(maxY, n.y);
        });
        const worldW = (maxX - minX) || 200;
        const worldH = (maxY - minY) || 200;

        // Choose a scale that fits the ENTIRE topology in the available area
        // (accounting for the legend strip at the bottom).
        // Never force the scale UP beyond what fits — that would clip nodes.
        // Readability is preserved instead by the export-mode font minimums
        // (textScale >= 1.4) and the 3x supersampling of the output image.
        let fitScale = Math.min(MAX_DIM / (worldW + BUFFER), MAX_DIM / (worldH + LEGEND_HEIGHT + BUFFER));
        fitScale = Math.min(fitScale, 2.0);

        const exportScale = 3; // 3x supersampling for a crisp high-res image
        const logicalW = Math.ceil((worldW + BUFFER) * fitScale);
        const logicalH = Math.ceil((worldH + LEGEND_HEIGHT + BUFFER) * fitScale);
        const exportCanvas = document.createElement('canvas');
        exportCanvas.width = logicalW * exportScale;
        exportCanvas.height = logicalH * exportScale;
        const exportCtx = exportCanvas.getContext('2d');

        // Scale the context for high-res output
        exportCtx.scale(exportScale, exportScale);

        // Fill white background
        exportCtx.fillStyle = '#FFFFFF';
        exportCtx.fillRect(0, 0, logicalW, logicalH);

        // ── Save current engine state and switch to export mode ──
        const origCanvas = engine.canvas;
        const origCtx = engine.ctx;
        const origScale = engine.scale;
        const origOffsetX = engine.offset.x;
        const origOffsetY = engine.offset.y;
        const origCenterX = engine.centerX;
        const origCenterY = engine.centerY;
        const origExportMode = engine._exportMode;
        engine._exportMode = true; // Larger text + all attributes shown

        // Temporarily switch the engine to the export canvas and fit the whole
        // topology so the exported image contains every node, edge and label.
        engine.canvas = exportCanvas;
        engine.ctx = exportCtx;
        engine.centerX = logicalW / 2;
        engine.centerY = (logicalH - LEGEND_HEIGHT) / 2;
        engine.scale = fitScale;
        engine.offset.x = -((minX + maxX) / 2) * fitScale;
        engine.offset.y = -((minY + maxY) / 2) * fitScale;

        // Clear and draw the topology
        exportCtx.clearRect(0, 0, logicalW, logicalH);
        exportCtx.fillStyle = '#FFFFFF';
        exportCtx.fillRect(0, 0, logicalW, logicalH);
        engine._doRender();

        // ── Draw the legend strip at the bottom of the exported image ──
        drawExportLegend(exportCtx, logicalW, logicalH, LEGEND_HEIGHT);

        // Restore the original engine state
        engine.canvas = origCanvas;
        engine.ctx = origCtx;
        engine.scale = origScale;
        engine.offset.x = origOffsetX;
        engine.offset.y = origOffsetY;
        engine.centerX = origCenterX;
        engine.centerY = origCenterY;
        engine._exportMode = origExportMode;

        // Download as PNG
        const link = document.createElement('a');
        link.download = 'network-topology.png';
        link.href = exportCanvas.toDataURL('image/png');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        engine.showToast('Diagram exported as PNG (full topology with legends & labels)', 'success');
    };

    // Draws a clean, compact legend panel at the bottom of the exported image.
    // Large fonts/icons in a tight boxed grid so it stays readable.
    function drawExportLegend(ctx, width, height, legendHeight) {
        const colors = {
            pc: '#3B82F6',
            laptop: '#8B5CF6',
            switch: '#10B981',
            server: '#F59E0B',
            printer: '#EC4899',
            nas: '#14B8A6',
            nvr: '#b97b49',
            vm: '#93C5FD'
        };
        const deviceTypes = ['pc', 'laptop', 'switch', 'server', 'printer', 'nas', 'nvr', 'vm'];
        const labels = { pc: 'PC', laptop: 'Laptop', switch: 'Switch', server: 'Server', printer: 'Printer', nas: 'NAS', nvr: 'NVR', vm: 'VM' };
        const emojis = { pc: '🖥', laptop: '💻', switch: '🔀', server: '🖧', printer: '🖨', nas: '🗄', nvr: '📹', vm: '☁' };

        const pad = 24;
        const panelTop = height - legendHeight + 10;

        // Panel background
        ctx.save();
        ctx.beginPath();
        ctx.roundRect(pad, panelTop, width - pad * 2, legendHeight - 20, 12);
        ctx.fillStyle = '#F8FAFC';
        ctx.fill();
        ctx.strokeStyle = '#CBD5E1';
        ctx.lineWidth = 2;
        ctx.stroke();

        // Title
        ctx.font = 'bold 16px -apple-system, sans-serif';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = '#1E293B';
        ctx.fillText('Legend', pad + 18, panelTop + 18);

        // Divider under title
        ctx.beginPath();
        ctx.moveTo(pad + 18, panelTop + 32);
        ctx.lineTo(width - pad - 18, panelTop + 32);
        ctx.strokeStyle = '#E2E8F0';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        // ── Device grid: 4 columns x 2 rows, evenly spaced inside the panel ──
        const gridLeft = pad + 28;
        const gridRight = width - pad - 28;
        const gridTop = panelTop + 42;
        const rowH = 40;
        const cols = 4;
        const cellW = (gridRight - gridLeft) / cols;

        deviceTypes.forEach((type, i) => {
            const col = i % cols;
            const row = Math.floor(i / cols);
            const cx = gridLeft + col * cellW;      // cell left edge
            const cy = gridTop + row * rowH + rowH / 2;

            const iconR = 14;
            const img = engine.iconImages[type] || engine.iconImages.default;

            // Colored circle behind icon
            ctx.beginPath();
            ctx.arc(cx + iconR, cy, iconR + 2.5, 0, Math.PI * 2);
            ctx.fillStyle = (colors[type] || '#6B7280') + '25';
            ctx.fill();
            ctx.strokeStyle = colors[type] || '#6B7280';
            ctx.lineWidth = 2;
            ctx.stroke();

            if (img) {
                ctx.drawImage(img, cx + 2, cy - iconR, iconR * 2, iconR * 2);
            } else {
                ctx.font = '15px sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(emojis[type] || '🔌', cx + iconR, cy);
            }

            // Label next to icon — proportional to diagram text
            ctx.font = 'bold 14px -apple-system, sans-serif';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#334155';
            ctx.fillText(labels[type] || type, cx + iconR * 2 + 9, cy);
        });

        // ── Bottom row: Connection | Junction | H | V — evenly spread ──
        const bottomY = gridTop + rowH * 2 + 4;
        const segW = (gridRight - gridLeft) / 4;

        // Connection line sample
        let sx = gridLeft + 8;
        ctx.beginPath();
        ctx.moveTo(sx, bottomY);
        ctx.lineTo(sx + 46, bottomY);
        ctx.strokeStyle = '#10B981';
        ctx.lineWidth = 3.5;
        ctx.stroke();
        ctx.beginPath();
        ctx.arc(sx, bottomY, 4, 0, Math.PI * 2);
        ctx.arc(sx + 46, bottomY, 4, 0, Math.PI * 2);
        ctx.fillStyle = '#10B981';
        ctx.fill();
        ctx.font = 'bold 14px -apple-system, sans-serif';
        ctx.textAlign = 'left';
        ctx.fillStyle = '#334155';
        ctx.fillText('Connection', sx + 56, bottomY);

        // Junction dot sample
        let jx = gridLeft + segW + 8;
        ctx.beginPath();
        ctx.arc(jx + 7, bottomY, 7, 0, Math.PI * 2);
        ctx.fillStyle = '#64748B';
        ctx.fill();
        ctx.strokeStyle = '#FFFFFF';
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.font = 'bold 14px -apple-system, sans-serif';
        ctx.fillStyle = '#334155';
        ctx.fillText('Junction / Tap', jx + 21, bottomY);

        // H badge sample
        let hx = gridLeft + segW * 2 + 8;
        ctx.beginPath();
        ctx.roundRect(hx, bottomY - 11, 22, 22, 5);
        ctx.fillStyle = '#3B82F6';
        ctx.fill();
        ctx.font = 'bold 13px -apple-system, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillStyle = '#FFFFFF';
        ctx.fillText('H', hx + 11, bottomY + 1);
        ctx.font = 'bold 14px -apple-system, sans-serif';
        ctx.textAlign = 'left';
        ctx.fillStyle = '#334155';
        ctx.fillText('Horizontal', hx + 30, bottomY);

        // V badge sample
        let vx = gridLeft + segW * 3 + 8;
        ctx.beginPath();
        ctx.roundRect(vx, bottomY - 11, 22, 22, 5);
        ctx.fillStyle = '#F59E0B';
        ctx.fill();
        ctx.font = 'bold 13px -apple-system, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillStyle = '#FFFFFF';
        ctx.fillText('V', vx + 11, bottomY + 1);
        ctx.font = 'bold 14px -apple-system, sans-serif';
        ctx.textAlign = 'left';
        ctx.fillStyle = '#334155';
        ctx.fillText('Vertical', vx + 30, bottomY);

        ctx.restore();
    }

    window.closePopup = function() {
        if (!engine) return;
        engine.hidePopup();
    };

    window.enableConnectMode = function(linkType) {
        if (!engine) return;
        // Toggle: if already in connect mode, cancel it
        if (engine.connectMode) {
            engine.cancelModes();
            const btn = document.getElementById('connectBtn');
            if (btn) btn.classList.remove('active', 'btn-primary');
            return;
        }
        engine.cancelModes();
        engine.setConnectMode(true, linkType || 'ethernet');
        // Update button states
        document.querySelectorAll('.topology-actions .btn').forEach(btn => {
            btn.classList.remove('active', 'btn-primary');
        });
        const btn = document.getElementById('connectBtn');
        if (btn) btn.classList.add('active', 'btn-primary');
        const cancelBtn = document.getElementById('cancelBtn');
        if (cancelBtn) cancelBtn.style.display = 'inline-flex';
    };

    window.enableDisconnectMode = function() {
        if (!engine) return;
        // Toggle: if already in disconnect mode, cancel it
        if (engine.disconnectMode) {
            engine.cancelModes();
            const btn = document.getElementById('disconnectBtn');
            if (btn) btn.classList.remove('active', 'btn-primary');
            return;
        }
        engine.cancelModes();
        engine.setDisconnectMode(true);
        // Update button states
        document.querySelectorAll('.topology-actions .btn').forEach(btn => {
            btn.classList.remove('active', 'btn-primary');
        });
        const btn = document.getElementById('disconnectBtn');
        if (btn) btn.classList.add('active', 'btn-primary');
        const cancelBtn = document.getElementById('cancelBtn');
        if (cancelBtn) cancelBtn.style.display = 'inline-flex';
    };

    window.enableLabelMode = function() {
        if (!engine) return;
        // Toggle: if already in label mode, cancel it
        if (engine.labelMode) {
            engine.cancelModes();
            const btn = document.getElementById('labelBtn');
            if (btn) btn.classList.remove('active', 'btn-primary');
            return;
        }
        engine.cancelModes();
        engine.setLabelMode(true);
        // Update button states
        document.querySelectorAll('.topology-actions .btn').forEach(btn => {
            btn.classList.remove('active', 'btn-primary');
        });
        const btn = document.getElementById('labelBtn');
        if (btn) btn.classList.add('active', 'btn-primary');
        const cancelBtn = document.getElementById('cancelBtn');
        if (cancelBtn) cancelBtn.style.display = 'inline-flex';
    };

window.cancelModes = function() {
        if (!engine) return;
        engine.cancelModes();
    };

    window.toggleBoxSelect = function() {
        if (!engine) return;
        // Toggle: if already in box select mode, cancel it
        if (engine.boxSelectMode) {
            engine.cancelModes();
            const btn = document.getElementById('boxSelectBtn');
            if (btn) {
                btn.classList.remove('active', 'btn-primary');
            }
            return;
        }
        engine.cancelModes();
        engine.toggleBoxSelect();
        const cancelBtn = document.getElementById('cancelBtn');
        if (cancelBtn) cancelBtn.style.display = 'inline-flex';
    };

    // ─── Initialize on DOM Ready ───
    document.addEventListener('DOMContentLoaded', function() {
        engine = new TopologyEngine('networkCanvas');
        window.topologyEngine = engine;
    });

})();

