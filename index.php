<?php
require_once 'config/database.php';

$login_error = $_SESSION['login_error'] ?? '';
$register_error_session = $_SESSION['register_error'] ?? '';
$register_error_field = $_SESSION['register_error_field'] ?? '';
$register_data = $_SESSION['register_data'] ?? [];
$register_success = $_SESSION['register_success'] ?? '';
$show_otp_modal = $_GET['show_otp_modal'] ?? '';
$otp_verified = $_SESSION['otp_verified'] ?? '';
$pending_otp = $_GET['pending_otp'] ?? '';
$message_param = $_GET['message'] ?? '';
$show_login_modal = $_GET['show_login_modal'] ?? '';
$pending_otp_email = $_SESSION['pending_otp_email'] ?? '';
$pending_otp_user_id = $_SESSION['pending_otp_user_id'] ?? 0;

// Handle pending OTP email from login attempt
$otp_pending_error = '';
if (isset($_SESSION['login_error']) && strpos($_SESSION['login_error'], 'OTP Verification Pending') !== false) {
    $otp_pending_error = $_SESSION['login_error'];
}

// Check if user has pending OTP verification
$show_pending_otp_notice = false;
if (isset($_SESSION['user_id'])) {
    $checkUser = getUserById($_SESSION['user_id']);
    if ($checkUser && $checkUser['verification_status'] === 'pending' && !empty($checkUser['otp'])) {
        $show_pending_otp_notice = true;
    }
} elseif (isset($_SESSION['temp_user_id'])) {
    $checkUser = getUserById($_SESSION['temp_user_id']);
    if ($checkUser && $checkUser['verification_status'] === 'pending' && !empty($checkUser['otp'])) {
        $show_pending_otp_notice = true;
    }
}

// Handle account deleted message
if ($message_param === 'account_deleted') {
    $register_success = 'Your account has been deleted. You can register again if needed.';
}

