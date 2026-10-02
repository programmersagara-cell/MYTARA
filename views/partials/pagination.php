<?php
/**
 * Pagination Partial
 * 
 * @var int $currentPage
 * @var int $totalPages
 * @var string $baseUrl (optional, defaults to current URL without page param)
 */
$baseUrl = $baseUrl ?? preg_replace('/[?&]page=\d+/', '', $_SERVER['REQUEST_URI']);
$separator = str_contains($baseUrl, '?') ? '&' : '?';
?>

<?php if ($totalPages > 1): ?>
<nav class="pagination-wrapper" aria-label="Page navigation">
    <ul class="pagination">
        <!-- Previous -->
        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $baseUrl . $separator ?>page=<?= $currentPage - 1 ?>" aria-label="Previous">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>

        <!-- Page Numbers -->
        <?php
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        if ($start > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl . $separator ?>page=1">1</a>
            </li>
            <?php if ($start > 2): ?>
            <li class="page-item disabled">
                <span class="page-link">...</span>
            </li>
            <?php endif;
        endif;
        
        for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="<?= $baseUrl . $separator ?>page=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor;
        
        if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
            <li class="page-item disabled">
                <span class="page-link">...</span>
            </li>
            <?php endif; ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl . $separator ?>page=<?= $totalPages ?>"><?= $totalPages ?></a>
            </li>
        <?php endif; ?>

        <!-- Next -->
        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $baseUrl . $separator ?>page=<?= $currentPage + 1 ?>" aria-label="Next">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    </ul>

    <div class="pagination-info">
        Page <?= $currentPage ?> of <?= $totalPages ?>
    </div>
</nav>
<?php endif; ?>
