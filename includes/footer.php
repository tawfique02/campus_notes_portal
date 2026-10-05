<?php
/**
 * Master Footer Component — BDSEC Theme
 */
require_once __DIR__ . '/../config/config.php';
?>
<footer class="footer-custom mt-auto">
    <div class="container">
        <div class="row g-4">

            <!-- Brand Column -->
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="brand-badge" style="width:44px;height:44px;font-size:1.2rem;">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div>
                        <div class="footer-brand-title">CampusNotes</div>
                        <div style="font-size:0.78rem;color:#4ade80;font-weight:600;">Academic Resource Portal</div>
                    </div>
                </div>
                <p style="color:#8da8c4;font-size:0.88rem;line-height:1.7;padding-right:1rem;">
                    The centralized academic repository for university courses — lecture notes, lab sheets, question banks, and exam archives shared by faculty and peers.
                </p>
                <div class="d-flex gap-2 mt-3">
                    <a href="#" class="footer-social-link fb"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="footer-social-link in"><i class="fa-brands fa-linkedin-in"></i></a>
                    <a href="#" class="footer-social-link gh"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="footer-social-link yt"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-6 col-lg-2">
                <h5>Quick Links</h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><a href="<?= BASE_URL ?>/index.php"><i class="fa-solid fa-chevron-right me-1" style="font-size:0.65rem;color:#4ade80;"></i>Home</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/explore.php"><i class="fa-solid fa-chevron-right me-1" style="font-size:0.65rem;color:#4ade80;"></i>Explore Notes</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="mb-2"><a href="<?= BASE_URL ?>/profile.php"><i class="fa-solid fa-chevron-right me-1" style="font-size:0.65rem;color:#4ade80;"></i>My Profile</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>/actions/auth_action.php?action=logout" style="color:#f87171;"><i class="fa-solid fa-chevron-right me-1" style="font-size:0.65rem;"></i>Logout</a></li>
                    <?php else: ?>
                        <li class="mb-2"><a href="<?= BASE_URL ?>/login.php"><i class="fa-solid fa-chevron-right me-1" style="font-size:0.65rem;color:#4ade80;"></i>Login</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>/register.php"><i class="fa-solid fa-chevron-right me-1" style="font-size:0.65rem;color:#4ade80;"></i>Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Departments -->
            <div class="col-6 col-lg-3">
                <h5>Departments</h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><a href="<?= BASE_URL ?>/explore.php?dept=1"><i class="fa-solid fa-chevron-right me-1" style="font-size:0.65rem;color:#4ade80;"></i>Comp. Science & Eng.</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/explore.php?dept=2"><i class="fa-solid fa-chevron-right me-1" style="font-size:0.65rem;color:#4ade80;"></i>Electrical & Electronic</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/explore.php?dept=3"><i class="fa-solid fa-chevron-right me-1" style="font-size:0.65rem;color:#4ade80;"></i>Business Administration</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/explore.php?dept=4"><i class="fa-solid fa-chevron-right me-1" style="font-size:0.65rem;color:#4ade80;"></i>Law & Jurisprudence</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/explore.php?dept=5"><i class="fa-solid fa-chevron-right me-1" style="font-size:0.65rem;color:#4ade80;"></i>Mathematics & Physics</a></li>
                </ul>
            </div>

            <!-- Tech Info -->
            <div class="col-lg-3">
                <h5>DBMS Architecture</h5>
                <p style="color:#8da8c4;font-size:0.88rem;line-height:1.7;margin-bottom:1rem;">
                    Engineered with <strong style="color:#a5f3c0;">3NF Normalization</strong>, Referential Cascades, Triggers, Analytical Views & Stored Procedures.
                </p>
                <div class="dbms-badge mb-2">
                    <i class="fa-solid fa-server"></i> MySQL (InnoDB) + PHP 8+
                </div>
                <div class="dbms-badge">
                    <i class="fa-solid fa-shield-halved"></i> Role-Based Access Control
                </div>
            </div>
        </div>

        <hr class="footer-divider">

        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-2" style="color:#4a6480;font-size:0.85rem;">
            <p class="mb-0">&copy; <?= date('Y') ?> Campus Academic Resource & Notes Sharing Portal. All rights reserved.</p>
            <p class="mb-0">
                <i class="fa-solid fa-code me-1" style="color:#4ade80;"></i>
                Designed for DBMS Project Evaluation
            </p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main App Interactivity Script -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

<!-- Counter Animation Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Animated number counters
    const counters = document.querySelectorAll('[data-target]');
    if (counters.length > 0) {
        const animateCounter = (el) => {
            const target = parseInt(el.getAttribute('data-target')) || 0;
            if (target === 0) { el.textContent = '0'; return; }
            let current = 0;
            const step = Math.max(1, Math.floor(target / 60));
            const timer = setInterval(() => {
                current = Math.min(current + step, target);
                el.textContent = current.toLocaleString();
                if (current >= target) clearInterval(timer);
            }, 25);
        };
        if ('IntersectionObserver' in window) {
            const obs = new IntersectionObserver((entries) => {
                entries.forEach(e => { if (e.isIntersecting) { animateCounter(e.target); obs.unobserve(e.target); } });
            }, { threshold: 0.5 });
            counters.forEach(c => obs.observe(c));
        } else {
            counters.forEach(c => animateCounter(c));
        }
    }

    // Navbar scroll shadow effect
    const navbar = document.querySelector('.navbar-custom');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                navbar.style.boxShadow = '0 4px 20px rgba(0,51,102,0.15)';
            } else {
                navbar.style.boxShadow = '0 2px 12px rgba(0,51,102,0.08)';
            }
        });
    }
});
</script>
</body>
</html>


