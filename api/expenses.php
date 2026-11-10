<?php

require_once '../config/database.php';
require_once '../includes/session.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['success' => false, 'message' => 'Not logged in'], 401);
}

$user_id = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $group_id = (int) ($_GET['group_id'] ?? 0);
        if ($group_id <= 0) {
            json_response(['success' => false, 'message' => 'Invalid group ID']);
        }

        $expenses = get_trip_expenses($group_id);
        $total = get_total_trip_cost($group_id);
        $balances = get_group_balances($group_id);
        json_response(['success' => true, 'expenses' => $expenses, 'total' => $total, 'balances' => $balances]);
        break;

    case 'add':
        require_json_request_or_abort();
        $data = read_json_input();

        $trip_id = (int) ($data['trip_id'] ?? 0);
        $group_id = (int) ($data['group_id'] ?? 0);
        $description = trim($data['description'] ?? '');
        $amount = (float) ($data['amount'] ?? 0);
        $category = $data['category'] ?? 'other';
        $split_with = $data['split_with'] ?? [];

        if (empty($description) || $amount <= 0) {
            json_response(['success' => false, 'message' => 'Invalid expense data']);
        }

        $expense_id = add_trip_expense($trip_id, $group_id, $user_id, $description, $amount, $category);

        if ($expense_id && !empty($split_with)) {
            split_expense_equally($expense_id, $split_with);
        }

        json_response(['success' => $expense_id > 0, 'expense_id' => $expense_id]);
        break;

    case 'settle':
        require_json_request_or_abort();
        $data = read_json_input();
        $expense_id = (int) ($data['expense_id'] ?? 0);

        if ($expense_id <= 0) {
            json_response(['success' => false, 'message' => 'Invalid expense ID']);
        }

        $success = mark_split_settled($expense_id, $user_id);
        json_response(['success' => $success]);
        break;

    case 'summary':
        $group_id = (int) ($_GET['group_id'] ?? 0);
        if ($group_id <= 0) {
            json_response(['success' => false, 'message' => 'Invalid group ID']);
        }

        $balances = get_group_balances($group_id);
        $total = get_total_trip_cost($group_id);
        $owes = get_user_owes_group($user_id, $group_id);

        json_response([
            'success' => true,
            'total_cost' => $total,
            'my_owes' => $owes,
            'balances' => $balances
        ]);
        break;

    default:
        json_response(['success' => false, 'message' => 'Invalid action']);
}
?>