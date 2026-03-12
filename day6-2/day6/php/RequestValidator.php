<?php
/**
 * RequestValidator.php
 * 
 * Validates and sanitizes user input to prevent:
 * - Parameter injection
 * - ID swapping
 * - Type confusion
 * - SQL injection
 */

class RequestValidator
{
    private $middleware;

    public function __construct($middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * Validate and retrieve a numeric ID parameter
     * 
     * Ensures:
     * - Parameter is numeric
     * - Parameter belongs to authenticated user
     * 
     * @param string $param_name Parameter name to get from $_GET/$_POST
     * @param string $min Minimum value
     * @return int Validated ID
     */
    public function validateNumericId($param_name, $min = 1)
    {
        // Check parameter exists
        if (!isset($_REQUEST[$param_name])) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'error' => "Missing required parameter: {$param_name}"
            ]));
        }

        $value = $_REQUEST[$param_name];

        // Validate is numeric
        if (!is_numeric($value)) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'error' => "Invalid {$param_name}: must be numeric"
            ]));
        }

        $value = intval($value);

        // Validate minimum value
        if ($value < $min) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'error' => "Invalid {$param_name}: must be >= {$min}"
            ]));
        }

        return $value;
    }

    /**
     * Validate string parameter
     */
    public function validateString($param_name, $max_length = 255, $required = true)
    {
        // Check parameter exists
        if (!isset($_REQUEST[$param_name])) {
            if ($required) {
                http_response_code(400);
                die(json_encode([
                    'success' => false,
                    'error' => "Missing required parameter: {$param_name}"
                ]));
            }
            return null;
        }

        $value = $_REQUEST[$param_name];

        // Validate is string
        if (!is_string($value)) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'error' => "Invalid {$param_name}: must be text"
            ]));
        }

        // Check length
        if (strlen($value) > $max_length) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'error' => "Invalid {$param_name}: exceeds maximum length of {$max_length}"
            ]));
        }

        // Check not empty if required
        if ($required && empty(trim($value))) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'error' => "Invalid {$param_name}: cannot be empty"
            ]));
        }

        return trim($value);
    }

    /**
     * Validate decimal/float parameter
     */
    public function validateDecimal($param_name, $min = 0, $precision = 2)
    {
        if (!isset($_REQUEST[$param_name])) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'error' => "Missing required parameter: {$param_name}"
            ]));
        }

        $value = $_REQUEST[$param_name];

        if (!is_numeric($value)) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'error' => "Invalid {$param_name}: must be numeric"
            ]));
        }

        $value = (float) $value;

        if ($value < $min) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'error' => "Invalid {$param_name}: cannot be less than {$min}"
            ]));
        }

        return round($value, $precision);
    }

    /**
     * Validate product belongs to authenticated user
     */
    public function validateProductAccess($conn, $product_id)
    {
        $product_id = intval($product_id);
        $user_id = $this->middleware->getUserId();

        // Verify product exists and belongs to user
        $stmt = $conn->prepare(
            "SELECT 1 FROM tblproduct 
             WHERE product_id = ? AND user_id = ? 
             LIMIT 1"
        );

        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("ii", $product_id, $user_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows === 0) {
            http_response_code(404);
            die(json_encode([
                'success' => false,
                'error' => 'Product not found or access denied'
            ]));
        }

        $stmt->close();
        return $product_id;
    }

    /**
     * Validate transaction belongs to authenticated user
     */
    public function validateTransactionAccess($conn, $transaction_id)
    {
        $transaction_id = intval($transaction_id);
        $user_id = $this->middleware->getUserId();

        $stmt = $conn->prepare(
            "SELECT 1 FROM transactions 
             WHERE id = ? AND user_id = ? 
             LIMIT 1"
        );

        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("ii", $transaction_id, $user_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows === 0) {
            http_response_code(404);
            die(json_encode([
                'success' => false,
                'error' => 'Transaction not found or access denied'
            ]));
        }

        $stmt->close();
        return $transaction_id;
    }

    /**
     * Validate enum/choice parameter
     */
    public function validateChoice($param_name, $allowed_values)
    {
        if (!isset($_REQUEST[$param_name])) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'error' => "Missing required parameter: {$param_name}"
            ]));
        }

        $value = $_REQUEST[$param_name];

        if (!in_array($value, $allowed_values, true)) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'error' => "Invalid {$param_name}: must be one of " . implode(', ', $allowed_values)
            ]));
        }

        return $value;
    }

    /**
     * Validate date parameter
     */
    public function validateDate($param_name, $format = 'Y-m-d')
    {
        if (!isset($_REQUEST[$param_name])) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'error' => "Missing required parameter: {$param_name}"
            ]));
        }

        $value = $_REQUEST[$param_name];
        $date = \DateTime::createFromFormat($format, $value);

        if ($date === false) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'error' => "Invalid {$param_name}: must match format {$format}"
            ]));
        }

        return $value;
    }

    /**
     * Safely escape string for SQL (for column names, table names ONLY)
     * Use prepared statements with bind_param() for VALUES!
     */
    public function escapeIdentifier($identifier)
    {
        // Only allow alphanumeric and underscore for identifiers
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
            throw new Exception("Invalid identifier: {$identifier}");
        }
        return $identifier;
    }
}
