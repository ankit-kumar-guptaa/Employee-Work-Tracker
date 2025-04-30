<?php
/**
 * Helper functions for the application
 */

/**
 * Format date
 */
function formatDate($date, $format = 'd-m-Y') {
    return date($format, strtotime($date));
}

/**
 * Get current date
 */
function getCurrentDate($format = 'Y-m-d') {
    return date($format);
}

/**
 * Get current time
 */
function getCurrentTime($format = 'H:i:s') {
    return date($format);
}

/**
 * Get current datetime
 */
function getCurrentDateTime($format = 'Y-m-d H:i:s') {
    return date($format);
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Display success message
 */
function successMessage($message) {
    return '<div class="alert alert-success alert-dismissible fade show" role="alert">
              <strong>Success!</strong> ' . $message . '
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

/**
 * Display error message
 */
function errorMessage($message) {
    return '<div class="alert alert-danger alert-dismissible fade show" role="alert">
              <strong>Error!</strong> ' . $message . '
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

/**
 * Display info message
 */
function infoMessage($message) {
    return '<div class="alert alert-info alert-dismissible fade show" role="alert">
              <strong>Info!</strong> ' . $message . '
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

/**
 * Clean input data
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Get Work Status Badge
 */
function getWorkStatusBadge($status) {
    switch($status) {
        case 'pending':
            return '<span class="badge bg-warning">Pending</span>';
        case 'in_progress':
            return '<span class="badge bg-info">In Progress</span>';
        case 'completed':
            return '<span class="badge bg-success">Completed</span>';
        case 'cancelled':
            return '<span class="badge bg-danger">Cancelled</span>';
        default:
            return '<span class="badge bg-secondary">Unknown</span>';
    }
}

/**
 * Get priority badge
 */
function getPriorityBadge($priority) {
    switch($priority) {
        case 'low':
            return '<span class="badge bg-secondary">Low</span>';
        case 'medium':
            return '<span class="badge bg-primary">Medium</span>';
        case 'high':
            return '<span class="badge bg-danger">High</span>';
        default:
            return '<span class="badge bg-secondary">Unknown</span>';
    }
}

/**
 * Check if request is POST
 */
function isPostRequest() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is GET
 */
function isGetRequest() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Calculate work duration in hours
 */
function calculateWorkDuration($start, $end) {
    $startTime = strtotime($start);
    $endTime = strtotime($end);

    // Calculate difference in seconds
    $diff = $endTime - $startTime;

    // Convert to hours
    $hours = round($diff / 3600, 2);

    return $hours > 0 ? $hours : 0;
}

/**
 * Format Work Time (convert decimal hours to hours and minutes)
 */
function formatWorkTime($hours) {
    $h = floor($hours);
    $m = round(($hours - $h) * 60);

    return $h . 'h ' . $m . 'm';
}

/**
 * Debug function
 */
function debug($data, $die = true) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';

    if ($die) {
        die();
    }
}