// Auto-delete unverified users older than 10 minutes
if ($conn) {
    $conn->query("DELETE FROM users WHERE verification_status = 'pending' AND otp IS NOT NULL AND otp != '' AND created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
}

// Clear errors after reading so they don't persist
$display_register_error = $register_error_session;
unset($_SESSION['register_error']);

$verified_users = $active_trips = $sos_alerts = 0;

if ($conn) {
    if ($r = $conn->query("SELECT COUNT(*) as c FROM users WHERE verification_status = 'verified'")) $verified_users = $r->fetch_assoc()['c'];
    if ($r = $conn->query("SELECT COUNT(*) as c FROM trips WHERE status = 'active'")) $active_trips = $r->fetch_assoc()['c'];
    if ($r = $conn->query("SELECT COUNT(*) as c FROM safety_alerts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")) $sos_alerts = $r->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeTrip - Travel Safer Together</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/ai-chat-widget.css?v=2">
    <style>
        .alert-warning {
            position: relative;
            z-index: 1000;
        }
        .alert-warning .btn:hover {
            opacity: 0.9;
        }
        .ai-chat-widget{position:fixed!important;bottom:20px!important;left:20px!important;z-index:99999!important}
        .ai-chat-box{position:fixed!important;bottom:90px!important;left:20px!important;width:380px!important;height:500px!important;background:#fff!important;border-radius:20px!important;box-shadow:0 20px 60px rgba(0,0,0,.15)!important;display:none!important;flex-direction:column!important;overflow:hidden!important;z-index:99999!important}
        .ai-chat-box.active{display:flex!important}
        .ai-chat-toggle{width:65px!important;height:65px!important;border-radius:50%!important;background:linear-gradient(135deg,#6366f1,#8b7cf7)!important;border:0!important;color:#fff!important;display:flex!important;align-items:center!important;justify-content:center!important;font-size:1.8rem!important;box-shadow:0 8px 30px rgba(108,92,231,.4)!important;cursor:pointer!important}
        .ai-chat-header{background:linear-gradient(135deg,#6366f1,#8b7cf7)!important;color:#fff!important;padding:20px!important;display:flex!important;align-items:center!important;justify-content:space-between!important}
        .ai-chat-header-info{display:flex!important;align-items:center!important;gap:12px!important}
        .ai-chat-avatar,.ai-chat-message-icon{width:40px!important;height:40px!important;border-radius:50%!important;display:flex!important;align-items:center!important;justify-content:center!important;flex-shrink:0!important}
        .ai-chat-avatar{background:rgba(255,255,255,.2)!important}
        .ai-chat-close,.ai-chat-send{border:0!important;cursor:pointer!important}
        .ai-chat-close{width:32px!important;height:32px!important;border-radius:50%!important;background:rgba(255,255,255,.2)!important;color:#fff!important;font-size:1rem!important}
        .ai-chat-messages{flex:1!important;padding:20px!important;overflow-y:auto!important;background:#f8f9fa!important}
        .ai-chat-welcome{text-align:center!important;padding:30px 20px!important;background:#fff!important;border-radius:16px!important}
        .ai-chat-quick-actions{padding:12px 16px!important;background:#fff!important;display:flex!important;gap:8px!important;flex-wrap:wrap!important;border-top:1px solid #e5e7eb!important}
        .ai-chat-quick-btn{border:1px solid #e0e7ff!important;background:#eef2ff!important;color:#6366f1!important;border-radius:999px!important;padding:8px 12px!important;font-size:.8rem!important;cursor:pointer!important}
        .ai-chat-input-area{padding:16px!important;background:#fff!important;display:flex!important;gap:10px!important;border-top:1px solid #e5e7eb!important}
        .ai-chat-input{flex:1!important;border:1px solid #e5e7eb!important;border-radius:999px!important;padding:12px 16px!important;outline:0!important}
        .ai-chat-send{width:45px!important;height:45px!important;border-radius:50%!important;background:#6366f1!important;color:#fff!important}
        .ai-chat-message{margin-bottom:16px!important;display:flex!important;gap:10px!important}
        .ai-chat-message.user{flex-direction:row-reverse!important}
        .ai-chat-message.bot .ai-chat-message-icon{background:#6366f1!important;color:#fff!important}
        .ai-chat-message.user .ai-chat-message-icon{background:#10b981!important;color:#fff!important}
        .ai-chat-message-content{max-width:75%!important;padding:12px 16px!important;border-radius:16px!important;font-size:.9rem!important;line-height:1.5!important}
        .ai-chat-message.bot .ai-chat-message-content{background:#fff!important;color:#0f172a!important}
        .ai-chat-message.user .ai-chat-message-content{background:#6366f1!important;color:#fff!important}
        @media (max-width:480px){.ai-chat-box{left:12px!important;right:12px!important;width:auto!important;height:70vh!important}.ai-chat-widget{left:12px!important}}
    </style>
</head>
<body>
    
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="nav-brand">
                <i class="fas fa-shield-heart"></i> SafeTrip
            </a>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#how">How It Works</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#" onclick="openLoginModal()">Login</a></li>
                <li><a href="#" onclick="openRegisterModal()" class="btn-nav">Sign Up Free</a></li>
            </ul>
        </div>
    </nav>

<!-- Login Modal -->
    <div id="loginModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header modal-header-primary">
                <h3>Welcome Back</h3>
                <button onclick="closeLoginModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (!empty($login_error)): ?>
                <div class="alert-error"><?php echo e($login_error); ?></div>
                <?php endif; ?>
                <?php if (!empty($otp_pending_error)): ?>
                <div class="alert-warning" style="padding: 15px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border-radius: 8px; margin-bottom: 15px;">
                    <p style="margin: 0 0 10px;"><i class="fas fa-exclamation-triangle"></i> <strong>OTP Verification Pending</strong></p>
                    <a href="auth/verify_otp.php?user_id=<?php echo $pending_otp_user_id; ?>" class="btn" style="background: white; color: #d97706; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-block;">Verify Now</a>
                </div>
                <?php unset($_SESSION['login_error'], $_SESSION['pending_otp_email'], $_SESSION['pending_otp_user_id']); ?>
                <?php endif; ?>
                <form method="POST" action="auth/login_process.php">
                    <?php echo csrf_input(); ?>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required class="input-full" placeholder="you@example.com">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required class="input-full" placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn-submit">Login</button>
                </form>
            </div>
            <div class="modal-footer">
                <p class="text-muted">Don't have account? <a href="#" onclick="closeLoginModal();openRegisterModal();return false;" class="link-primary">Sign Up</a></p>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header modal-header-primary">
                <h3>Create Account</h3>
                <button onclick="closeRegisterModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="registerError" class="alert-error" style="display:<?php echo !empty($display_register_error) ? 'block' : 'none'; ?>;"><?php echo e($display_register_error); ?></div>
                <?php if (!empty($otp_pending_error)): ?>
                <div class="alert-warning" style="padding: 15px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border-radius: 8px; margin-bottom: 15px;">
                    <p style="margin: 0 0 10px;"><i class="fas fa-exclamation-triangle"></i> <strong>OTP Verification Pending</strong></p>
                    <a href="auth/verify_otp.php?user_id=<?php echo $pending_otp_user_id; ?>" class="btn" style="background: white; color: #d97706; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-block;">Verify Now</a>
                </div>
                <?php endif; ?>
                <form method="POST" action="auth/register_process.php" id="registerForm" onsubmit="return handleRegister(event)">
                    <?php echo csrf_input(); ?>
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" id="reg_full_name" required class="input-full" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_SESSION['register_data']['full_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" id="reg_email" required class="input-full" placeholder="you@example.com" value="<?php echo htmlspecialchars($_SESSION['register_data']['email'] ?? ''); ?>">
                    </div>
                    <div class="flex-row">
                        <div class="flex-row-item">
                            <label>Phone *</label>
                            <input type="tel" name="phone" id="reg_phone" required class="input-full" placeholder="10 digit number" value="<?php echo htmlspecialchars($_SESSION['register_data']['phone'] ?? ''); ?>">
                        </div>
                        <div class="flex-row-item">
                            <label>Age *</label>
                            <input type="number" name="age" id="reg_age" required class="input-full" placeholder="18-100" min="18" value="<?php echo htmlspecialchars($_SESSION['register_data']['age'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="flex-row">
                        <div class="flex-row-item">
                            <label>Gender *</label>
                            <select name="gender" id="reg_gender" required class="input-full">
                                <option value="">Select</option>
                                <option value="male" <?php echo ($_SESSION['register_data']['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($_SESSION['register_data']['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($_SESSION['register_data']['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="flex-row-item">
                            <label>ID Type *</label>
                            <select name="id_type" id="reg_id_type" required class="input-full">
                                <option value="">Select</option>
                                <option value="aadhaar" <?php echo ($_SESSION['register_data']['id_type'] ?? '') === 'aadhaar' ? 'selected' : ''; ?>>Aadhaar Card</option>
                                <option value="pan" <?php echo ($_SESSION['register_data']['id_type'] ?? '') === 'pan' ? 'selected' : ''; ?>>PAN Card</option>
                                <option value="driving_license" <?php echo ($_SESSION['register_data']['id_type'] ?? '') === 'driving_license' ? 'selected' : ''; ?>>Driving License</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>ID Number *</label>
                        <input type="text" name="id_number" id="reg_id_number" required class="input-full" placeholder="Enter your ID number" value="<?php echo htmlspecialchars($_SESSION['register_data']['id_number'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" id="reg_password" required class="input-full" placeholder="Min 8 chars">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <input type="password" name="password_confirmation" id="reg_confirm_password" required class="input-full" placeholder="Confirm password">
                        <div id="passwordMatch" class="text-xs"></div>
                    </div>
                    <button type="submit" class="btn-submit" id="registerBtn">Create Account</button>
                </form>
            </div>
            <div class="modal-footer">
                <p class="text-muted">Already have account? <a href="#" onclick="closeRegisterModal();openLoginModal();return false;" class="link-primary">Login</a></p>
            </div>
        </div>
    </div>

    <!-- OTP Verification Modal -->
    <div id="otpModal" class="modal-overlay">
        <div class="modal-container modal-container-sm">
            <div class="modal-header modal-header-teal">
                <h3><i class="fas fa-envelope"></i> Verify Email</h3>
                <button onclick="closeOtpModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body-center">
                <div class="icon-3x"><i class="fas fa-envelope-open-text"></i></div>
                <p class="text-muted mb-md">We've sent an OTP to your email. Please enter the 6-digit code to verify your account.</p>
                <div id="otpError" class="alert-error" style="display:none;"></div>
                <div id="otpSuccess" class="alert-success" style="display:none;"></div>
                <div class="form-group">
                    <input type="text" name="otp" id="otpInput" required maxlength="6" inputmode="numeric" pattern="[0-9]{6}" class="input-full input-lg" placeholder="000000">
                </div>
                <button type="button" onclick="verifyOtp()" id="verifyOtpBtn" class="btn-submit">Verify OTP</button>
                <button type="button" onclick="resendOtp()" id="resendOtpBtn" class="btn-outline-primary">Resend OTP</button>
                <p class="text-sm text-muted mt-sm">OTP is valid for 10 minutes</p>
            </div>
        </div>
    </div>

    <!-- Registration Success Modal -->
    <div id="registerSuccessModal" class="modal-overlay">
        <div class="modal-container modal-container-sm">
            <div class="modal-header modal-header-success">
                <h3><i class="fas fa-check-circle"></i> Registration Complete</h3>
                <button onclick="closeRegisterSuccessModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body-center">
                <div class="icon-4x"><i class="fas fa-check-circle"></i></div>
                <h2 class="mb-md">Welcome to SafeTrip!</h2>
                <p class="text-muted mb-lg">Your email has been verified successfully. Your account is now <strong>pending admin verification</strong>.</p>
                <div class="alert-info">
                    <p class="text-success"><i class="fas fa-info-circle"></i> You will receive an email notification once your account is approved by our admin team.</p>
                </div>
                <p class="text-muted text-sm mb-lg">After approval, you can log in and start creating trips!</p>
                <button onclick="closeRegisterSuccessModal();openLoginModal();return false;" class="btn-submit">Go to Login</button>
            </div>
        </div>
    </div>

    <!-- Hero -->
    <section class="hero" id="home">
        <div class="hero-inner">
            <div class="hero-left">
                <span class="hero-badge">🛡️ Verified Travel Platform</span>
                <h1>Your Journey. Your Safety.</h1>
                <p class="hero-text">Connect with verified travelers going your way. Safety features, emergency SOS, and trusted companions - all in one place!</p>
                
                <div class="hero-btns">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="#" onclick="openRegisterModal()" class="btn btn-white btn-lg">
                        <i class="fas fa-rocket"></i> Get Started Free
                    </a>
                    <?php else: ?>
                    <a href="user/dashboard.php" class="btn btn-white btn-lg">
                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                    </a>
                    <?php endif; ?>
                    <a href="#features" class="btn btn-outline btn-lg">
                        <i class="fas fa-compass"></i> Explore Features
                    </a>
                </div>

                <div class="hero-stats">
                    <div class="stat-item">
                        <strong><?php echo number_format($verified_users); ?>+</strong>
                        <span>Verified Travelers</span>
                    </div>
                    <div class="stat-item">
                        <strong><?php echo number_format($active_trips); ?>+</strong>
                        <span>Active Trips</span>
                    </div>
                    <div class="stat-item">
                        <strong><?php echo number_format($sos_alerts); ?>+</strong>
                        <span>Saved Journeys</span>
                    </div>
                </div>
            </div>
            <div class="hero-right">
                <img src="https://plus.unsplash.com/premium_photo-1683121262276-7c44faf62c2d?q=80&w=2069&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Travel Safety" class="hero-image">
            </div>
        </div>
    </section>

    <!-- Image Slider -->
    <section class="slider-section" id="gallery">
        <div class="container">
            <div class="section-header">
                <h2>Travel Stories 📸</h2>
                <p>See what's happening on SafeTrip</p>
            </div>
            
            <div class="slider">
                <div class="slider-track" id="sliderTrack">
                    <div class="slider-slide">
                        <img src="https://images.unsplash.com/photo-1494515843206-f3117d3f51b7?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTh8fGJ1c3xlbnwwfHwwfHx8MA%3D%3D/1200/500" alt="Bus Journey" class="slider-img">
                        <div class="slider-overlay">
                            <h3>🚌 Safe Bus Journeys</h3>
                            <p>Travel together safely with verified companions</p>
                        </div>
                    </div>
                    <div class="slider-slide">
                        <img src="https://images.unsplash.com/photo-1515165562839-978bbcf18277?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D/1200/500" alt="Train Travel" class="slider-img">
                        <div class="slider-overlay">
                            <h3>🚂 Train Travel</h3>
                            <p>Find travel buddies for your train journey</p>
                        </div>
                    </div>
                    <div class="slider-slide">
                        <img src="https://images.unsplash.com/photo-1569916385763-5f248d0f04c8?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D/1200/500" alt="Road Trip" class="slider-img">
                        <div class="slider-overlay">
                            <h3>🛣️ Road Trip</h3>
                            <p>Share rides and split costs safely</p>
                        </div>
                    </div>
                    <div class="slider-slide">
                        <img src="https://plus.unsplash.com/premium_photo-1682048358672-1c5c6c9ed2ae?q=80&w=3132&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D/1200/500" alt="City Travel" class="slider-img">
                        <div class="slider-overlay">
                            <h3>🏙️ City Travel</h3>
                            <p>Explore cities with trusted travelers</p>
                        </div>
                    </div>
                </div>
                
                <button class="slider-nav slider-prev" onclick="moveSlide(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="slider-nav slider-next" onclick="moveSlide(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <div class="slider-dots">
                <button class="slider-dot active" onclick="goToSlide(0)"></button>
                <button class="slider-dot" onclick="goToSlide(1)"></button>
                <button class="slider-dot" onclick="goToSlide(2)"></button>
                <button class="slider-dot" onclick="goToSlide(3)"></button>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="section features" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Why SafeTrip? ✨</h2>
                <p>Everything you need for a safer journey.</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon purple"><i class="fas fa-id-card"></i></div>
                    <h3>Verified Profiles ✅</h3>
                    <p>Every traveler verified with government ID. Trust before you connect.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon pink"><i class="fas fa-map-marker-alt"></i></div>
                    <h3>Route Matching 📍</h3>
                    <p>Find travelers going the same route. Same destination, same time!</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon teal"><i class="fas fa-shield-virus"></i></div>
                    <h3>Emergency SOS 🆘</h3>
                    <p>One-tap alert to your trusted contacts. Help is just one tap away!</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon green"><i class="fas fa-users"></i></div>
                    <h3>Group Travel 👥</h3>
                    <p>Create trip groups, chat safely, and travel together.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon orange"><i class="fas fa-user-shield"></i></div>
                    <h3>Privacy First 🔒</h3>
                    <p>Your data stays protected. Share only what you want.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon yellow"><i class="fas fa-bell"></i></div>
                    <h3>Live Updates 🔔</h3>
                    <p>Get real-time notifications about your trip.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="section" id="how">
        <div class="container">
            <div class="section-header">
                <h2>How It Works? 🚀</h2>
                <p>Get started in 4 easy steps.</p>
            </div>
            
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3>Sign Up</h3>
                    <p>Create your free account in 2 minutes.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3>Add Trip</h3>
                    <p>Share your route, date and time.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3>Find Friends</h3>
                    <p>Connect with verified travelers.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h3>Travel Safe</h3>
                    <p>Enjoy your safe journey! 🎉</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About -->
    <section class="section features" id="about">
        <div class="container">
            <div class="about-grid">
                <div class="about-content">
                    <h2>About SafeTrip 💡</h2>
                    <p>We believe every traveler deserves a safe journey. SafeTrip connects verified people traveling the same route, making solo travel safer and more social.</p>
                    <p>Whether you're commuting to college, traveling home, or going on an adventure - SafeTrip has got your back!</p>
                    <p>🎯 <strong>Our Mission:</strong> Make every journey safer by connecting verified travelers.</p>
                </div>
                <div class="about-card">
                    <h3>What Makes Us Different? 🌟</h3>
                    <p>"SafeTrip is not just an app - it's your travel companion that keeps you safe."</p>
                    <span class="badge">Free</span>
                    <span class="badge">Verified</span>
                    <span class="badge">Safe</span>
                    <span class="badge">Easy</span>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="container">
            <h2>Ready to Travel Safer? 🚗</h2>
            <p>Join thousands of students and travelers enjoying safer journeys.</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="#" onclick="openRegisterModal()" class="btn btn-white btn-pulse">
                <i class="fas fa-user-plus"></i> Create Free Account
            </a>
            <?php else: ?>
            <a href="user/dashboard.php" class="btn btn-white">
                <i class="fas fa-tachometer-alt"></i> Go to Dashboard
            </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h3>🚀 SafeTrip</h3>
                    <p>Travel safer together. Made with ❤️ for students and travelers.</p>
                </div>
                <div class="footer-col">
                    <h4>Features</h4>
                    <ul>
                        <li><a href="#">Verified Profiles</a></li>
                        <li><a href="#">Route Matching</a></li>
                        <li><a href="#">Emergency SOS</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="#about">About</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#how">How It Works</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">support@safetrip.com</a></li>
                        <li><a href="#">Emergency: 112</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2026 SafeTrip. Travel safer together. 🎉</p>
            </div>
</div>
</footer>

    <!-- AI Chat Widget -->
    <div class="ai-chat-widget">
        <div class="ai-chat-box" id="aiChatBox">
            <div class="ai-chat-header">
                <div class="ai-chat-header-info">
                    <div class="ai-chat-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="ai-chat-header-text">
                        <h4>SafeTrip Assistant</h4>
                        <span>AI Powered Help</span>
                    </div>
                </div>
                <button class="ai-chat-close" onclick="toggleAIChat()">&times;</button>
            </div>
            
            <div class="ai-chat-messages" id="aiChatMessages">
                <div class="ai-chat-welcome">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Hi! I'm SafeTrip Assistant 🤖</h4>
                    <p>Ask me anything about travel safety, trip planning, or emergency guidance.</p>
                </div>
            </div>
            
            <div class="ai-chat-quick-actions">
                <button class="ai-chat-quick-btn" onclick="sendQuickMessage('Safety tips for solo travel')">Safety Tips</button>
                <button class="ai-chat-quick-btn" onclick="sendQuickMessage('How to create a trip?')">Create Trip</button>
                <button class="ai-chat-quick-btn" onclick="sendQuickMessage('Emergency SOS how to use')">Emergency SOS</button>
                <button class="ai-chat-quick-btn" onclick="sendQuickMessage('Privacy features')">Privacy</button>
            </div>
            
            <div class="ai-chat-input-area">
                <input type="text" class="ai-chat-input" id="aiChatInput" placeholder="Type your message..." onkeypress="if(event.key==='Enter')sendAIMessage()">
                <button class="ai-chat-send" id="aiChatSendBtn" onclick="sendAIMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
        
        <button class="ai-chat-toggle" id="aiChatToggle" onclick="toggleAIChat()">
            <i class="fas fa-solid fa-robot"></i>
        </button>
    </div>
    <iframe src="api/ai_chat.php?warmup=1" title="SafeTrip AI warmup" style="display:none;width:0;height:0;border:0" aria-hidden="true"></iframe>

    <script>
        window.SafeTripAI = {
            endpoint: 'https://safetrip-ai-backend.onrender.com/chat'
        };

        window.toggleAIChat = window.toggleAIChat || function() {
            var chatBox = document.getElementById('aiChatBox');
            var toggle = document.getElementById('aiChatToggle');
            if (!chatBox || !toggle) return;
            chatBox.classList.toggle('active');
            toggle.classList.toggle('active');
            if (chatBox.classList.contains('active')) {
                var input = document.getElementById('aiChatInput');
                if (input) input.focus();
            }
        };

        window.sendAIMessage = window.sendAIMessage || function() {
            var input = document.getElementById('aiChatInput');
            var messagesDiv = document.getElementById('aiChatMessages');
            if (!input || !messagesDiv) return;

            var message = input.value.trim();
            if (!message) return;

            addAIChatMessage(message, 'user');
            input.value = '';

            requestAIChatResponse(message, false)
            .then(function(data) {
                addAIChatMessage(data.response || getSafeTripInlineFallback(message), 'bot');
            })
            .catch(function() {
                addAIChatMessage(getSafeTripInlineFallback(message), 'bot');
            });
        };

        function requestAIChatResponse(message, retried) {
            var formData = new FormData();
            formData.append('message', message);

            return fetch('api/ai_chat.php?t=' + Date.now(), {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json' },
                body: formData
            })
            .then(function(res) { return res.text(); })
            .then(function(text) {
                try {
                    return JSON.parse(text);
                } catch (error) {
                    if (!retried) {
                        return fetch('api/ai_chat.php?warmup=1&t=' + Date.now(), {
                            credentials: 'same-origin',
                            cache: 'no-store'
                        }).then(function() {
                            return new Promise(function(resolve) {
                                setTimeout(resolve, 500);
                            });
                        }).then(function() {
                            return requestAIChatResponse(message, true);
                        });
                    }
                    return {
                        success: true,
                        response: getSafeTripInlineFallback(message)
                    };
                }
            });
        }

        function getSafeTripInlineFallback(message) {
            var text = String(message || '').toLowerCase();
            if (text.indexOf('create') !== -1 || text.indexOf('trip') !== -1) {
                return 'To create a trip, sign up or log in, open your dashboard, choose Create Trip, add from/to locations, date, time, seats, and save it.';
            }
            if (text.indexOf('emergency') !== -1 || text.indexOf('sos') !== -1 || text.indexOf('help') !== -1) {
                return 'For Emergency SOS, keep trusted contacts updated. In danger, call emergency services first, then use SOS from the dashboard to alert contacts with your location.';
            }
            if (text.indexOf('privacy') !== -1 || text.indexOf('location') !== -1 || text.indexOf('number') !== -1) {
                return 'Privacy tips: avoid sharing phone numbers publicly, travel with verified users, and share live trip links only with trusted people.';
            }
            if (text.indexOf('solo') !== -1 || text.indexOf('safety') !== -1 || text.indexOf('tips') !== -1) {
                return 'Safety tips: travel with verified companions, share your route with a trusted contact, keep check-ins enabled, meet in public places, and trust your instincts.';
            }
            return 'I can help with SafeTrip basics: safety tips, creating trips, Emergency SOS, privacy, trusted contacts, and verified traveler guidance.';
        }

        window.sendQuickMessage = window.sendQuickMessage || function(message) {
            var input = document.getElementById('aiChatInput');
            if (input) {
                input.value = message;
                window.sendAIMessage();
            }
        };

        function addAIChatMessage(content, type) {
            var messagesDiv = document.getElementById('aiChatMessages');
            if (!messagesDiv) return;

            var welcome = messagesDiv.querySelector('.ai-chat-welcome');
            if (welcome) welcome.remove();

            var messageDiv = document.createElement('div');
            messageDiv.className = 'ai-chat-message ' + type;
            var icon = type === 'bot' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';
            messageDiv.innerHTML = '<div class="ai-chat-message-icon">' + icon + '</div><div class="ai-chat-message-content">' + String(content).replace(/\n/g, '<br>') + '</div>';
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
    </script>
    <script src="assets/js/index.js?v=2"></script>
    <script src="assets/js/ai-chat.js?v=2"></script>
</body>
</html>
