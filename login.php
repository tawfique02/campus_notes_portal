<?php
/**
 * Authentication Portal - Sign In
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth_middleware.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pageTitle = "Sign In - Campus Notes Portal";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border rounded-4 p-4 p-md-5 bg-white shadow-sm">
                <div class="text-center mb-4">
                    <div class="brand-badge mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <h3 class="font-weight-bold text-dark mb-1">Welcome Back</h3>
                    <p class="text-secondary small">Sign in to your CampusNotes academic account</p>
                </div>

                <?php displayFlash(); ?>

                <form action="<?= BASE_URL ?>/actions/auth_action.php" method="POST" id="loginForm">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="login">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
                            <input type="email" name="email" id="login_email" class="form-control border-start-0 ps-0" placeholder="student@campus.edu" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label class="form-label small fw-bold text-secondary">Password</label>
                            <a href="<?= BASE_URL ?>/forgot_password.php" class="small fw-bold text-primary text-decoration-none">Forgot Password?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" name="password" id="login_password" class="form-control border-start-0 border-end-0 ps-0" placeholder="Enter your password" autocomplete="current-password" required>
                            <button type="button" class="btn btn-light border border-start-0" id="togglePasswordBtn" onclick="togglePasswordVisibility()">
                                <i class="fa-solid fa-eye text-muted" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2 mt-2 font-weight-bold">
                        <i class="fa-solid fa-right-to-bracket me-1"></i> Sign In to Portal
                    </button>
                    

                </form>



                <div class="text-center mt-4 pt-3 border-top">
                    <p class="text-secondary small mb-0">
                        Don't have an account? <a href="<?= BASE_URL ?>/register.php" class="fw-bold text-primary">Register Here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>


function togglePasswordVisibility() {
    const passwordInput = document.getElementById('login_password');
    const icon = document.getElementById('togglePasswordIcon');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
