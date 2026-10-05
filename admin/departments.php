<?php
/**
 * Department & Course Catalog Management
 */

$pageTitle = "Departments & Courses Management";
$isDashboard = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole([ROLE_ADMIN, ROLE_MODERATOR]);
$pdo = Database::getConnection();

// Fetch Departments
$departments = $pdo->query("SELECT d.*, COUNT(c.course_id) AS course_count 
                            FROM `departments` d 
                            LEFT JOIN `courses` c ON d.dept_id = c.dept_id 
                            GROUP BY d.dept_id 
                            ORDER BY d.dept_name ASC")->fetchAll();

// Fetch Courses with Semester & Dept
$courses = $pdo->query("SELECT c.*, d.dept_code, d.dept_name, s.semester_name 
                        FROM `courses` c 
                        JOIN `departments` d ON c.dept_id = d.dept_id 
                        JOIN `semesters` s ON c.semester_id = s.semester_id 
                        ORDER BY d.dept_code, c.course_code ASC")->fetchAll();

$semesters = $pdo->query("SELECT * FROM `semesters` ORDER BY `semester_id` ASC")->fetchAll();
?>

<div class="dashboard-wrapper">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="<?= BASE_URL ?>/admin/index.php" class="sidebar-brand"><i class="fa-solid fa-shield-halved text-primary"></i> Management</a>
        </div>
        <div class="sidebar-user">
            <div class="user-avatar-circle"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></div>
            <div class="user-info-text">
                <div class="user-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                <div class="user-role"><?= htmlspecialchars($currentUser['role_name']) ?></div>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?= BASE_URL ?>/admin/index.php" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <?php if ($currentUser['role_id'] == ROLE_ADMIN): ?>
                <li><a href="<?= BASE_URL ?>/admin/users.php" class="sidebar-link"><i class="fa-solid fa-users-gear"></i> User Management</a></li>
            <?php endif; ?>
            <li><a href="<?= BASE_URL ?>/admin/departments.php" class="sidebar-link active"><i class="fa-solid fa-building-columns"></i> Depts & Courses</a></li>
            <li><a href="<?= BASE_URL ?>/admin/resources.php" class="sidebar-link"><i class="fa-solid fa-folder-tree"></i> Note Moderation</a></li>
            <li><a href="<?= BASE_URL ?>/admin/logs.php" class="sidebar-link"><i class="fa-solid fa-clock-rotate-left"></i> Audit Trail</a></li>
            <li><a href="<?= BASE_URL ?>/admin/db_status.php" class="sidebar-link"><i class="fa-solid fa-database"></i> Database Diagnostics</a></li>
            <li><a href="<?= BASE_URL ?>/index.php" class="sidebar-link"><i class="fa-solid fa-arrow-left"></i> Public Portal</a></li>
        </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Academic Structure & Catalog</h1>
                <p class="text-secondary small mb-0">Manage university departments, course codes, and curriculum mapping.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-custom btn-sm" data-bs-toggle="modal" data-bs-target="#addDeptModal">
                    <i class="fa-solid fa-plus me-1"></i> Add Department
                </button>
                <button class="btn btn-primary-custom btn-sm" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                    <i class="fa-solid fa-plus me-1"></i> Register Course
                </button>
            </div>
        </div>

        <?php displayFlash(); ?>

        <!-- DEPARTMENTS LIST -->
        <div class="data-card mb-4">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-building-columns text-primary me-2"></i> University Departments (<?= count($departments) ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Department Name</th>
                            <th>Description</th>
                            <th>Total Courses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td><strong class="text-primary"><?= htmlspecialchars($dept['dept_code']) ?></strong></td>
                                <td class="font-weight-bold text-dark"><?= htmlspecialchars($dept['dept_name']) ?></td>
                                <td class="text-secondary small"><?= htmlspecialchars($dept['description']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= $dept['course_count'] ?> Courses</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- COURSES LIST -->
        <div class="data-card">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-book-open text-success me-2"></i> Course Catalog (<?= count($courses) ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Title</th>
                            <th>Department</th>
                            <th>Semester</th>
                            <th>Credits</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td><span class="course-badge"><?= htmlspecialchars($c['course_code']) ?></span></td>
                                <td class="font-weight-bold text-dark"><?= htmlspecialchars($c['course_title']) ?></td>
                                <td><?= htmlspecialchars($c['dept_code']) ?></td>
                                <td><?= htmlspecialchars($c['semester_name']) ?></td>
                                <td><?= $c['credit_hours'] ?>.0</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- ADD DEPARTMENT MODAL -->
<div class="modal fade" id="addDeptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/actions/admin_action.php" method="POST">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="add_department">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold">Add Academic Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Department Code (e.g. CSE)</label>
                        <input type="text" name="dept_code" class="form-control" placeholder="CSE" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Department Name</label>
                        <input type="text" name="dept_name" class="form-control" placeholder="Computer Science & Engineering" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Create Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ADD COURSE MODAL -->
<div class="modal fade" id="addCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/actions/admin_action.php" method="POST">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="add_course">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold">Register New Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Department</label>
                        <select name="dept_id" class="form-select" required>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['dept_id'] ?>"><?= htmlspecialchars($d['dept_code']) ?> - <?= htmlspecialchars($d['dept_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Semester</label>
                        <select name="semester_id" class="form-select" required>
                            <?php foreach ($semesters as $s): ?>
                                <option value="<?= $s['semester_id'] ?>"><?= htmlspecialchars($s['semester_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label small fw-bold">Course Code</label>
                            <input type="text" name="course_code" class="form-control" placeholder="e.g. CSE311" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold">Credits</label>
                            <input type="number" step="0.5" name="credit_hours" class="form-control" value="3.0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Course Title</label>
                        <input type="text" name="course_title" class="form-control" placeholder="Database Management Systems" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Syllabus Summary</label>
                        <textarea name="syllabus_summary" class="form-control" rows="2" placeholder="Topics covered..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Register Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
