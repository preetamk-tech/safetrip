<?php
$page_title = 'Emergency SOS';
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotLoggedIn();

$user_id = getUserId();
$contacts = $conn->query("SELECT * FROM emergency_contacts WHERE user_id = $user_id");

require_once '../includes/user_header.php';
?>

<!-- Page Header -->
<div class="page-header-emergency">
    <div class="page-header-kicker">
        <span>Safety</span>
    </div>
    <h1><i class="fas fa-exclamation-triangle"></i> Emergency SOS</h1>
    <p>Press the button below in case of emergency. Your live location will be shared with police and your trusted contacts.</p>
</div>

<div style="text-align: center; margin-top: 30px;">
    <!-- SOS Button -->
    <div style="position: relative; display: inline-block;">
        <button class="sos-button" id="emergencyBtn" onclick="triggerEmergency()">
            <i class="fas fa-bell"></i> EMERGENCY SOS
        </button>
        <div style="position: absolute; top: -10px; right: -10px; width: 20px; height: 20px; background: #ef4444; border-radius: 50%; animation: pulse 1s infinite;"></div>
    </div>
    <div id="emergencyStatus"></div>
</div>

<!-- Emergency Contacts Grid -->
<div class="st-card" style="margin-top: 30px;">
    <div class="st-card-header">
        <h3><i class="fas fa-phone"></i> Emergency Contact Numbers</h3>
    </div>
    <div class="st-card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
            <a href="tel:100" style="display: flex; flex-direction: column; align-items: center; padding: 20px; background: linear-gradient(135deg, #1e40af, #3b82f6); border-radius: 12px; color: white; text-decoration: none; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <i class="fas fa-shield-alt" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <strong>Police</strong>
                <span style="font-size: 1.5rem; font-weight: 700;">100</span>
            </a>
            <a href="tel:102" style="display: flex; flex-direction: column; align-items: center; padding: 20px; background: linear-gradient(135deg, #dc2626, #ef4444); border-radius: 12px; color: white; text-decoration: none; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <i class="fas fa-ambulance" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <strong>Ambulance</strong>
                <span style="font-size: 1.5rem; font-weight: 700;">102</span>
            </a>
            <a href="tel:1091" style="display: flex; flex-direction: column; align-items: center; padding: 20px; background: linear-gradient(135deg, #db2777, #ec4899); border-radius: 12px; color: white; text-decoration: none; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <i class="fas fa-venus" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <strong>Women Helpline</strong>
                <span style="font-size: 1.5rem; font-weight: 700;">1091</span>
            </a>
            <a href="tel:112" style="display: flex; flex-direction: column; align-items: center; padding: 20px; background: linear-gradient(135deg, #059669, #10b981); border-radius: 12px; color: white; text-decoration: none; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <i class="fas fa-phone-alt" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <strong>National Emergency</strong>
                <span style="font-size: 1.5rem; font-weight: 700;">112</span>
            </a>
        </div>
    </div>
</div>

<!-- Your Trusted Contacts -->
<?php if ($contacts && $contacts->num_rows > 0): ?>
<div class="st-card" style="margin-top: 20px;">
    <div class="st-card-header">
        <h3><i class="fas fa-user-friends"></i> Your Trusted Contacts</h3>
    </div>
    <div class="st-card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <?php while($contact = $contacts->fetch_assoc()): ?>
            <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #f8fafc; border-radius: 12px; border: 1px solid #e5e7eb;">
                <div style="width: 50px; height: 50px; background: #0f766e; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700;">
                    <?php echo strtoupper(substr($contact['contact_name'], 0, 1)); ?>
                </div>
                <div>
                    <strong><?php echo htmlspecialchars($contact['contact_name']); ?></strong>
                    <div style="color: #667085; font-size: 0.85rem;"><?php echo htmlspecialchars($contact['relationship']); ?></div>
                    <a href="tel:<?php echo htmlspecialchars($contact['contact_phone']); ?>" style="color: #0f766e; font-weight: 600;">
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($contact['contact_phone']); ?>
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Safety Tips -->
<div class="st-card" style="margin-top: 20px; background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 1px solid #bbf7d0;">
    <div class="st-card-header" style="background: transparent;">
        <h3><i class="fas fa-shield-alt" style="color: #0f766e;"></i> Safety Tips</h3>
    </div>
    <div class="st-card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: white; border-radius: 10px;">
                <i class="fas fa-check-circle" style="color: #0f766e; font-size: 1.2rem; margin-top: 2px;"></i>
                <span>Always share your trip details with family/friends</span>
            </div>
            <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: white; border-radius: 10px;">
                <i class="fas fa-check-circle" style="color: #0f766e; font-size: 1.2rem; margin-top: 2px;"></i>
                <span>Keep emergency contacts handy at all times</span>
            </div>
            <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: white; border-radius: 10px;">
                <i class="fas fa-check-circle" style="color: #0f766e; font-size: 1.2rem; margin-top: 2px;"></i>
                <span>Avoid traveling alone late at night</span>
            </div>
            <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: white; border-radius: 10px;">
                <i class="fas fa-check-circle" style="color: #0f766e; font-size: 1.2rem; margin-top: 2px;"></i>
                <span>Keep your phone charged at all times</span>
            </div>
            <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: white; border-radius: 10px;">
                <i class="fas fa-check-circle" style="color: #0f766e; font-size: 1.2rem; margin-top: 2px;"></i>
                <span>Trust your instincts - if something feels wrong, seek help</span>
            </div>
        </div>
    </div>
</div>

<style>
.sos-button {
    background: linear-gradient(135deg, #dc2626, #ef4444);
    color: white;
    font-size: 1.5rem;
    padding: 25px 50px;
    border: none;
    border-radius: 50px;
    cursor: pointer;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 10px 40px rgba(220, 38, 38, 0.4);
    transition: all 0.3s;
}
.sos-button:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 50px rgba(220, 38, 38, 0.5);
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>

<script>
function showNotification(msg, type) {
    alert(msg);
}
</script>
<script src="../assets/js/emergency.js"></script>

<?php require_once '../includes/user_footer.php'; ?>