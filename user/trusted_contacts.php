<?php

require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/mailer.php';
redirectIfNotLoggedIn();

$page_title = 'Trusted Contacts';
require_once '../includes/user_header.php';

$user_id = getUserId();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_contact'])) {
    $contact_name = mysqli_real_escape_string($conn, $_POST['contact_name']);
    $contact_phone = mysqli_real_escape_string($conn, $_POST['contact_phone']);
    $contact_email = mysqli_real_escape_string($conn, $_POST['contact_email']);
    $relationship = mysqli_real_escape_string($conn, $_POST['relationship']);
    
    $sql = "INSERT INTO emergency_contacts (user_id, contact_name, contact_phone, contact_email, relationship) 
            VALUES ($user_id, '$contact_name', '$contact_phone', '$contact_email', '$relationship')";
    if ($conn->query($sql)) {
        $success = "Contact added successfully!";
    } else {
        $error = "Failed to add contact";
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $contact_id = (int)$_GET['id'];
    $conn->query("DELETE FROM emergency_contacts WHERE id = $contact_id AND user_id = $user_id");
    header("Location: trusted_contacts.php");
    exit();
}

$contacts = $conn->query("SELECT * FROM emergency_contacts WHERE user_id = $user_id ORDER BY id DESC");
?>

<!-- Page Header -->
<div class="page-header-gradient">
    <div class="page-header-kicker">
        <span>Safety Features</span>
    </div>
    <h1><i class="fas fa-address-book"></i> Trusted Contacts</h1>
    <p>Manage your emergency contacts for safety during trips.</p>
</div>

<?php if ($success): ?>
<div class="st-alert st-alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="st-alert st-alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
<?php endif; ?>

<div class="st-card" style="margin-bottom: 20px;">
    <div class="st-card-header">
        <h3><i class="fas fa-user-plus"></i> Add New Contact</h3>
    </div>
    <div class="st-card-body">
        <form method="POST">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Contact Name</label>
                    <input type="text" name="contact_name" required placeholder="Full Name" class="input-full">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Phone Number</label>
                    <input type="tel" name="contact_phone" required placeholder="+91..." class="input-full">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Email Address</label>
                    <input type="email" name="contact_email" placeholder="email@example.com" class="input-full">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Relationship</label>
                    <select name="relationship" required class="input-full">
                        <option value="Parent">Parent</option>
                        <option value="Spouse">Spouse</option>
                        <option value="Sibling">Sibling</option>
                        <option value="Friend">Friend</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="add_contact" class="st-btn st-btn-primary" style="margin-top: 20px;">
                <i class="fas fa-plus"></i> Add Contact
            </button>
        </form>
    </div>
</div>

<div class="st-card">
    <div class="st-card-header">
        <h3><i class="fas fa-users"></i> Your Contacts (<?php echo $contacts ? $contacts->num_rows : 0; ?>)</h3>
    </div>
    <div class="st-card-body">
        <?php if ($contacts && $contacts->num_rows > 0): ?>
            <?php while($contact = $contacts->fetch_assoc()): ?>
            <?php 
            $cleanPhone = preg_replace('/[^0-9]/', '', $contact['contact_phone']);
            $emergencyMsg = "🚨 EMERGENCY! I need help. This is " . $contact['contact_name'] . ".";
            $whatsappLink = 'https://wa.me/' . $cleanPhone . '?text=' . urlencode($emergencyMsg);
            ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #e5e7eb;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: #0f766e; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        <?php echo strtoupper(substr($contact['contact_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <strong style="font-size: 1.05rem;"><?php echo htmlspecialchars($contact['contact_name']); ?></strong>
                        <div style="color: #667085; font-size: 0.9rem; margin-top: 3px;">
                            <a href="tel:<?php echo htmlspecialchars($contact['contact_phone']); ?>" style="color: #667085; text-decoration: none;">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($contact['contact_phone']); ?>
                            </a>
                            <a href="<?php echo $whatsappLink; ?>" target="_blank" style="color: #25D366; text-decoration: none; margin-left: 8px;">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                            <?php if (!empty($contact['contact_email'])): ?>
                            | <a href="mailto:<?php echo htmlspecialchars($contact['contact_email']); ?>" style="color: #667085; text-decoration: none;">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact['contact_email']); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 5px;">
                            <span style="background: rgba(15, 118, 110, 0.1); padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; color: #0f766e;">
                                <?php echo htmlspecialchars($contact['relationship']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <a href="?action=delete&id=<?php echo $contact['id']; ?>" class="st-btn st-btn-sm st-btn-danger" onclick="return confirm('Remove this contact?')">
                    <i class="fas fa-trash"></i>
                </a>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #667085;">
                <i class="fas fa-user-friends" style="font-size: 3rem; opacity: 0.3; margin-bottom: 15px;"></i>
                <p>No trusted contacts added yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/user_footer.php'; ?>