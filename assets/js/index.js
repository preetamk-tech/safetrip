// ==========================================
// SafeTrip - Common JS Functions (Pure JS - No PHP)
// ==========================================

// Auto-open login modal AFTER DOM is ready
window.addEventListener('DOMContentLoaded', function() {
    var loginModal = document.getElementById('loginModal');
    var registerModal = document.getElementById('registerModal');
    var registerError = document.getElementById('registerError');
    
    // Auto-open login modal if error exists - delay to ensure DOM is rendered
    setTimeout(function() {
        if (loginModal) {
            var errorDiv = loginModal.querySelector('.alert-error');
            var warningDiv = loginModal.querySelector('.alert-warning');
            
            if (errorDiv && errorDiv.textContent.trim().length > 0) {
                loginModal.style.display = 'flex';
                loginModal.classList.add('active');
            }
            
            if (warningDiv && warningDiv.textContent.trim().length > 0) {
                loginModal.style.display = 'flex';
                loginModal.classList.add('active');
            }
        }
    }, 100);
    
    // Modal Functions
    window.openLoginModal = function() { if(loginModal) { loginModal.style.display = 'flex'; loginModal.classList.add('active'); } };
    window.closeLoginModal = function() { if(loginModal) { loginModal.style.display = 'none'; loginModal.classList.remove('active'); } };
    window.openRegisterModal = function() { if(registerModal) { registerModal.style.display = 'flex'; registerModal.classList.add('active'); if(registerError) registerError.style.display = 'none'; } };
    window.closeRegisterModal = function() { if(registerModal) { registerModal.style.display = 'none'; registerModal.classList.remove('active'); } };
    
    window.openOtpModal = function() {
        var m = document.getElementById('otpModal');
        if(m) {
            m.style.display = 'flex';
            m.classList.add('active');
            var inp = document.getElementById('otpInput');
            if(inp) { inp.value = ''; inp.focus(); }
            var err = document.getElementById('otpError');
            var suc = document.getElementById('otpSuccess');
            if(err) err.style.display = 'none';
            if(suc) suc.style.display = 'none';
        }
    };
    
    window.closeOtpModal = function() {
        var m = document.getElementById('otpModal');
        if(m) m.style.display = 'none';
    };
    
    window.openRegisterSuccessModal = function() {
        var m = document.getElementById('registerSuccessModal');
        if(m) {
            m.classList.add('active');
            setTimeout(function() {
                if(m) m.classList.remove('active');
            }, 6000);
        }
    };
    
    window.closeRegisterSuccessModal = function() {
        var m = document.getElementById('registerSuccessModal');
        if(m) m.classList.remove('active');
    };
    
    // Don't close login modal on backdrop click or escape (keep open on error)
    
    // Don't close register on backdrop click
    // Only close on X button
    
    var otpModal = document.getElementById('otpModal');
    var successModal = document.getElementById('registerSuccessModal');
    if(otpModal) otpModal.addEventListener('click', function(e) { 
        if(e.target === this) { this.style.display = 'none'; } 
    });
    if(successModal) successModal.addEventListener('click', function(e) { 
        if(e.target === this) { this.style.display = 'none'; } 
    });
    
    // Escape key closes modals (except login modal to keep error visible)
    document.addEventListener('keydown', function(e) {
        if(e.key === 'Escape') {
            if(registerModal && (!registerError || registerError.style.display === 'none')) {
                registerModal.style.display = 'none';
            }
            if(otpModal) otpModal.style.display = 'none';
            if(successModal) successModal.style.display = 'none';
        }
    });
});

// Password Validation for Registration
(function() {
    var password = document.getElementById('reg_password');
    var confirmPassword = document.getElementById('reg_confirm_password');
    var passwordMatch = document.getElementById('passwordMatch');

    if(password && confirmPassword && passwordMatch) {
        confirmPassword.addEventListener('input', function() {
            if(this.value === password.value && this.value.length > 0) {
                passwordMatch.textContent = '✓ Passwords match';
                passwordMatch.style.color = '#00b894';
            } else if(this.value.length > 0) {
                passwordMatch.textContent = '✗ Passwords do not match';
                passwordMatch.style.color = '#e17055';
            } else {
                passwordMatch.textContent = '';
            }
        });

        var form = document.getElementById('registerForm');
        if(form) {
            form.addEventListener('submit', function(e) {
                if(password.value !== confirmPassword.value) {
                    e.preventDefault();
                    confirmPassword.focus();
                    passwordMatch.textContent = '✗ Passwords do not match';
                    passwordMatch.style.color = '#e17055';
                }
            });
        }
    }
})();

