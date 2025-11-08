<?php
// Thiết lập title mặc định nếu không được set
$page_title = $page_title ?? 'FashionStore - Vogue Lane Clothing';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?></title>

<!-- CSS Cơ bản -->
<?php
    // Cache-bust style.css using file modification time so updates appear immediately in browser
    $stylePath = __DIR__ . '/../../css/style.css';
    $styleVersion = file_exists($stylePath) ? filemtime($stylePath) : time();
?>
<link rel="stylesheet" href="/fashionstore/css/style.css?v=<?php echo $styleVersion; ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   


<!-- CSS Bổ sung nếu có -->
<?php if (isset($extra_css) && is_array($extra_css)): ?>
    <?php foreach ($extra_css as $css): ?>
        <link rel="stylesheet" href="/fashionstore/<?php echo ltrim($css, '/'); ?>">
    <?php endforeach; ?>
<?php endif; ?>

<!-- JavaScript Bổ sung nếu có -->
<?php if (isset($extra_js) && is_array($extra_js)): ?>
    <?php foreach ($extra_js as $js): ?>
        <script src="/fashionstore/<?php echo ltrim($js, '/'); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>