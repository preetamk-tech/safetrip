<?php

require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotLoggedIn();

$page_title = 'Trip Expenses';
require_once '../includes/user_header.php';

$user_id = getUserId();
$group_id = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

$check = $conn->query("SELECT * FROM group_members WHERE group_id = $group_id AND user_id = $user_id");
if ($check->num_rows == 0) {
    header("Location: groups.php");
    exit();
}

$group_info = $conn->query("
    SELECT tg.*, t.from_location, t.to_location, t.travel_date
    FROM travel_groups tg
    JOIN trips t ON tg.trip_id = t.id
    WHERE tg.id = $group_id
")->fetch_assoc();

$expenses = get_trip_expenses($group_id);
$total_cost = get_total_trip_cost($group_id);
$balances = get_group_balances($group_id);

$members = $conn->query("
    SELECT gm.user_id, u.full_name
    FROM group_members gm
    JOIN users u ON gm.user_id = u.id
    WHERE gm.group_id = $group_id
");

$member_list = [];
while ($m = $members->fetch_assoc()) {
    $member_list[$m['user_id']] = $m['full_name'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_expense'])) {
    verify_csrf_or_abort();
    $description = trim($_POST['description']);
    $amount = (float) $_POST['amount'];
    $category = $_POST['category'] ?? 'other';
    $split_with = isset($_POST['split_with']) ? array_map('intval', $_POST['split_with']) : array_keys($member_list);

    if (!empty($description) && $amount > 0) {
        $expense_id = add_trip_expense($group_info['trip_id'], $group_id, $user_id, $description, $amount, $category);
        if ($expense_id) {
            split_expense_equally($expense_id, $split_with);
            header("Location: expenses.php?group_id=$group_id&added=1");
            exit();
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header-gradient">
    <div class="page-header-kicker">
        <a href="groups.php?group_id=<?php echo $group_id; ?>" class="st-btn st-btn-secondary" style="margin-bottom: 0;">
            <i class="fas fa-arrow-left"></i> Back to Group Chat
        </a>
    </div>
    <h1><i class="fas fa-calculator"></i> Trip Expenses</h1>
    <p><i class="fas fa-route"></i> <?php echo htmlspecialchars($group_info['from_location']); ?> → <?php echo htmlspecialchars($group_info['to_location']); ?></p>
</div>

<?php if (isset($_GET['added'])): ?>
<div class="st-alert st-alert-success"><i class="fas fa-check-circle"></i> Expense added successfully!</div>
<?php endif; ?>

<div class="st-card" style="margin-bottom: 20px;">
    <div class="st-card-header">
        <h3><i class="fas fa-chart-pie"></i> Balance Summary</h3>
    </div>
    <div class="st-card-body">
        <div style="text-align: center; margin-bottom: 20px;">
            <span style="font-size: 2rem; font-weight: 700; color: #0f766e;">₹<?php echo number_format($total_cost, 2); ?></span>
            <span style="color: #667085;"> Total Trip Cost</span>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
            <?php
            foreach ($member_list as $id => $name):
                $balance = $balances[$id] ?? 0;
                $per_person = count($member_list) > 0 ? $total_cost / count($member_list) : 0;
                $owes = $per_person - $balance;
            ?>
            <div style="text-align: center; padding: 15px; background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <strong style="display: block; margin-bottom: 5px;"><?php echo htmlspecialchars($name); ?></strong>
                <div style="font-size: 1.1rem; color: <?php echo $owes > 0 ? '#dc2626' : '#0f766e'; ?>;">
                    <?php if ($owes > 0): ?>
                    Owes ₹<?php echo number_format($owes, 2); ?>
                    <?php else: ?>
                    Gets ₹<?php echo number_format(abs($owes), 2); ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="st-card" style="margin-bottom: 20px;">
    <div class="st-card-header">
        <h3><i class="fas fa-plus-circle"></i> Add New Expense</h3>
    </div>
    <div class="st-card-body">
        <form method="POST">
            <?php echo csrf_input(); ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Description</label>
                    <input type="text" name="description" required placeholder="e.g., Fuel, Tolls, Snacks" class="input-full">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Amount (₹)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0.00" class="input-full">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Category</label>
                    <select name="category" class="input-full">
                        <option value="fuel">Fuel</option>
                        <option value="toll">Toll</option>
                        <option value="food">Food</option>
                        <option value="ticket">Ticket</option>
                        <option value="accommodation">Stay</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div style="margin-top: 20px;">
                <label style="display: block; margin-bottom: 10px; font-weight: 600;"><i class="fas fa-users"></i> Split With:</label>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php foreach ($member_list as $id => $name): ?>
                    <label style="display: flex; align-items: center; gap: 5px; padding: 8px 12px; background: rgba(15, 118, 110, 0.1); border-radius: 999px; cursor: pointer;">
                        <input type="checkbox" name="split_with[]" value="<?php echo $id; ?>" <?php echo $id == $user_id ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($name); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" name="add_expense" class="st-btn st-btn-primary" style="margin-top: 20px;">
                <i class="fas fa-plus"></i> Add Expense
            </button>
        </form>
    </div>
</div>

<div class="st-card">
    <div class="st-card-header">
        <h3><i class="fas fa-list"></i> Expense History</h3>
    </div>
    <div class="st-card-body">
        <?php if (!empty($expenses)): ?>
            <?php foreach ($expenses as $expense): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #e5e7eb;">
                <div>
                    <strong><i class="fas fa-<?php echo $expense['category'] === 'fuel' ? 'gas-pump' : ($expense['category'] === 'food' ? 'utensils' : 'receipt'); ?>" style="color: #0f766e;"></i> <?php echo htmlspecialchars($expense['description']); ?></strong>
                    <div style="color: #667085; font-size: 0.85rem; margin-top: 5px;">
                        <?php echo htmlspecialchars($expense['added_by_name']); ?> • <?php echo date('d M, h:i A', strtotime($expense['created_at'])); ?>
                    </div>
                    <span style="display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; margin-top: 5px; background: <?php echo $expense['category'] === 'fuel' ? '#fee2e2' : ($expense['category'] === 'food' ? '#dcfce7' : '#f3f4f6'); ?>; color: <?php echo $expense['category'] === 'fuel' ? '#991b1b' : ($expense['category'] === 'food' ? '#166534' : '#374151'); ?>;"><?php echo ucfirst($expense['category']); ?></span>
                </div>
                <div style="font-size: 1.2rem; font-weight: 700; color: #0f766e;">₹<?php echo number_format($expense['amount'], 2); ?></div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #667085;">
                <i class="fas fa-receipt" style="font-size: 3rem; opacity: 0.3; margin-bottom: 15px;"></i>
                <p>No expenses recorded yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/user_footer.php'; ?>