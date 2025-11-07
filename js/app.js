document.addEventListener("DOMContentLoaded", () => {
    const menuToggle = document.querySelector(".menu-toggle");
    const menuClose = document.querySelector(".menu-close");
    const navbar = document.querySelector(".navbar");

    if (menuToggle && menuClose && navbar) {
        menuToggle.addEventListener("click", () => navbar.classList.add("active"));
        menuClose.addEventListener("click", () => navbar.classList.remove("active"));
    }

    function updateCartBadge() {
        const cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];
        const total = cartItems.reduce((sum, item) => sum + item.quantity, 0);
        const cartCountEl = document.querySelector(".cart-count");
        if (cartCountEl) {
            cartCountEl.textContent = total;
            cartCountEl.style.display = total > 0 ? "inline-block" : "none";
        }
    }
    updateCartBadge();
    const addToCartBtns = document.querySelectorAll(".add-to-cart");
    addToCartBtns.forEach((btn) => {
        btn.addEventListener("click", () => {
            const card = btn.closest(".product-card");
            const name = card.querySelector("h3").textContent.trim();
            const price = parseInt(card.querySelector(".price").textContent.replace(/[^\d]/g, ""));
            const image = card.querySelector("img").src;
            let cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];

            const existing = cartItems.find((p) => p.name === name);
            if (existing) {
                existing.quantity += 1;
            } else {
                cartItems.push({ name, price, image, quantity: 1 });
            }
            localStorage.setItem("cartItems", JSON.stringify(cartItems));
            updateCartBadge();

            if (typeof Swal !== "undefined") {
                Swal.fire({
                    icon: "success",
                    title: "Đã thêm vào giỏ hàng!",
                    text: `${name} đã được thêm.`,
                    showConfirmButton: false,
                    timer: 1500,
                });
            } else {
                alert(`${name} đã được thêm vào giỏ hàng!`);
            }
        });
    });

    const cartIcon = document.querySelector(".cart-icon");
    if (cartIcon) {
        cartIcon.addEventListener("click", () => {
            window.location.href = "cart.php";
        });
    }

    const searchInput = document.querySelector("header input[type='text']");
    if (searchInput) {
        searchInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                alert(`Tìm kiếm: ${searchInput.value}`);
            }
        });
    }

    const payBtn = document.querySelector(".checkout-actions .btn.primary");
    if (payBtn) {
        payBtn.addEventListener("click", () => {
            const nameInput = document.querySelector("input[placeholder='Nhập họ và tên']");
            if (!nameInput || nameInput.value.trim() === "") {
                alert("Vui lòng nhập đầy đủ thông tin khách hàng!");
                return;
            }
            alert("✅ Cảm ơn bạn đã thanh toán! Đơn hàng của bạn đang được xử lý.");
            localStorage.removeItem("cartItems");
            updateCartBadge();
            window.location.href = "index.php";
        });
    }

    const footerText = document.querySelector("footer p");
    if (footerText && footerText.textContent.includes("©")) {
        const year = new Date().getFullYear();
        footerText.textContent = `© ${year} Vogue Lane Clothing - Bản quyền thuộc về chúng tôi`;
    }

    function renderCartItems() {
        const cartTable = document.querySelector(".cart-content-left table");
        if (!cartTable) return;
        const cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];
        cartTable.innerHTML = `
        <tr>
            <th>Sản phẩm</th>
            <th>Tên sản phẩm</th>
            <th>Số lượng</th>
            <th>Thành tiền</th>
            <th>Xóa</th>
        </tr>
        `;

        let total = 0;

        cartItems.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;

            const row = document.createElement("tr");
            row.innerHTML = `
                <td><img src="${item.image}" alt="${item.name}" width="60"></td>
                <td>${item.name}</td>
                <td><input type="number" min="1" value="${item.quantity}" data-index="${index}" class="quantity-input"></td>
                <td>${itemTotal.toLocaleString()}đ</td>
                <td><button class="remove-item" data-index="${index}">X</button></td>
            `;
            cartTable.appendChild(row);
        });

        const totalCells = document.querySelectorAll(".cart-content-right td p");
        if (totalCells.length >= 2) {
            totalCells[0].textContent = total.toLocaleString() + "đ";
            totalCells[1].textContent = total.toLocaleString() + "đ";
        }

        document.querySelectorAll(".remove-item").forEach(btn => {
            btn.addEventListener("click", () => {
                const i = btn.dataset.index;
                cartItems.splice(i, 1);
                localStorage.setItem("cartItems", JSON.stringify(cartItems));
                renderCartItems();
                updateCartBadge();
            });
        });

        document.querySelectorAll(".quantity-input").forEach(input => {
            input.addEventListener("change", () => {
                const i = input.dataset.index;
                cartItems[i].quantity = parseInt(input.value);
                localStorage.setItem("cartItems", JSON.stringify(cartItems));
                renderCartItems();
                updateCartBadge();
            });
        });
    }

    if (window.location.pathname.includes("cart.php")) {
        renderCartItems();
    }

    console.log("✅ FashionStore app.js đã khởi động thành công!");
});
