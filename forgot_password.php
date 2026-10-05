<?php
$pageTitle = "Forgot Password - Campus Notes Portal";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth_middleware.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border rounded-4 p-4 p-md-5 bg-white shadow-sm">
                <div class="text-center mb-4">
                    <div class="brand-badge mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <h3 class="font-weight-bold text-dark mb-1">Forgot Password</h3>
                    <p class="text-secondary small">Enter your email address to receive a password reset link.</p>
                </div>

                <?php displayFlash(); ?>

                <form action="<?= BASE_URL ?>/actions/password_action.php" method="POST">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="forgot_password">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="student@campus.edu" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2 mt-2 font-weight-bold">
                        <i class="fa-solid fa-paper-plane me-1"></i> Send Reset Link
                    </button>
                    
                    <div class="text-center mt-3">
                        <a href="<?= BASE_URL ?>/login.php" class="small text-decoration-none">Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
