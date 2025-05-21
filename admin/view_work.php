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

// Process actions
$action = isset($_GET['action']) ? $_GET['action'] : '';
$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Process delete action
if ($action === 'delete' && $assignment_id > 0) {
    if ($db->query("DELETE FROM work_assignments WHERE id = $assignment_id")) {
        $_SESSION['success'] = 'Work assignment deleted successfully!';
    } else {
        $_SESSION['error'] = 'Error deleting work assignment: ' . $db->getConnection()->error;
    }
    redirect(BASE_URL . '/admin/view_work.php');
}

// Process change status action
if ($action === 'change_status' && $assignment_id > 0 && isset($_GET['status'])) {
    $status = cleanInput($_GET['status']);

    // Validate status
    $valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
    if (in_array($status, $valid_statuses)) {
        if ($db->query("UPDATE work_assignments SET status = '$status' WHERE id = $assignment_id")) {
            $_SESSION['success'] = 'Work status updated successfully!';
        } else {
            $_SESSION['error'] = 'Error updating work status: ' . $db->getConnection()->error;
        }
    } else {
        $_SESSION['error'] = 'Invalid status!';
    }
    redirect(BASE_URL . '/admin/view_work.php');
}

// Handle filters
$filter_employee = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$filter_status = isset($_GET['status']) ? cleanInput($_GET['status']) : '';
$filter_date = isset($_GET['date']) ? cleanInput($_GET['date']) : '';
$filter_priority = isset($_GET['priority']) ? cleanInput($_GET['priority']) : '';

// Build query with filters
$query = "SELECT a.*, e.name as employee_name
          FROM work_assignments a
          JOIN employees e ON a.employee_id = e.id
          WHERE 1=1";

$params = [];

if ($filter_employee > 0) {
    $query .= " AND a.employee_id = $filter_employee";
    $params[] = "employee_id=$filter_employee";
}

if (!empty($filter_status)) {
    $query .= " AND a.status = '$filter_status'";
    $params[] = "status=$filter_status";
}

if (!empty($filter_date)) {
    $query .= " AND a.assigned_date = '$filter_date'";
    $params[] = "date=$filter_date";
}

if (!empty($filter_priority)) {
    $query .= " AND a.priority = '$filter_priority'";
    $params[] = "priority=$filter_priority";
}

// Add order by
$query .= " ORDER BY a.created_at DESC";

// Execute query
$assignments = $db->query($query);

// Get employees for filter dropdown
$employees = $db->query("SELECT id, name FROM employees WHERE status = 'active' ORDER BY name ASC");

// Set page title
$page_title = 'View Work Assignments';

// Include header
include_once '../templates/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Work Assignments</h1>
    <a href="<?php echo BASE_URL; ?>/admin/assign_work.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50 me-1"></i> Assign New Work
    </a>
</div>

