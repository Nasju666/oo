<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (
    !isset($_SESSION["loggedin"]) || $_SESSION["loggedin"]
    !== true
) {
    header("location: login.php");
    exit();
}
require_once 'db_connect_updated.php';
require_once 'TenantMiddleware.php';

$middleware = new TenantMiddleware($conn, $user_db);
$user_id = $middleware->getUserId();

// Set Philippine timezone
date_default_timezone_set('Asia/Manila');

// Initialize variables with all 12 months predefined
$monthNames = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December'
];

// Predefine monthly data structure with all months
$monthlyData = [];
foreach ($monthNames as $num => $name) {
    $monthlyData[$num] = [
        'month_name' => $name,
        'total_sales' => 0,
        'total_customers' => 0,
        'best_selling' => 'N/A',
        'cogs' => 0,
        'expenses' => 0,
        'net_profit' => 0
    ];
}

$yearlySales = 0;
$yearlyCustomers = 0;
$yearlyExpenses = 0;
$bestSelling = "N/A";
$maxSales = 1; // Initialize to prevent division by zero

try {
    // Get current year
    $currentYear = date('Y');

    // 1. Get yearly sales summary
    $yearlyQuery = "SELECT 
                   SUM(total_price) as total_sales,
                   COUNT(*) as total_customers
                   FROM transactions
                   WHERE user_id = ? AND YEAR(date) = ?";

    $yearlyStmt = $conn->prepare($yearlyQuery);
    $yearlyStmt->bind_param("ii", $user_id, $currentYear);
    $yearlyStmt->execute();
    $yearlyResult = $yearlyStmt->get_result();
    if ($yearlyData = $yearlyResult->fetch_assoc()) {
        $yearlySales = $yearlyData['total_sales'] ?? 0;
        $yearlyCustomers = $yearlyData['total_customers'] ?? 0;
    }

    $expensesQuery = "SELECT 
                     SUM(amount) as total_expenses
                     FROM tblexpense
                     WHERE user_id = ? AND YEAR(date) = ?";

    $expensesStmt = $conn->prepare($expensesQuery);
    $expensesStmt->bind_param("ii", $user_id, $currentYear);
    $expensesStmt->execute();
    $expensesResult = $expensesStmt->get_result();
    if ($expensesData = $expensesResult->fetch_assoc()) {
        $yearlyExpenses = $expensesData['total_expenses'] ?? 0;
    }

    // 2. Get best selling item for the year
    $bestQuery = "SELECT 
                 td.product_name,
                 SUM(td.quantity) as total_quantity
                 FROM transaction_details td
                 JOIN transactions t ON td.transaction_id = t.id
                 WHERE t.user_id = ? AND YEAR(t.date) = ?
                 GROUP BY td.product_name
                 ORDER BY total_quantity DESC
                 LIMIT 1";

    $bestStmt = $conn->prepare($bestQuery);
    $bestStmt->bind_param("ii", $user_id, $currentYear);
    $bestStmt->execute();
    $bestResult = $bestStmt->get_result();
    if ($bestData = $bestResult->fetch_assoc()) {
        $bestSelling = $bestData['product_name'] ?? "N/A";
    }

    // 3. Get monthly data and merge with predefined structure
    $monthlyQuery = "SELECT 
                    MONTH(t.date) as month_num,
                    SUM(t.total_price) as total_sales,
                    COUNT(DISTINCT t.id) as total_customers,
                    COALESCE(SUM(td.quantity * p.cost_price), 0) as cogs
                    FROM transactions t
                    LEFT JOIN transaction_details td ON t.id = td.transaction_id AND td.user_id = ?
                    LEFT JOIN tblproduct p ON td.product_id = p.product_id
                    WHERE t.user_id = ? AND YEAR(t.date) = ?
                    GROUP BY MONTH(t.date)
                    ORDER BY MONTH(t.date)";

    $monthlyStmt = $conn->prepare($monthlyQuery);
    $monthlyStmt->bind_param("iii", $user_id, $user_id, $currentYear);
    $monthlyStmt->execute();
    $monthlyResult = $monthlyStmt->get_result();

    while ($row = $monthlyResult->fetch_assoc()) {
        $monthNum = $row['month_num'];
        if (isset($monthlyData[$monthNum])) {
            $monthlyData[$monthNum]['total_sales'] = $row['total_sales'] ?? 0;
            $monthlyData[$monthNum]['total_customers'] = $row['total_customers'] ?? 0;
            $monthlyData[$monthNum]['best_selling'] = 'N/A';
            $monthlyData[$monthNum]['cogs'] = $row['cogs'] ?? 0;
            $monthlyData[$monthNum]['net_profit'] = ($row['total_sales'] ?? 0) - ($row['cogs'] ?? 0);
        }
    }

    // Get best selling product for each month
    $bestProductQuery = "SELECT 
                        MONTH(t.date) as month_num,
                        td.product_name,
                        SUM(td.quantity) as total_qty
                        FROM transaction_details td
                        JOIN transactions t ON td.transaction_id = t.id
                        WHERE t.user_id = ? AND td.user_id = ? AND YEAR(t.date) = ?
                        GROUP BY MONTH(t.date), td.product_name
                        ORDER BY MONTH(t.date), total_qty DESC";

    $bestProductStmt = $conn->prepare($bestProductQuery);
    $bestProductStmt->bind_param("iii", $user_id, $user_id, $currentYear);
    $bestProductStmt->execute();
    $bestProductResult = $bestProductStmt->get_result();

    $bestProductByMonth = [];
    while ($row = $bestProductResult->fetch_assoc()) {
        $monthNum = $row['month_num'];
        if (!isset($bestProductByMonth[$monthNum])) {
            $bestProductByMonth[$monthNum] = $row['product_name'];
        }
    }

    // Merge best selling products into monthly data
    foreach ($monthlyData as $monthNum => &$month) {
        if (isset($bestProductByMonth[$monthNum])) {
            $month['best_selling'] = $bestProductByMonth[$monthNum];
        }
    }

    $monthlyExpensesQuery = "SELECT 
                            MONTH(date) as month_num,
                            SUM(amount) as total_expenses
                            FROM tblexpense
                            WHERE user_id = ? AND YEAR(date) = ?
                            GROUP BY MONTH(date)
                            ORDER BY MONTH(date)";

    $monthlyExpensesStmt = $conn->prepare($monthlyExpensesQuery);
    $monthlyExpensesStmt->bind_param("ii", $user_id, $currentYear);
    $monthlyExpensesStmt->execute();
    $monthlyExpensesResult = $monthlyExpensesStmt->get_result();

    while ($row = $monthlyExpensesResult->fetch_assoc()) {
        $monthNum = $row['month_num'];
        if (isset($monthlyData[$monthNum])) {
            $monthlyData[$monthNum]['expenses'] = $row['total_expenses'] ?? 0;
        }
    }

    // Calculate net profit for each month
    foreach ($monthlyData as &$month) {
        $month['net_profit'] = $month['total_sales'] - $month['cogs'] - $month['expenses'];
    }
    // break the reference to avoid later accidental overwrites
    unset($month);

    // Calculate max sales for chart scaling
    $salesValues = array_column($monthlyData, 'total_sales');
    $maxSales = max($salesValues) > 0 ? max($salesValues) : 1;

} catch (Exception $e) {
    error_log("Monthly Sales Error: " . $e->getMessage());
}
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sari-Sari Store Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Dots&display=swap" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Zen+Dots&display=swap"
        rel="stylesheet">

