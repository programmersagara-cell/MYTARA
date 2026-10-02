<?php
/**
 * One-time import of ISMS-E-005 Software License Ledger (2026 sheet) into software_licenses.
 * Usage: php import_ledger.php [--dry]
 */
$dry = in_array('--dry', $argv ?? [], true);

$pdo = new PDO('mysql:host=localhost;dbname=itassets;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Load departments, assets, users for matching
$departments = $pdo->query("SELECT id, name FROM departments")->fetchAll(PDO::FETCH_ASSOC);
$assets = $pdo->query("SELECT id, asset_tag, hostname FROM assets")->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query("SELECT id, full_name, username FROM users")->fetchAll(PDO::FETCH_ASSOC);

function norm(string $s): string {
    return strtolower(preg_replace('/[^a-z0-9]/i', '', $s));
}

function findDepartment(array $departments, string $name): ?int {
    if ($name === '' || strcasecmp($name, 'n/a') === 0) return null;
    $n = norm($name);
    foreach ($departments as $d) {
        if (norm($d['name']) === $n) return (int)$d['id'];
    }
    // Only allow partial matching for longer names (avoid "IT" matching "Quality")
    if (strlen($n) > 3) {
        foreach ($departments as $d) {
            if (str_contains(norm($d['name']), $n)) return (int)$d['id'];
        }
    }
    return null;
}

function findAssetId(array $assets, string $tag): ?int {
    if ($tag === '') return null;
    $n = norm($tag);
    foreach ($assets as $a) {
        if (norm($a['asset_tag']) === $n) return (int)$a['id'];
    }
    foreach ($assets as $a) {
        if ($a['hostname'] && norm($a['hostname']) === $n) return (int)$a['id'];
    }
    foreach ($assets as $a) {
        if (str_contains(norm($a['asset_tag']), $n)) return (int)$a['id'];
    }
    return null;
}

function findUserId(array $users, string $name): ?int {
    if ($name === '') return null;
    $n = norm($name);
    foreach ($users as $u) {
        if (norm($u['username']) === $n || norm($u['full_name']) === $n) return (int)$u['id'];
    }
    foreach ($users as $u) {
        if (str_contains(norm($u['full_name']), $n)) return (int)$u['id'];
    }
    return null;
}

/** Parse messy dates: "Jan. 7 , 2023", "Sept. 30, 2023", "April. 25, 2026", "Sept. 2026". */
function parseDate(?string $raw): ?string {
    if (!$raw) return null;
    $raw = trim($raw);
    if ($raw === '' || strcasecmp($raw, 'n/a') === 0 || stripos($raw, 'august,') !== false) return null;

    $low = strtolower($raw);
    $map = ['sept'=>'sep','jan'=>'jan','feb'=>'feb','mar'=>'mar','apr'=>'apr','may'=>'may',
            'jun'=>'jun','jul'=>'jul','aug'=>'aug','sep'=>'sep','oct'=>'oct','nov'=>'nov','dec'=>'dec'];
    foreach ($map as $abbr => $std) {
        $low = preg_replace('/\b' . preg_quote($abbr, '/') . '\.?\s*/', $std . ' ', $low);
    }
    $ts = strtotime(preg_replace('/\s+/', ' ', trim($low)));
    if ($ts === false) $ts = strtotime($raw); // last resort
    return $ts !== false ? date('Y-m-d', $ts) : null;
}

function mapType(string $category): string {
    $c = strtolower($category);
    if (str_contains($c, 'subscription')) return 'subscription';
    if (str_contains($c, 'perpetual')) return 'perpetual';
    return 'other';
}

$rows = file('c:/xampp/htdocs/Itara/storage/ledger_sheet5.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$inserted = $skipped = 0;
$check = $pdo->prepare("SELECT COUNT(*) FROM software_licenses WHERE license_name = ? AND IFNULL(purchase_date,'') = ? AND IFNULL(version,'') = ?");
$stmt = $pdo->prepare("INSERT INTO software_licenses
    (license_name, software_name, version, purchase_date, expiration_date, purchased_seats, used_seats,
     cost, currency, department_id, assigned_user_id, assigned_name, assigned_asset_id, assigned_asset_tag,
     license_type, status, notes, created_by)
    VALUES (?,?,?,?,?,1,0,NULL,'PHP',?,?,?,?,?,?,?,?,1)"); // 13 placeholders: 5 before literals, 8 after

foreach ($rows as $line) {
    $cells = str_getcsv($line);
    array_shift($cells); // remove excel row number column
    $get = fn(int $i): string => isset($cells[$i]) ? trim($cells[$i]) : '';

    $no      = $get(1);   // No.
    $swName  = $get(4);   // Software Name*
    $version = $get(6);   // Software Version*
    $acqDate = $get(7);   // Acquisition Date
    $hwTag   = $get(8);   // Assigned Hardware - Asset Tag*
    $dept    = $get(9);   // Department/Section *
    $cat     = $get(10);  // License Category *
    $renewal = $get(11);  // Next Renewal Date
    $remarks = $get(14);  // Remarks (ACTIVE / INACTIVE)

    // Only data rows: numeric No. + non-empty software name
    if (!ctype_digit($no) || $swName === '' || str_contains($swName, 'Software Name')) continue;

    $version    = $version !== '' ? $version : null;
    $purchase   = parseDate($acqDate);
    $expiration = parseDate($renewal);
    $type       = mapType($cat);
    $status     = strcasecmp($remarks, 'INACTIVE') === 0 ? 'suspended' : 'active';

    // "Assigned Hardware" column holds an asset tag OR a person's name
    $assetTag = null; $assetId = null; $userId = null; $userName = null;
    if ($hwTag !== '' && strcasecmp($hwTag, 'n/a') !== 0) {
        $assetId = findAssetId($assets, $hwTag);
        if ($assetId !== null) {
            $assetTag = $hwTag;
        } else {
            $uid = findUserId($users, $hwTag);
            if ($uid !== null) { $userId = $uid; $userName = $hwTag; }
            else { $assetTag = $hwTag; } // keep verbatim even unresolved
        }
    }

    $deptId = findDepartment($departments, $dept);

    // Preserve anything that couldn't be structured
    $notesParts = ["Ledger category: {$cat}", "Source: ISMS-E-005 2026 ledger"];
    if ($dept !== '' && $deptId === null) $notesParts[] = "Ledger dept/section: {$dept}";
    if ($renewal !== '' && strcasecmp($renewal, 'n/a') !== 0 && $expiration === null) $notesParts[] = "Renewal (unparsed): {$renewal}";
    if ($acqDate !== '' && $purchase === null) $notesParts[] = "Acquisition (unparsed): {$acqDate}";
    if (strcasecmp($remarks, 'INACTIVE') === 0) $notesParts[] = 'Marked INACTIVE in ledger';
    $notes = implode(' | ', $notesParts);

    // Duplicate guard removed: identical name+date+version rows can legitimately exist
    // for different assets/users in the ledger.

    if ($dry) {
        printf("[%3s] %-26s acq=%-10s exp=%-10s type=%-12s dept=%-14s asset=%s(%s) user=%s(%s) %s\n",
            $no, mb_substr($swName, 0, 26), $purchase ?? '-', $expiration ?? '-', $type,
            $dept, $assetTag ?? '-', $assetId ?? '-', $userName ?? '-', $userId ?? '-', $status);
        $inserted++;
        continue;
    }

    $stmt->execute([
        $swName, $swName, $version, $purchase, $expiration,
        $deptId, $userId, $userName, $assetId, $assetTag,
        $type, $status, $notes,
    ]);
    $inserted++;
}

echo ($dry ? 'DRY RUN — would insert ' : 'Inserted ') . $inserted . ", skipped(dupes): $skipped\n";
