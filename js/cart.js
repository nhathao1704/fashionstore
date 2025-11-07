// Cập nhật số lượng trong giỏ hàng
function updateCartCount(count) {
    const badge = document.querySelector('.cart-count');
    if (badge) {
        badge.textContent = count;
        // Hiệu ứng nhấp nháy khi cập nhật
        badge.style.transform = 'scale(1.2)';
        setTimeout(() => badge.style.transform = 'scale(1)', 200);
    }
}

// Hiển thị thông báo
function showMessage(message, isError = false) {
    const div = document.createElement('div');
    div.className = `message ${isError ? 'error' : 'success'}`;
    div.textContent = message;
    document.body.appendChild(div);
    
    // Auto hide sau 3s
    setTimeout(() => div.remove(), 3000);
}

// Thêm vào giỏ hàng
async function addToCart(formData) {
    try {
        const response = await fetch('/FashionStore2/FashionStore2/api/add_to_cart.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Có lỗi xảy ra');
        }
        
        // Cập nhật UI
        updateCartCount(result.data.cart_count);
        showMessage(result.data.message);
        
        return true;
    } catch (error) {
        showMessage(error.message, true);
        return false;
    }
}