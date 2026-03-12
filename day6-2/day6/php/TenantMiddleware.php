<?php
/**
 * TenantMiddleware.php
 * 
 * Enforces multi-tenancy isolation at the application level
 * Validates user session and prevents unauthorized access
 */

class TenantMiddleware
{
    private $conn;
    private $user_db;
    private $user_id;
    private $authenticated_username;

    /**
     * Constructor - Validates session and retrieves verified user_id
     */
    public function __construct($conn, $user_db)
    {
        $this->conn = $conn;
        $this->user_db = $user_db;

        $this->validateSession();
    }

    /**
     * CRITICAL: Validate user session and retrieve verified user_id
     * 
     * This prevents:
     * - Unauthenticated access
     * - Session hijacking
     * - User ID spoofing
     */
    private function validateSession()
    {
        // Check if user is logged in
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            die(json_encode([
                'success' => false,
                'error' => 'Unauthorized - Please login'
            ]));
        }

        // Check if username is in session
        if (!isset($_SESSION['username'])) {
            session_destroy();
            http_response_code(401);
            die(json_encode([
                'success' => false,
                'error' => 'Invalid session'
            ]));
        }

        $this->authenticated_username = $_SESSION['username'];

        // Query database to get verified user_id
        // This ensures the user actually exists and prevents ID spoofing
        $stmt = $this->user_db->prepare(
            "SELECT id FROM users WHERE username = ? LIMIT 1"
        );

        if (!$stmt) {
            error_log("Database error: " . $this->user_db->error);
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'error' => 'Server error'
            ]));
        }

        $stmt->bind_param("s", $this->authenticated_username);
        $stmt->execute();
        $result = $stmt->get_result();

        // User not found - logout immediately
        if ($result->num_rows === 0) {
            session_destroy();
            http_response_code(401);
            die(json_encode([
                'success' => false,
                'error' => 'User no longer exists'
            ]));
        }

        $row = $result->fetch_assoc();
        $this->user_id = intval($row['id']);
        $stmt->close();

        // Double-check user is active (optional: add 'status' column)
        // $this->validateUserStatus();
    }

    /**
     * Get the authenticated user's ID
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Get the authenticated user's username
     */
    public function getUsername()
    {
        return $this->authenticated_username;
    }

    /**
     * SECURITY: Validate that requested user_id matches authenticated user
     * 
     * Use this when filtering by a user_id parameter:
     * 
     * Example:
     * $middleware->enforceUserContext($_GET['user_id']);
     * 
     * @throws Exception if user_id doesn't match
     */
    public function enforceUserContext($requested_user_id)
    {
        $requested_user_id = intval($requested_user_id);

        if ($requested_user_id !== $this->user_id) {
            http_response_code(403);
            die(json_encode([
                'success' => false,
                'error' => 'Access Denied - User ID Mismatch'
            ]));
        }

        return true;
    }

    /**
     * Verify that a specific record belongs to the authenticated user
     * 
     * This adds an extra layer of protection against ID swapping
     * 
     * @param string $table Table name
     * @param string $id_column Primary key column name
     * @param int $record_id Record ID to verify
     * @return bool True if record belongs to user
     */
    public function verifyRecordOwnership($table, $id_column, $record_id)
    {
        $record_id = intval($record_id);

        // Check if record exists and belongs to user
        $stmt = $this->conn->prepare(
            "SELECT 1 FROM {$table} WHERE {$id_column} = ? AND user_id = ? LIMIT 1"
        );

        if (!$stmt) {
            throw new Exception("Database error: " . $this->conn->error);
        }

        $stmt->bind_param("ii", $record_id, $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    /**
     * Prevent access if record doesn't belong to user
     * 
     * @param string $table
     * @param string $id_column
     * @param int $record_id
     * @throws Exception if record not found or doesn't belong to user
     */
    public function requireRecordOwnership($table, $id_column, $record_id)
    {
        $record_id = intval($record_id);

        if (!$this->verifyRecordOwnership($table, $id_column, $record_id)) {
            http_response_code(404);
            die(json_encode([
                'success' => false,
                'error' => 'Record not found or access denied'
            ]));
        }
    }

    /**
     * Log security event
     */
    public function logSecurityEvent($event_type, $details = "")
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $timestamp = date('Y-m-d H:i:s');

        error_log(
            "[{$timestamp}] Security Event | User: {$this->user_id} | IP: {$ip} | Type: {$event_type} | {$details}"
        );
    }

    /**
     * Log access attempt
     */
    public function logAccessAttempt($resource, $action, $status = 'success')
    {
        $details = "Resource: {$resource} | Action: {$action} | Status: {$status}";
        $this->logSecurityEvent('ACCESS_ATTEMPT', $details);
    }
}
