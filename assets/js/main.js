document.addEventListener('DOMContentLoaded', function () {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    const scrollBtn = document.getElementById('scrollTop');
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');

    function setNavState(isOpen) {
        if (!navMenu || !navToggle) {
            return;
        }

        navMenu.classList.toggle('active', isOpen);
        navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        const icon = navToggle.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-bars', !isOpen);
            icon.classList.toggle('fa-times', isOpen);
        }
    }

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function () {
            setNavState(!navMenu.classList.contains('active'));
        });

        document.addEventListener('click', function (event) {
            if (navMenu.classList.contains('active') && !navMenu.contains(event.target) && !navToggle.contains(event.target)) {
                setNavState(false);
            }
        });
    }

    if (scrollBtn) {
        const toggleScrollButton = function () {
            scrollBtn.classList.toggle('visible', window.pageYOffset > 280);
        };

        toggleScrollButton();
        window.addEventListener('scroll', toggleScrollButton);
        scrollBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    document.querySelectorAll('.alert').forEach(function (alert) {
        setTimeout(function () {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-6px)';
            setTimeout(function () {
                alert.style.display = 'none';
            }, 260);
        }, 5000);
    });

    document.querySelectorAll('.scroll-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (!href || href.charAt(0) !== '#') {
                return;
            }

            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                setNavState(false);
            }
        });
    });

    document.querySelectorAll('[data-slider]').forEach(function (slider) {
        const slides = Array.prototype.slice.call(slider.querySelectorAll('.slide-card'));
        const dots = Array.prototype.slice.call(slider.querySelectorAll('.slider-dot'));
        const prev = slider.querySelector('.slider-prev');
        const next = slider.querySelector('.slider-next');
        const autoplayDelay = parseInt(slider.getAttribute('data-slider-autoplay'), 10) || 4500;
        let currentIndex = 0;
        let autoPlay = null;

        if (!slides.length) {
            return;
        }

        function render(index) {
            slides.forEach(function (slide, slideIndex) {
                slide.classList.toggle('is-active', slideIndex === index);
            });

            dots.forEach(function (dot, dotIndex) {
                dot.classList.toggle('is-active', dotIndex === index);
            });

            currentIndex = index;
        }

        function goTo(index) {
            const total = slides.length;

            if (index < 0) {
                index = total - 1;
            }

            if (index >= total) {
                index = 0;
            }

            render(index);
        }

        function stopAutoplay() {
            if (autoPlay) {
                clearInterval(autoPlay);
                autoPlay = null;
            }
        }

        function startAutoplay() {
            stopAutoplay();
            autoPlay = setInterval(function () {
                goTo(currentIndex + 1);
            }, autoplayDelay);
        }

        if (prev) {
            prev.addEventListener('click', function () {
                goTo(currentIndex - 1);
                startAutoplay();
            });
        }

        if (next) {
            next.addEventListener('click', function () {
                goTo(currentIndex + 1);
                startAutoplay();
            });
        }

        dots.forEach(function (dot, index) {
            dot.addEventListener('click', function () {
                goTo(index);
                startAutoplay();
            });
        });

        slider.addEventListener('mouseenter', stopAutoplay);
        slider.addEventListener('mouseleave', startAutoplay);

        render(0);
        startAutoplay();
    });

function closeIfBackdrop(event, modal) {
        if (modal && event.target === modal) {
            modal.classList.remove('show');
        }
    }

    window.addEventListener('click', function (event) {
        closeIfBackdrop(event, loginModal);
        closeIfBackdrop(event, registerModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (loginModal) {
                loginModal.classList.remove('show');
            }
            if (registerModal) {
                registerModal.classList.remove('show');
            }
            setNavState(false);
        }
    });
});

function openLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) {
        modal.classList.add('show');
    }
}

function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

function openRegisterModal() {
    const modal = document.getElementById('registerModal');
    if (modal) {
        modal.classList.add('show');
    }
}

