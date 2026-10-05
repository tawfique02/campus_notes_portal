<?php
/**
 * Registration Portal - Sign Up
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pageTitle = "Register - Campus Notes Portal";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$pdo = Database::getConnection();
$departments = $pdo->query("SELECT * FROM `departments` ORDER BY `dept_name` ASC")->fetchAll();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border rounded-4 p-4 p-md-5 bg-white shadow-sm">
                <div class="text-center mb-4">
                    <div class="brand-badge mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h3 class="font-weight-bold text-dark mb-1">Create an Account</h3>
                    <p class="text-secondary small">Join our university note sharing community</p>
                </div>

                <?php displayFlash(); ?>

                <form action="<?= BASE_URL ?>/actions/auth_action.php" method="POST">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="register">

                    <!-- Role Selection -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">I am joining as a:</label>
                        <div class="d-flex gap-3">
                            <div class="form-check flex-fill border rounded-3 p-2 ps-4">
                                <input class="form-check-input" type="radio" name="role_id" id="role_student" value="<?= ROLE_STUDENT ?>" checked>
                                <label class="form-check-label fw-bold font-sm" for="role_student">
                                    <i class="fa-solid fa-user-graduate text-primary me-1"></i> Student
                                </label>
                            </div>
                            <div class="form-check flex-fill border rounded-3 p-2 ps-4">
                                <input class="form-check-input" type="radio" name="role_id" id="role_faculty" value="<?= ROLE_FACULTY ?>">
                                <label class="form-check-label fw-bold font-sm" for="role_faculty">
                                    <i class="fa-solid fa-chalkboard-user text-success me-1"></i> Faculty
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Full Name -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Robert Fox" required>
                    </div>

                    <!-- Email Address -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">University Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="username@campus.edu" required>
                    </div>

                    <!-- Department & Academic ID -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Department</label>
                            <select name="dept_id" class="form-select" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['dept_id'] ?>"><?= htmlspecialchars($d['dept_code']) ?> - <?= htmlspecialchars($d['dept_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Student / Faculty ID</label>
                            <input type="text" name="academic_id" class="form-control" placeholder="e.g. 2026-CSE-101" required>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="reg_password" class="form-control border-end-0" placeholder="Minimum 6 characters" autocomplete="new-password" required>
                            <button type="button" class="btn btn-light border border-start-0" onclick="toggleRegPassword()">
                                <i class="fa-solid fa-eye text-muted" id="regPasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2 font-weight-bold">
                        <i class="fa-solid fa-circle-check me-1"></i> Complete Registration
                    </button>


                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="text-secondary small mb-0">
                        Already have an account? <a href="<?= BASE_URL ?>/login.php" class="fw-bold text-primary">Sign In</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRegPassword() {
    const p = document.getElementById('reg_password');
    const icon = document.getElementById('regPasswordIcon');
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
