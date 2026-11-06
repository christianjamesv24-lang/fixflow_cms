<?php if (($totalPages ?? 1) > 1) { ?>
    <nav class="pagination" aria-label="Pagination">
        <?php if ($currentPage > 1) { ?>
            <a class="page-link" href="<?= e(query_url(['page' => $currentPage - 1])) ?>" aria-label="Previous page"><?= icon('chevron-right', 'flip') ?></a>
        <?php } ?>

        <?php
        $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    ?>
        <?php if ($start > 1) { ?>
            <a class="page-link" href="<?= e(query_url(['page' => 1])) ?>">1</a>
            <?php if ($start > 2) { ?><span class="page-gap">…</span><?php } ?>
        <?php } ?>

        <?php for ($i = $start; $i <= $end; $i++) { ?>
            <a class="page-link <?= $i === $currentPage ? 'active' : '' ?>" href="<?= e(query_url(['page' => $i])) ?>"><?= $i ?></a>
        <?php } ?>

        <?php if ($end < $totalPages) { ?>
            <?php if ($end < $totalPages - 1) { ?><span class="page-gap">…</span><?php } ?>
            <a class="page-link" href="<?= e(query_url(['page' => $totalPages])) ?>"><?= $totalPages ?></a>
        <?php } ?>

        <?php if ($currentPage < $totalPages) { ?>
            <a class="page-link" href="<?= e(query_url(['page' => $currentPage + 1])) ?>" aria-label="Next page"><?= icon('chevron-right') ?></a>
        <?php } ?>
    </nav>
<?php } ?>
