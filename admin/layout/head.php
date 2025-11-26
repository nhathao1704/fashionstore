
<?php
// Thiết lập title mặc định nếu không được set
$page_title = $page_title ?? 'FashionStore - Vogue Lane Clothing';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?></title>

<!-- CSS Cơ bản -->
<?php
    $stylePath = __DIR__ . '/../../css/admin.css';
    $styleVersion = file_exists($stylePath) ? filemtime($stylePath) : time();
?>
<link rel="stylesheet" href="/fashionstore/css/admin.css?v=<?php echo $styleVersion; ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   


<!-- CSS Bổ sung nếu có -->
<?php if (isset($extra_css) && is_array($extra_css)): ?>
    <?php foreach ($extra_css as $css): ?>
        <link rel="stylesheet" href="/fashionstore/<?php echo ltrim($css, '/'); ?>">
    <?php endforeach; ?>
<?php endif; ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const input = document.querySelector(".searchbar input");
    const btn   = document.querySelector(".searchbtn");

    console.log("JS Loaded!", input, btn);

    if (!input || !btn) {
        console.log("Không tìm thấy input hoặc button");
        return;
    }

    function doSearch() {
        const kw = input.value.trim();
        console.log("Search keyword =", kw);

        if (kw === "") {
            alert("Vui lòng nhập từ khóa!");
            return;
        }

        // === Gọi router search ===
        window.location.href = `index.php?page=search&keyword=${encodeURIComponent(kw)}`;
    }

    // Enter để tìm kiếm
    input.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
            console.log("Press Enter");
            doSearch();
        }
    });

    // Click icon để tìm
    btn.addEventListener("click", () => {
        console.log("Click button");
        doSearch();
    });
});
</script>

