<?php
// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Initialize auth
$auth = new Auth();

// Require employee login
$auth->requireEmployee();

// Get database instance
$db = Database::getInstance();

// Get employee data
$employee_id = $_SESSION['employee_id'];

// Get assignment ID from URL
$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize variables
$assignment = null;
$error = '';
$message = '';

// If assignment ID is provided, get assignment details
if ($assignment_id > 0) {
    // Fetch assignment details
    $assignment_result = $db->query("
        SELECT a.*, e.name as employee_name
        FROM work_assignments a
        JOIN employees e ON a.employee_id = e.id
        WHERE a.id = $assignment_id AND a.employee_id = $employee_id
    ");

    if ($assignment_result->num_rows === 1) {
        $assignment = $assignment_result->fetch_assoc();
    } else {
        $error = "Work assignment not found or you don't have permission to update it.";
    }
}

// Process work update form submission
if (isPostRequest() && $assignment_id > 0) {
    // Get form data
    $update_description = cleanInput($_POST['update_description']);
    $work_status = cleanInput($_POST['work_status']);
    $start_time = !empty($_POST['start_time']) ? cleanInput($_POST['start_time']) : null;
    $end_time = !empty($_POST['end_time']) ? cleanInput($_POST['end_time']) : null;
    $is_extra_work = isset($_POST['is_extra_work']) ? 1 : 0;

    // Calculate hours spent
    $hours_spent = 0;
    if (!empty($start_time) && !empty($end_time)) {
        $hours_spent = calculateWorkDuration($start_time, $end_time);
    }

    // Validate data
    if (empty($update_description) || empty($work_status)) {
        $error = "Please provide update description and select status.";
    } else {
        // Insert work update
        $sql = "INSERT INTO work_updates (assignment_id, employee_id, update_description, work_status, start_time, end_time, hours_spent, is_extra_work)
                VALUES ($assignment_id, $employee_id, '$update_description', '$work_status', " .
                (!empty($start_time) ? "'$start_time'" : "NULL") . ", " .
                (!empty($end_time) ? "'$end_time'" : "NULL") . ",
                $hours_spent, $is_extra_work)";

        if ($db->query($sql)) {
            // Update assignment status in work_assignments table
            $db->query("UPDATE work_assignments SET status = '$work_status' WHERE id = $assignment_id");

            $_SESSION['success'] = "Work update submitted successfully!";
            redirect(BASE_URL . '/employee/dashboard.php');
        } else {
            $error = "Error submitting work update: " . $db->getConnection()->error;
        }
    }
}

// Get employee's assignments for dropdown
$assignments = $db->query("
    SELECT * FROM work_assignments
    WHERE employee_id = $employee_id AND status != 'completed' AND status != 'cancelled'
    ORDER BY priority DESC, created_at DESC
");

// Set page title
$page_title = 'Update Work Status';

// Include header
include_once '../templates/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?php echo $assignment_id > 0 ? 'Update Work Status' : 'Select Work to Update'; ?></h1>
    <a href="<?php echo BASE_URL; ?>/employee/history.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-history fa-sm text-white-50 me-1"></i> View Work History
    </a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($assignment_id <= 0): ?>
<!-- Select Work Assignment -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Select Work to Update</h6>
    </div>
    <div class="card-body">
        <?php if ($assignments->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Assigned Date</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $assignments->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                    <?php if (!empty($row['description'])): ?>
                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . (strlen($row['description']) > 50 ? '...' : ''); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($row['assigned_date'], 'd M Y'); ?></td>
                                <td><?php echo getPriorityBadge($row['priority']); ?></td>
                                <td><?php echo getWorkStatusBadge($row['status']); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/employee/update_work.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit me-1"></i> Update
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                <p class="mb-0">You don't have any pending or in-progress work assignments.</p>
                <a href="<?php echo BASE_URL; ?>/employee/dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<!-- Update Work Status Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Update Work Status</h6>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $assignment_id); ?>">
                    <div class="mb-3">
                        <label for="work_status" class="form-label">Work Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="work_status" name="work_status" required>
                            <option value="">-- Select Status --</option>
                            <option value="pending" <?php if ($assignment['status'] === 'pending') echo 'selected'; ?>>Pending</option>
                            <option value="in_progress" <?php if ($assignment['status'] === 'in_progress') echo 'selected'; ?>>In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="update_description" class="form-label">Update Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="update_description" name="update_description" rows="4" placeholder="Describe your progress, challenges, or anything relevant about the work." required></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-5">
                            <label for="start_time" class="form-label">Start Time</label>
                            <input type="datetime-local" class="form-control" id="start_time" name="start_time">
                        </div>
                        <div class="col-md-5">
                            <label for="end_time" class="form-label">End Time</label>
                            <input type="datetime-local" class="form-control" id="end_time" name="end_time">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" id="setCurrentTime" class="btn btn-outline-secondary w-100">Set Now</button>
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_extra_work" name="is_extra_work">
                        <label class="form-check-label" for="is_extra_work">Mark as Extra Work</label>
                        <small class="form-text text-muted d-block">Check this if you've done additional work beyond what was assigned.</small>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Submit Update
                        </button>
                        <a href="<?php echo BASE_URL; ?>/employee/dashboard.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Work Details Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Work Details</h6>
            </div>
            <div class="card-body">
                <h5><?php echo htmlspecialchars($assignment['title']); ?></h5>

                <div class="mb-3">
                    <span class="d-block text-muted small">Assigned Date</span>
                    <strong><?php echo formatDate($assignment['assigned_date'], 'd M Y'); ?></strong>
                </div>

                <div class="mb-3">
                    <span class="d-block text-muted small">Priority</span>
                    <?php echo getPriorityBadge($assignment['priority']); ?>
                </div>

                <div class="mb-3">
                    <span class="d-block text-muted small">Current Status</span>
                    <?php echo getWorkStatusBadge($assignment['status']); ?>
                </div>

                <?php if (!empty($assignment['deadline'])): ?>
                <div class="mb-3">
                    <span class="d-block text-muted small">Deadline</span>
                    <strong><?php echo formatDate($assignment['deadline'], 'd M Y h:i A'); ?></strong>

                    <?php
                    // Calculate time left to deadline
                    $deadline_time = strtotime($assignment['deadline']);
                    $current_time = time();
                    $time_left = $deadline_time - $current_time;

                    if ($time_left > 0) {
                        $days_left = floor($time_left / (60 * 60 * 24));
                        $hours_left = floor(($time_left % (60 * 60 * 24)) / (60 * 60));

                        echo '<span class="badge bg-info mt-1">';
                        if ($days_left > 0) {
                            echo $days_left . ' days, ' . $hours_left . ' hours left';
                        } else {
                            echo $hours_left . ' hours left';
                        }
                        echo '</span>';
                    } else {
                        echo '<span class="badge bg-danger mt-1">Deadline passed</span>';
                    }
                    ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($assignment['description'])): ?>
                <div class="mb-3">
                    <span class="d-block text-muted small">Description</span>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Previous Updates -->
        <?php
        $updates = $db->query("
            SELECT * FROM work_updates
            WHERE assignment_id = $assignment_id
            ORDER BY created_at DESC
            LIMIT 5
        ");

        if ($updates->num_rows > 0):
        ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Previous Updates</h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php while ($update = $updates->fetch_assoc()): ?>
                    <div class="timeline-item">
                        <div class="timeline-item-marker">
                            <div class="timeline-item-marker-text"><?php echo formatDate($update['created_at'], 'd M'); ?></div>
                            <div class="timeline-item-marker-indicator bg-primary"></div>
                        </div>
                        <div class="timeline-item-content pt-0">
                            <div class="card mb-2">
                                <div class="card-body py-2 px-3">
                                    <div class="small text-muted"><?php echo formatDate($update['created_at'], 'h:i A'); ?></div>
                                    <div><?php echo getWorkStatusBadge($update['work_status']); ?></div>
                                    <?php if (!empty($update['update_description'])): ?>
                                    <div class="small mt-1"><?php echo htmlspecialchars($update['update_description']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($update['hours_spent'] > 0): ?>
                                    <div class="small text-muted mt-1">Time spent: <?php echo formatWorkTime($update['hours_spent']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Timeline style */
.timeline {
    position: relative;
    padding-left: 1rem;
    margin: 1rem 0;
}
.timeline:before {
    content: '';
    position: absolute;
    left: 0.25rem;
    top: 0;
    bottom: 0;
    width: 1px;
    background-color: #e3e6ec;
}
.timeline-item {
    position: relative;
    padding-left: 1.5rem;
    margin-bottom: 1rem;
}
.timeline-item-marker {
    position: absolute;
    left: -1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.timeline-item-marker-text {
    font-size: 0.75rem;
    color: #a2acba;
}
.timeline-item-marker-indicator {
    height: 0.75rem;
    width: 0.75rem;
    border-radius: 100%;
    margin-top: 0.25rem;
    margin-bottom: 0.25rem;
}
</style>

<script>
// Set current date/time for start/end time inputs
document.addEventListener('DOMContentLoaded', function() {
    const setCurrentTimeBtn = document.getElementById('setCurrentTime');
    const endTimeInput = document.getElementById('end_time');

    setCurrentTimeBtn.addEventListener('click', function() {
        const now = new Date();
        // Format the date as YYYY-MM-DDThh:mm
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');

        const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
        endTimeInput.value = formattedDateTime;
    });
});
</script>
<?php endif; ?>

<?php include_once '../templates/footer.php'; ?>
