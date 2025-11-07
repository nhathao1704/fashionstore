// Toggle sidebar menu trên mobile
const menuIcon = document.getElementById("menuicn");
const nav = document.getElementById("nav");

if (menuIcon && nav) {
  menuIcon.addEventListener("click", () => {
    nav.classList.toggle("active");
  });

  // Đóng sidebar khi click bên ngoài trên mobile
  document.addEventListener("click", (e) => {
    if (window.innerWidth <= 768) {
      if (!nav.contains(e.target) && !menuIcon.contains(e.target)) {
        nav.classList.remove("active");
      }
    }
  });
}

// Kiểm tra đăng nhập admin (có thể mở rộng sau)
function checkAdminAuth() {
  // Tạm thời cho phép truy cập tự do
  // Sau này có thể thêm logic kiểm tra quyền admin
  return true;
}

// Format số tiền
function formatCurrency(amount) {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND'
  }).format(amount);
}

// Format ngày tháng
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  });
}

// Load dữ liệu từ localStorage
function getUsers() {
  return JSON.parse(localStorage.getItem('users') || '[]');
}

function getOrders() {
  return JSON.parse(localStorage.getItem('orders') || '[]');
}

function getProducts() {
  return JSON.parse(localStorage.getItem('products') || '[]');
}

// Lưu dữ liệu vào localStorage
function saveUsers(users) {
  localStorage.setItem('users', JSON.stringify(users));
}

function saveOrders(orders) {
  localStorage.setItem('orders', JSON.stringify(orders));
}

function saveProducts(products) {
  localStorage.setItem('products', JSON.stringify(products));
}

// Thống kê dashboard
function updateDashboardStats() {
  const users = getUsers();
  const orders = getOrders();
  const products = getProducts();

  // Cập nhật số liệu nếu có các element tương ứng
  const totalOrdersEl = document.querySelector('.box1 .topic-heading');
  const completedOrdersEl = document.querySelector('.box2 .topic-heading');
  const totalProductsEl = document.querySelector('.box3 .topic-heading');
  const totalUsersEl = document.querySelector('.box4 .topic-heading');

  if (totalOrdersEl) totalOrdersEl.textContent = orders.length;
  if (completedOrdersEl) {
    const completed = orders.filter(o => o.status === 'completed').length;
    completedOrdersEl.textContent = completed;
  }
  if (totalProductsEl) totalProductsEl.textContent = products.length;
  if (totalUsersEl) totalUsersEl.textContent = users.length;
}

// Initialize dữ liệu mẫu nếu chưa có
function initSampleData() {
  // Chỉ init nếu chưa có dữ liệu
  if (!localStorage.getItem('ordersInitialized')) {
    const sampleOrders = [
      {
        id: 1001,
        customer: 'Nguyễn Văn A',
        products: 'Áo thun (2)',
        total: 500000,
        date: '2025-10-12',
        status: 'completed'
      },
      {
        id: 1002,
        customer: 'Lê Thị B',
        products: 'Quần jean (1), Áo sơ mi (1)',
        total: 750000,
        date: '2025-10-13',
        status: 'pending'
      },
      {
        id: 1003,
        customer: 'Trần Văn C',
        products: 'Áo thun (3)',
        total: 750000,
        date: '2025-10-13',
        status: 'cancelled'
      }
    ];
    saveOrders(sampleOrders);
    localStorage.setItem('ordersInitialized', 'true');
  }

  if (!localStorage.getItem('productsInitialized')) {
    const sampleProducts = [
      {
        id: 1,
        name: 'Áo Thun Nam',
        price: 250000,
        stock: 50,
        sizes: 'M, L, XL',
        colors: 'Trắng, Đen',
        status: 'active'
      },
      {
        id: 2,
        name: 'Quần Jeans',
        price: 400000,
        stock: 30,
        sizes: '29, 30, 31',
        colors: 'Xanh đen',
        status: 'active'
      },
      {
        id: 3,
        name: 'Áo Sơ Mi',
        price: 350000,
        stock: 5,
        sizes: 'M, L',
        colors: 'Trắng, Xanh',
        status: 'warning'
      },
      {
        id: 4,
        name: 'Quần Kaki',
        price: 320000,
        stock: 0,
        sizes: '30, 31, 32',
        colors: 'Be, Xám',
        status: 'danger'
      }
    ];
    saveProducts(sampleProducts);
    localStorage.setItem('productsInitialized', 'true');
  }
}

// Khởi tạo khi trang load
document.addEventListener('DOMContentLoaded', () => {
  // Init dữ liệu mẫu
  initSampleData();
  
  // Cập nhật dashboard stats nếu đang ở trang dashboard
  if (document.getElementById('dashboard')) {
    updateDashboardStats();
  }

  // Highlight active menu
  const currentPage = window.location.pathname.split('/').pop();
  const navOptions = document.querySelectorAll('.nav-option');
  
  navOptions.forEach(option => {
    const link = option.querySelector('a');
    if (link && link.getAttribute('href') === currentPage) {
      option.classList.add('option1');
    }
  });
});

// Export functions để có thể sử dụng ở các file khác
window.adminUtils = {
  formatCurrency,
  formatDate,
  getUsers,
  getOrders,
  getProducts,
  saveUsers,
  saveOrders,
  saveProducts
};