<?php
// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Initialize auth
$auth = new Auth();

// Require admin login
$auth->requireAdmin();

// Get database instance
$db = Database::getInstance();

// Get assignment ID
$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize response array
$response = [
    'success' => false,
    'updates' => [],
    'message' => ''
];

// Validate assignment ID
if ($assignment_id <= 0) {
    $response['message'] = 'Invalid assignment ID';
    echo json_encode($response);
    exit;
}

// Get updates for the assignment
$updates = $db->query("
    SELECT wu.*, e.name as employee_name 
    FROM work_updates wu
    JOIN employees e ON wu.employee_id = e.id
    WHERE wu.assignment_id = $assignment_id
    ORDER BY wu.created_at DESC
");

// Check if updates exist
if ($updates->num_rows > 0) {
    $response['success'] = true;
    
    // Format updates
    while ($update = $updates->fetch_assoc()) {
        $response['updates'][] = [
            'id' => $update['id'],
            'employee_name' => $update['employee_name'],
            'update_description' => $update['update_description'],
            'work_status' => $update['work_status'],
            'start_time' => $update['start_time'],
            'end_time' => $update['end_time'],
            'hours_spent' => $update['hours_spent'],
            'is_extra_work' => $update['is_extra_work'],
            'created_at' => formatDate($update['created_at'], 'd M Y h:i A')
        ];
    }
} else {
    $response['message'] = 'No updates found';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>