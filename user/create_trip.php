<?php
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotLoggedIn();

$page_title = 'Create Trip';
require_once '../includes/user_header.php';

$user_id = getUserId();
$success = '';
$error = '';

$favorites = get_favorite_routes($user_id, 5);
$quick_from = $_GET['from'] ?? ($_SESSION['quickTripFrom'] ?? '');
$quick_to = $_GET['to'] ?? ($_SESSION['quickTripTo'] ?? '');
$quick_mode = $_GET['mode'] ?? ($_SESSION['quickTripMode'] ?? 'train');

unset($_SESSION['quickTripFrom'], $_SESSION['quickTripTo'], $_SESSION['quickTripMode']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $from_location = mysqli_real_escape_string($conn, $_POST['from_location']);
    $to_location = mysqli_real_escape_string($conn, $_POST['to_location']);
    $travel_date = $_POST['travel_date'];
    $travel_time = $_POST['travel_time'];
    $seats_available = (int)$_POST['seats_available'];
    $transport_mode = $_POST['transport_mode'];
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
    $recurring_days = isset($_POST['recurring_days']) ? implode(',', $_POST['recurring_days']) : '';
    $safety_alerts = isset($_POST['safety_alerts']) ? 1 : 0;
    $women_only = isset($_POST['women_only']) ? 1 : 0;

    $share_code = generate_share_code();

    $sql = "INSERT INTO trips (user_id, share_code, from_location, to_location, travel_date, travel_time, seats_available, transport_mode)
            VALUES ($user_id, '$share_code', '$from_location', '$to_location', '$travel_date', '$travel_time', $seats_available, '$transport_mode')";

    if ($conn->query($sql)) {
        $trip_id = $conn->insert_id;
        add_favorite_route($user_id, $from_location, $to_location, $transport_mode);
        $success = "Trip created successfully!";
        log_activity($user_id, 'trip_created', "Trip $trip_id created");
    } else {
        $error = "Failed to create trip: " . $conn->error;
    }
}

if (!empty($favorites)):
?>
<div class="st-card" style="margin-bottom: 20px; background: linear-gradient(135deg, #0f766e, #14b8a6); border: none;">
    <div class="st-card-body" style="padding: 20px;">
        <div style="display: flex; align-items: center; gap: 10px; color: white; margin-bottom: 10px;">
            <i class="fas fa-bolt"></i> <strong>Quick Fill from Favorites:</strong>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php foreach ($favorites as $fav): ?>
            <button type="button" onclick="fillFromFav('<?php echo htmlspecialchars(addslashes($fav['from_location'])); ?>', '<?php echo htmlspecialchars(addslashes($fav['to_location'])); ?>', '<?php echo htmlspecialchars($fav['transport_mode']); ?>')" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 8px 15px; border-radius: 20px; cursor: pointer; font-size: 0.85rem;">
                <?php echo htmlspecialchars($fav['from_location']); ?> → <?php echo htmlspecialchars($fav['to_location']); ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header-gradient">
    <div class="page-header-kicker">
        <span>Trip Studio</span>
    </div>
    <h1><i class="fas fa-plus-circle"></i> Create New Travel Plan</h1>
    <p>Create a polished route listing so verified co-travelers can discover and join you with confidence.</p>
</div>

