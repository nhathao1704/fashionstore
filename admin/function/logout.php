ob_start(); 
?> 
<main style="padding:120px 20px;"> 
    <div class="po-container"> 
        <div class="po-header"> 
            <div class="status-icon"> 
                <i class="fas fa-check-circle"></i> 
            </div> 
            <div class="status-text"> 
                <h2>Đặt hàng thành công</h2> 
                 <p>Mã đơn hàng: <b><?= htmlspecialchars($new_id_order) ?></b></p> 
            </div> 
        </div> 
        <div class="po-card delivery-info"> 
            <h3><i class="fas fa-map-marker-alt"></i> Địa chỉ nhận hàng</h3> 
            <div class="info-content"> 
                <p class="name">
                    <?= htmlspecialchars($data['full_name']) ?>
                </p> 
                <p class="phone">
                    <?= htmlspecialchars($data['phone']) ?>
                </p> 
                <p class="address"> 
                    <?= htmlspecialchars($data['address']) ?>, 
                    <?= htmlspecialchars($data['district']) ?>, 
                    <?= htmlspecialchars($data['city']) ?> 
                </p> 
                <?php if (!empty($data['note'])): ?> 
                <p class="note"><strong>Ghi chú:</strong> <?= htmlspecialchars($data['note']) ?></p> 
                <?php endif; ?> 
            </div> 
        </div> 
        <div class="po-card order-items"> 
            <h3><i class="fas fa-shopping-bag"></i> Sản phẩm đã đặt</h3> 
        <div class="items-list"> 
            <?php foreach ($checkout['items'] as $item): ?> 
            <div class="item"> 
                <div class="item-image"> 
                    <img src="<?= htmlspecialchars($item['image'] ?? '/fashionstore/uploads/no-image.jpg') ?>" alt=""> 
                </div> 
                <div class="item-details"> 
                    <h4><?= htmlspecialchars($item['product_name']) ?></h4> 
                    <?php if (!empty($item['variant'])): ?> 
                    <p class="variant">Size: <?= htmlspecialchars($item['variant']) ?></p> 
                    <?php endif; ?>
                    <p class="quantity">Số lượng: <?= (int)$item['quantity'] ?></p> 
                </div> 
                <div class="item-price"> 
                    <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ </div> 
                </div>
                 <?php endforeach; ?> 
            </div> 
            <div class="order-summary"> 
                <div class="summary-row"> 
                    <span>Tổng tiền hàng</span> 
                    <span><?= number_format($checkout['total_amount'], 0, ',', '.') ?>đ</span> 
                </div> 
                <div class="summary-row"> 
                    <span>Phí vận chuyển</span> 
                    <span>Miễn phí</span> </div> 
                <div class="summary-row total"> 
                    <span>Tổng thanh toán</span> 
                    <span><?= number_format($checkout['total_amount'], 0, ',', '.') ?>đ</span> 
                </div> 
            </div> 
            <div class="po-actions"> 
                <a href="index.php?page=purchase_order" class="btn-outline">Xem đơn hàng của tôi</a> 
                <a href="index.php?page=product" class="btn-primary">Tiếp tục mua sắm</a> 
            </div> 
        </div> 
    </div> 
</main> 
<?php $content = ob_get_clean(); 
require __DIR__ . '/../../includes/layouts/' . $layout . '.php'; 
exit;