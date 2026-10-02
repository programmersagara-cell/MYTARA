<?php
/**
 * One-time Tag Year Shortener (CLI)
 *
 * Rewrites existing org-standard tags that carry a 4-digit acquisition year
 * (e.g. O-2026-IT-4821) to the current 2-digit-year format (O-26-IT-4821).
 * Tags already in the 2-digit form, legacy sticker tags, placeholders, and
 * free-text tags are NEVER touched.
 *
 * Each retag writes an `asset_history` row and a unified `audit_log` entry
 * (action = 'tag_year_shortened', old -> new tag) so the change is audited.
 * If the shortened tag would collide with an existing asset_tag the row is
 * SKIPPED and reported instead of failing on the UNIQUE constraint.
 *
 * Usage:
 *   php scripts/cli/shorten_tag_year.php --dry-run   # preview only
 *   php scripts/cli/shorten_tag_year.php             # apply
 */

// CLI-only worker. Must never be reachable over the web.
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

$db = Database::getInstance();
$audit = new AuditService(); // built before output: Session singleton needs clean headers

$org = strtoupper(trim(Setting::get('asset.tag.org', 'O') ?? 'O'));
if ($org === '') {
    $org = 'O';
}

// Strict 4-digit-year standard tags only: ORG-YYYY-DEPT-NNNN.
$re = '/^' . preg_quote($org, '/') . '-((19|20)\d{2})-([A-Z]{2,4})-(\d{4})$/';

$rows = $db->fetchAll(
    "SELECT id, asset_tag FROM assets WHERE asset_tag REGEXP '^" . preg_quote($org, '/') . "-[0-9]{4}-[A-Z]{2,4}-[0-9]{4}$' ORDER BY id ASC"
);

$mode = $dryRun ? 'DRY RUN (no changes will be written)' : 'APPLYING CHANGES';
echo "== Asset tag year shortener — {$mode} ==\n";
echo 'Scanned: ' . count($rows) . " tags in 4-digit-year format\n\n";

$applied = 0;
$skipped = 0;
$planned = [];

foreach ($rows as $row) {
    if (!preg_match($re, $row['asset_tag'], $m)) {
        continue; // REGEXP/PCRE code-set mismatch guard — skip anything not a strict match.
    }
    $newTag = $org . '-' . substr($m[1], -2) . '-' . $m[3] . '-' . $m[4];

    if (in_array($newTag, $planned, true)) {
        echo "  SKIP #{$row['id']}: {$row['asset_tag']} -> $newTag (planned collision in this batch)\n";
        $skipped++;
        continue;
    }
    $taken = $db->fetch("SELECT id FROM assets WHERE asset_tag = ? AND id <> ?", [$newTag, $row['id']]);
    if ($taken) {
        echo "  SKIP #{$row['id']}: {$row['asset_tag']} -> $newTag (already used by asset #{$taken['id']})\n";
        $skipped++;
        continue;
    }

    printf("#%-5s %-20s -> %s\n", $row['id'], $row['asset_tag'], $newTag);
    $planned[] = $newTag;

    if ($dryRun) {
        $applied++;
        continue;
    }

    try {
        $oldTag = $row['asset_tag'];
        Asset::update((int) $row['id'], ['asset_tag' => $newTag]);
        AssetHistory::log((int) $row['id'], null, 'tag_year_shortened', 'asset_tag', $oldTag, $newTag);
        $audit->log(
            'tag_year_shortened',
            'asset',
            (int) $row['id'],
            $newTag,
            'asset_tag',
            $oldTag,
            $newTag,
            'Acquisition year shortened to 2 digits (' . $org . '-YY-DEPT-NNNN)'
        );
        $applied++;
    } catch (\Throwable $e) {
        $skipped++;
        echo '  !! FAILED for asset #' . $row['id'] . ': ' . $e->getMessage() . "\n";
    }
}

echo "\n";
if ($dryRun) {
    echo 'Dry run only: ' . $applied . ' tags would be shortened, ' . $skipped . " skipped. Re-run without --dry-run to apply.\n";
} else {
    echo 'Done. Shortened: ' . $applied . ' | skipped: ' . $skipped . "\n";
}