<div class="st-card">
    <div class="st-card-body">
        <?php if ($success): ?>
        <div class="st-alert st-alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?> <a href="dashboard.php" style="color: inherit; text-decoration: underline;">Go to Dashboard</a></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="st-alert st-alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;"><i class="fas fa-map-marker-alt" style="color: #0f766e;"></i> From Location *</label>
                    <input type="text" name="from_location" id="from_location" required placeholder="e.g., Delhi, Mumbai, Bangalore" value="<?php echo htmlspecialchars($quick_from); ?>" style="width: 100%; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 0.95rem; transition: border-color 0.3s;" onfocus="this.style.borderColor='#0f766e'" onblur="this.style.borderColor='#e5e7eb'">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;"><i class="fas fa-map-marker-alt" style="color: #ef4444;"></i> To Location *</label>
                    <input type="text" name="to_location" id="to_location" required placeholder="e.g., Jaipur, Pune, Chennai" value="<?php echo htmlspecialchars($quick_to); ?>" style="width: 100%; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 0.95rem; transition: border-color 0.3s;" onfocus="this.style.borderColor='#0f766e'" onblur="this.style.borderColor='#e5e7eb'">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;"><i class="fas fa-calendar" style="color: #0f766e;"></i> Travel Date *</label>
                    <input type="date" name="travel_date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" style="width: 100%; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 0.95rem; transition: border-color 0.3s;" onfocus="this.style.borderColor='#0f766e'" onblur="this.style.borderColor='#e5e7eb'">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;"><i class="fas fa-clock" style="color: #0f766e;"></i> Travel Time *</label>
                    <input type="time" name="travel_time" required value="09:00" style="width: 100%; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 0.95rem; transition: border-color 0.3s;" onfocus="this.style.borderColor='#0f766e'" onblur="this.style.borderColor='#e5e7eb'">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;"><i class="fas fa-chair" style="color: #0f766e;"></i> Seats Available</label>
                    <input type="number" name="seats_available" value="1" min="1" max="10" style="width: 100%; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 0.95rem; transition: border-color 0.3s;" onfocus="this.style.borderColor='#0f766e'" onblur="this.style.borderColor='#e5e7eb'">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;"><i class="fas fa-bus" style="color: #0f766e;"></i> Transport Mode</label>
                    <select name="transport_mode" id="transport_mode" style="width: 100%; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 0.95rem; background: white; transition: border-color 0.3s;" onfocus="this.style.borderColor='#0f766e'" onblur="this.style.borderColor='#e5e7eb'">
                        <option value="train" <?php echo $quick_mode === 'train' ? 'selected' : ''; ?>>🚂 Train</option>
                        <option value="bus" <?php echo $quick_mode === 'bus' ? 'selected' : ''; ?>>🚌 Bus</option>
                        <option value="flight" <?php echo $quick_mode === 'flight' ? 'selected' : ''; ?>>✈️ Flight</option>
                        <option value="car" <?php echo $quick_mode === 'car' ? 'selected' : ''; ?>>🚗 Car</option>
                        <option value="bike" <?php echo $quick_mode === 'bike' ? 'selected' : ''; ?>>🏍️ Bike</option>
                        <option value="other">🚶 Other</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 25px;">
                <h4 style="margin-bottom: 15px; display: flex; align-items: center; gap: 8px;"><i class="fas fa-shield-heart" style="color: #0f766e;"></i> Safety Options</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <label style="display: flex; align-items: flex-start; gap: 12px; padding: 15px; background: #f0fdf4; border-radius: 12px; border: 2px solid transparent; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#0f766e'" onmouseout="this.style.borderColor='transparent'" onclick="toggleCheckbox(this)">
                        <input type="checkbox" name="safety_alerts" value="1" checked style="margin-top: 3px; width: 18px; height: 18px; accent-color: #0f766e;">
                        <div>
                            <strong style="color: #0f766e;">Safety Check-ins</strong>
                            <p style="margin: 5px 0 0; color: #667085; font-size: 0.85rem;">Periodic safety prompts during trip</p>
                        </div>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 12px; padding: 15px; background: #fdf2f8; border-radius: 12px; border: 2px solid transparent; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#ec4899'" onmouseout="this.style.borderColor='transparent'" onclick="toggleCheckbox(this)">
                        <input type="checkbox" name="women_only" value="1" style="margin-top: 3px; width: 18px; height: 18px; accent-color: #ec4899;">
                        <div>
                            <strong style="color: #ec4899;">Women-Only Trip</strong>
                            <p style="margin: 5px 0 0; color: #667085; font-size: 0.85rem;">Only female travelers can join</p>
                        </div>
                    </label>
                </div>
            </div>

            <div style="margin-top: 25px;">
                <h4 style="margin-bottom: 15px; display: flex; align-items: center; gap: 8px;"><i class="fas fa-repeat" style="color: #f97316;"></i> Recurring Trip</h4>
                <label style="display: flex; align-items: flex-start; gap: 12px; padding: 15px; background: #fff7ed; border-radius: 12px; border: 2px solid transparent; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#f97316'" onmouseout="this.style.borderColor='transparent'" onclick="toggleRecurringCheck()">
                    <input type="checkbox" name="is_recurring" id="is_recurring" value="1" style="margin-top: 3px; width: 18px; height: 18px; accent-color: #f97316;">
                    <div>
                        <strong style="color: #f97316;">Recurring Trip</strong>
                        <p style="margin: 5px 0 0; color: #667085; font-size: 0.85rem;">Repeat this trip on specific days</p>
                    </div>
                </label>
                <div id="recurring_days_box" style="display: none; margin-top: 15px;">
                    <p style="margin-bottom: 12px; color: #667085; font-weight: 600;">Select days:</p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <?php
                        $days = ['Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday', 'Sun' => 'Sunday'];
                        $day_colors = ['Mon' => '#3498db', 'Tue' => '#9b59b6', 'Wed' => '#e74c3c', 'Thu' => '#f39c12', 'Fri' => '#1abc9c', 'Sat' => '#2ecc71', 'Sun' => '#34495e'];
                        foreach ($days as $key => $day): ?>
                        <label style="display: flex; align-items: center; gap: 5px; padding: 10px 16px; background: white; border-radius: 25px; border: 2px solid <?php echo $day_colors[$key]; ?>; cursor: pointer; transition: all 0.3s; font-weight: 500;" onmouseover="this.style.background='<?php echo $day_colors[$key]; ?>'; this.style.color='white'" onmouseout="this.style.background='white'; this.style.color='inherit'">
                            <input type="checkbox" name="recurring_days[]" value="<?php echo $key; ?>" style="display: none;">
                            <?php echo $day; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <button type="submit" style="background: linear-gradient(135deg, #0f766e, #14b8a6); color: white; border: none; padding: 14px 30px; border-radius: 12px; font-weight: 600; font-size: 1rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <i class="fas fa-save"></i> Create Trip
                </button>
                <a href="dashboard.php" style="background: #f3f4f6; color: #374151; border: none; padding: 14px 30px; border-radius: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/user_footer.php'; ?>

<script>
function fillFromFav(from, to, mode) {
    document.getElementById('from_location').value = from;
    document.getElementById('to_location').value = to;
    document.getElementById('transport_mode').value = mode;
}

function toggleRecurringCheck() {
    setTimeout(() => {
        const checkbox = document.getElementById('is_recurring');
        const daysBox = document.getElementById('recurring_days_box');
        daysBox.style.display = checkbox.checked ? 'block' : 'none';
    }, 10);
}

function toggleCheckbox(label) {
    const checkbox = label.querySelector('input[type="checkbox"]');
    checkbox.checked = !checkbox.checked;
}
</script>