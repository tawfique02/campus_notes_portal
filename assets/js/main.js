/**
 * Master JavaScript for Frontend Interactivity
 * Campus Notes Sharing Portal
 */

document.addEventListener('DOMContentLoaded', () => {

    // 0. Animated counter for hero statistics
    const counters = document.querySelectorAll('[data-target]');
    if (counters.length > 0) {
        const animateCounter = (el) => {
            const target = parseInt(el.getAttribute('data-target'), 10);
            if (isNaN(target) || target === 0) { el.textContent = '0'; return; }
            const duration = 1800;
            const step = Math.max(1, Math.ceil(target / (duration / 16)));
            let current = 0;
            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    el.textContent = target.toLocaleString();
                    clearInterval(timer);
                } else {
                    el.textContent = current.toLocaleString();
                }
            }, 16);
        };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) { animateCounter(e.target); observer.unobserve(e.target); }
            });
        }, { threshold: 0.5 });
        counters.forEach(c => observer.observe(c));
    }

    // 1. Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // 2. Interactive Star Rating Selector
    const starContainers = document.querySelectorAll('.interactive-star-rating');
    starContainers.forEach(container => {
        const stars = container.querySelectorAll('.star-btn');
        const input = container.querySelector('input[type="hidden"]');

        stars.forEach((star, index) => {
            star.addEventListener('mouseenter', () => {
                highlightStars(stars, index + 1);
            });

            star.addEventListener('click', () => {
                const val = index + 1;
                input.value = val;
                setActiveStars(stars, val);
            });
        });

        container.addEventListener('mouseleave', () => {
            const currentVal = parseInt(input.value) || 0;
            setActiveStars(stars, currentVal);
        });
    });

    function highlightStars(stars, count) {
        stars.forEach((s, idx) => {
            if (idx < count) {
                s.classList.remove('fa-regular', 'text-muted');
                s.classList.add('fa-solid', 'text-warning');
            } else {
                s.classList.remove('fa-solid', 'text-warning');
                s.classList.add('fa-regular', 'text-muted');
            }
        });
    }

    function setActiveStars(stars, count) {
        highlightStars(stars, count);
    }

    // 3. Dynamic Course Dropdown filter by Department
    const deptSelect = document.getElementById('dept_select');
    const courseSelect = document.getElementById('course_select');

    if (deptSelect && courseSelect) {
        deptSelect.addEventListener('change', () => {
            const selectedDept = deptSelect.value;
            const options = courseSelect.querySelectorAll('option');

            options.forEach(opt => {
                if (!opt.value) return; // Skip placeholder
                const optDept = opt.getAttribute('data-dept');
                if (!selectedDept || optDept === selectedDept) {
                    opt.style.display = 'block';
                } else {
                    opt.style.display = 'none';
                }
            });
            courseSelect.value = '';
        });
    }

    // 4. Bookmark Toggle with AJAX
    const bookmarkBtns = document.querySelectorAll('.btn-bookmark-toggle');
    bookmarkBtns.forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const resourceId = btn.getAttribute('data-resource-id');
            const icon = btn.querySelector('i');

            try {
                const formData = new FormData();
                formData.append('resource_id', resourceId);

                const response = await fetch(btn.getAttribute('data-url') || 'actions/bookmark_action.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.status === 'success') {
                    if (data.action === 'bookmarked') {
                        icon.classList.remove('fa-regular');
                        icon.classList.add('fa-solid', 'text-danger');
                    } else {
                        icon.classList.remove('fa-solid', 'text-danger');
                        icon.classList.add('fa-regular');
                    }
                } else {
                    alert(data.message || 'Please log in to save bookmarks.');
                }
            } catch (err) {
                console.error('Bookmark error:', err);
            }
        });
    });
});