</head>

<body>
    <div class="sidebar">
        <div class="sidebarhead">
            <div class="sidebarlogo">
                <div class="sidelogo-left">
                    <img src="../llogo.png" alt="">
                </div>
                <div class="sidelogo-right">
                    <h2>Sari Sari Store Performance Tracking System</h2>
                </div>
            </div>

            <div class="sidebarmenu">
                <ul>
                    <li class="menu-item">
                        <div class="active">
                            <div class="menu">
                                <a href="dashboard.php" class="damndashboard">
                                    <div class="menulogo-left">
                                        <img src="../dashboard.png" alt="" class="light">
                                        Dashboard
                                    </div>
                                </a>
                                <div class="menulogo-right arrow">
                                    <div class="menu" onclick="toggleActive(this)"><img src="../downarrow.png" alt=""
                                            class="arrow-icon"></div>
                                </div>
                            </div>
                        </div>
                        <ul class="dropdown1">
                            <div class="dropbox">
                                <li><a href="monthlysales.php">Monthly Sales</a></li>
                            </div>
                            <div class="dropbox">
                                <li><a href="topselling.php">Top Selling</a></li>
                            </div>
                            <div class="dropbox">
                                <li><a href="expensetracker.php">Expense Tracker</a></li>
                            </div>
                        </ul>
                    </li>
                    <li class="inactive">
                        <div class="inactive-box">
                            <div class="menu">
                                <div class="menulogo-left">
                                    <img src="../inventory.png" alt=""> Inventory
                                </div>
                                <div class="menulogo-right">
                                    <img src="../arrow.png" alt="" class="arrow-icon">
                                </div>
                            </div>
                        </div>
                        <ul class="dropdown">
                            <div class="dropbox">
                                <li><a href="viewinventory.php">View Inventory</a></li>
                            </div>
                            <div class="dropbox">
                                <li><a href="stockalert.php">Stock Alert</a></li>
                            </div>

                        </ul>
                    </li>
                    <li class="inactive">
                        <div class="inactive-box">
                            <div class="menu">
                                <a href="category.php" class="categ">
                                    <div class="menulogo-left categ">
                                        <img src="../category.png" alt=""> Category
                                    </div>
                                    <div class="menulogo-right">

                                    </div>
                                </a>
                            </div>
                        </div>
                    </li>
                    <li class="inactive">
                        <div class="inactive-box">
                            <div class="menu">
                                <div class="menulogo-left">
                                    <img src="../product.png" alt=""> Product
                                </div>
                                <div class="menulogo-right">
                                    <img src="../arrow.png" alt="" class="arrow-icon">
                                </div>
                            </div>
                        </div>
                        <ul class="dropdown">
                            <div class="dropbox">
                                <li><a href="viewproduct.php">View Product</a></li>
                            </div>
                            <div class="dropbox">
                                <li><a href="addproduct.php">Add Product</a></li>
                            </div>

                        </ul>
                    </li>
                    <li class="inactive">
                        <div class="inactive-box">
                            <div class="menu">
                                <div class="menulogo-left">
                                    <img src="../sales.png" alt=""> Sales
                                </div>
                                <div class="menulogo-right">
                                    <img src="../arrow.png" alt="" class="arrow-icon">
                                </div>
                            </div>
                        </div>
                        <ul class="dropdown">
                            <div class="dropbox">
                                <li><a href="transaction.php">Transaction</a></li>
                            </div>
                            <div class="dropbox">
                                <li><a href="history.php">History</a></li>
                            </div>

                        </ul>
                    </li>
                </ul>
            </div>
        </div>

        <div class="boxside">
            <p><strong>Need Help?</strong>
                <br>Please check our documentation
            </p>
            <div class="sidebarbottom">
                <button class="tutorial-btn">Tutorial</button>
            </div>

        </div>

    </div>
    <div class="main-content">
        <header>
            <h1>Dashboard</h1>
            <div class="header-right">
                <div class="feedback">
                    <button class="feedback-btn">Share your feedback</button>
                </div>
                <div class="profile">
                    <h2><span>Hello</span>, <?php echo $_SESSION["username"]; ?>!</h2>

                    <img src="../profile.png" alt="" class="toggleprofile">


                </div>
            </div>
            <div class="profile-box">
                <div class="profile-dropdown">
                    <div class="profile-dropdown-content">
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>

        </header>

        <div class="sales-report">
            <div class="sales-report-header">
                <div class="sales-left">
                    <h2>Sales this year</h2>
                    <p>₱<?= number_format($yearlySales, 2) ?> Sales</p>
                </div>
                <div class="sales-right">
                    <p><?= date('Y') ?></p>
                    <img src="../date.png" alt="">
                </div>
            </div>

            <div class="chart">
                <?php
                $months = [
                    'January',
                    'February',
                    'March',
                    'April',
                    'May',
                    'June',
                    'July',
                    'August',
                    'September',
                    'October',
                    'November',
                    'December'
                ];

                foreach ($months as $index => $month) {
                    // Get month number (1-12) from the index (0-11)
                    $monthNum = $index + 1;
                    $monthSales = $monthlyData[$monthNum]['total_sales'] ?? 0;
                    $height = ($monthSales / $maxSales) * 100;
                    ?>
                    <div class="day">
                        <div class="bar" style="height: <?= $height ?>%; background-color: #4CE4FF;"></div>
                        <p><span><?= substr($month, 0, 3) ?></span></p>
                    </div>
                <?php } ?>
            </div>

            <div class="summary">
                <div class="total-customers">
                    <div class="total-left">
                        <img src="../totalcus.png" alt="">
                        <div class="total-middle">
                            <p>Total Customers</p>
                            <p><span><?= number_format($yearlyCustomers) ?></span></p>
                        </div>
                    </div>
                    <p><span style="color: green;">↑ 3%</span></p>
                </div>
                <div class="total-customers">
                    <div class="total-left">
                        <img src="../totalcus.png" alt="">
                        <div class="total-middle">
                            <p>Best Selling</p>
                            <p><span><?= $bestSelling ?></span></p>
                        </div>
                    </div>
                    <p><span style="color: green;">↑ 3%</span></p>
                </div>
            </div>
        </div>

        <div class="sales-report">
            <div class="sales-report-header">
                <div class="sales-left">
                    <h2>Monthly Sales Report</h2>
                    <p> </p>
                </div>
                <div class="sales-right">
                    <p>2025</p>
                    <img src="../date.png" alt="">
                </div>
            </div>

            <table>
                <tr>
                    <th>Date</th>
                    <th>Total Sales</th>
                    <th>Total Customers</th>
                    <th>Best-Selling Item</th>
                    <th>COGS</th>
                    <th>Expenses</th>
                    <th>Net Profit</th>
                </tr>
                <?php
                // Ensure we only show each month once by using the predefined month order
                foreach (range(1, 12) as $monthNum):
                    // avoid using the same variable name previously used as a reference
                    $rowData = $monthlyData[$monthNum] ?? [
                        'month_name' => date('F', mktime(0, 0, 0, $monthNum, 1)),
                        'total_sales' => 0,
                        'total_customers' => 0,
                        'best_selling' => 'N/A',
                        'cogs' => 0,
                        'expenses' => 0,
                        'net_profit' => 0
                    ];
                    ?>
                    <tr>
                        <td><?= $rowData['month_name'] ?></td>
                        <td>₱<?= number_format($rowData['total_sales'], 2) ?></td>
                        <td><?= number_format($rowData['total_customers']) ?></td>
                        <td><?= htmlspecialchars($rowData['best_selling']) ?></td>
                        <td>₱<?= number_format($rowData['cogs'], 2) ?></td>
                        <td>₱<?= number_format($rowData['expenses'], 2) ?></td>
                        <td>₱<?= number_format($rowData['net_profit'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <footer>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms & Conditions</a>
                <a href="#">Cookie Policy</a>
                <a href="#">Cookie Settings</a>
            </div>
            <p>©Sari-Sari performance tracking system, 2025. All rights reserved.</p>
        </footer>
    </div>

    <script>

        document.addEventListener("DOMContentLoaded", function () {
            const profileImg = document.querySelector(".toggleprofile");
            const profileBox = document.querySelector(".profile-box");


            profileImg.addEventListener("click", function (event) {
                profileBox.classList.toggle("active");
                event.stopPropagation();
            });

            document.addEventListener("click", function (event) {
                if (!profileBox.contains(event.target) && !profileImg.contains(event.target)) {
                    profileBox.classList.remove("active");
                }
            });
        }
        );

        document.querySelectorAll('.inactive').forEach(item => {
            item.addEventListener('click', function () {
                let dropdown = this.querySelector('.dropdown');
                let arrow = this.querySelector('.arrow-icon');

                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                    arrow.style.transform = 'rotate(0deg)';


                } else {
                    document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('show'));
                    document.querySelectorAll('.arrow-icon').forEach(a => a.style.transform = 'rotate(0deg)');
                    dropdown.classList.add('show');
                    arrow.style.transform = 'rotate(180deg)';
                }
            });
        });

        document.querySelectorAll(".menulogo-right").forEach(arrow => {
            arrow.addEventListener("click", function () {
                let dropdown = this.closest(".menu-item").querySelector(".dropdown1");
                dropdown.classList.toggle("show1");
            });
        });

        function toggleActive(element) {
            console.log("Before:", element.classList);
            let arrowImg = element.querySelector(".arrow-icon");
            let isActive = element.classList.contains("selected-menu");

            document.querySelectorAll(".menu").forEach(menu => menu.classList.remove("selected-menu"));

            if (!isActive) {
                element.classList.add("selected-menu");
                arrowImg.src = "../uparrow.png";

                document.querySelector(".light").src = "../light.svg";
                document.querySelector(".arrow-icon").style.transform = "rotate(360deg)";
                document.querySelector(".active").style.background = "#39727C";
                document.querySelector(".active").style.color = "#fff";
                document.querySelector(".damndashboard").style.color = "#fff";
                document.querySelector(".active").text.style.textShadow = "0px 0px 13px rgba(255,255,255,1)";
            } else {
                element.classList.remove("selected-menu");
                arrowImg.src = "../downarrow.png";

                document.querySelector(".light").src = "../dashboard.png";
                document.querySelector(".active").style.background = "#52DCF5";
                document.querySelector(".active").style.color = "#000";
                document.querySelector(".damndashboard").style.color = "#000";
                document.querySelector(".arrow-icon").style.transform = "rotate(0deg)";
                document.querySelector(".active").text.style.textShadow = "0px 0px 13px rgba(0,0,0,1)";
            }
            console.log("After:", element.classList);
        }
    </script>
</body>

</html>