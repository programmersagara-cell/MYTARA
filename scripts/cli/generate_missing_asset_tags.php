<?php
/**
 * Legacy Asset Tag Backfill (CLI)
 *
 * Replaces placeholder / stand-in asset tags (empty, `PC-001` style, or
 * hostname-only values like `SPC1`, `QA PC4`, `N/A`) with the org standard
 * format `{ORG}-{YY}-{DEPT}-{NNNN}` produced by Asset::generateAssetTag().
 *
 * Every retag writes BOTH an `asset_history` row AND a unified `audit_log`
 * entry (action = 'tag_generated', old -> new tag) so the change is audited.
 *
 * Rows already matching the standard format (e.g. O-26-IT-4821, or the legacy
 * 4-digit-year form O-2026-IT-4821) are NEVER
 * touched — that includes the legacy sticker tags currently in the ledger
 * (O-23-IT-3032 (1), O-(14)-HRAD-73998-(1), ...): this script only backfills
 * assets that effectively have no tag.
 *
 * Usage:
 *   php scripts/cli/generate_missing_asset_tags.php --dry-run   # preview only
 *   php scripts/cli/generate_missing_asset_tags.php             # apply
 */

// CLI-only worker. Must never be reachable over the web (it would allow
// anonymous visitors to rewrite asset tags).
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require __DIR__ . '/../../config/constants.php';

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../../src/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;
use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\Setting;
use App\Services\AuditService;

$options = getopt('', ['dry-run']);
$dryRun = isset($options['dry-run']);

// --- Tag format rules ---------------------------------------------------

$org = strtoupper(trim(Setting::get('asset.tag.org', 'O') ?? 'O'));
if ($org === '') {
    $org = 'O';
}

// Rows already in the org standard format are untouchable. The year segment
// accepts the current 2-digit form (O-26-IT-4821) as well as the legacy
// 4-digit form (O-2026-IT-4821) produced by older builds.
$standardRe = '/^' . preg_quote($org, '/') . '-\d{2,4}-[A-Z]{2,4}-\d{4}$/i';

// Placeholder pattern from the old generator, e.g. PC-001, NVR-1.
$placeholderRe = '/^[A-Z]{2,3}-\d{1,3}$/i';

// Obvious stand-ins: text a human typed because no tag existed. Kept
// deliberately conservative — sticker-style tags (even messy legacy ones
// like "O-23-IT-3032   (1)" or "0-90-IT-1872") do NOT match these.
$standInRes = [
    '/^n\/?a/i',                      // N/A, N/A_1
    '/no\s*tag|notag/i',              // "No asset tag", "HR-New no tag"
    '/\bnew\b/i',                     // "New PC2"
    '/printer/i',                     // "Printer 1", "PrinterPP1", "ppprinter1"
    '/laptop/i',                      // "QA Laptop", "HRMGR - Laptop"
    '/^[A-Z]{1,3}\d{1,2}$/i',         // SPC1, VM2, TC1
    '/^[A-Z]\d?[A-Z]{1,2}\d{1,2}$/i', // R2VM1, R1VM5, Rvm0
    '/^[A-Z]{2}-\d{1,2}$/i',          // EF-01, ME-01
    '/^[A-Za-z ,.\'-]+\d{0,3}$/i',    // free text: "Service Pc", "Vice Press", "QA PC4", "PC-Guard"
];

/**
 * Does this asset row effectively have no (usable) asset tag?
 */
$isUntagged = function (array $row) use ($standardRe, $placeholderRe, $standInRes): bool {
    $tag = trim((string) ($row['asset_tag'] ?? ''));

    // Never touch rows already in the org standard format.
    if ($tag !== '' && preg_match($standardRe, $tag)) {
        return false;
    }
    if ($tag === '') {
        return true;
    }
    if (preg_match($placeholderRe, $tag)) {
        return true;
    }
    foreach ($standInRes as $re) {
        if (preg_match($re, $tag)) {
            return true;
        }
    }
    // Hostname-only value: tag simply repeats the hostname (e.g. NVR-1).
    $hostname = trim((string) ($row['hostname'] ?? ''));
    if ($hostname !== '' && strcasecmp($tag, $hostname) === 0) {
        return true;
    }

    return false;
};


