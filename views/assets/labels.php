<?php
/**
 * Printable asset label sheet: QR code + org-standard tag per asset.
 * Uses the minimal layouts/print layout (no sidebar/navbar).
 * Supports ?ids=1,2,3 (single asset print), department & status filters.
 *
 * @var array  $assets        Assets with department_name/department_code
 * @var array  $departments   Options for the department filter
 * @var int    $deptFilter    Active department filter (0 = all)
 * @var string $statusFilter  Active status filter ('' = all)
 *
 * The sticker shows the asset tag, model, hostname and the IP address. The IP
 * row keeps only the last octet ("10.97.2.192" -> "192") so the line stays
 * short and readable at sticker size.
 *
 * The QR encodes the asset details themselves as plain text lines — the tag,
 * model, hostname and full IP address — so any scanner or inventory app reads
 * the asset data without needing the web app.
 */
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
/**
 * Last octet of an IPv4 address: "10.97.2.192" -> "192".
 * A CIDR suffix and any extra addresses are dropped, and a value without
 * octets (IPv6, hostname-style entry) is returned unchanged.
 */
$lastOctet = static function ($ip): string {
    $ip = trim((string) $ip);
    if ($ip === '') {
        return '';
    }
    $ip = trim((string) (preg_split('/[\s,\/]+/', $ip)[0] ?? ''));
    $parts = explode('.', $ip);
    $last = trim((string) end($parts));
    return ($last !== '' && $last !== $ip) ? $last : $ip;
};
$allStatuses = ['active', 'inactive', 'maintenance', 'retired', 'lost', 'reserved'];
?>
<script>
    // Follow the theme picked in the main app (theme.js stores it under the same key).
    try { document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light'); } catch (e) {}
</script>
<style>
    /* ── Design tokens (mirror app.css - layouts/print loads no stylesheets) ── */
    :root,
    [data-theme="light"] {
        --primary: #2563EB;
        --primary-hover: #1D4ED8;
        --primary-soft: rgba(37, 99, 235, 0.1);
        --success: #10B981;
        --success-hover: #059669;
        --bg-body: #F1F5F9;
        --bg-card: #FFFFFF;
        --bg-input: #FFFFFF;
        --bg-hover: #F1F5F9;
        --text-primary: #0F172A;
        --text-secondary: #475569;
        --text-muted: #94A3B8;
        --border-color: #E2E8F0;
        --border-strong: #CBD5E1;
        --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.05);
        --shadow: 0 1px 3px rgba(15, 23, 42, 0.1), 0 1px 2px rgba(15, 23, 42, 0.06);
        --shadow-md: 0 4px 6px -1px rgba(15, 23, 42, 0.1), 0 2px 4px -1px rgba(15, 23, 42, 0.06);
        --radius-sm: 6px;
        --radius: 8px;
        --radius-lg: 12px;
        color-scheme: light;
    }
    [data-theme="dark"] {
        --primary: #3B82F6;
        --primary-hover: #60A5FA;
        --primary-soft: rgba(59, 130, 246, 0.15);
        --success: #10B981;
        --success-hover: #34D399;
        --bg-body: #0F172A;
        --bg-card: #1E293B;
        --bg-input: #334155;
        --bg-hover: #334155;
        --text-primary: #F1F5F9;
        --text-secondary: #CBD5E1;
        --text-muted: #94A3B8;
        --border-color: #334155;
        --border-strong: #475569;
        --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.2);
        --shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        color-scheme: dark;
    }

    body {
        font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Arial, sans-serif;
        background-color: var(--bg-body);
        /* Faint dot grid so the sticker sheet reads like a cutting mat. */
        background-image: radial-gradient(rgba(15, 23, 42, 0.07) 1px, transparent 1px);
        background-size: 22px 22px;
        color: var(--text-primary);
        margin: 16px;
    }
    [data-theme="dark"] body {
        background-image: radial-gradient(rgba(148, 163, 184, 0.10) 1px, transparent 1px);
    }

    /* ── Page shell ── */
    .lp { max-width: 1440px; margin: 0 auto; }

    .lp-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px 16px;
        margin-bottom: 16px;
    }
    .lp-brand { display: flex; align-items: center; gap: 14px; min-width: 0; }
    .lp-titles { min-width: 0; }
    .lp-icon {
        width: 46px;
        height: 46px;
        border-radius: 13px;
        display: grid;
        place-items: center;
        font-size: 1.2rem;
        color: #fff;
        background: linear-gradient(135deg, var(--primary), #4F46E5);
        box-shadow: 0 8px 18px -8px rgba(37, 99, 235, 0.65);
        flex: 0 0 auto;
    }
    [data-theme="dark"] .lp-icon { background: linear-gradient(135deg, #3B82F6, #6366F1); }
    .lp-head h1 {
        font-size: 1.3rem;
        font-weight: 700;
        letter-spacing: -0.01em;
        line-height: 1.25;
        color: var(--text-primary);
    }
    .lp-sub { font-size: 0.83rem; color: var(--text-muted); margin-top: 2px; }
    .lp-head-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }

    .lp-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 999px;
        padding: 6px 13px;
        font-size: 0.78rem;
        color: var(--text-secondary);
        box-shadow: var(--shadow-sm);
        white-space: nowrap;
    }
    .lp-chip i { color: var(--primary); font-size: 0.72rem; }
    .lp-chip b { font-weight: 700; color: var(--text-primary); font-variant-numeric: tabular-nums; }
    /* ── Buttons (same vocabulary as app.css) ── */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 15px;
        font-size: 0.85rem;
        font-weight: 600;
        font-family: inherit;
        line-height: 1.4;
        border: 1px solid transparent;
        border-radius: var(--radius-sm);
        cursor: pointer;
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.18s ease;
    }
    .btn:active { transform: translateY(0); }
    .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 2px 8px -3px rgba(37, 99, 235, 0.55); }
    .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
    .btn-success { background: var(--success); color: #fff; box-shadow: 0 2px 8px -3px rgba(16, 185, 129, 0.55); }
    .btn-success:hover { background: var(--success-hover); transform: translateY(-1px); }
    .btn-secondary { background: var(--bg-card); color: var(--text-secondary); border-color: var(--border-color); box-shadow: var(--shadow-sm); }
    .btn-secondary:hover { background: var(--bg-hover); color: var(--text-primary); border-color: var(--border-strong); }
    .btn-ghost { background: transparent; color: var(--text-secondary); }
    .btn-ghost:hover { background: var(--bg-hover); color: var(--text-primary); }
    .btn .count-pill {
        background: rgba(255, 255, 255, 0.22);
        padding: 1px 8px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-variant-numeric: tabular-nums;
    }

    /* ── Control panel ── */
    .lp-panel {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        align-items: start;
        gap: 16px 26px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow);
        padding: 16px 20px 18px;
        margin-bottom: 14px;
    }
    .lp-group { display: flex; flex-direction: column; gap: 10px; min-width: 0; }
    .lp-group-title {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        color: var(--text-muted);
    }
    .lp-group-title i { color: var(--primary); font-size: 0.75rem; }
    @media (min-width: 1100px) {
        .lp-group + .lp-group { border-left: 1px dashed var(--border-color); padding-left: 26px; }
    }
    .lp-row { display: flex; flex-wrap: wrap; gap: 10px 12px; align-items: flex-end; }
    .lp-field { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
    .lp-field.grow { flex: 1 1 100%; }
    .lp-field > label,
    .lp-group form > label {
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-secondary);
        min-width: 0;
    }
    .lp-group form { display: flex; flex-direction: column; gap: 4px; min-width: 0; }

    .lp-field select,
    .lp-field input[type="number"],
    .lp-group form select {
        padding: 7px 10px;
        font-size: 0.84rem;
        font-family: inherit;
        color: var(--text-primary);
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        outline: none;
        transition: border-color 0.18s, box-shadow 0.18s;
        line-height: 1.4;
        max-width: 100%;
    }
    .lp-field select,
    .lp-group form select { cursor: pointer; }
    .lp-field input[type="number"] { width: 88px; font-variant-numeric: tabular-nums; }
    .lp-field select:focus,
    .lp-field input[type="number"]:focus,
    .lp-group form select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-soft);
    }
    .lp-field select:disabled,
    .lp-field input:disabled {
        background: var(--bg-hover);
        color: var(--text-muted);
        cursor: not-allowed;
    }
    .lp-check {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 0.8rem;
        color: var(--text-secondary);
        cursor: pointer;
        user-select: none;
    }
    .lp-check input[type="checkbox"] {
        width: 15px;
        height: 15px;
        margin: 0;
        accent-color: var(--primary);
        cursor: pointer;
    }

    .lp-actions { display: flex; flex-wrap: wrap; gap: 9px; }
    .lp-hint { font-size: 0.72rem; color: var(--text-muted); line-height: 1.6; }
    .lp-hint b { color: var(--text-secondary); }

    /* ── Help / print instructions ── */
    .lp-help {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-left: 3px solid var(--primary);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        margin-bottom: 18px;
    }
    .lp-help > summary {
        list-style: none;
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 11px 16px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-secondary);
        cursor: pointer;
        user-select: none;
    }
    .lp-help > summary::-webkit-details-marker { display: none; }
    .lp-help > summary:hover { color: var(--text-primary); }
    .lp-help > summary .fa-circle-info { color: var(--primary); }
    .lp-help > summary .fa-chevron-down {
        margin-left: auto;
        color: var(--text-muted);
        font-size: 0.72rem;
        transition: transform 0.2s ease;
    }
    .lp-help[open] > summary { border-bottom: 1px solid var(--border-color); }
    .lp-help[open] > summary .fa-chevron-down { transform: rotate(180deg); }
    .lp-help-body {
        padding: 13px 18px 15px;
        font-size: 0.82rem;
        line-height: 1.75;
        color: var(--text-secondary);
    }
    .lp-help-body p + p { margin-top: 9px; }
    .lp-help-body b { color: var(--text-primary); }
    .lp-help-body code {
        font-family: 'Consolas', 'SF Mono', monospace;
        font-size: 0.94em;
        background: var(--bg-hover);
        border: 1px solid var(--border-color);
        border-radius: 4px;
        padding: 1px 5px;
        color: var(--text-primary);
    }

    @media (max-width: 760px) {
        body { margin: 10px; }
        .lp-head { flex-direction: column; align-items: stretch; }
        .lp-panel { padding: 14px; }
        .lp-actions { width: 100%; }
        .lp-actions .btn { flex: 1 1 auto; }
    }

    /* Label geometry comes from CSS vars so the size can change live.
       Defaults match the standard 101.6 x 21.4 mm (4" x 0.84") asset sticker. */
    .label-sheet {
        --lw: 101.6mm;
        --lh: 21.4mm;
        --qr: 19.4mm;
        --pad: 1mm;
        --gap: 1.5mm;
        --tagfs: 14pt;
        --linefs: 8pt;
        display: grid;
        grid-template-columns: repeat(auto-fill, var(--lw));
        gap: 4mm;
        justify-content: start;
        /* Screen: line the sheet up with the header/panel above it. The print
           block below resets this so output stays flush to the page corner. */
        max-width: 1440px;
        margin: 0 auto 26px;
    }
    .asset-label {
        position: relative;
        box-sizing: border-box;
        width: var(--lw);
        height: var(--lh);
        border: 1px dashed #A9B1BD;
        border-radius: 1mm;
        /* Screen-only "sticker resting on the sheet" elevation - the print
           block below strips it back to paper-flat output. */
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.07), 0 10px 22px -16px rgba(15, 23, 42, 0.45);
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
        padding: var(--pad);
        background: #fff;
        /* The sticker is always white paper: pin the text colour so dark mode
           (and printing from a dark session) can't turn it light-on-light. */
        color: #0F172A;
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        /* QR always hugs one short edge of the sticker — never centred */
        justify-content: flex-start;
        align-items: center;
        gap: var(--gap);
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .label-sheet.qr-right .asset-label { flex-direction: row-reverse; }
    .label-sheet.qr-right .info { text-align: right; }
    .label-sheet.qr-right .asset-label .pick { right: auto; left: 1.5mm; }
    .asset-label .pick {
        position: absolute;
        top: 1.5mm;
        right: 1.5mm;
        width: 14px;
        height: 14px;
        cursor: pointer;
        accent-color: var(--primary);
        opacity: 0.55;
        transition: opacity 0.15s ease;
    }
    .asset-label:hover .pick,
    .asset-label .pick:checked { opacity: 1; }
    .asset-label:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.08), 0 16px 30px -18px rgba(15, 23, 42, 0.55);
    }
    .asset-label.selected { outline: 2px solid var(--primary); outline-offset: -1px; }
    .asset-label .qr-box {
        flex: 0 0 var(--qr);
        width: var(--qr);
        height: var(--qr);
        align-self: center;
        order: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .asset-label .qr-box img, .asset-label .qr-box canvas {
        display: block;
        width: var(--qr) !important;
        height: var(--qr) !important;
        image-rendering: pixelated;
    }
    .asset-label .qr-box .qr-failed {
        font-size: 7pt;
        color: #b91c1c;
        text-align: center;
        padding: 0 1mm;
    }
    .asset-label .info {
        order: 1;
        flex: 1 1 auto;
        min-width: 0;
        overflow: hidden;
    }
    .asset-label .org {
        font-size: calc(var(--linefs) - .5pt);
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #555;
        margin-bottom: .4mm;
    }
    .asset-label .tag {
        font-family: 'Consolas', 'Courier New', monospace;
        font-weight: 700;
        font-size: var(--tagfs);
        line-height: 1.15;
        margin-bottom: .9mm;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .asset-label .line {
        font-size: var(--linefs);
        color: #333;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    /* Breathing room between the detail rows - without this they run together
       and read as one block. The sticker still has ~1.9mm of spare height. */
    .asset-label .line + .line { margin-top: .4mm; }
    .asset-label .line b { color: #000; }
    /* Short stickers: drop the org banner + the optional Dept/User rows,
       but ALWAYS keep the key fields: tag, model, hostname and IP. */
    .label-sheet.compact .line-optional,
    .label-sheet.compact .org { display: none; }
    /* Very small thermal sizes (e.g. 25.4×15.2): QR + tag only, and the tag
       wraps instead of being truncated (nothing is lost). */
    .label-sheet.tiny .org, .label-sheet.tiny .line { display: none; }
    .label-sheet.tiny .tag { white-space: normal; word-break: break-all; }
    /* ── Empty state ── */
    .empty-state {
        max-width: 520px;
        margin: 44px auto;
        padding: 46px 24px;
        text-align: center;
        background: var(--bg-card);
        border: 1px dashed var(--border-strong);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        color: var(--text-secondary);
    }
    .empty-state .empty-icon {
        width: 56px;
        height: 56px;
        margin: 0 auto 14px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 1.25rem;
    }
    .empty-state h2 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 6px;
    }
    .empty-state p { font-size: 0.875rem; margin-bottom: 18px; }

    /* ── Print: screen chrome and polish are stripped; labels stay flush ── */
    @media print {
        body { background: #fff; background-image: none; margin: 0; }
        .no-print, .asset-label .pick { display: none !important; }
        .asset-label.print-off { display: none !important; }
        /* One sticker per page-slice, flush to the top-left of the page so the
           printed output matches the physical sticker instead of being centred. */
        .label-sheet { display: block; gap: 0; margin: 0; max-width: none; }
        .asset-label {
            border: none;
            border-radius: 0;
            outline: none !important;
            margin: 0;
            box-shadow: none !important;
            transform: none !important;
            transition: none;
        }
    }
</style>

<style id="pageStyle">
    /* Rewritten by JS with the live sticker size so the print engine stops
       scaling the label down and centring it on an A4 page. */
    @page { size: 101.6mm 21.4mm; margin: 0; }
</style>

<div class="lp no-print">
    <header class="lp-head">
        <div class="lp-brand">
            <div class="lp-icon"><i class="fas fa-tags"></i></div>
            <div class="lp-titles">
                <h1>Asset Labels</h1>
                <p class="lp-sub">Design and print QR stickers for every asset in one sheet</p>
            </div>
        </div>
        <div class="lp-head-actions">
            <span class="lp-chip" title="Labels on this sheet"><i class="fas fa-layer-group"></i> <b><?= count($assets) ?></b> label<?= count($assets) === 1 ? '' : 's' ?></span>
            <span class="lp-chip" title="Current sticker size"><i class="fas fa-crop-simple"></i> <b id="sizeChip">101.6 × 21.4 mm</b></span>
            <a class="btn btn-ghost" href="<?= url('/assets') ?>"><i class="fas fa-arrow-left"></i> Back to Assets</a>
        </div>
    </header>

    <section class="lp-panel">
        <div class="lp-group">
            <div class="lp-group-title"><i class="fas fa-ruler-combined"></i> Sticker</div>
            <div class="lp-row">
                <div class="lp-field grow">
                    <label for="sizeSel">Sticker size</label>
                    <select id="sizeSel">
                        <option value="z101" selected>Standard 101.6×21.4 mm (4″×0.84″)</option>
                        <option value="z25">Zebra 25.4×15.2 mm</option>
                        <option value="z51">Zebra 51×25.4 mm</option>
                        <option value="small">Small 50×29 mm</option>
                        <option value="medium">Medium 62×29 mm</option>
                        <option value="large">Large 88×46 mm</option>
                        <option value="z4x6">Zebra 4×6″ 104×152 mm</option>
                        <option value="custom">Custom…</option>
                    </select>
                </div>
            </div>
            <div class="lp-row">
                <div class="lp-field">
                    <label for="labW">W (mm)</label>
                    <input type="number" id="labW" min="20" max="110" step="0.1" value="101.6">
                </div>
                <div class="lp-field">
                    <label for="labH">H (mm)</label>
                    <input type="number" id="labH" min="10" max="160" step="0.1" value="21.4">
                </div>
                <div class="lp-field">
                    <label for="qrPos">QR position</label>
                    <select id="qrPos">
                        <option value="left" selected>Left side</option>
                        <option value="right">Right side</option>
                    </select>
                </div>
                <div class="lp-field">
                    <label for="detailSel">Details</label>
                    <select id="detailSel">
                        <option value="full" selected>Tag + Model + Host + IP (+Dept/User)</option>
                        <option value="key">Tag + Model + Host + IP only</option>
                    </select>
                </div>
            </div>
            <label class="lp-check"><input type="checkbox" id="pageFit" checked> Match paper size to sticker</label>
        </div>

        <div class="lp-group">
            <div class="lp-group-title"><i class="fas fa-filter"></i> Filter</div>

    <form method="get" action="<?= url('/assets/labels') ?>">
        <input type="hidden" name="ids" value="<?= $e((string)($_GET['ids'] ?? '')) ?>">
        <label>Department
        <select name="department_id" onchange="this.form.submit()">
            <option value="0">All departments</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?= (int) $d['id'] ?>" <?= $deptFilter === (int) $d['id'] ? 'selected' : '' ?>>
                    <?= $e($d['name']) ?><?= !empty($d['code']) ? ' (' . $e($d['code']) . ')' : '' ?>
                </option>
            <?php endforeach; ?>
        </select></label>
        <input type="hidden" name="status" value="<?= $e($statusFilter) ?>">
    </form>
    <form method="get" action="<?= url('/assets/labels') ?>">
        <input type="hidden" name="ids" value="<?= $e((string)($_GET['ids'] ?? '')) ?>">
        <?php if ($deptFilter): ?><input type="hidden" name="department_id" value="<?= $deptFilter ?>"><?php endif; ?>
        <label>Status
        <select name="status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <?php foreach ($allStatuses as $s): ?>
                <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select></label>
    </form>

        </div>

        <div class="lp-group">
            <div class="lp-group-title"><i class="fas fa-print"></i> Output</div>
            <div class="lp-actions">
                <button type="button" class="btn btn-secondary" id="selAll"><i class="fas fa-check-double"></i> Select all</button>
                <button type="button" class="btn btn-primary" id="printBtn">
                    <i class="fas fa-print"></i> Print <span class="count-pill" id="printCount"><?= count($assets) ?></span> label<?= count($assets) === 1 ? '' : 's' ?>
                </button>
                <button type="button" class="btn btn-success" id="zplBtn" title="Download a .zpl file for direct printing to a Zebra thermal printer">
                    <i class="fas fa-download"></i> Zebra ZPL
                </button>
            </div>
            <p class="lp-hint">
                Nothing ticked = print all labels. In the print dialog use margins <b>Default</b>,
                <b>Background graphics</b> on and scale <b>100%</b>.
            </p>
        </div>
    </section>

    <details class="lp-help">
        <summary>
            <i class="fas fa-circle-info"></i> Printing tips &amp; what the QR contains
            <i class="fas fa-chevron-down"></i>
        </summary>
        <div class="lp-help-body">
            <p>Tick the boxes to print only specific labels (nothing ticked = print all).
                <b>Each sticker prints on its own page-slice, QR on the chosen side, showing the tag, model, hostname and IP (last octet only).</b></p>
            <p>The QR carries the asset details as <b>one line of plain text</b>, with every value
                named and separated by <code>|</code> —
                <code>TAG: … | MODEL: … | HOST: … | IP: …</code>
                (e.g. <code>TAG: 0-13-QA-2153 | MODEL: PC | HOST: WSTC02 | IP: 10.97.2.224</code>),
                so any scanner or inventory app reads the data and tells the fields apart without
                the web app. Empty fields are left out.</p>
            <p>With <b>Match paper size to sticker</b> ticked the page becomes exactly the sticker size (101.6×21.4 mm),
                so nothing is scaled down or centred; untick it to print onto a normal sheet.
                In the print dialog set margins to <b>Default</b>, enable <b>Background graphics</b> and scale 100%.</p>
            <p><b>Zebra ZD220:</b> pick the sticker size (or a Zebra preset), tick labels, click <b>Zebra ZPL</b>,
                then send the file raw to the printer: <code>copy /b asset-labels.zpl \\YOUR-PC\Zebra</code>
                (or drag it into the printer queue with "Print as document").</p>
        </div>
    </details>
</div>

<?php if (empty($assets)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-tags"></i></div>
        <h2>No labels to print</h2>
        <p>No assets found for this filter.</p>
        <a class="btn btn-secondary" href="<?= url('/assets') ?>"><i class="fas fa-arrow-left"></i> Back to Assets</a>
    </div>
<?php else: ?>
    <div class="label-sheet" id="labelSheet">
        <?php foreach ($assets as $a): ?>
            <?php
            // QR payload = the asset details on ONE line, each value named and
            // separated by " | ", so a scanner can tell the fields apart:
            //   TAG: O-25-IT-4144 | MODEL: Dell Vostro | HOST: WSIT15 | IP: 10.97.2.192
            // The sticker shows only the last octet of the IP, the QR has it all.
            // Each label is bound to its own value, so a blank field is dropped
            // without shifting the name onto the wrong value; values are trimmed
            // so a stored trailing space cannot double up the separator, and any
            // stray line break inside a value is flattened to a space.
            // NB: the array keys below must match the column names exactly - a
            // stray space inside a key makes the lookup return null and silently
            // drops that value.
            $deptLine = trim(((string) ($a['department_code'] ?? '')) . ' · ' . ((string) ($a['department_name'] ?? '')), " ·");
            // Model line = "vendor model" (whichever parts exist).
            $modelLine = trim(((string) ($a['vendor'] ?? '')) . ' ' . ((string) ($a['model'] ?? '')));
            // Sticker only shows the last octet of the IP ("10.97.2.192" -> "192").
            $ipFull = trim((string) ($a['ip_address'] ?? ''));
            $ipLast = $lastOctet($ipFull);

            $qrTag = (string) $a['asset_tag'];
            $qrValues = [
                'TAG' => trim($qrTag) !== '' ? $qrTag : '#' . (int) $a['id'],
                'MODEL' => $modelLine !== '' ? $modelLine : strtoupper((string) $a['type']),
            ];
            if (!empty($a['hostname'])) {
                $qrValues['HOST'] = (string) $a['hostname'];
            }
            if ($ipFull !== '') {
                $qrValues['IP'] = $ipFull;
            }
            $qrFields = [];
            foreach ($qrValues as $qrName => $qrValue) {
                $qrValue = trim((string) $qrValue);
                if ($qrValue !== '') {
                    $qrFields[] = $qrName . ': ' . $qrValue;
                }
            }
            $qrData = preg_replace('/\s*[\r\n]+\s*/', ' ', implode(' | ', $qrFields));
            $qrAttr = $e($qrData);
            ?>
            <div class="asset-label" data-id="<?= (int) $a['id'] ?>">
                <input type="checkbox" class="pick" title="Include in print">
                <div class="qr-box" data-qr="<?= $qrAttr ?>"></div>
                <div class="info">
                    <div class="org">IT ASSET</div>
                    <div class="tag"><?= $e($a['asset_tag']) ?></div>
                    <div class="line line-model"><b>Model:</b> <?= $e($modelLine !== '' ? $modelLine : strtoupper((string) $a['type'])) ?></div>
                    <?php if (!empty($a['hostname'])): ?>
                        <div class="line line-host"><b>Host:</b> <?= $e($a['hostname']) ?></div>
                    <?php endif; ?>
                    <?php if ($ipLast !== ''): ?>
                        <div class="line line-ip" title="<?= $e($ipFull) ?>"><b>IP:</b> <?= $e($ipLast) ?></div>
                    <?php endif; ?>
                    <div class="line line-optional"><b>Dept:</b> <?= $e($deptLine !== '' ? $deptLine : (string) $a['status']) ?></div>
                    <?php if (!empty($a['assigned_user_name'])): ?>
                        <div class="line line-optional"><b>User:</b> <?= $e($a['assigned_user_name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script src="<?= JS_URL ?>/vendor/qrcode.min.js"></script>

    <script>
    (function () {
        const sheet = document.getElementById('labelSheet');
        const sizeSel = document.getElementById('sizeSel');
        const labW = document.getElementById('labW');
        const labH = document.getElementById('labH');
        const labels = Array.from(document.querySelectorAll('.asset-label'));
        const picks = labels.map(l => l.querySelector('.pick'));
        const printBtn = document.getElementById('printBtn');
        const printCount = document.getElementById('printCount');
        const total = labels.length;

        const pageStyle = document.getElementById('pageStyle');
        const qrPos = document.getElementById('qrPos');
        const detailSel = document.getElementById('detailSel');
        const pageFit = document.getElementById('pageFit');
        // New key: an old 88x46 save must not override the 101.6x21.4 default.
        const SIZE_KEY = 'itaraLabelV2';
        const MMPT = 2.8346;                                 // 1 mm in pt
        const clamp = (v, lo, hi) => Math.min(hi, Math.max(lo, v));

        // Current geometry, also consumed by the ZPL exporter.
        const cur = { w: 101.6, h: 21.4, qr: 19.4, tagfs: 14, linefs: 8, tiny: false };

        /**
         * Geometry for a w x h mm sticker:
         *  - the QR is a square pinned to a short edge (never centred),
         *  - the text block gets every remaining mm and is sized so the tag,
         *    the model, the hostname and the IP all stay readable on short
         *    stickers.
         */
        function applySize(w, h) {
            const tiny = h < 20;                                  // QR + tag only
            const keyOnly = detailSel.value === 'key';            // hide Dept/User rows
            const compact = h < 36 || keyOnly;                    // tag + model + host + IP only
            const pad = h < 28 ? 1 : 2;                           // mm inner padding
            const gap = h < 28 ? 1.5 : 2.5;                       // mm QR -> text gap
            const textH = Math.max(4, h - pad * 2);               // mm left for text
            const qr = clamp(Math.min(textH, w * 0.36), 7, 60);   // mm (kept square)
            const textW = Math.max(8, w - pad * 2 - qr - gap);    // mm left for text

            // Rows rendered at this height: tag + N info rows (+ org banner).
            // Info rows = model, host, IP (always) + Dept/User in full mode.
            const infoRows = tiny ? 0 : (compact ? 3 : 5);
            const orgRows = (compact || tiny) ? 0 : 1;
            // Height budget in pt (tag 1.25x, info rows 1.35x, org 1.25x linefs).
            const need = 2.15 + 1.35 * infoRows + 1.25 * orgRows;
            const budget = Math.max(12, textH * MMPT - 0.4 * (infoRows + orgRows + 1) * MMPT);
            // Also cap so a ~16 char info row still fits horizontally (0.5 em/char).
            const linefs = clamp(Math.min(budget / need, textW * MMPT / 16), 3.5, 8);
            // The tag is monospace (~0.62 em/char) and must fit a 14 char tag.
            const tagfs = clamp(Math.min(linefs * 1.75, textW * MMPT / (0.62 * 14)), 5, 15);

            sheet.style.setProperty('--lw', w + 'mm');
            sheet.style.setProperty('--lh', h + 'mm');
            sheet.style.setProperty('--qr', qr.toFixed(1) + 'mm');
            sheet.style.setProperty('--pad', pad.toFixed(1) + 'mm');
            sheet.style.setProperty('--gap', gap.toFixed(1) + 'mm');
            sheet.style.setProperty('--tagfs', tagfs.toFixed(2) + 'pt');
            sheet.style.setProperty('--linefs', linefs.toFixed(2) + 'pt');
            sheet.classList.toggle('compact', compact);
            sheet.classList.toggle('tiny', tiny);
            sheet.classList.toggle('qr-right', qrPos.value === 'right');
            Object.assign(cur, {
                w: w, h: h, qr: qr, tagfs: tagfs, linefs: linefs, tiny: tiny,
                pad: pad, gap: gap, qrSide: qrPos.value
            });
            // Live size chip in the header (screen-only).
            const chip = document.getElementById('sizeChip');
            if (chip) chip.textContent = w + ' × ' + h + ' mm';
            updatePageRule();
            try {
                localStorage.setItem(SIZE_KEY, JSON.stringify({
                    w: w, h: h, qr: qrPos.value, detail: detailSel.value
                }));
            } catch (e) {}
            requestAnimationFrame(fitText);
        }

        /** Rewrite @page so the print engine uses the sticker box as-is. */
        function updatePageRule() {
            if (!pageStyle) return;
            pageStyle.textContent = (pageFit && pageFit.checked)
                ? '@page { size: ' + cur.w + 'mm ' + cur.h + 'mm; margin: 0; }'
                : '@page { margin: 8mm; }';
        }

        function tagClipped() {
            return labels.some(l => {
                const tag = l.querySelector('.tag');
                return !!tag && tag.scrollWidth - tag.clientWidth > 1;
            });
        }

        function infoOverflow() {
            return labels.some(l => {
                const info = l.querySelector('.info');
                return !!info && info.scrollHeight - info.clientHeight > 1;
            });
        }

        /**
         * Safety net so nothing is ever cut off:
         *  1. a long (legacy) tag first shrinks on its own, down to ~1.2x the
         *     line size, so the model/host rows stay readable;
         *  2. only if the text block still does not fit is everything shrunk.
         */
        function fitText() {
            let tagfs = cur.tagfs, linefs = cur.linefs, guard = 0;
            const tagFloor = Math.max(4.5, linefs * 1.2);
            while (guard++ < 14 && tagfs > tagFloor && tagClipped()) {
                tagfs *= 0.94;
                sheet.style.setProperty('--tagfs', tagfs.toFixed(2) + 'pt');
            }
            guard = 0;
            while (guard++ < 10 && linefs > 3.4 && infoOverflow()) {
                linefs *= 0.94;
                tagfs = Math.max(4.5, tagfs * 0.94);
                sheet.style.setProperty('--linefs', linefs.toFixed(2) + 'pt');
                sheet.style.setProperty('--tagfs', tagfs.toFixed(2) + 'pt');
            }
            cur.linefs = linefs;
            cur.tagfs = tagfs;
        }

        function fromPreset(name) {
            const p = {
                z101: [101.6, 21.4], z25: [25.4, 15.2], z51: [51, 25.4],
                small: [50, 29], medium: [62, 29], large: [88, 46], z4x6: [104, 152]
            }[name];
            if (p) { labW.value = p[0]; labH.value = p[1]; applySize(p[0], p[1]); }
        }
        sizeSel.addEventListener('change', () => {
            if (sizeSel.value !== 'custom') fromPreset(sizeSel.value);
            labW.disabled = labH.disabled = sizeSel.value !== 'custom';
        });
        [labW, labH].forEach(inp => inp.addEventListener('change', () => {
            sizeSel.value = 'custom';
            labW.disabled = labH.disabled = false;
            const w = Math.min(110, Math.max(20, +labW.value || 101.6));
            const h = Math.min(160, Math.max(10, +labH.value || 21.4));
            applySize(w, h);
        }));
        qrPos.addEventListener('change', () => applySize(cur.w, cur.h));
        detailSel.addEventListener('change', () => applySize(cur.w, cur.h));
        if (pageFit) pageFit.addEventListener('change', updatePageRule);
        // Re-fit once the QR canvases/images exist (they change nothing vertically,
        // but their size must be final before we measure overflow).
        window.addEventListener('load', () => requestAnimationFrame(fitText));

        // Restore last used size + options
        try {
            const saved = JSON.parse(localStorage.getItem(SIZE_KEY) || 'null');
            if (saved && saved.w && saved.h) {
                sizeSel.value = 'custom';
                labW.disabled = labH.disabled = false;
                labW.value = saved.w; labH.value = saved.h;
                if (saved.qr) qrPos.value = saved.qr;
                if (saved.detail) detailSel.value = saved.detail;
                applySize(+saved.w, +saved.h);
            } else {
                fromPreset(sizeSel.value);
            }
        } catch (e) {
            fromPreset(sizeSel.value);
        }

        function refreshCount() {
            const checked = picks.filter(c => c.checked).length;
            printCount.textContent = checked > 0 ? checked : total;
            labels.forEach((l, i) => l.classList.toggle('selected', picks[i].checked));
        }
        picks.forEach(c => c.addEventListener('change', refreshCount));
        document.getElementById('selAll').addEventListener('click', function () {
            const allOn = picks.every(c => c.checked);
            picks.forEach(c => c.checked = !allOn);
            this.textContent = allOn ? 'Select all' : 'Clear selection';
            refreshCount();
        });

        printBtn.addEventListener('click', () => {
            const checked = picks.filter(c => c.checked).length;
            if (checked > 0) {
                labels.forEach((l, i) => l.classList.toggle('print-off', !picks[i].checked));
            }
            window.print();
        });
        window.addEventListener('afterprint', () =>
            labels.forEach(l => l.classList.remove('print-off')));

        // --- Zebra ZPL export (native 203 dpi = 8 dots/mm; ZD220) -----------
        const zEsc = s => String(s).replace(/[\\^~]/g, ch => '\\' + ch);

        /**
         * Data for a ^FD QR field. The payload is a single line, so normally only
         * one escape matters: a literal underscore in the data becomes _5F,
         * otherwise the printer would read it as the start of a hex escape.
         * ^ and ~ are the other ZPL control characters and are backslash-escaped.
         * Any stray line break in the data is sent as _0A.
         */
        const zQr = s => zEsc(String(s).replace(/_/g, '_5F').replace(/\r?\n/g, '_0A'));

        function labelDataFrom(labelEl) {
            const visible = el => !!el && el.offsetParent !== null;
            const orgEl = labelEl.querySelector('.org');
            const tagEl = labelEl.querySelector('.tag');
            return {
                org: visible(orgEl) ? orgEl.textContent : '',
                tag: tagEl ? tagEl.textContent.trim() : '',
                // Hidden rows (Dept/User in compact/key mode) are skipped.
                lines: Array.from(labelEl.querySelectorAll('.line'))
                    .filter(el => el.offsetParent !== null)
                    .map(el => el.textContent.trim())
                    .filter(Boolean),
                qr: labelEl.querySelector('.qr-box').getAttribute('data-qr') || ''
            };
        }

        /**
         * Module count of the QR that qrcode.min.js draws for a payload.
         *
         * The library always encodes in 8-bit byte mode at ECC level M and picks
         * the smallest version whose capacity covers its own "effective length".
         * That effective length is encodeURI(text) with every %XX escape
         * collapsed back to a single character, plus 3 - the library compares
         * that number against the text itself, so for any real payload the
         * comparison is always true and the 3 is always added. For our payloads
         * (tag + model + host + IP lines) that is text.length + 3, i.e. 36-87
         * for the 33-84 character sheets, i.e. versions 4-6 = 33-41 modules.
         * Anything past version 11 is clamped to its 61-module grid.
         *
         * Byte-mode capacities for ECC level M, versions 1-11.
         */
        const QR_ECC_M_CAPACITY = [14, 26, 42, 62, 84, 106, 122, 152, 180, 213, 251];

        function qrModuleCount(text) {
            const enc = encodeURI(text || '');
            let n = 0;
            for (let i = 0; i < enc.length; i++) {
                if (enc[i] === '%') i += 2;   // one %XX escape == one encoded byte
                n++;
            }
            n += 3;                           // the library's unconditional +3
            for (let v = 0; v < QR_ECC_M_CAPACITY.length; v++) {
                if (QR_ECC_M_CAPACITY[v] >= n) return 21 + 4 * v;   // v1=21 ... v11=61
            }
            return 61;
        }

        function buildLabelZpl(d) {
            const D = mm => Math.round(mm * 8);                    // mm -> dots @203dpi
            const F = pt => Math.max(12, Math.round(pt * 2.82));   // pt -> dots @203dpi
            const PW = D(cur.w), LL = D(cur.h);
            const padD = D(cur.pad != null ? cur.pad : 1);
            const gapD = D(cur.gap != null ? cur.gap : 1.5);
            const right = cur.qrSide === 'right';
            let z = '^XA^CI28^PW' + PW + '^LL' + LL + '\n';

            // QR on one short edge, vertically centred; magnification fits --qr.
            const qrPx = D(cur.qr);
            const qrMods = qrModuleCount(d.qr);
            // ^BQ prints 'mag' dots per module. The printer picks its own version
            // from the same byte-mode ECC-M table (without the library's +3), so
            // it may choose one version *smaller* than the browser did. Sizing
            // mag from the browser's count - the larger of the two - keeps the
            // printed symbol inside the reserved box either way.
            const mag = Math.max(1, Math.floor(qrPx / qrMods));
            const qx = right ? Math.max(padD, PW - padD - qrPx) : padD;
            const qy = Math.max(padD, Math.round((LL - mag * qrMods) / 2));
            z += '^FO' + qx + ',' + qy + '^BY' + mag + ',3,' + mag + '^BQN,2,' + mag
               + '^FH_^FDLA,' + zQr(d.qr) + '^FS\n';

            // Text block on the other side, vertically centred as a whole.
            const tx = right ? padD : qx + qrPx + gapD;
            const tw = Math.max(D(10), right ? (qx - gapD - padD) : (PW - tx - padD));
            const align = right ? 'R' : 'L';
            // Shrink the tag font if needed so e.g. "O-26-IT-8537" never clips.
            const tagF = Math.max(11, Math.min(
                F(cur.tagfs),
                Math.floor(tw / (0.62 * Math.max(8, d.tag.length)))
            ));
            const rows = [];
            if (!cur.tiny && d.org) rows.push({ t: d.org.trim().toUpperCase(), f: F(Math.max(4.5, cur.linefs - 0.5)) });
            rows.push({ t: d.tag, f: tagF });
            if (!cur.tiny) d.lines.forEach(t => rows.push({ t, f: F(cur.linefs) }));
            // Row pitch: 1.5x the font height, so the printed rows have the same
            // visible gap between them as the on-screen label. Must stay under
            // the label height or the last row would fall off.
            const PITCH = 1.5;
            const blockH = rows.reduce((s, r) => s + Math.round(r.f * PITCH), 0);
            let y = Math.max(padD, Math.round((LL - blockH) / 2));
            rows.forEach(r => {
                z += '^FO' + tx + ',' + y + '^FB' + tw + ',1,,' + align + '^A0N,' + r.f + ',' + r.f + '^FD' + zEsc(r.t) + '^FS\n';
                y += Math.round(r.f * PITCH);
            });
            return z + '^PQ1^XZ\n';
        }

        document.getElementById('zplBtn').addEventListener('click', () => {
            const anyChecked = picks.some(c => c.checked);
            const chosen = labels.filter((l, i) => !anyChecked || picks[i].checked);
            const zpl = chosen.map(labelDataFrom).map(buildLabelZpl).join('');
            const blob = new Blob([zpl], { type: 'application/octet-stream' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'asset-labels.zpl';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(a.href);
        });

        document.querySelectorAll('.qr-box').forEach(box => {
            const qrText = box.getAttribute('data-qr');
            // 8 whole pixels per module: qrcode.min.js would otherwise use a
            // fractional module size, which blurs the grid when printed.
            const qrPx = qrModuleCount(qrText) * 8;
            try {
                new QRCode(box, {
                    text: qrText,
                    width: qrPx,
                    height: qrPx,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            } catch (err) {
                // Only reachable for payloads past version 11 (>251 encoded
                // bytes). Keep the rest of the sheet printable.
                box.innerHTML = '<span class="qr-failed">QR too long</span>';
                box.setAttribute('title', 'QR payload too long: ' + err.message);
                console.warn('QR for payload too long:', qrText, err);
            }
        });

        // Single label (opened from an asset page): open print dialog automatically.
        <?php if (count($assets) === 1): ?>
        setTimeout(() => window.print(), 400);
        <?php endif; ?>
    })();
    </script>
<?php endif; ?>

