<?php
/**
 * Script to add Asset Lifecycle section to views/assets/show.php
 * This inserts a lifecycle timeline card before the closing div of col-8.
 */
$file = dirname(__DIR__) . '/views/assets/show.php';
$content = file_get_contents($file);

if (strpos($content, 'Asset Lifecycle') !== false) {
    echo "Lifecycle section already exists.\n";
    exit(0);
}

// Use a marker-based approach: find the end of notes section
// Look for the pattern: <?php endif; ?>\n            </div>\n        </div>\n    </div>\n\n    <div class="col-4">
// and insert lifecycle section before </div>\n\n    <div class="col-4">

$marker = '    </div>' . "\n" . '';
// Let's try a simpler approach - find "</div>" followed by col-4
// The pattern we need to find is the closing of the notes card + closing col-8
// Then we insert the lifecycle card before the col-4 starts

// Define the lifecycle HTML as a heredoc (no variable interpolation in PHP code)
$lifecycle = <<<'HTML'
        <!-- Asset Lifecycle -->
        <div class="card mt-4">
            <div class="card-header">
                <h3>Asset Lifecycle</h3>
            </div>
            <div class="card-body">
                <div class="lifecycle-timeline">
                    <div class="lifecycle-steps d-flex">
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-box"></i></div>
                            <div class="step-label">Purchased</div>
                            <small><?= \App\Helpers\Format::date($asset['purchase_date']) ?></small>
                        </div>
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="step-label">Active</div>
                        </div>
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-user"></i></div>
                            <div class="step-label">Assigned</div>
                        </div>
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-tools"></i></div>
                            <div class="step-label">Maintenance</div>
                        </div>
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-warehouse"></i></div>
                            <div class="step-label">Retired</div>
                        </div>
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-hourglass-half"></i></div>
                            <div class="step-label">Pending Disposal</div>
                        </div>
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-check-double"></i></div>
                            <div class="step-label">Approved</div>
                        </div>
                        <div class="lifecycle-step">
                            <div class="step-icon"><i class="fas fa-trash-alt"></i></div>
                            <div class="step-label">Disposed</div>
                        </div>
                    </div>
                </div>

                <?php if (isset($disposal) && $disposal): ?>
                <div class="mt-4">
                    <h4>Disposal Record</h4>
                    <?php if ($disposal['disposal_status'] === 'disposed'): ?>
                        <a href="<?= url('/disposals/' . $disposal['id'] . '/certificate') ?>" class="btn btn-primary">
                            <i class="fas fa-file-alt"></i> View Certificate
                        </a>
                    <?php else: ?>
                        <a href="<?= url('/disposals/' . $disposal['id']) ?>" class="btn btn-info">
                            <i class="fas fa-eye"></i> View Disposal Record
                        </a>
                    <?php endif; ?>
                </div>
                <?php elseif (isset($asset) && $asset['status'] === 'retired' && isset($user) && $user['role'] !== 'viewer'): ?>
                <div class="mt-4">
                    <h4>Disposal Record</h4>
                    <p>No disposal record exists. <a href="<?= url('/disposals/create/' . $asset['id']) ?>" class="btn btn-success">
                        <i class="fas fa-plus"></i> Create Disposal Request
                    </a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
HTML;

// Find the position to insert - look for the close of col-8 section
// The pattern after the notes section is:
//   </div>
//   </div>
// </div>
//
// <div class="col-4">
//
// We need to find the first occurrence of "    </div>\n\n    <div class=\"col-4\">"
$pattern = '/(\s*<\/div>\s*\n\s*<\/div>\s*\n\s*<\/div>\s*\n\s*\n\s*)(<div class="col-4">)/';
if (!preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
    // Try with \r\n
    $pattern = '/(\s*<\/div>\s*\r\n\s*<\/div>\s*\r\n\s*<\/div>\s*\r\n\s*\r\n)(<div class="col-4">)/';
    if (!preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        echo "Pattern not found. Trying line-by-line search...\n";
        // Debug: search for col-4
        $pos = strpos($content, 'col-4');
        if ($pos !== false) {
            echo "Found 'col-4' at position $pos\n";
            echo "Surrounding content:\n";
            echo substr($content, max(0, $pos - 200), 300);
        } else {
            echo "col-4 not found at all.\n";
        }
        exit(1);
    }
}

$insertPosition = $matches[1][1] + strlen($matches[1][0]);
$newline = (strpos($matches[0][0], "\r\n") !== false) ? "\r\n" : "\n";

$insertHtml = $newline . $lifecycle . $newline . '    ';
$newContent = substr($content, 0, $insertPosition) . $insertHtml . substr($content, $insertPosition);
file_put_contents($file, $newContent);
echo "Lifecycle section added successfully.\n";