<!-- Filters -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Filter Work Assignments</h6>
        <?php if (!empty($params)): ?>
            <a href="<?php echo BASE_URL; ?>/admin/view_work.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-times me-1"></i> Clear Filters
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
            <div class="col-md-3">
                <label for="employee_id" class="form-label">Employee</label>
                <select class="form-select" id="employee_id" name="employee_id">
                    <option value="">All Employees</option>
                    <?php
                    // Reset the internal pointer
                    $employees->data_seek(0);
                    while ($employee = $employees->fetch_assoc()):
                    ?>
                        <option value="<?php echo $employee['id']; ?>" <?php if ($employee['id'] == $filter_employee) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($employee['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php if ($filter_status === 'pending') echo 'selected'; ?>>Pending</option>
                    <option value="in_progress" <?php if ($filter_status === 'in_progress') echo 'selected'; ?>>In Progress</option>
                    <option value="completed" <?php if ($filter_status === 'completed') echo 'selected'; ?>>Completed</option>
                    <option value="cancelled" <?php if ($filter_status === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="priority" class="form-label">Priority</label>
                <select class="form-select" id="priority" name="priority">
                    <option value="">All Priorities</option>
                    <option value="low" <?php if ($filter_priority === 'low') echo 'selected'; ?>>Low</option>
                    <option value="medium" <?php if ($filter_priority === 'medium') echo 'selected'; ?>>Medium</option>
                    <option value="high" <?php if ($filter_priority === 'high') echo 'selected'; ?>>High</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="date" class="form-label">Assigned Date</label>
                <input type="date" class="form-control" id="date" name="date" value="<?php echo $filter_date; ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Work Assignments Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">All Work Assignments</h6>
    </div>
    <div class="card-body">
        <?php if ($assignments->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Employee</th>
                            <th>Assigned Date</th>
                            <th>Deadline</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($assignment = $assignments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $assignment['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                                    <?php if (!empty($assignment['description'])): ?>
                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars(substr($assignment['description'], 0, 50)) . (strlen($assignment['description']) > 50 ? '...' : ''); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($assignment['employee_name']); ?></td>
                                <td><?php echo formatDate($assignment['assigned_date'], 'd M Y'); ?></td>
                                <td>
                                    <?php
                                    if (!empty($assignment['deadline'])) {
                                        echo formatDate($assignment['deadline'], 'd M Y h:i A');
                                    } else {
                                        echo '<span class="text-muted">No deadline</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo getPriorityBadge($assignment['priority']); ?></td>
                                <td><?php echo getWorkStatusBadge($assignment['status']); ?></td>
                                <td>
                                    <!-- View Details -->
                                    <button type="button" class="btn btn-sm btn-info view-details" data-bs-toggle="modal" data-bs-target="#detailsModal"
                                            data-id="<?php echo $assignment['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($assignment['title']); ?>"
                                            data-description="<?php echo htmlspecialchars($assignment['description']); ?>"
                                            data-employee="<?php echo htmlspecialchars($assignment['employee_name']); ?>"
                                            data-assigned-date="<?php echo formatDate($assignment['assigned_date'], 'd M Y'); ?>"
                                            data-deadline="<?php echo !empty($assignment['deadline']) ? formatDate($assignment['deadline'], 'd M Y h:i A') : ''; ?>"
                                            data-priority="<?php echo ucfirst($assignment['priority']); ?>"
                                            data-status="<?php echo ucfirst(str_replace('_', ' ', $assignment['status'])); ?>"
                                            data-created-at="<?php echo formatDate($assignment['created_at'], 'd M Y h:i A'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    <!-- Change Status Dropdown -->
                                    <div class="btn-group dropstart d-inline-block">
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><h6 class="dropdown-header">Change Status</h6></li>
                                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/view_work.php?action=change_status&id=<?php echo $assignment['id']; ?>&status=pending">Pending</a></li>
                                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/view_work.php?action=change_status&id=<?php echo $assignment['id']; ?>&status=in_progress">In Progress</a></li>
                                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/view_work.php?action=change_status&id=<?php echo $assignment['id']; ?>&status=completed">Completed</a></li>
                                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/view_work.php?action=change_status&id=<?php echo $assignment['id']; ?>&status=cancelled">Cancelled</a></li>
                                        </ul>
                                    </div>

                                    <!-- Delete Button -->
                                    <a href="<?php echo BASE_URL; ?>/admin/view_work.php?action=delete&id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this work assignment?')">
                                        <i class="fas fa-trash"></i>
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
                <p class="mb-0">No work assignments found.</p>
                <a href="<?php echo BASE_URL; ?>/admin/assign_work.php" class="btn btn-primary mt-3">Assign New Work</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Work Assignment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4 id="modal-title"></h4>
                        <p class="text-muted mb-4" id="modal-created-at"></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span id="modal-priority" class="badge bg-primary me-1"></span>
                        <span id="modal-status" class="badge bg-success"></span>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Description</h6>
                            </div>
                            <div class="card-body">
                                <p id="modal-description" class="mb-0"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Assigned To:</strong>
                            <p id="modal-employee" class="mb-0"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Assigned Date:</strong>
                            <p id="modal-assigned-date" class="mb-0"></p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Deadline:</strong>
                            <p id="modal-deadline" class="mb-0"></p>
                        </div>
                    </div>
                </div>

                <div class="row mt-4" id="updates-container">
                    <div class="col-md-12">
                        <h5>Recent Updates</h5>
                        <div id="updates-loading" class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mb-0 mt-2">Loading updates...</p>
                        </div>
                        <div id="updates-list"></div>
                        <div id="no-updates" class="text-center py-3" style="display: none;">
                            <p class="text-muted mb-0">No updates found for this work assignment.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
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
    left: -0.25rem;
    width: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.timeline-item-marker-text {
    font-size: 0.7rem;
    color: #a2acba;
    margin-bottom: 0.25rem;
}
.timeline-item-marker-indicator {
    height: 0.75rem;
    width: 0.75rem;
    border-radius: 100%;
}
.timeline-item-content {
    padding-top: 0.25rem;
    padding-bottom: 1rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle details modal
    const detailsModal = document.getElementById('detailsModal');
    const viewButtons = document.querySelectorAll('.view-details');

    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            const description = this.getAttribute('data-description');
            const employee = this.getAttribute('data-employee');
            const assignedDate = this.getAttribute('data-assigned-date');
            const deadline = this.getAttribute('data-deadline');
            const priority = this.getAttribute('data-priority');
            const status = this.getAttribute('data-status');
            const createdAt = this.getAttribute('data-created-at');

            // Set modal values
            document.getElementById('modal-title').textContent = title;
            document.getElementById('modal-created-at').textContent = 'Created: ' + createdAt;
            document.getElementById('modal-description').textContent = description || 'No description provided.';
            document.getElementById('modal-employee').textContent = employee;
            document.getElementById('modal-assigned-date').textContent = assignedDate;
            document.getElementById('modal-deadline').textContent = deadline || 'No deadline set';

            // Set priority badge
            const priorityBadge = document.getElementById('modal-priority');
            priorityBadge.textContent = priority;
            priorityBadge.className = 'badge me-1';
            if (priority === 'Low') {
                priorityBadge.classList.add('bg-secondary');
            } else if (priority === 'Medium') {
                priorityBadge.classList.add('bg-primary');
            } else if (priority === 'High') {
                priorityBadge.classList.add('bg-danger');
            }

            // Set status badge
            const statusBadge = document.getElementById('modal-status');
            statusBadge.textContent = status;
            statusBadge.className = 'badge';
            if (status === 'Pending') {
                statusBadge.classList.add('bg-warning');
            } else if (status === 'In progress') {
                statusBadge.classList.add('bg-info');
            } else if (status === 'Completed') {
                statusBadge.classList.add('bg-success');
            } else if (status === 'Cancelled') {
                statusBadge.classList.add('bg-danger');
            }

            // Load updates
            loadUpdates(id);
        });
    });

    // Function to load updates for a work assignment
    function loadUpdates(assignmentId) {
        const updatesLoading = document.getElementById('updates-loading');
        const updatesList = document.getElementById('updates-list');
        const noUpdates = document.getElementById('no-updates');

        // Show loading, hide content
        updatesLoading.style.display = 'block';
        updatesList.innerHTML = '';
        noUpdates.style.display = 'none';

        // Fetch updates from server
        fetch(`<?php echo BASE_URL; ?>/admin/get_updates.php?id=${assignmentId}`)
            .then(response => response.json())
            .then(data => {
                updatesLoading.style.display = 'none';
                
                if (data.success && data.updates.length > 0) {
                    // Create timeline for updates
                    const timeline = document.createElement('div');
                    timeline.className = 'timeline';
                    
                    // Add each update to the timeline
                    data.updates.forEach(update => {
                        const timelineItem = document.createElement('div');
                        timelineItem.className = 'timeline-item';
                        
                        // Create marker
                        const marker = document.createElement('div');
                        marker.className = 'timeline-item-marker';
                        
                        const markerText = document.createElement('div');
                        markerText.className = 'timeline-item-marker-text';
                        markerText.textContent = update.created_at.split(' ')[0] + ' ' + update.created_at.split(' ')[1];
                        
                        const markerIndicator = document.createElement('div');
                        markerIndicator.className = 'timeline-item-marker-indicator bg-primary';
                        
                        marker.appendChild(markerText);
                        marker.appendChild(markerIndicator);
                        
                        // Create content
                        const content = document.createElement('div');
                        content.className = 'timeline-item-content pt-0';
                        
                        const card = document.createElement('div');
                        card.className = 'card mb-2';
                        
                        const cardBody = document.createElement('div');
                        cardBody.className = 'card-body py-2 px-3';
                        
                        // Add time
                        const time = document.createElement('div');
                        time.className = 'small text-muted';
                        time.textContent = update.created_at.split(' ')[2] + ' ' + update.created_at.split(' ')[3];
                        cardBody.appendChild(time);
                        
                        // Add employee name
                        const employee = document.createElement('div');
                        employee.className = 'fw-bold';
                        employee.textContent = update.employee_name;
                        cardBody.appendChild(employee);
                        
                        // Add status badge
                        const status = document.createElement('div');
                        let statusBadge = '';
                        
                        if (update.work_status === 'pending') {
                            statusBadge = '<span class="badge bg-warning">Pending</span>';
                        } else if (update.work_status === 'in_progress') {
                            statusBadge = '<span class="badge bg-info">In Progress</span>';
                        } else if (update.work_status === 'completed') {
                            statusBadge = '<span class="badge bg-success">Completed</span>';
                        } else if (update.work_status === 'cancelled') {
                            statusBadge = '<span class="badge bg-danger">Cancelled</span>';
                        }
                        
                        status.innerHTML = statusBadge;
                        cardBody.appendChild(status);
                        
                        // Add description
                        if (update.update_description) {
                            const description = document.createElement('div');
                            description.className = 'small mt-1';
                            description.textContent = update.update_description;
                            cardBody.appendChild(description);
                        }
                        
                        // Add hours spent
                        if (parseFloat(update.hours_spent) > 0) {
                            const hours = document.createElement('div');
                            hours.className = 'small text-muted mt-1';
                            hours.textContent = 'Time spent: ' + formatWorkTime(update.hours_spent);
                            cardBody.appendChild(hours);
                        }
                        
                        card.appendChild(cardBody);
                        content.appendChild(card);
                        
                        // Assemble timeline item
                        timelineItem.appendChild(marker);
                        timelineItem.appendChild(content);
                        timeline.appendChild(timelineItem);
                    });
                    
                    updatesList.appendChild(timeline);
                } else {
                    noUpdates.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error fetching updates:', error);
                updatesLoading.style.display = 'none';
                noUpdates.style.display = 'block';
            });
    }
    
    // Helper function to format work time
    function formatWorkTime(hours) {
        const h = Math.floor(hours);
        const m = Math.round((hours - h) * 60);
        
        if (h > 0 && m > 0) {
            return h + ' hour' + (h > 1 ? 's' : '') + ' ' + m + ' minute' + (m > 1 ? 's' : '');
        } else if (h > 0) {
            return h + ' hour' + (h > 1 ? 's' : '');
        } else {
            return m + ' minute' + (m > 1 ? 's' : '');
        }
    }
});
</script>

<?php include_once '../templates/footer.php'; ?>
