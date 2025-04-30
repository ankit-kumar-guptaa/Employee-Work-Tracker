<?php
// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php'; // Composer autoload

// Initialize auth
$auth = new Auth();

// Require admin login
$auth->requireAdmin();

// Get database instance
$db = Database::getInstance();

// Handle export functionality
if (isset($_GET['export']) && $_GET['export'] == 1) {
    $format = isset($_GET['format']) ? $_GET['format'] : 'pdf';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    $employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
    $status = isset($_GET['status']) ? $_GET['status'] : '';

    // Get report data
    $report_query = "SELECT 
        a.id,
        a.title,
        a.description,
        a.status,
        a.priority,
        a.created_at,
        a.updated_at,
        a.deadline,
        e.name as employee_name
        FROM work_assignments a
        JOIN employees e ON a.employee_id = e.id
        WHERE a.created_at BETWEEN '$start_date' AND '$end_date'";

    if ($employee_id > 0) {
        $report_query .= " AND a.employee_id = $employee_id";
    }
    if (!empty($status)) {
        $report_query .= " AND a.status = '$status'";
    }

    $report_query .= " ORDER BY a.created_at DESC";
    $report_data = $db->query($report_query);

    // Generate report based on format
    if ($format == 'pdf') {
        // Create PDF using Dompdf
        $dompdf = new Dompdf\Dompdf();
        
        // Generate HTML content for PDF
        $html = '<html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .header { text-align: center; margin-bottom: 20px; }
                .filter-info { margin-bottom: 20px; }
                .logo { text-align: center; margin-bottom: 20px; }
                .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #666; }
            </style>
        </head>
        <body>
            <div class="logo">
                <h1>Employee Work Tracker</h1>
            </div>
            <div class="header">
                <h2>Work Assignment Report</h2>
                <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
            </div>';

        // Add filter information
        $html .= '<div class="filter-info">
            <p><strong>Date Range:</strong> ' . $start_date . ' to ' . $end_date . '</p>';
        if ($employee_id > 0) {
            $emp = $db->query("SELECT name FROM employees WHERE id = $employee_id")->fetch_assoc();
            $html .= '<p><strong>Employee:</strong> ' . $emp['name'] . '</p>';
        }
        if (!empty($status)) {
            $html .= '<p><strong>Status:</strong> ' . ucfirst($status) . '</p>';
        }
        $html .= '</div>';

        // Add table
        $html .= '<table>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Employee</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Created</th>
                <th>Deadline</th>
            </tr>';

        while ($row = $report_data->fetch_assoc()) {
            $html .= '<tr>
                <td>' . $row['id'] . '</td>
                <td>' . htmlspecialchars($row['title']) . '</td>
                <td>' . htmlspecialchars($row['employee_name']) . '</td>
                <td>' . ucfirst($row['status']) . '</td>
                <td>' . ucfirst($row['priority']) . '</td>
                <td>' . date('Y-m-d', strtotime($row['created_at'])) . '</td>
                <td>' . ($row['deadline'] ? date('Y-m-d', strtotime($row['deadline'])) : 'N/A') . '</td>
            </tr>';
        }

        $html .= '</table>
            <div class="footer">
                <p>© ' . date('Y') . ' Employee Work Tracker. All rights reserved.</p>
            </div>
        </body>
        </html>';

        // Load HTML content
        $dompdf->loadHtml($html);

        // Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF
        $dompdf->stream('work_report_' . date('Y-m-d') . '.pdf', array('Attachment' => true));
        exit;
    } elseif ($format == 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="work_report_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo '<table border="1">';
        echo '<tr><th>ID</th><th>Title</th><th>Employee</th><th>Status</th><th>Priority</th><th>Created</th><th>Deadline</th></tr>';
        
        while ($row = $report_data->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . $row['title'] . '</td>';
            echo '<td>' . $row['employee_name'] . '</td>';
            echo '<td>' . ucfirst($row['status']) . '</td>';
            echo '<td>' . ucfirst($row['priority']) . '</td>';
            echo '<td>' . date('Y-m-d', strtotime($row['created_at'])) . '</td>';
            echo '<td>' . ($row['deadline'] ? date('Y-m-d', strtotime($row['deadline'])) : 'N/A') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        exit;
    } elseif ($format == 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="work_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        fputcsv($output, array('ID', 'Title', 'Employee', 'Status', 'Priority', 'Created', 'Deadline'));
        
        // Add data
        while ($row = $report_data->fetch_assoc()) {
            fputcsv($output, array(
                $row['id'],
                $row['title'],
                $row['employee_name'],
                ucfirst($row['status']),
                ucfirst($row['priority']),
                date('Y-m-d', strtotime($row['created_at'])),
                $row['deadline'] ? date('Y-m-d', strtotime($row['deadline'])) : 'N/A'
            ));
        }
        
        fclose($output);
        exit;
    }
}

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Default to start of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Default to today
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get all employees for filter dropdown
$employees = $db->query("SELECT id, name FROM employees WHERE status = 'active' ORDER BY name");

// Get work statistics
$stats_query = "SELECT 
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_tasks
    FROM work_assignments 
    WHERE created_at BETWEEN '$start_date' AND '$end_date'";

if ($employee_id > 0) {
    $stats_query .= " AND employee_id = $employee_id";
}
if (!empty($status)) {
    $stats_query .= " AND status = '$status'";
}

$stats = $db->query($stats_query)->fetch_assoc();

// Get employee performance
$performance_query = "SELECT 
    e.id, 
    e.name,
    COUNT(a.id) as total_tasks,
    SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN a.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
    AVG(CASE WHEN a.status = 'completed' THEN TIMESTAMPDIFF(HOUR, a.created_at, a.updated_at) ELSE NULL END) as avg_completion_time
    FROM employees e
    LEFT JOIN work_assignments a ON e.id = a.employee_id
    WHERE e.status = 'active'";

if (!empty($start_date) && !empty($end_date)) {
    $performance_query .= " AND a.created_at BETWEEN '$start_date' AND '$end_date'";
}

$performance_query .= " GROUP BY e.id ORDER BY completed_tasks DESC";
$employee_performance = $db->query($performance_query);

// Get task completion rate
$completion_rate = 0;
if ($stats['total_tasks'] > 0) {
    $completion_rate = round(($stats['completed_tasks'] / $stats['total_tasks']) * 100);
}

// Set page title
$page_title = 'Reports';

// Include header
include_once '../templates/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Reports</h1>
    <div class="dropdown">
        <button class="btn btn-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-download fa-sm text-white-50 me-1"></i> Export Report
        </button>
        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
            <li><a class="dropdown-item" href="?export=1&format=pdf&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&employee_id=<?php echo $employee_id; ?>&status=<?php echo $status; ?>">Export as PDF</a></li>
            <li><a class="dropdown-item" href="?export=1&format=excel&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&employee_id=<?php echo $employee_id; ?>&status=<?php echo $status; ?>">Export as Excel</a></li>
            <li><a class="dropdown-item" href="?export=1&format=csv&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&employee_id=<?php echo $employee_id; ?>&status=<?php echo $status; ?>">Export as CSV</a></li>
        </ul>
    </div>
</div>

<!-- Filters -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filter Reports</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="employee_id" class="form-label">Employee</label>
                <select class="form-select" id="employee_id" name="employee_id">
                    <option value="">All Employees</option>
                    <?php while ($emp = $employees->fetch_assoc()): ?>
                        <option value="<?php echo $emp['id']; ?>" <?php echo $employee_id == $emp['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $status == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Generate Report</button>
                <a href="reports.php" class="btn btn-secondary">Reset Filters</a>
            </div>
        </form>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-primary shadow h-100">
            <div class="card-body stat-card-body">
                <div>
                    <div class="stat-card-title">Total Tasks</div>
                    <div class="stat-card-value"><?php echo $stats['total_tasks']; ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-tasks text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-success shadow h-100">
            <div class="card-body stat-card-body">
                <div>
                    <div class="stat-card-title">Completed Tasks</div>
                    <div class="stat-card-value"><?php echo $stats['completed_tasks']; ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-check-circle text-success"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-warning shadow h-100">
            <div class="card-body stat-card-body">
                <div>
                    <div class="stat-card-title">Pending Tasks</div>
                    <div class="stat-card-value"><?php echo $stats['pending_tasks']; ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-clock text-warning"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-info shadow h-100">
            <div class="card-body stat-card-body">
                <div>
                    <div class="stat-card-title">In Progress</div>
                    <div class="stat-card-value"><?php echo $stats['in_progress_tasks']; ?></div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-spinner text-info"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Completion Progress -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Task Completion Rate</h6>
            </div>
            <div class="card-body">
                <h4 class="small font-weight-bold">Overall Completion Rate <span class="float-end"><?php echo $completion_rate; ?>%</span></h4>
                <div class="progress mb-4">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completion_rate; ?>%" 
                         aria-valuenow="<?php echo $completion_rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Task Distribution</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-3">
                        <h5><?php echo $stats['completed_tasks']; ?></h5>
                        <p class="text-muted small mb-0">Completed</p>
                    </div>
                    <div class="col-3">
                        <h5><?php echo $stats['pending_tasks']; ?></h5>
                        <p class="text-muted small mb-0">Pending</p>
                    </div>
                    <div class="col-3">
                        <h5><?php echo $stats['in_progress_tasks']; ?></h5>
                        <p class="text-muted small mb-0">In Progress</p>
                    </div>
                    <div class="col-3">
                        <h5><?php echo $stats['cancelled_tasks']; ?></h5>
                        <p class="text-muted small mb-0">Cancelled</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Employee Performance -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Employee Performance</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Total Tasks</th>
                        <th>Completed</th>
                        <th>Pending</th>
                        <th>In Progress</th>
                        <th>Avg. Completion Time (hours)</th>
                        <th>Completion Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($emp = $employee_performance->fetch_assoc()): 
                        $emp_completion_rate = $emp['total_tasks'] > 0 ? round(($emp['completed_tasks'] / $emp['total_tasks']) * 100) : 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($emp['name']); ?></td>
                        <td><?php echo $emp['total_tasks']; ?></td>
                        <td><?php echo $emp['completed_tasks']; ?></td>
                        <td><?php echo $emp['pending_tasks']; ?></td>
                        <td><?php echo $emp['in_progress_tasks']; ?></td>
                        <td><?php echo $emp['avg_completion_time'] ? round($emp['avg_completion_time'], 2) : 'N/A'; ?></td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $emp_completion_rate; ?>%" 
                                     aria-valuenow="<?php echo $emp_completion_rate; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo $emp_completion_rate; ?>%
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>
