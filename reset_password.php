<?php
$pageTitle = "Reset Password - Campus Notes Portal";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth_middleware.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if (!isset($_GET['token'])) {
    setFlash('error', 'Invalid password reset link.');
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$token = $_GET['token'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border rounded-4 p-4 p-md-5 bg-white shadow-sm">
                <div class="text-center mb-4">
                    <div class="brand-badge mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="fa-solid fa-unlock-keyhole"></i>
                    </div>
                    <h3 class="font-weight-bold text-dark mb-1">Set New Password</h3>
                    <p class="text-secondary small">Enter your new password below.</p>
                </div>

                <?php displayFlash(); ?>

                <form action="<?= BASE_URL ?>/actions/password_action.php" method="POST">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="Minimum 6 characters" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-check text-muted"></i></span>
                            <input type="password" name="confirm_password" class="form-control border-start-0 ps-0" placeholder="Confirm your new password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2 mt-2 font-weight-bold">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save New Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
