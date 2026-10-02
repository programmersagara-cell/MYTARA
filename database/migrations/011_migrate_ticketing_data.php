<?php
/**
 * Migration 011: Migrate TicketingSystem2 (login_system) data into itassets
 *
 * Reads data from the `login_system` database and imports it into the
 * unified `itassets` database. Preserves all data and maps relationships.
 *
 * Run: php database/migrations/011_migrate_ticketing_data.php
 */

// Connection Config
$src = new PDO('mysql:host=127.0.0.1;dbname=login_system;charset=utf8mb4', 'root', '');
$src->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$dst = new PDO('mysql:host=127.0.0.1;dbname=itassets;charset=utf8mb4', 'root', '');
$dst->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Ticketing Data Migration (login_system to itassets) ===\n\n";

// Helper: map ticketing role to unified role
function mapRole(string $role): string
{
    $r = strtolower($role);
    if ($r === 'admin') return 'admin';
    return 'user'; // legacy role names (user/encoder/operator) -> user
}

// Helper: map department name to department_id (best-effort)
function mapDepartmentId(PDO $dst, string $dept): ?int
{
    $dept = trim($dept);
    if ($dept === '') return null;

    // Try exact name match
    $stmt = $dst->prepare("SELECT id FROM departments WHERE name = ? LIMIT 1");
    $stmt->execute([$dept]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) return (int) $row['id'];

    // Try code match (case-insensitive)
    $stmt = $dst->prepare("SELECT id FROM departments WHERE LOWER(code) = LOWER(?) LIMIT 1");
    $stmt->execute([$dept]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) return (int) $row['id'];

    // Try partial name match
    $stmt = $dst->prepare("SELECT id FROM departments WHERE LOWER(name) LIKE ? LIMIT 1");
    $stmt->execute(['%' . strtolower($dept) . '%']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) return (int) $row['id'];

    return null;
}

