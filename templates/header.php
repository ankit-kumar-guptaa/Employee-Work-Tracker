<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialize auth
$auth = new Auth();

// Set default page title
$page_title = isset($page_title) ? $page_title . ' | ' . APP_NAME : APP_NAME;

// Determine if user is admin or employee
$is_admin = $auth->isAdminLoggedIn();
$is_employee = $auth->isEmployeeLoggedIn();

// Get current file name
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
        }

        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            color: white;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid white;
        }

        .sidebar .nav-link i {
            margin-right: 10px;
        }

        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin: 1rem 0;
        }

        .content-wrapper {
            flex: 1;
            overflow-x: hidden;
        }

        .topbar {
            background-color: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .dropdown-menu {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border: none;
            border-radius: 0.5rem;
        }

        .card-header {
            background-color: rgba(0,0,0,0.025);
            border-bottom: 1px solid rgba(0,0,0,0.125);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-info {
            background-color: var(--info-color);
            border-color: var(--info-color);
        }

        .table th {
            font-weight: 600;
        }

        .dashboard-card {
            transition: all 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }

        .stat-card-body {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-card-icon {
            font-size: 2rem;
            opacity: 0.3;
        }

        .stat-card-title {
            font-size: 0.9rem;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
        }

        .stat-card-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 0;
        }

        /* Customizations */
        .logo-text {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: 0.05rem;
        }

        .progress {
            height: 10px;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -8px;
        }

        .notification-dropdown {
            min-width: 300px;
            max-height: 350px;
            overflow-y: auto;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                width: 250px;
                z-index: 1000;
                transition: all 0.3s;
            }

            .sidebar.show {
                left: 0;
            }

            .content-wrapper {
                margin-left: 0 !important;
            }

            .sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                z-index: 999;
                display: none;
            }

            .sidebar-backdrop.show {
                display: block;
            }
        }
    </style>
</head>
<body class="d-flex">
    <?php if ($is_admin || $is_employee): ?>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="text-center py-4">
            <img src="https://elitecorporatesolutions.com/images/logo/logo.png" style="height: 60px;" alt="logo"><br>
            <span class="logo-text text-white" style="font-size: 1.22rem;">
                <i class="fas fa-tasks me-2"></i>
                <?php echo APP_NAME; ?>
            </span>
        </div>

        <hr class="sidebar-divider my-0">

        <ul class="nav flex-column">
            <?php if ($is_admin): ?>
            <!-- Admin navigation -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'employees.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/employees.php">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Employees</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'assign_work.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/assign_work.php">
                    <i class="fas fa-fw fa-clipboard-list"></i>
                    <span>Assign Work</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'view_work.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/view_work.php">
                    <i class="fas fa-fw fa-tasks"></i>
                    <span>View Work</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/reports.php">
                    <i class="fas fa-fw fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
            <?php else: ?>
            <!-- Employee navigation -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/employee/dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'update_work.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/employee/update_work.php">
                    <i class="fas fa-fw fa-clipboard-check"></i>
                    <span>Update Work</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'history.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/employee/history.php">
                    <i class="fas fa-fw fa-history"></i>
                    <span>Work History</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <hr class="sidebar-divider">

        <ul class="nav flex-column mb-4">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo $is_admin ? BASE_URL . '/admin/logout.php' : BASE_URL . '/employee/logout.php'; ?>">
                    <i class="fas fa-fw fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Mobile sidebar backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Content Wrapper -->
    <div class="content-wrapper w-100 <?php echo ($is_admin || $is_employee) ? 'ms-0 ms-md-auto' : ''; ?>" style="<?php echo ($is_admin || $is_employee) ? 'margin-left: 250px;' : ''; ?>">

        <?php if ($is_admin || $is_employee): ?>
        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light topbar mb-4 p-3">
            <button id="sidebarToggleBtn" class="btn d-md-none">
                <i class="fas fa-bars"></i>
            </button>

            <ul class="navbar-nav ms-auto">
                <!-- Notifications Dropdown -->
                <li class="nav-item dropdown me-3">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownNotifications" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fa-fw"></i>
                        <?php
                        if (isset($_SESSION['employee_id'])) {
                            // Get unread notifications count
                            $db = Database::getInstance();
                            $employee_id = $_SESSION['employee_id'];
                            $result = $db->query("SELECT COUNT(*) as count FROM notifications WHERE employee_id = $employee_id AND is_read = 0");
                            $row = $result->fetch_assoc();
                            $count = $row['count'];

                            if ($count > 0) {
                                echo '<span class="badge rounded-pill bg-danger notification-badge">' . $count . '</span>';
                            }
                        }
                        ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in notification-dropdown p-0" aria-labelledby="navbarDropdownNotifications">
                        <h6 class="dropdown-header bg-primary text-white">
                            Notifications
                        </h6>
                        <?php
                        if (isset($_SESSION['employee_id'])) {
                            $employee_id = $_SESSION['employee_id'];
                            $notifications = $db->query("SELECT * FROM notifications WHERE employee_id = $employee_id ORDER BY created_at DESC LIMIT 5");

                            if ($notifications->num_rows > 0) {
                                while ($notification = $notifications->fetch_assoc()) {
                                    $isRead = $notification['is_read'] ? 'bg-light' : '';
                                    $date = date('d M h:i A', strtotime($notification['created_at']));

                                    echo '<a class="dropdown-item d-flex align-items-center ' . $isRead . '" href="#">
                                        <div class="me-3">
                                            <div class="icon-circle bg-primary text-white p-2">
                                                <i class="fas fa-file-alt"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="small text-gray-500">' . $date . '</div>
                                            <span class="fw-bold">' . $notification['title'] . '</span><br>
                                            <span>' . $notification['message'] . '</span>
                                        </div>
                                    </a>';
                                }
                            } else {
                                echo '<div class="dropdown-item text-center">No notifications</div>';
                            }
                        } else {
                            echo '<div class="dropdown-item text-center">No notifications</div>';
                        }
                        ?>
                        <a class="dropdown-item text-center small text-gray-500" href="#">Show All Notifications</a>
                    </div>
                </li>

                <!-- User Information -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="d-none d-lg-inline text-gray-600 me-2">
                            <?php
                            if ($is_admin) {
                                echo 'Admin';
                            } else if ($is_employee) {
                                echo $_SESSION['employee_name'];
                            }
                            ?>
                        </span>
                        <img class="img-profile rounded-circle" src="https://ui-avatars.com/api/?name=<?php echo $is_admin ? 'Admin' : $_SESSION['employee_name']; ?>&background=4e73df&color=fff" width="32">
                    </a>
                    <!-- Dropdown - User Information -->
                    <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="<?php echo $is_admin ? BASE_URL . '/admin/profile.php' : BASE_URL . '/employee/profile.php'; ?>">
                            <i class="fas fa-user fa-sm fa-fw me-2 text-gray-400"></i>
                            Profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo $is_admin ? BASE_URL . '/admin/logout.php' : BASE_URL . '/employee/logout.php'; ?>">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i>
                            Logout
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="container-fluid pb-4"><?php
        // Display messages if set
        if (isset($_SESSION['success'])) {
            echo successMessage($_SESSION['success']);
            unset($_SESSION['success']);
        }

        if (isset($_SESSION['error'])) {
            echo errorMessage($_SESSION['error']);
            unset($_SESSION['error']);
        }

        if (isset($_SESSION['info'])) {
            echo infoMessage($_SESSION['info']);
            unset($_SESSION['info']);
        }
        ?>
