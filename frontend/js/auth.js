// Authentication and Session Management

// Store user in localStorage for quick UI state
function setAuthSession(user) {
    localStorage.setItem('sb_user', JSON.stringify(user));
}

function getStoredUser() {
    const raw = localStorage.getItem('sb_user');
    try {
        return raw ? JSON.parse(raw) : null;
    } catch (e) {
        return null;
    }
}

function clearAuthSession() {
    localStorage.removeItem('sb_user');
}

// Redirect helpers
function getDashboardUrlByRole(role) {
    if (role === 'admin') return 'admin-dashboard.html';
    if (role === 'provider') return 'provider-dashboard.html';
    return 'user-dashboard.html';
}

function requireAuthGuard(allowedRoles = []) {
    const user = getStoredUser();
    if (!user) {
        window.location.href = 'login.html';
        return null;
    }
    if (allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
        alert('You do not have permission to access this page.');
        window.location.href = getDashboardUrlByRole(user.role);
        return null;
    }
    return user;
}

// Update common navbar across all pages
function renderNavbar() {
    const navRight = document.getElementById('nav-user-area');
    if (!navRight) return;

    const user = getStoredUser();

    if (user) {
        const dashUrl = getDashboardUrlByRole(user.role);
        let roleBadge = user.role.toUpperCase();
        if (user.role === 'provider') roleBadge = 'PROVIDER';

        navRight.innerHTML = `
            <a href="${dashUrl}" class="btn btn-outline btn-sm">Dashboard (${user.name})</a>
            <button onclick="handleLogout()" class="btn btn-danger btn-sm">Logout</button>
        `;
    } else {
        navRight.innerHTML = `
            <a href="login.html" class="btn btn-outline btn-sm">Login</a>
            <a href="register.html" class="btn btn-primary btn-sm">Register</a>
        `;
    }
}

// Global logout function
async function handleLogout() {
    await apiRequest('auth/logout.php', 'POST');
    clearAuthSession();
    window.location.href = 'index.html';
}

// Handle login submission
async function handleLoginSubmit(event) {
    event.preventDefault();
    const alertBox = document.getElementById('alert-box');
    alertBox.className = 'alert';
    alertBox.style.display = 'none';

    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value.trim();

    if (!email || !password) {
        showAlert('alert-box', 'Please enter email and password.', 'alert-error');
        return;
    }

    const res = await apiRequest('auth/login.php', 'POST', { email, password });

    if (res.ok && res.data.status === 'success') {
        const user = res.data.data.user;
        setAuthSession(user);
        window.location.href = getDashboardUrlByRole(user.role);
    } else {
        showAlert('alert-box', res.data.message || 'Login failed. Please check credentials.', 'alert-error');
    }
}

// Handle registration submission
async function handleRegisterSubmit(event, role) {
    event.preventDefault();
    const alertBox = document.getElementById('alert-box');
    alertBox.className = 'alert';
    alertBox.style.display = 'none';

    const name = document.getElementById('reg-name').value.trim();
    const email = document.getElementById('reg-email').value.trim();
    const phone = document.getElementById('reg-phone').value.trim();
    const password = document.getElementById('reg-password').value.trim();

    const payload = { name, email, phone, password, role };

    if (role === 'provider') {
        payload.service_id = document.getElementById('reg-service').value;
        payload.experience_years = document.getElementById('reg-experience').value;
        payload.hourly_rate = document.getElementById('reg-rate').value;
        payload.location = document.getElementById('reg-location').value.trim();
    }

    const res = await apiRequest('auth/register.php', 'POST', payload);

    if (res.ok && res.data.status === 'success') {
        showAlert('alert-box', res.data.message, 'alert-success');
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 1500);
    } else {
        showAlert('alert-box', res.data.message || 'Registration failed.', 'alert-error');
    }
}

// Helper to show alert message
function showAlert(elementId, message, typeClass) {
    const el = document.getElementById(elementId);
    if (!el) return;
    el.textContent = message;
    el.className = `alert ${typeClass} show`;
    el.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', () => {
    renderNavbar();
});