// 1. Migrate USERS
echo "[1/8] Migrating users...\n";
$srcUsers = $src->query("SELECT * FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$usernameToId = []; // old username => new user id
$insertedUsers = 0;

$userStmt = $dst->prepare(
    "INSERT INTO users (username, email, password, full_name, role, department, is_active, created_at, updated_at)
     VALUES (:username, :email, :password, :full_name, :role, :department, :is_active, NOW(), NOW())"
);

foreach ($srcUsers as $u) {
    $username = $u['username'];
    $emailRaw = ($u['email'] ?? '') !== '' ? $u['email'] : '';

    // email column is NOT NULL. Handle empty & duplicates with a unique fallback.
    $email = $emailRaw;
    if ($email === '') {
        $email = "{$username}@ticketing.local";
    }
    $chk = $dst->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
    $chk->execute([$email]);
    if ($chk->fetch(PDO::FETCH_ASSOC)) {
        $email = $username . '_' . uniqid() . '@ticketing.local';
    }

    // If username already exists (case-insensitive), map to existing user and skip insert
    $existing = $dst->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
    $existing->execute([$username]);
    $existingRow = $existing->fetch(PDO::FETCH_ASSOC);
    if ($existingRow) {
        $usernameToId[$username] = (int) $existingRow['id'];
        echo "   - '{$username}' exists, mapped to user #{$usernameToId[$username]}\n";
        continue;
    }

    $password = $u['password'];
    // Ensure it's a valid bcrypt hash; if not, rehash
    if (!password_get_info($password)['algo'] || strlen($password) < 20) {
        $password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    $userStmt->execute([
        'username'   => $username,
        'email'      => $email,
        'password'   => $password,
        'full_name'  => $username !== '' ? ucfirst($username) : $username,
        'role'       => mapRole($u['role'] ?? 'user'),
        'department' => ($u['department'] ?? '') !== '' ? $u['department'] : null,
        'is_active'  => 1,
    ]);

    $newId = (int) $dst->lastInsertId();
    $usernameToId[$username] = $newId;
    $insertedUsers++;
    $mappedRole = mapRole($u['role'] ?? 'user');
    echo "   + '{$username}' inserted as user #{$newId} (role={$mappedRole})\n";
}
echo "   Inserted {$insertedUsers} new users, mapped " . count($usernameToId) . " total.\n\n";

// 2. Migrate CONCERNS
echo "[2/8] Migrating concerns...\n";
$srcConcerns = $src->query("SELECT * FROM concerns ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$insertedConcerns = 0;
$concernStmt = $dst->prepare(
    "INSERT INTO concerns (
        user_id, username_ref, ticket_number, sender_name, department, department_id,
        description, image_path, status, priority, submitted_at, repaired_date, repaired_by,
        canceled_reason, canceled_date, canceled_by, remarks,
        backed_up_at, backed_up_status, backed_up_remarks_hash,
        backed_up_canceled_reason_hash, backed_up_repaired_by_hash
     ) VALUES (
        :user_id, :username_ref, :ticket_number, :sender_name, :department, :department_id,
        :description, :image_path, :status, :priority, :submitted_at, :repaired_date, :repaired_by,
        :canceled_reason, :canceled_date, :canceled_by, :remarks,
        :backed_up_at, :backed_up_status, :backed_up_remarks_hash,
        :backed_up_canceled_reason_hash, :backed_up_repaired_by_hash
     )"
);

foreach ($srcConcerns as $c) {
    $oldUser = $c['user_id'];
    $newUserId = isset($usernameToId[$oldUser]) ? $usernameToId[$oldUser] : null;
    $deptId = mapDepartmentId($dst, $c['department'] ?? '');

    $concernStmt->execute([
        'user_id' => $newUserId,
        'username_ref' => $oldUser,
        'ticket_number' => ($c['ticket_number'] ?? null),
        'sender_name' => $c['sender_name'],
        'department' => $c['department'],
        'department_id' => $deptId,
        'description' => $c['description'],
        'image_path' => ($c['image_path'] ?? null),
        'status' => ($c['status'] ?? 'active'),
        'priority' => ($c['priority'] ?? 'Medium'),
        'submitted_at' => $c['submitted_at'],
        'repaired_date' => ($c['repaired_date'] ?? null),
        'repaired_by' => ($c['repaired_by'] ?? null),
        'canceled_reason' => ($c['canceled_reason'] ?? null),
        'canceled_date' => ($c['canceled_date'] ?? null),
        'canceled_by' => ($c['canceled_by'] ?? null),
        'remarks' => ($c['remarks'] ?? null),
        'backed_up_at' => ($c['backed_up_at'] ?? null),
        'backed_up_status' => ($c['backed_up_status'] ?? null),
        'backed_up_remarks_hash' => ($c['backed_up_remarks_hash'] ?? null),
        'backed_up_canceled_reason_hash' => ($c['backed_up_canceled_reason_hash'] ?? null),
        'backed_up_repaired_by_hash' => ($c['backed_up_repaired_by_hash'] ?? null),
    ]);
    $insertedConcerns++;
}
echo "   Inserted {$insertedConcerns} concerns.\n\n";

// 3. Migrate INSTRUCTIONS
echo "[3/8] Migrating instructions...\n";
$srcInstructions = $src->query("SELECT * FROM instructions ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$instrStmt = $dst->prepare(
    "INSERT INTO instructions (tittle, instruction_text, created_at) VALUES (:tittle, :text, NOW())"
);
$inserted = 0;
foreach ($srcInstructions as $i) {
    $instrStmt->execute([
        'tittle' => ($i['tittle'] ?? null),
        'text' => $i['instruction_text'],
    ]);
    $inserted++;
}
echo "   Inserted {$inserted} instructions.\n\n";

// 4. Migrate TICKET_SEQUENCES
echo "[4/8] Migrating ticket_sequences...\n";
$seqStmt = $dst->prepare("INSERT INTO ticket_sequences (date_key, next_seq) VALUES (:dk, :ns)");
$inserted = 0;
foreach ($src->query("SELECT * FROM ticket_sequences") as $s) {
    $seqStmt->execute(['dk' => $s['date_key'], 'ns' => $s['next_seq']]);
    $inserted++;
}
echo "   Inserted {$inserted} ticket_sequences.\n\n";

// 5. Migrate NOTIFICATIONS
echo "[5/8] Migrating notifications to trouble_notifications...\n";
$srcNotifs = $src->query("SELECT * FROM notifications ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$notifStmt = $dst->prepare(
    "INSERT INTO trouble_notifications
        (user_id, username_ref, concern_id, ticket_number, department, type, message, created_at, read_at)
     VALUES
        (:user_id, :username_ref, :concern_id, :ticket_number, :department, :type, :message, :created_at, :read_at)"
);
$inserted = 0;
foreach ($srcNotifs as $n) {
    $oldUser = $n['user_id'];
    $newUserId = isset($usernameToId[$oldUser]) ? $usernameToId[$oldUser] : null;
    $notifStmt->execute([
        'user_id' => $newUserId,
        'username_ref' => $oldUser,
        'concern_id' => ($n['concern_id'] ?? null),
        'ticket_number' => ($n['ticket_number'] ?? null),
        'department' => ($n['department'] ?? null),
        'type' => ($n['type'] ?? 'email'),
        'message' => $n['message'],
        'created_at' => $n['created_at'],
        'read_at' => ($n['read_at'] ?? null),
    ]);
    $inserted++;
}
echo "   Inserted {$inserted} notifications.\n\n";

// 6. Migrate CONCERN_SUBMISSIONS
echo "[6/8] Migrating concern_submissions...\n";
$subStmt = $dst->prepare(
    "INSERT INTO concern_submissions (user_id, username_ref, request_key, created_at, concern_id)
     VALUES (:user_id, :username_ref, :request_key, :created_at, :concern_id)"
);
$inserted = 0;
foreach ($src->query("SELECT * FROM concern_submissions") as $s) {
    $oldUser = $s['user_id'];
    $newUserId = isset($usernameToId[$oldUser]) ? $usernameToId[$oldUser] : null;
    $subStmt->execute([
        'user_id' => $newUserId,
        'username_ref' => $oldUser,
        'request_key' => $s['request_key'],
        'created_at' => $s['created_at'],
        'concern_id' => ($s['concern_id'] ?? null),
    ]);
    $inserted++;
}
echo "   Inserted {$inserted} concern_submissions.\n\n";

// 7. Migrate CONCERN_SUBMISSION_TOKENS
echo "[7/8] Migrating concern_submission_tokens...\n";
$tokStmt = $dst->prepare(
    "INSERT INTO concern_submission_tokens (token, user_id, username_ref, used_at)
     VALUES (:token, :user_id, :username_ref, :used_at)"
);
$inserted = 0;
foreach ($src->query("SELECT * FROM concern_submission_tokens") as $t) {
    $oldUser = $t['user_id'];
    $newUserId = isset($usernameToId[$oldUser]) ? $usernameToId[$oldUser] : null;
    $tokStmt->execute([
        'token' => $t['token'],
        'user_id' => $newUserId,
        'username_ref' => $oldUser,
        'used_at' => $t['used_at'],
    ]);
    $inserted++;
}
echo "   Inserted {$inserted} concern_submission_tokens.\n\n";

// 8. Migrate EMAIL_QUEUE + META
echo "[8/8] Migrating email_queue + meta...\n";
$eqStmt = $dst->prepare(
    "INSERT INTO email_queue (to_email, subject, body, status, attempts, max_attempts, available_at, sent_at, last_error)
     VALUES (:to_email, :subject, :body, :status, :attempts, :max_attempts, :available_at, :sent_at, :last_error)"
);
$inserted = 0;
foreach ($src->query("SELECT * FROM email_queue") as $e) {
    $eqStmt->execute([
        'to_email' => $e['to_email'],
        'subject' => $e['subject'],
        'body' => $e['body'],
        'status' => ($e['status'] ?? 'queued'),
        'attempts' => ($e['attempts'] ?? 0),
        'max_attempts' => ($e['max_attempts'] ?? 5),
        'available_at' => $e['available_at'],
        'sent_at' => ($e['sent_at'] ?? null),
        'last_error' => ($e['last_error'] ?? null),
    ]);
    $inserted++;
}
echo "   Inserted {$inserted} email_queue rows.\n";

$metaStmt = $dst->prepare("INSERT INTO meta (name, value) VALUES (:name, :value)");
$insertedMeta = 0;
foreach ($src->query("SELECT * FROM meta") as $m) {
    $metaStmt->execute(['name' => $m['name'], 'value' => $m['value']]);
    $insertedMeta++;
}
echo "   Inserted {$insertedMeta} meta rows.\n\n";

// Record migration
$dst->exec("INSERT INTO _migrations (migration, batch) VALUES ('011_migrate_ticketing_data', 1)");

echo "=== Migration complete ===\n";
echo "Users in itassets: " . $dst->query("SELECT COUNT(*) FROM users")->fetchColumn() . "\n";
echo "Concerns in itassets: " . $dst->query("SELECT COUNT(*) FROM concerns")->fetchColumn() . "\n";
