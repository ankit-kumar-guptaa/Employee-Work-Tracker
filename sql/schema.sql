-- Create the database
CREATE DATABASE IF NOT EXISTS employee_tracker;
USE employee_tracker;

-- Employees table
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    department VARCHAR(50) NULL,
    position VARCHAR(50) NULL,
    joining_date DATE NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Work assignments table
CREATE TABLE IF NOT EXISTS work_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    assigned_date DATE NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    deadline DATETIME NULL,
    assigned_by VARCHAR(100) NOT NULL DEFAULT 'admin',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Work updates table
CREATE TABLE IF NOT EXISTS work_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    employee_id INT NOT NULL,
    update_description TEXT NOT NULL,
    work_status ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL,
    start_time DATETIME NULL,
    end_time DATETIME NULL,
    hours_spent DECIMAL(5,2) DEFAULT 0.00,
    is_extra_work BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES work_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employee stats table (for dashboard)
CREATE TABLE IF NOT EXISTS employee_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL UNIQUE,
    total_tasks INT DEFAULT 0,
    completed_tasks INT DEFAULT 0,
    pending_tasks INT DEFAULT 0,
    cancelled_tasks INT DEFAULT 0,
    in_progress_tasks INT DEFAULT 0,
    total_hours_spent DECIMAL(10,2) DEFAULT 0.00,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Triggers to update stats
DELIMITER //

-- Trigger to create stats record when new employee is added
CREATE TRIGGER after_employee_insert
AFTER INSERT ON employees
FOR EACH ROW
BEGIN
    INSERT INTO employee_stats (employee_id) VALUES (NEW.id);
END//

-- Trigger to update assignment counters
CREATE TRIGGER after_assignment_update
AFTER UPDATE ON work_assignments
FOR EACH ROW
BEGIN
    -- Only process if status changed
    IF NEW.status != OLD.status THEN
        -- Decrement old status counter
        IF OLD.status = 'pending' THEN
            UPDATE employee_stats SET pending_tasks = pending_tasks - 1 WHERE employee_id = NEW.employee_id;
        ELSEIF OLD.status = 'in_progress' THEN
            UPDATE employee_stats SET in_progress_tasks = in_progress_tasks - 1 WHERE employee_id = NEW.employee_id;
        ELSEIF OLD.status = 'completed' THEN
            UPDATE employee_stats SET completed_tasks = completed_tasks - 1 WHERE employee_id = NEW.employee_id;
        ELSEIF OLD.status = 'cancelled' THEN
            UPDATE employee_stats SET cancelled_tasks = cancelled_tasks - 1 WHERE employee_id = NEW.employee_id;
        END IF;

        -- Increment new status counter
        IF NEW.status = 'pending' THEN
            UPDATE employee_stats SET pending_tasks = pending_tasks + 1 WHERE employee_id = NEW.employee_id;
        ELSEIF NEW.status = 'in_progress' THEN
            UPDATE employee_stats SET in_progress_tasks = in_progress_tasks + 1 WHERE employee_id = NEW.employee_id;
        ELSEIF NEW.status = 'completed' THEN
            UPDATE employee_stats SET completed_tasks = completed_tasks + 1 WHERE employee_id = NEW.employee_id;
        ELSEIF NEW.status = 'cancelled' THEN
            UPDATE employee_stats SET cancelled_tasks = cancelled_tasks + 1 WHERE employee_id = NEW.employee_id;
        END IF;

        -- Update last_updated timestamp
        UPDATE employee_stats SET last_updated = NOW() WHERE employee_id = NEW.employee_id;
    END IF;
END//

-- Trigger to increment stats on new assignment
CREATE TRIGGER after_assignment_insert
AFTER INSERT ON work_assignments
FOR EACH ROW
BEGIN
    UPDATE employee_stats SET
        total_tasks = total_tasks + 1,
        pending_tasks = pending_tasks + 1,
        last_updated = NOW()
    WHERE employee_id = NEW.employee_id;

    -- Create notification for employee
    INSERT INTO notifications (employee_id, title, message)
    VALUES (NEW.employee_id, 'New Work Assigned', CONCAT('You have been assigned a new task: ', NEW.title));
END//

-- Trigger to update hours when work update is added
CREATE TRIGGER after_work_update_insert
AFTER INSERT ON work_updates
FOR EACH ROW
BEGIN
    -- Update total hours spent
    UPDATE employee_stats
    SET total_hours_spent = total_hours_spent + NEW.hours_spent,
        last_updated = NOW()
    WHERE employee_id = NEW.employee_id;

    -- Update assignment status if work_update changes status
    UPDATE work_assignments
    SET status = NEW.work_status
    WHERE id = NEW.assignment_id;
END//

DELIMITER ;

-- Initial data (Admin is hardcoded in config.php)
INSERT INTO employees (name, username, password, email, department, position, joining_date)
VALUES
('John Doe', 'john', '$2y$10$GfbW.ccT0LPSdSZzO7jM9.ZbCFy7hDzQY7Bdd3OSs9IKZBPyZ.Wre', 'john@example.com', 'IT', 'Developer', '2023-01-01'); -- Password: john123

-- Initial stats for John
INSERT INTO employee_stats (employee_id, total_tasks, completed_tasks, pending_tasks, total_hours_spent)
VALUES (1, 0, 0, 0, 0);
