<?php
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
        echo '<p>Không tìm thấy sản phẩm.</p>';
        return;
}

// Product basic info + min price
$sql = "SELECT p.product_id, p.product_name, p.description, c.category_name,
                             (SELECT MIN(price) FROM ProductVariants v WHERE v.product_id = p.product_id) AS min_price
                FROM Products p
                LEFT JOIN Categories c ON p.category_id = c.category_id
                WHERE p.product_id = {$product_id} LIMIT 1";
$res = mysqli_query($conn, $sql);
if (!$res || mysqli_num_rows($res) == 0) {
        echo '<p>Không tìm thấy sản phẩm.</p>';
        return;
}
$product = mysqli_fetch_assoc($res);

// Images: fetch image_url and optional alt_text
$imgs = [];
$resImg = mysqli_query($conn, "SELECT image_url, COALESCE(alt_text, '') AS alt_text FROM ProductImages WHERE product_id = {$product_id} ORDER BY image_url ASC");
while ($r = mysqli_fetch_assoc($resImg)) $imgs[] = $r;
if (empty($imgs)) $imgs[] = ['image_url' => 'uploads/no-image.jpg', 'alt_text' => $product['product_name']];

// Variants
$variants = [];
$resVar = mysqli_query($conn, "SELECT variant_id, size, price, stock_quantity FROM ProductVariants WHERE product_id = {$product_id} ORDER BY variant_id ASC");
while ($v = mysqli_fetch_assoc($resVar)) $variants[] = $v;
?>

<section class="detail-wrap">
    <div class="detail-grid">
        <div class="detail-photos">
            <div class="thumbs">
                <?php foreach ($imgs as $i => $img): ?>
                    <img src="<?= htmlspecialchars($img['image_url']) ?>" 
                             alt="<?= htmlspecialchars($img['alt_text']) ?>"
                             class="<?= $i===0?'active':'' ?> thumb"
                             data-full="<?= htmlspecialchars($img['image_url']) ?>">
                <?php endforeach; ?>
            </div>
            <div class="main-photo">
                <img id="mainImage" src="<?= htmlspecialchars($imgs[0]['image_url']) ?>" alt="<?= htmlspecialchars($imgs[0]['alt_text']) ?>">
            </div>
        </div>

        <div class="detail-info">
            <h1><?= htmlspecialchars($product['product_name']) ?></h1>
            <div class="price-box">
                <div class="price-main" id="displayPrice"><?= $product['min_price'] ? number_format($product['min_price'], 0, ",", ".") . 'đ' : 'Liên hệ' ?></div>
            </div>

            <form id="addToCartForm" method="post">
                <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">

                <div class="opts">
                    <div><b>Kích thước:</b></div>
                    <div class="chips">
                        <?php if (empty($variants)): ?>
                            <span>Hiện chưa có biến thể.</span>
                        <?php else: ?>
                            <?php foreach ($variants as $v): ?>
                                <label class="chip" data-stock="<?= (int)$v['stock_quantity'] ?>" data-price="<?= (float)$v['price'] ?>">
                                    <input type="radio" name="variant_id" value="<?= (int)$v['variant_id'] ?>" hidden>
                                    <span><?= htmlspecialchars($v['size'] ?: 'Free') ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="qty">
                        <label><b>Số lượng:</b>
                            <input type="number" name="quantity" value="1" min="1" class="qty-input">
                        </label>
                        <span class="stock">Còn <span class="stock-qty">0</span> sản phẩm</span>
                    </div>
                </div>

                <div class="cta">
                    <button type="submit" class="btn-primary" id="addToCartBtn" disabled>
                        <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ hàng
                    </button>
                    <a href="/fashionstore/index.php?page=cart" class="btn-outline">Xem giỏ hàng</a>
                </div>
            </form>

            <div class="desc">
                <h3>Mô tả sản phẩm</h3>
                <div><?= nl2br(htmlspecialchars($product['description'] ?: 'Đang cập nhật...')) ?></div>
            </div>
        </div>
    </div>
</section>

<script>
(function(){
    const thumbs = document.querySelectorAll('.thumbs .thumb');
    const main = document.getElementById('mainImage');
    const chips = document.querySelectorAll('.chips .chip');
    const stockQtyEl = document.querySelector('.stock-qty');
    const displayPrice = document.getElementById('displayPrice');
    const qtyInput = document.querySelector('.qty-input');
    const addBtn = document.getElementById('addToCartBtn');
    const form = document.getElementById('addToCartForm');

    let selectedVariant = null;

    thumbs.forEach(t => {
        t.addEventListener('click', () => {
            thumbs.forEach(x=>x.classList.remove('active'));
            t.classList.add('active');
            main.src = t.dataset.full;
        });
    });

    chips.forEach(ch => {
        ch.addEventListener('click', () => {
            // mark active
            chips.forEach(c=>c.classList.remove('active'));
            ch.classList.add('active');
            // toggle radio inside
            const radio = ch.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            selectedVariant = {
                id: radio ? radio.value : null,
                stock: parseInt(ch.dataset.stock || '0', 10),
                price: parseFloat(ch.dataset.price || '0')
            };
            // update UI
            stockQtyEl.textContent = selectedVariant.stock;
            displayPrice.textContent = selectedVariant.price ? selectedVariant.price.toLocaleString('vi-VN') + 'đ' : displayPrice.textContent;
            qtyInput.max = selectedVariant.stock;
            addBtn.disabled = !(selectedVariant && selectedVariant.stock > 0);
        });
    });

    qtyInput.addEventListener('input', () => {
        let v = parseInt(qtyInput.value, 10) || 1;
        if (selectedVariant && v > selectedVariant.stock) v = selectedVariant.stock;
        if (v < 1) v = 1;
        qtyInput.value = v;
    });
    (function autoSelect(){
        const first = Array.from(chips).find(c => parseInt(c.dataset.stock||'0',10) > 0);
        if (first) first.click();
    })();

    form.addEventListener('submit', async function(e){
        e.preventDefault();
        if (!selectedVariant || !selectedVariant.id) { alert('Vui lòng chọn biến thể.'); return; }
        const qty = parseInt(qtyInput.value, 10) || 1;
        if (qty < 1 || (selectedVariant.stock && qty > selectedVariant.stock)) { alert('Số lượng không hợp lệ'); return; }

        const data = new FormData();
        data.append('product_id', <?= (int)$product_id ?>);
        data.append('variant_id', selectedVariant.id);
        data.append('quantity', qty);

        addBtn.disabled = true;
        const prevText = addBtn.innerHTML;
        addBtn.innerHTML = 'Đang thêm...';

        try {
            const resp = await fetch('/fashionstore/api/add_to_cart.php', { method: 'POST', credentials: 'same-origin', body: data });
            const json = await resp.json();
            if (!resp.ok) {
                if (resp.status === 401) {
                     alert('Bạn chưa đăng nhập. Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng.');
                    const returnUrl = encodeURIComponent(window.location.pathname + window.location.search);
                    window.location.href = '/fashionstore/index.php?page=login&return=' + returnUrl;
                    return;
                }
                throw new Error(json.error || 'Lỗi khi thêm vào giỏ');
            }
            if (json.data && typeof json.data.cart_count !== 'undefined') {
                const el = document.querySelector('.cart-count'); if (el) el.textContent = json.data.cart_count;
            }    
            alert(json.data && json.data.message ? json.data.message : 'Đã thêm vào giỏ');
        } catch (err) {
            console.error(err);
            alert(err.message || 'Có lỗi xảy ra');
        } finally {
            addBtn.disabled = false;
            addBtn.innerHTML = prevText;
        }
    });

})();
</script>
