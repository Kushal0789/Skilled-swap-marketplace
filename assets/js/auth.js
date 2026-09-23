/**
 * Authentication Client Controller
 * Login & Registration Validation & AJAX Handling
 */

document.addEventListener('DOMContentLoaded', () => {
    // Password visibility toggle
    document.querySelectorAll('.password-toggle-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('input');
            if (input) {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.style.opacity = isPassword ? '1' : '0.6';
            }
        });
    });

    // Login Form Handler
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const identity = loginForm.querySelector('[name="identity"]').value.trim();
            const password = loginForm.querySelector('[name="password"]').value;
            const submitBtn = loginForm.querySelector('button[type="submit"]');

            if (!identity || !password) {
                showToast('Please enter both your email/username and password.', 'error');
                return;
            }

            setBtnLoading(submitBtn, true, 'Signing in...');

            const res = await window.apiFetch('api/auth/login.php', {
                method: 'POST',
                body: { identity, password }
            });

            setBtnLoading(submitBtn, false, 'Sign In');

            if (res.success) {
                showToast(res.message, 'success', 'Welcome');
                setTimeout(() => {
                    const params = new URLSearchParams(window.location.search);
                    const redirect = params.get('redirect') || res.data.redirect_to || 'dashboard.php';
                    window.location.href = redirect;
                }, 800);
            } else {
                showToast(res.message || 'Login failed.', 'error');
                if (res.errors) {
                    displayFieldErrors(loginForm, res.errors);
                }
            }
        });
    }

    // Register Form Handler
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const name = registerForm.querySelector('[name="name"]').value.trim();
            const username = registerForm.querySelector('[name="username"]').value.trim();
            const email = registerForm.querySelector('[name="email"]').value.trim();
            const password = registerForm.querySelector('[name="password"]').value;
            const confirmPassword = registerForm.querySelector('[name="confirm_password"]').value;
            const submitBtn = registerForm.querySelector('button[type="submit"]');

            // Quick client check
            if (password !== confirmPassword) {
                showToast('Passwords do not match.', 'error');
                displayFieldError(registerForm, 'confirm_password', 'Passwords do not match.');
                return;
            }

            setBtnLoading(submitBtn, true, 'Creating Account...');

            const res = await window.apiFetch('api/auth/register.php', {
                method: 'POST',
                body: { name, username, email, password, confirm_password: confirmPassword }
            });

            setBtnLoading(submitBtn, false, 'Create Free Account');

            if (res.success) {
                showToast(res.message, 'success', 'Account Created');
                setTimeout(() => {
                    window.location.href = res.data.redirect_to || 'profile.php?welcome=1';
                }, 1000);
            } else {
                showToast(res.message || 'Registration failed.', 'error');
                if (res.errors) {
                    displayFieldErrors(registerForm, res.errors);
                }
            }
        });
    }

    function setBtnLoading(btn, isLoading, text) {
        if (!btn) return;
        btn.disabled = isLoading;
        if (isLoading) {
            btn.innerHTML = `<span class="spinner"></span> <span>${text}</span>`;
        } else {
            btn.innerHTML = text;
        }
    }

    function displayFieldError(form, fieldName, message) {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (field) {
            const group = field.closest('.form-group') || field.parentElement;
            let errEl = group.querySelector('.form-error');
            if (!errEl) {
                errEl = document.createElement('div');
                errEl.className = 'form-error';
                group.appendChild(errEl);
            }
            errEl.textContent = message;
            field.classList.add('has-error');
        }
    }

    function displayFieldErrors(form, errors) {
        if (typeof errors === 'object') {
            for (const [key, msg] of Object.entries(errors)) {
                displayFieldError(form, key, Array.isArray(msg) ? msg[0] : msg);
            }
        }
    }

    function clearErrors() {
        document.querySelectorAll('.form-error').forEach(el => el.remove());
        document.querySelectorAll('.has-error').forEach(el => el.classList.remove('has-error'));
    }
});
