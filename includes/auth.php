<?php
require_once 'config.php';
require_once 'db.php';

/**
 * Authentication functions
 */
class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();

        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check session timeout
        $this->checkSessionTimeout();
    }

    /**
     * Check if admin is logged in
     */
    public function isAdminLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    /**
     * Check if employee is logged in
     */
    public function isEmployeeLoggedIn() {
        return isset($_SESSION['employee_logged_in']) && $_SESSION['employee_logged_in'] === true;
    }

    /**
     * Login admin
     */
    public function loginAdmin($username, $password) {
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['last_activity'] = time();
            return true;
        }
        return false;
    }

    /**
     * Login employee
     */
    public function loginEmployee($username, $password) {
        // Sanitize input
        $username = $this->db->escapeString($username);

        // Use prepared statement for security
        $stmt = $this->db->prepare("SELECT id, name, username, password FROM employees WHERE username = ? AND status = 'active' LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $employee = $result->fetch_assoc();

            // Verify password (using password_verify for hashed passwords)
            if (password_verify($password, $employee['password'])) {
                $_SESSION['employee_logged_in'] = true;
                $_SESSION['employee_id'] = $employee['id'];
                $_SESSION['employee_name'] = $employee['name'];
                $_SESSION['employee_username'] = $employee['username'];
                $_SESSION['last_activity'] = time();
                return true;
            }
        }
        return false;
    }

    /**
     * Logout user (admin or employee)
     */
    public function logout() {
        // Unset all session variables
        $_SESSION = array();

        // Destroy the session
        session_destroy();
    }

    /**
     * Check session timeout
     */
    private function checkSessionTimeout() {
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];

            if ($inactive >= SESSION_LIFETIME) {
                // Session expired
                $this->logout();
                header("Location: " . BASE_URL . "/index.php?error=timeout");
                exit;
            }

            // Update last activity time
            $_SESSION['last_activity'] = time();
        }
    }

    /**
     * Redirect if not admin
     */
    public function requireAdmin() {
        if (!$this->isAdminLoggedIn()) {
            header("Location: " . BASE_URL . "/admin/login.php?error=unauthorized");
            exit;
        }
    }

    /**
     * Redirect if not employee
     */
    public function requireEmployee() {
        if (!$this->isEmployeeLoggedIn()) {
            header("Location: " . BASE_URL . "/employee/login.php?error=unauthorized");
            exit;
        }
    }
}
