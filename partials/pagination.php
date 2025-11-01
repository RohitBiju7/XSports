<?php
if (!isset($totalPages, $currentPage)) {
    return;
}

$buildUrl = function($page) use ($searchQuery, $filterCategory) {
    $params = [];
    if ($searchQuery !== '') {
        $params['q'] = $searchQuery;
    }
    if ($filterCategory !== 'all' && $filterCategory !== '') {
        $params['category'] = $filterCategory;
    }
    $params['page'] = max(1, (int)$page);
    return 'admin.php?' . http_build_query($params);
};

$visibleRange = 2; // how many pages to show around current
$pages = [];

if ($totalPages <= 1) {
    return;
}

$pages[] = 1;
for ($i = $currentPage - $visibleRange; $i <= $currentPage + $visibleRange; $i++) {
    if ($i > 1 && $i < $totalPages) {
        $pages[] = $i;
    }
}
if ($totalPages > 1) {
    $pages[] = $totalPages;
}
$pages = array_values(array_unique($pages));
sort($pages);

static $paginationInstance = 0;
$paginationInstance++;
$jumpInputId = 'pageJump' . $paginationInstance;

?>
<div class="pagination-controls">
    <button type="button" onclick="window.location='<?php echo $buildUrl(max(1, $currentPage - 1)); ?>'" <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>>Prev</button>
    <?php
    $lastPage = 0;
    foreach ($pages as $page):
        if ($lastPage && $page > $lastPage + 1) {
            echo '<span>…</span>';
        }
        $lastPage = $page;
    ?>
        <button type="button" onclick="window.location='<?php echo $buildUrl($page); ?>'" class="<?php echo $page == $currentPage ? 'active' : ''; ?>"><?php echo $page; ?></button>
    <?php endforeach; ?>
    <button type="button" onclick="window.location='<?php echo $buildUrl(min($totalPages, $currentPage + 1)); ?>'" <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>>Next</button>
</div>
<div class="pagination-input">
    <label for="<?php echo $jumpInputId; ?>">Go to</label>
    <input type="number" id="<?php echo $jumpInputId; ?>" min="1" max="<?php echo $totalPages; ?>" value="<?php echo $currentPage; ?>" onkeydown="if(event.key==='Enter'){var v=this.value; if(v){window.location='<?php echo $buildUrl('PAGE_JUMP'); ?>'.replace('PAGE_JUMP', v);}}">
    <button type="button" onclick="var inp=document.getElementById('<?php echo $jumpInputId; ?>'); if(inp && inp.value){window.location='<?php echo $buildUrl('PAGE_JUMP'); ?>'.replace('PAGE_JUMP', inp.value);}}">Go</button>
</div>

