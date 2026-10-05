<?php
/**
 * Resource Upload Portal (Faculty, Admin, & Student)
 */

$pageTitle = "Upload Academic Resource";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireAuth();
$pdo = Database::getConnection();

// Determine Dynamic Dashboard Return Link based on User Role
$dashboardUrl = BASE_URL . '/faculty/index.php';
if ($currentUser['role_id'] == ROLE_ADMIN) {
    $dashboardUrl = BASE_URL . '/admin/index.php';
} elseif ($currentUser['role_id'] == ROLE_MODERATOR) {
    $dashboardUrl = BASE_URL . '/moderator/index.php';
} elseif ($currentUser['role_id'] == ROLE_STUDENT) {
    $dashboardUrl = BASE_URL . '/student/index.php';
}

// Fetch Courses and Categories
$courses = $pdo->query("SELECT c.*, d.dept_code, d.dept_name 
                        FROM `courses` c 
                        JOIN `departments` d ON c.dept_id = d.dept_id 
                        ORDER BY d.dept_code, c.course_code ASC")->fetchAll();

$categories = $pdo->query("SELECT * FROM `categories` ORDER BY `category_id` ASC")->fetchAll();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border rounded-4 p-4 p-md-5 bg-white shadow-sm">
                <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                    <div>
                        <h3 class="font-weight-bold text-dark mb-1">
                            <i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i> Upload Academic Material
                        </h3>
                        <p class="text-secondary small mb-0">Share lecture notes, question banks, or lab manuals with your university peers.</p>
                    </div>
                    <a href="<?= $dashboardUrl ?>" class="btn btn-outline-custom btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>

                <?php displayFlash(); ?>

                <form action="<?= BASE_URL ?>/actions/resource_action.php" method="POST" enctype="multipart/form-data">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="upload">

                    <!-- Document Title -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Document / Note Title *</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Relational Normalization & SQL Complete Chapter 3" required>
                    </div>

                    <!-- Course & Category Grid -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Course Code & Title *</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= $c['course_id'] ?>">
                                        [<?= htmlspecialchars($c['dept_code']) ?>] <?= htmlspecialchars($c['course_code']) ?>: <?= htmlspecialchars($c['course_title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Resource Category *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- File Attachment -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Attach Document (PDF, DOCX, PPTX, ZIP, TXT) *</label>
                        <input type="file" name="note_file" class="form-control" accept=".pdf,.docx,.pptx,.zip,.txt,.jpg,.png" required>
                        <div class="form-text small">Maximum file size: 25 MB. Formats supported: PDF, Word, PowerPoint, ZIP archive.</div>
                    </div>

                    <!-- Detailed Description -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Description & Chapter Details</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Mention the topics, solved questions, formulas, or semester exam years covered in this document..."></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-secondary small">
                            <i class="fa-solid fa-shield-halved text-success me-1"></i> Checked for academic data integrity
                        </div>
                        <button type="submit" class="btn btn-primary-custom px-4 py-2 font-weight-bold">
                            <i class="fa-solid fa-arrow-up-from-bracket me-1"></i> Publish Resource
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
