// Hàm toggle hiển thị mật khẩu
function togglePassword(fieldId) {
    const field = fieldId ? document.getElementById(fieldId) : document.getElementById('password');
    if (field) {
        if (field.type === 'password') {
            field.type = 'text';
        } else {
            field.type = 'password';
        }
    }
}

// Hàm hiển thị thông báo
function showMessage(message, isError = false) {
    const msgElement = document.getElementById('successMessage');
    if (msgElement) {
        msgElement.textContent = message;
        msgElement.className = 'success-message show ' + (isError ? 'error' : 'success');
        
        setTimeout(() => {
            msgElement.classList.remove('show');
        }, 3000);
    }
}

// Hàm validate email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Hàm validate mật khẩu
function isValidPassword(password) {
    return password.length >= 6;
}

// Hàm quên mật khẩu
function forgotPassword() {
    alert('Tính năng quên mật khẩu sẽ được phát triển sau!\nVui lòng liên hệ với chúng tôi qua email: fashionstore@gmail.com');
}

// Xử lý đăng nhập
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        // Lấy danh sách người dùng từ localStorage
        const users = JSON.parse(localStorage.getItem('users') || '[]');
        
        // Tìm người dùng khớp
        const user = users.find(u => u.username === username && u.password === password);
        
        if (user) {
            showMessage('Đăng nhập thành công! Chào mừng ' + user.fullname);
            
            // Lưu thông tin đăng nhập
            localStorage.setItem('currentUser', JSON.stringify(user));
            
            // Chuyển về trang chủ sau 1.5 giây
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 1500);
        } else {
            showMessage('Tên đăng nhập hoặc mật khẩu không đúng!', true);
        }
    });
}

// Xử lý đăng ký
const signupForm = document.getElementById('signupForm');
if (signupForm) {
    signupForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fullname = document.getElementById('fullname').value.trim();
        const email = document.getElementById('email').value.trim();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        // Validate email
        if (!isValidEmail(email)) {
            showMessage('Email không hợp lệ!', true);
            return;
        }
        
        // Validate độ dài mật khẩu
        if (!isValidPassword(password)) {
            showMessage('Mật khẩu phải có ít nhất 6 ký tự!', true);
            return;
        }
        
        // Kiểm tra mật khẩu khớp
        if (password !== confirmPassword) {
            showMessage('Mật khẩu xác nhận không khớp!', true);
            return;
        }
        
        // Lấy danh sách người dùng từ localStorage
        const users = JSON.parse(localStorage.getItem('users') || '[]');
        
        // Kiểm tra username đã tồn tại
        if (users.find(u => u.username === username)) {
            showMessage('Tên đăng nhập đã tồn tại! Vui lòng chọn tên khác.', true);
            return;
        }
        
        // Kiểm tra email đã tồn tại
        if (users.find(u => u.email === email)) {
            showMessage('Email đã được sử dụng! Vui lòng sử dụng email khác.', true);
            return;
        }
        
        // Tạo người dùng mới
        const newUser = {
            id: Date.now(),
            fullname: fullname,
            email: email,
            username: username,
            password: password,
            createdAt: new Date().toISOString()
        };
        
        // Lưu người dùng mới
        users.push(newUser);
        localStorage.setItem('users', JSON.stringify(users));
        
        showMessage('Đăng ký thành công! Đang chuyển đến trang đăng nhập...');
        
        // Reset form
        signupForm.reset();
        
        // Chuyển đến trang đăng nhập sau 2 giây
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 2000);
    });
}

// Hiển thị thông tin user nếu đã đăng nhập
function updateAuthDisplay() {
    const currentUser = localStorage.getItem('currentUser');
    const authDiv = document.querySelector('.auth');
    
    if (currentUser && authDiv) {
        const user = JSON.parse(currentUser);
        authDiv.innerHTML = `
            <div class="user-info show">
                <span class="user-name">👤 ${user.fullname}</span>
                <button class="logout-btn" onclick="logout()">Đăng xuất</button>
            </div>
        `;
    }
}

// Đăng xuất
function logout() {
    if (confirm('Bạn có chắc muốn đăng xuất?')) {
        localStorage.removeItem('currentUser');
        window.location.href = 'index.html';
    }
}

// Cập nhật hiển thị khi trang load
document.addEventListener('DOMContentLoaded', updateAuthDisplay);