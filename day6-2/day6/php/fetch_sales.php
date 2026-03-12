<?php
header('Content-Type: application/json');

try {
    require_once 'db_connect_updated.php';
    require_once 'TenantMiddleware.php';

    $middleware = new TenantMiddleware($conn, $user_db);
    $user_id = $middleware->getUserId();

    // Initialize default values
    $defaultData = [
        'today_sales' => 0,
        'today_customers' => 0,
        'today_profit' => 0,
        'best_seller' => 'N/A'
    ];

    // Fetch today's sales data
    $today = date('Y-m-d');
    $query = "SELECT 
                COALESCE(SUM(t.total_price), 0) as today_sales,
                COALESCE(COUNT(DISTINCT t.id), 0) as today_customers,
                COALESCE(SUM(t.total_price - (SELECT SUM(p.cost_price * td.quantity)
                                   FROM transaction_details td
                                   JOIN tblproduct p ON td.product_id = p.product_id
                                   WHERE td.transaction_id = t.id)), 0) as today_profit,
                COALESCE((SELECT p.product_name 
                 FROM transaction_details td
                 JOIN tblproduct p ON td.product_id = p.product_id
                 JOIN transactions t2 ON td.transaction_id = t2.id
                 WHERE t2.user_id = t.user_id AND DATE(t2.date) = ?
                 GROUP BY td.product_id
                 ORDER BY SUM(td.quantity) DESC LIMIT 1), 'N/A') as best_seller
              FROM transactions t
              WHERE t.user_id = ? AND DATE(t.date) = ? AND t.status = 'completed'";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("sis", $today, $user_id, $today);
    $stmt->execute();
    $today_result = $stmt->get_result();
    $today_data = $today_result->fetch_assoc() ?: $defaultData;

    // Fetch weekly sales data
    $week_start = date('Y-m-d', strtotime('last sunday'));
    $query = "SELECT 
                DAYNAME(t.date) as day,
                COALESCE(SUM(t.total_price), 0) as daily_sales,
                COALESCE(COUNT(DISTINCT t.id), 0) as daily_customers,
                COALESCE(SUM(t.total_price - (SELECT SUM(p.cost_price * td.quantity)
                                   FROM transaction_details td
                                   JOIN tblproduct p ON td.product_id = p.product_id
                                   WHERE td.transaction_id = t.id)), 0) as daily_profit
              FROM transactions t
              WHERE t.user_id = ? AND DATE(t.date) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND t.status = 'completed'
              GROUP BY DAYNAME(t.date)
              ORDER BY FIELD(DAYNAME(t.date), 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $week_result = $stmt->get_result();
    $week_data = [];
    $max_sales = 0;

    // Initialize all days with zero values
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    foreach ($days as $day) {
        $week_data[$day] = [
            'day' => $day,
            'daily_sales' => 0,
            'daily_customers' => 0,
            'daily_profit' => 0
        ];
    }

    // Update with actual data
    while ($row = $week_result->fetch_assoc()) {
        $week_data[$row['day']] = $row;
        $max_sales = max($max_sales, (float) $row['daily_sales']);
    }

    // Convert to indexed array
    $week_data = array_values($week_data);

    // Calculate total customers and growth
    $total_customers = array_sum(array_column($week_data, 'daily_customers'));
    $prev_week_start = date('Y-m-d', strtotime('last sunday -7 days'));
    $week_start = date('Y-m-d', strtotime('last sunday'));
    $query = "SELECT COALESCE(COUNT(DISTINCT id), 0) as prev_customers 
              FROM transactions 
              WHERE user_id = ? AND DATE(date) >= ? AND DATE(date) < ? AND status = 'completed'";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $user_id, $prev_week_start, $week_start);
    $stmt->execute();
    $prev_result = $stmt->get_result();
    $prev_data = $prev_result->fetch_assoc() ?: ['prev_customers' => 0];

    $customer_growth = $prev_data['prev_customers'] > 0
        ? (($total_customers - $prev_data['prev_customers']) / $prev_data['prev_customers'] * 100)
        : 0;

    echo json_encode([
        'success' => true,
        'today' => $today_data,
        'week' => $week_data,
        'max_sales' => $max_sales,
        'total_customers' => $total_customers,
        'customer_growth' => round($customer_growth, 2)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>