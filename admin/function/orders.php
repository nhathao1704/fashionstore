<?php

require_once __DIR__ . '/../../config/config.php';

if (empty($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    header('Location: index.php?page=login-admin&return=' . urlencode('/fashionstore/admin/index.php?page=orders'));
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

//  Tạo đơn hàng mới 
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $total = (float)($_POST['total_amount'] ?? 0);
    $status_id = (int)($_POST['status_id'] ?? 1);
    $shipping_address = mysqli_real_escape_string($conn, trim($_POST['shipping_address'] ?? ''));

    mysqli_query($conn, "INSERT INTO orders (user_id, total_amount, status_id, shipping_address, order_date)
                         VALUES ($user_id, $total, $status_id, '$shipping_address', NOW())");
    header('Location: index.php?page=orders');
    exit;
}

//  Cập nhật đơn hàng 
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $status_id = (int)($_POST['status_id'] ?? 1);
    
    // Kiểm tra status_id hợp lệ
    if ($status_id > 0 && $status_id <= 5) {
        $result = mysqli_query($conn, "UPDATE orders SET status_id=$status_id WHERE order_id=$id");
        if ($result) {
            // Thành công - redirect với thông báo
            header('Location: index.php?page=orders&updated=1');
        } else {
            // Lỗi query
           header('Location: index.php?page=orders&error=1');
        }
    } else {
        // Status_id không hợp lệ
        header('Location: index.php?page=orders&error=2');
    }
    exit;
}

//  Xóa đơn hàng 
if ($action === 'delete' && $id > 0) {
    mysqli_query($conn, "DELETE FROM orders WHERE order_id=$id");
    header('Location: index.php?page=orders');
    exit;
}


// Xem chi tiết đơn hàng 
if ($action === 'detail' && $id > 0) {
    // Tắt layout khi trả về chi tiết
    header("Content-Type: text/html; charset=UTF-8");
    ob_clean(); // Xóa mọi output trước đó (nếu có)

    $r = mysqli_query($conn, "
        SELECT 
            o.*, 
            u.full_name, 
            u.email, 
            os.status_name,
            p.promotion_name
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        LEFT JOIN order_status os ON o.status_id = os.status_id
        LEFT JOIN promotions p ON o.promotion_id = p.promotion_id
        WHERE o.order_id = $id
    ");


    $order = $r ? mysqli_fetch_assoc($r) : null;

    if ($order) {

        $items = json_decode($order['items'], true);

        // Tổng tiền ban đầu = tổng cuối + giảm giá
        $discount = (int)($order['discount_amount'] ?? 0);
        $final_total = (int)($order['total_amount'] ?? 0);
        $original_total = $final_total + $discount;

        echo '<div style="line-height: 1.8;">';

        echo '<p><strong>Mã đơn:</strong> ' . h($order['id_order']) . '</p>';
        echo '<p><strong>Khách hàng:</strong> ' . h($order['full_name']) . '</p>';
        echo '<p><strong>Email:</strong> ' . h($order['email'] ?? 'N/A') . '</p>';
        echo '<p><strong>Trạng thái:</strong> ' . h($order['status_name']) . '</p>';
        echo '<p><strong>Ngày đặt:</strong> ' . date('d/m/Y H:i', strtotime($order['order_date'])) . '</p>';
        echo '<p><strong>Địa chỉ giao hàng:</strong> ' . h($order['shipping_address'] ?? 'N/A') . '</p>';

        // TIỀN & GIẢM GIÁ
        echo '<hr style="margin:10px 0;">';
        echo '<h3>Thanh toán:</h3>';

        echo '<p><strong>Tổng tiền ban đầu:</strong> ' . number_format($original_total, 0, ',', '.') . 'đ</p>';

        if ($discount > 0) {
            echo '<p><strong>Giảm giá:</strong> -' . number_format($discount, 0, ',', '.') . 'đ</p>';

           if (!empty($order['promotion_name'])) {
                echo '<p><strong>Khuyến mãi:</strong> ' . h($order['promotion_name']) . '</p>';
            }
        }

        echo '<p><strong>Tổng thanh toán:</strong> ' . number_format($final_total, 0, ',', '.') . 'đ</p>';

        // DANH SÁCH SẢN PHẨM
        if ($items && is_array($items)) {
            echo '<h3 style="margin-top: 20px; margin-bottom: 10px;">Sản phẩm:</h3>';

            echo '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
            echo '<thead>
                    <tr style="background: #f5f5f5;">
                        <th style="padding: 10px; border: 1px solid #ddd;">Tên sản phẩm</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Số lượng</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align:right;">Giá</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align:right;">Thành tiền</th>
                    </tr>
                  </thead>';
            echo '<tbody>';

            foreach ($items as $item) {
                $name = h($item['product_name'] ?? 'Sản phẩm');
                $qty = (int)($item['quantity'] ?? 1);
                $price = (float)($item['price'] ?? 0);
                $total = $price * $qty;

                echo '<tr>';
                echo '<td style="padding:10px;border:1px solid #ddd;">' . $name . '</td>';
                echo '<td style="padding:10px;border:1px solid #ddd;">' . $qty . '</td>';
                echo '<td style="padding:10px;border:1px solid #ddd;text-align:right;">' . number_format($price, 0, ',', '.') . 'đ</td>';
                echo '<td style="padding:10px;border:1px solid #ddd;text-align:right;">' . number_format($total, 0, ',', '.') . 'đ</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '</div>';

    } else {
        echo '<p>Không tìm thấy đơn hàng.</p>';
    }

    exit;
}


// === Lấy dữ liệu đơn hàng để chỉnh sửa ===
$editing = null;
if ($action === 'edit' && $id > 0) {
    $r = mysqli_query($conn, "SELECT * FROM orders WHERE order_id=$id");
    $editing = $r ? mysqli_fetch_assoc($r) : null;
}

// === Filter theo trạng thái ===
$filter_status = isset($_GET['status']) ? (int)$_GET['status'] : 0;
$where_clause = '';
if ($filter_status > 0) {
    $where_clause = "WHERE o.status_id = $filter_status";
}

// === Lấy danh sách đơn hàng ===
$rows = mysqli_query(
    $conn,
    "SELECT o.*, u.full_name, os.status_name
     FROM orders o 
     LEFT JOIN users u ON o.user_id = u.user_id 
     LEFT JOIN order_status os ON o.status_id = os.status_id
     $where_clause
     ORDER BY o.order_date DESC"
);

// === Lấy danh sách users ===
$users = mysqli_query($conn, "SELECT user_id, full_name FROM users ORDER BY full_name");

// === Lấy danh sách status ===
$statuses = mysqli_query($conn, "SELECT status_id, status_name FROM order_status ORDER BY status_id");
?>
            <h1 class="page-title">Quản lý Đơn hàng</h1>
            
            <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
               
            <?php elseif (isset($_GET['error'])): ?>
                <div style="background: #f9d6d5; color: #922b21; padding: 12px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #e74c3c;">
                    ✗ Có lỗi xảy ra khi cập nhật trạng thái. Vui lòng thử lại.
                </div>
            <?php endif; ?>

            <div class="filter-section" style="margin-bottom: 20px;">
                <form method="get" action="index.php" style="display:inline-block;">
                    <input type="hidden" name="page" value="orders">
                    <select name="status" class="filter-select" style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; background: #fff; cursor: pointer;" onchange="this.form.submit()">
                        <option value="0" <?php echo $filter_status == 0 ? 'selected' : ''; ?>>Tất cả đơn hàng</option>
                        <?php 
                        mysqli_data_seek($statuses, 0);
                        while ($st = mysqli_fetch_assoc($statuses)): 
                        ?>
                            <option value="<?php echo $st['status_id']; ?>" <?php echo $filter_status == $st['status_id'] ? 'selected' : ''; ?>>
                                <?php echo h($st['status_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </form>
            </div>

            <div class="table-container">
                <div style="margin-bottom: 20px;">
                   <a class="btn-edit" href="index.php?page=orders&action=new" style="display: inline-block; padding: 10px 20px; background: #27ae60; color: #fff; text-decoration: none; border-radius: 5px;">
                        <i class="fas fa-plus"></i> Tạo đơn hàng mới
                    </a>
                </div>

                <?php if ($action === 'new' || $editing): ?>
                    <div class="table-container" style="margin-bottom: 30px;">
                        <h2><?php echo $editing ? 'Cập nhật đơn hàng' : 'Tạo đơn hàng mới'; ?></h2>
                        <form method="post" action="orders.php?action=<?php echo $editing ? 'update&id=' . $editing['order_id'] : 'create'; ?>">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Khách hàng</label>
                                <select name="user_id" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" <?php echo $editing ? 'disabled' : ''; ?>>
                                    <option value="">-- Chọn khách hàng --</option>
                                    <?php if ($users): 
                                        mysqli_data_seek($users, 0);
                                        while ($u = mysqli_fetch_assoc($users)): ?>
                                        <option value="<?php echo $u['user_id']; ?>"
                                            <?php echo ($editing && $editing['user_id'] == $u['user_id']) ? 'selected' : ''; ?>>
                                            <?php echo h($u['full_name']); ?>
                                        </option>
                                    <?php endwhile; endif; ?>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Tổng tiền</label>
                                <input type="number" step="0.01" name="total_amount"
                                       class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                                       required
                                       value="<?php echo $editing ? h($editing['total_amount']) : ''; ?>"
                                       <?php echo $editing ? 'disabled' : ''; ?>>
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Trạng thái</label>
                                <select name="status_id" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    <?php if ($statuses): 
                                        mysqli_data_seek($statuses, 0);
                                        while ($st = mysqli_fetch_assoc($statuses)): ?>
                                        <option value="<?php echo $st['status_id']; ?>"
                                            <?php echo ($editing && $editing['status_id'] == $st['status_id']) ? 'selected' : ''; ?>>
                                            <?php echo h($st['status_name']); ?>
                                        </option>
                                    <?php endwhile; endif; ?>
                                </select>
                            </div>

                            <?php if (!$editing): ?>
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Địa chỉ giao hàng</label>
                                <input type="text" name="shipping_address"
                                       class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                                       value="<?php echo $editing ? h($editing['shipping_address']) : ''; ?>">
                            </div>
                            <?php endif; ?>

                            <div style="margin-top: 20px;">
                                <button type="submit" class="btn-edit" style="padding: 10px 20px; background: #27ae60; color: #fff; border: none; border-radius: 5px; cursor: pointer;">
                                    <?php echo $editing ? 'Cập nhật' : 'Tạo mới'; ?>
                                </button>
                                <a class="btn-delete" href="index.php?page=orders" style="display: inline-block; padding: 10px 20px; background: #95a5a6; color: #fff; text-decoration: none; border-radius: 5px; margin-left: 10px;">
                                    Hủy
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Tổng tiền</th>
                            <th>Ngày đặt</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rows): 
                            mysqli_data_seek($rows, 0);
                            while ($r = mysqli_fetch_assoc($rows)): 
                                $status_class = 'pending';
                                $status_id = (int)($r['status_id'] ?? 0);
                                // Xác định class màu dựa trên status_id
                                if ($status_id == 4) {
                                    $status_class = 'done'; // Hoàn thành
                                } elseif ($status_id == 5) {
                                    $status_class = 'cancel'; // Đã hủy
                                } elseif ($status_id == 3) {
                                    $status_class = 'done'; // Đang giao hàng (màu xanh)
                                } elseif ($status_id == 2) {
                                    $status_class = 'pending'; // Đang xử lý (màu cam)
                                } else {
                                    $status_class = 'pending'; // Chờ xác nhận (màu cam)
                                }
                        ?>
                            <tr>
                                <td><?php echo h($r['id_order']); ?></td>
                                <td><?php echo h($r['full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($r['total_amount'], 0, ',', '.'); ?>đ</td>
                                <td><?php echo date('Y-m-d', strtotime($r['order_date'])); ?></td>
                                <td>
                                    <span class="order-status <?php echo $status_class; ?>" id="status-<?php echo $r['order_id']; ?>">
                                        <?php echo h($r['status_name'] ?? 'Chưa xác định'); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-view" onclick="viewOrderDetail(<?php echo $r['order_id']; ?>)" style="padding: 6px 12px; background: #3498db; color: #fff; border: none; border-radius: 4px; cursor: pointer; margin-right: 5px; font-size: 13px;">
                                        Chi tiết
                                    </button>
                                    <button class="btn-edit" onclick="updateOrderStatus(<?php echo $r['order_id']; ?>, <?php echo $status_id; ?>)" style="padding: 6px 12px; background: #27ae60; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">
                                        Cập nhật
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px;">Chưa có đơn hàng nào</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
    <!-- Modal Chi tiết đơn hàng -->
    <div id="orderDetailModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
        <div style="background: #fff; margin: 50px auto; max-width: 800px; padding: 30px; border-radius: 10px; position: relative;">
            <span onclick="closeOrderDetail()" style="position: absolute; top: 15px; right: 20px; font-size: 28px; cursor: pointer; color: #999;">&times;</span>
            <h2 style="margin-bottom: 20px;">Chi tiết đơn hàng</h2>
            <div id="orderDetailContent"></div>
        </div>
    </div>

    <!-- Modal Cập nhật trạng thái -->
    <div id="updateStatusModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: #fff; margin: 100px auto; max-width: 500px; padding: 30px; border-radius: 10px; position: relative;">
            <span onclick="closeUpdateStatus()" style="position: absolute; top: 15px; right: 20px; font-size: 28px; cursor: pointer; color: #999;">&times;</span>
            <h2 style="margin-bottom: 20px;">Cập nhật trạng thái đơn hàng</h2>
            <form id="updateStatusForm" method="post" action="index.php?page=orders">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="update_order_id" name="id" value="">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Trạng thái mới:</label>
                    <select name="status_id" id="update_status_id" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;" required>
                        <option value="">-- Chọn trạng thái --</option>
                        <?php 
                        // Lấy lại danh sách status để đảm bảo có dữ liệu
                        $statuses_list = mysqli_query($conn, "SELECT status_id, status_name FROM order_status ORDER BY status_id");
                        if ($statuses_list):
                            while ($st = mysqli_fetch_assoc($statuses_list)): 
                        ?>
                            <option value="<?php echo $st['status_id']; ?>">
                                <?php echo h($st['status_name']); ?>
                            </option>
                        <?php 
                            endwhile;
                        endif; 
                        ?>
                    </select>
                </div>
                <div style="text-align: right;">
                    <button type="button" onclick="closeUpdateStatus()" style="padding: 10px 20px; background: #95a5a6; color: #fff; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
                        Hủy
                    </button>
                    <button type="submit" style="padding: 10px 20px; background: #27ae60; color: #fff; border: none; border-radius: 5px; cursor: pointer;">
                        Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewOrderDetail(orderId) {
            // Tạo request để lấy chi tiết đơn hàng
            fetch('index.php?page=orders&action=detail&id=' + orderId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('orderDetailContent').innerHTML = html;
                    document.getElementById('orderDetailModal').style.display = 'block';
                })
                .catch(error => {
                    alert('Không thể tải chi tiết đơn hàng');
                });
        }

        function closeOrderDetail() {
            document.getElementById('orderDetailModal').style.display = 'none';
        }

        function updateOrderStatus(orderId, currentStatusId) {
            document.getElementById('update_order_id').value = orderId;
            // Set giá trị mặc định là trạng thái hiện tại
            const statusSelect = document.getElementById('update_status_id');
            statusSelect.value = currentStatusId;
            document.getElementById('updateStatusModal').style.display = 'block';
        }

        function closeUpdateStatus() {
            document.getElementById('updateStatusModal').style.display = 'none';
        }

        // Đóng modal khi click bên ngoài
        window.onclick = function(event) {
            const detailModal = document.getElementById('orderDetailModal');
            const statusModal = document.getElementById('updateStatusModal');
            if (event.target == detailModal) {
                closeOrderDetail();
            }
            if (event.target == statusModal) {
                closeUpdateStatus();
            }
        }
    </script>
</body>
</html>