// --- Collect targets ------------------------------------------------------

$db = Database::getInstance();
$rows = $db->fetchAll(
    "SELECT a.id, a.asset_tag, a.hostname, a.type, a.department_id, a.purchase_date,
            d.code AS department_code
     FROM assets a
     LEFT JOIN departments d ON a.department_id = d.id
     ORDER BY a.id ASC"
);

$targets = array_values(array_filter($rows, $isUntagged));

// Build the audit service BEFORE any CLI output: its Session singleton would
// otherwise hit "headers already sent" warnings when session_start() runs.
$audit = new AuditService();

$mode = $dryRun ? 'DRY RUN (no changes will be written)' : 'APPLYING CHANGES';
echo "== Legacy asset tag backfill — {$mode} ==\n";
echo 'ORG letter: ' . $org . ' | standard format: ' . $org . '-YY-DEPT-NNNN' . "\n";
echo 'Scanned: ' . count($rows) . ' assets | to retag: ' . count($targets) . "\n\n";

$header = sprintf("%-5s  %-22s  %-18s  %-6s  %s\n", 'ID', 'Current tag', 'Hostname', 'Dept', 'Planned tag');
echo $header;
echo str_repeat('-', 88) . "\n";

$applied = 0;
$failed = 0;
$usedTags = [];

foreach ($targets as $row) {
    $deptId = ($row['department_id'] === null || $row['department_id'] === '')
        ? null
        : (int) $row['department_id'];

    // Acquisition year = YEAR(purchase_date); '0000-00-00'/empty fall back to
    // the current year inside generateAssetTag(). $m[0] = full year ($m[1] is
    // only the century group).
    $year = null;
    if (preg_match('/^(19|20)\d{2}/', (string) ($row['purchase_date'] ?? ''), $m)) {
        $year = $m[0];
    }

    // Generate; re-roll if it collides with another row planned in this batch
    // (only possible in --dry-run, where nothing is written to the DB yet).
    $newTag = Asset::generateAssetTag($deptId, $year);
    for ($i = 0; $i < 10 && in_array($newTag, $usedTags, true); $i++) {
        $newTag = Asset::generateAssetTag($deptId, $year);
    }
    $usedTags[] = $newTag;

    $oldTag = trim((string) ($row['asset_tag'] ?? ''));
    $oldLabel = $oldTag === '' ? '(empty)' : $oldTag;
    printf(
        "%-5s  %-22s  %-18s  %-6s  %s\n",
        $row['id'],
        substr($oldLabel, 0, 22),
        substr((string) ($row['hostname'] ?? ''), 0, 18),
        $row['department_code'] ?? 'GEN',
        $newTag
    );

    if ($dryRun) {
        continue;
    }

    // Apply: retag + asset_history row + unified audit_log entry.
    try {
        Asset::update((int) $row['id'], ['asset_tag' => $newTag]);

        // CLI run has no session user -> user_id NULL (allowed by the FK).
        AssetHistory::log((int) $row['id'], null, 'tag_generated', 'asset_tag', $oldTag, $newTag);

        $audit->log(
            'tag_generated',
            'asset',
            (int) $row['id'],
            $newTag,
            'asset_tag',
            $oldTag,
            $newTag,
            'Legacy asset tag backfilled to org standard format ' . $org . '-YY-DEPT-NNNN'
        );

        $applied++;
    } catch (\Throwable $e) {
        $failed++;
        echo '  !! FAILED for asset #' . $row['id'] . ': ' . $e->getMessage() . "\n";
    }
}

echo str_repeat('-', 88) . "\n";
if ($dryRun) {
    echo 'Dry run only: nothing was changed. Re-run without --dry-run to apply.' . "\n";
} else {
    echo 'Done. Retagged: ' . $applied . ' | failed: ' . $failed . "\n";
}