// AJAX Registration Handler
window.handleRegister = function(e) {
    e.preventDefault();
    
    var form = document.getElementById('registerForm');
    var errorDiv = document.getElementById('registerError');
    var submitBtn = document.getElementById('registerBtn');
    var formData = new FormData(form);
    
    // Disable button
    if(submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating...';
    }
    
    // Hide previous error
    if(errorDiv) errorDiv.style.display = 'none';
    
    fetch('auth/register_process.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if(submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create Account';
        }
        
        if(data.success) {
            // Success - show OTP modal
            if(data.otp_sent) {
                closeRegisterModal();
                setTimeout(function() {
                    openOtpModal();
                }, 300);
            }
        } else {
            // Error - show in modal, keep form data
            if(errorDiv) {
                errorDiv.textContent = data.error;
                errorDiv.style.display = 'block';
            }
            
            // Focus the error field
            if(data.field) {
                var field = document.getElementById(data.field);
                if(field) field.focus();
            }
        }
    })
    .catch(function(err) {
        if(submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create Account';
        }
        if(errorDiv) {
            errorDiv.textContent = 'Something went wrong. Please try again.';
            errorDiv.style.display = 'block';
        }
    });
    
    return false;
};

// Image Slider
(function() {
    var sliderTrack = document.getElementById('sliderTrack');
    var dots = document.querySelectorAll('.slider-dot');
    if(!sliderTrack || !dots.length) {
        console.log('Slider not found');
        return;
    }

    var currentSlide = 0;
    var totalSlides = 4;

    window.moveSlide = function(direction) {
        currentSlide += direction;
        if(currentSlide < 0) currentSlide = totalSlides - 1;
        if(currentSlide >= totalSlides) currentSlide = 0;
        updateSlider();
    };

    window.goToSlide = function(index) {
        currentSlide = index;
        updateSlider();
    };

    function updateSlider() {
        sliderTrack.style.transform = 'translateX(-' + (currentSlide * 100) + '%)';
        dots.forEach(function(dot, i) {
            dot.classList.toggle('active', i === currentSlide);
        });
    }

    // Initial position
    updateSlider();

    // Auto slide every 5 seconds
    setInterval(function() { 
        window.moveSlide(1); 
    }, 5000);
})();

// OTP Functions
window.verifyOtp = function() {
    var otpInput = document.getElementById('otpInput');
    var errorDiv = document.getElementById('otpError');
    var successDiv = document.getElementById('otpSuccess');
    var verifyBtn = document.getElementById('verifyOtpBtn');

    if(!otpInput || !errorDiv || !successDiv || !verifyBtn) return;
    
    var otp = otpInput.value.trim();

    if(!/^\d{6}$/.test(otp)) {
        errorDiv.textContent = 'Please enter a valid 6-digit OTP.';
        errorDiv.style.display = 'block';
        successDiv.style.display = 'none';
        return;
    }

    verifyBtn.disabled = true;
    verifyBtn.textContent = 'Verifying...';
    errorDiv.style.display = 'none';

    var csrfToken = '';
    var csrfInput = document.querySelector('input[name="csrf_token"]');
    if(csrfInput) csrfToken = csrfInput.value;

    fetch('api/verify_otp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ otp: otp })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        verifyBtn.disabled = false;
        verifyBtn.textContent = 'Verify OTP';
        if(data.success) {
            errorDiv.style.display = 'none';
            successDiv.textContent = data.message;
            successDiv.style.display = 'block';
            setTimeout(function() {
                closeOtpModal();
                openRegisterSuccessModal();
            }, 1500);
        } else {
            successDiv.style.display = 'none';
            errorDiv.textContent = data.message;
            errorDiv.style.display = 'block';
        }
    })
    .catch(function(err) {
        verifyBtn.disabled = false;
        verifyBtn.textContent = 'Verify OTP';
        errorDiv.textContent = 'Unable to connect. Please try again.';
        errorDiv.style.display = 'block';
    });
};

window.resendOtp = function() {
    var resendBtn = document.getElementById('resendOtpBtn');
    var errorDiv = document.getElementById('otpError');
    if(!resendBtn || !errorDiv) return;
    
    resendBtn.disabled = true;
    resendBtn.textContent = 'Sending...';

    var csrfToken = '';
    var csrfInput = document.querySelector('input[name="csrf_token"]');
    if(csrfInput) csrfToken = csrfInput.value;

    fetch('api/resend_otp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({})
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        resendBtn.disabled = false;
        resendBtn.textContent = 'Resend OTP';
        if(data.success) {
            errorDiv.style.display = 'none';
            alert('OTP sent successfully!');
        } else {
            errorDiv.textContent = data.message;
            errorDiv.style.display = 'block';
        }
    })
    .catch(function(err) {
        resendBtn.disabled = false;
        resendBtn.textContent = 'Resend OTP';
    });
};