function closeRegisterModal() {
    const modal = document.getElementById('registerModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

function switchToLogin() {
    closeRegisterModal();
    openLoginModal();
}

function switchToRegister() {
    closeLoginModal();
    openRegisterModal();
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = 'alert alert-' + (type || 'info');
    notification.style.position = 'fixed';
    notification.style.top = '94px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.maxWidth = '320px';
    notification.style.transition = 'all 0.25s ease';
    notification.innerHTML = '<i class="fas fa-bell"></i> ' + message;
    document.body.appendChild(notification);

    setTimeout(function () {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-6px)';
        setTimeout(function () {
            notification.remove();
        }, 250);
    }, 3200);
}

function safeTripJsonHeaders() {
    const headers = { 'Content-Type': 'application/json' };

    if (window.SafeTrip && window.SafeTrip.csrfToken) {
        headers['X-CSRF-Token'] = window.SafeTrip.csrfToken;
    }

    return headers;
}

function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) {
        return true;
    }

    let isValid = true;
    form.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (field) {
        const hasValue = String(field.value || '').trim() !== '';
        field.style.borderColor = hasValue ? '' : '#ef4444';
        if (!hasValue) {
            isValid = false;
        }
    });

    return isValid;
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatTime(timeString) {
    return new Date('2000-01-01T' + timeString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function showLoading(elementId) {
    const el = document.getElementById(elementId);
    if (el) {
        el.innerHTML = '<div class="spinner"></div>';
    }
}

function hideLoading(elementId, originalContent) {
    const el = document.getElementById(elementId);
    if (el) {
        el.innerHTML = originalContent;
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function () {
        showNotification('Copied to clipboard!', 'success');
    });
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Dark Mode Toggle
(function() {
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');

    // Check for saved theme preference or default to light
    const savedTheme = localStorage.getItem('theme') || 'light';

    // Apply saved theme
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        if (themeIcon) {
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        }
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            if (newTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                if (themeIcon) {
                    themeIcon.classList.remove('fa-moon');
                    themeIcon.classList.add('fa-sun');
                }
            } else {
                document.documentElement.removeAttribute('data-theme');
                if (themeIcon) {
                    themeIcon.classList.remove('fa-sun');
                    themeIcon.classList.add('fa-moon');
                }
            }

            localStorage.setItem('theme', newTheme);
        });
    }
})();

// =============================================
// ONLINE STATUS TRACKING
// =============================================
(function() {
    let isOnlinePingActive = false;
    let pingInterval = null;
    let heartbeatInterval = null;

    function startOnlineTracking() {
        if (isOnlinePingActive) return;
        isOnlinePingActive = true;

        // Initial ping
        pingOnlineStatus('ping');

        // Send ping every 30 seconds to keep status alive
        pingInterval = setInterval(() => {
            pingOnlineStatus('ping');
        }, 30000);

        // Heartbeat check for visibility changes
        heartbeatInterval = setInterval(() => {
            if (document.visibilityState === 'visible') {
                pingOnlineStatus('ping');
            }
        }, 60000);
    }

    function stopOnlineTracking() {
        isOnlinePingActive = false;
        if (pingInterval) {
            clearInterval(pingInterval);
            pingInterval = null;
        }
        if (heartbeatInterval) {
            clearInterval(heartbeatInterval);
            heartbeatInterval = null;
        }
        pingOnlineStatus('go_offline');
    }

    function pingOnlineStatus(action) {
        fetch('api/online_status.php?action=' + action)
            .then(response => response.json())
            .catch(() => {});
    }

    // Start tracking when user is logged in
    if (typeof SafeTrip !== 'undefined' && SafeTrip.userId) {
        startOnlineTracking();

        // Stop on page leave
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                pingOnlineStatus('go_offline');
            } else {
                pingOnlineStatus('ping');
            }
        });

        // Stop on beforeunload
        window.addEventListener('beforeunload', function() {
            pingOnlineStatus('go_offline');
        });
    }

    // Expose functions globally
    window.SafeTripOnline = {
        start: startOnlineTracking,
        stop: stopOnlineTracking,
        ping: pingOnlineStatus
    };
})();

// Helper to update online status display
function updateOnlineStatusDisplay(userId, containerId) {
    fetch('api/online_status.php?action=status&user_ids=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.status && data.status[userId]) {
                const status = data.status[userId];
                const container = document.getElementById(containerId);
                if (container) {
                    container.innerHTML = formatOnlineStatusHTML(status.is_online, status.last_seen);
                }
            }
        })
        .catch(() => {});
}

function formatOnlineStatusHTML(isOnline, lastSeen) {
    if (isOnline) {
        return '<span class="online-status online"><i class="fas fa-circle"></i> Online</span>';
    }

    let timeAgo = 'Offline';
    if (lastSeen) {
        const diff = Date.now() - new Date(lastSeen).getTime();
        const mins = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);

        if (mins < 1) timeAgo = 'Just now';
        else if (mins < 60) timeAgo = mins + 'm ago';
        else if (hours < 24) timeAgo = hours + 'h ago';
        else timeAgo = 'Offline';
    }

    return '<span class="online-status offline"><i class="fas fa-circle"></i> ' + timeAgo + '</span>';
}
