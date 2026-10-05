<?php
/**
 * User Profile Settings Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/includes/helpers.php';
requireAuth();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
$pdo = Database::getConnection();
$user = getCurrentUser();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid. Please refresh.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';

        try {
            if (!empty($newPassword)) {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE `users` SET `full_name` = ?, `bio` = ?, `phone` = ?, `password_hash` = ? WHERE `user_id` = ?");
                $stmt->execute([$fullName, $bio, $phone, $hash, $user['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE `users` SET `full_name` = ?, `bio` = ?, `phone` = ? WHERE `user_id` = ?");
                $stmt->execute([$fullName, $bio, $phone, $user['id']]);
            }

            $_SESSION['full_name'] = $fullName;
            $message = 'Your profile details have been updated.';
        } catch (Exception $e) {
            logError($e, 'Profile Update Error');
            $error = 'Failed to update profile due to a system error. Please try again.';
        }
    }
}

// Fetch full user record
$stmt = $pdo->prepare("SELECT u.*, r.role_name, r.display_name AS role_display, d.dept_name, d.dept_code 
                       FROM `users` u 
                       JOIN `roles` r ON u.role_id = r.role_id 
                       LEFT JOIN `departments` d ON u.dept_id = d.dept_id 
                       WHERE u.user_id = ?");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border rounded-4 p-4 p-md-5 bg-white shadow-sm">
                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                    <div class="user-avatar-circle" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <?= strtoupper(substr($profile['full_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <h4 class="font-weight-bold text-dark mb-0"><?= htmlspecialchars($profile['full_name']) ?></h4>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($profile['role_display']) ?></span>
                            <?php if ($profile['dept_code']): ?>
                                <span class="badge bg-light text-secondary border"><?= htmlspecialchars($profile['dept_code']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <?= Security::csrfField() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($profile['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">University Email</label>
                            <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($profile['email']) ?>" readonly>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Academic ID</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($profile['academic_id'] ?? 'N/A') ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" placeholder="+880 1XXX-XXXXXX">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Academic Bio / Research Area</label>
                        <textarea name="bio" class="form-control" rows="3" placeholder="Tell other scholars about your academic interests..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Change Password (leave empty to keep current)</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="profile_password" class="form-control border-end-0" placeholder="New strong password" autocomplete="new-password">
                            <button type="button" class="btn btn-light border border-start-0" onclick="toggleProfilePassword()">
                                <i class="fa-solid fa-eye text-muted" id="profilePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary-custom px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleProfilePassword() {
    const p = document.getElementById('profile_password');
    const icon = document.getElementById('profilePasswordIcon');
    if (p.type === 'password') {
        p.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        p.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
