<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit();
}

// Include updated connection with user_db support
require_once 'db_connect_updated.php';
require_once 'TenantMiddleware.php';

// Get verified user_id
$middleware = new TenantMiddleware($conn, $user_db);
$user_id = $middleware->getUserId();

date_default_timezone_set('Asia/Manila');

// Get today's day name (Sunday, Monday, etc.)
$todayDayName = date('l');
$today = date('Y-m-d');

// Initialize default values
$todaySales = 0;
$todayCustomers = 0;
$todayProfit = 0;
$weeklyData = [];
$maxSales = 1;

try {
    // 1. Get Today's Sales, Customers, and Profit in a single query
    $todayQuery = "SELECT 
              SUM(t.total_price) as today_sales,
              COUNT(t.id) as today_customers,
              SUM(td.subtotal) - SUM(td.quantity * p.cost_price) as today_profit
              FROM transactions t
              JOIN transaction_details td ON t.id = td.transaction_id
              JOIN tblproduct p ON td.product_id = p.product_id
              WHERE t.user_id = ? AND DATE(t.date) = ?";

    $todayStmt = $conn->prepare($todayQuery);
    if ($todayStmt) {
        $todayStmt->bind_param("is", $user_id, $today);
        $todayStmt->execute();
        $todayResult = $todayStmt->get_result();
        $todayData = $todayResult ? $todayResult->fetch_assoc() : null;

        // Debugging output
        error_log("Today's Data: " . print_r($todayData, true));

        $todaySales = $todayData['today_sales'] ?? 0;
        $todayCustomers = $todayData['today_customers'] ?? 0;
        $todayProfit = $todayData['today_profit'] ?? 0;
        $todayStmt->close();
    }


    // 2. Get Weekly Sales Data
    $weeklyQuery = "SELECT 
                    DAYNAME(t.date) as day_name,
                    DATE(t.date) as day_date,
                    SUM(t.total_price) as day_sales,
                    COUNT(DISTINCT t.id) as day_customers,
                    COALESCE(SUM(td.quantity * p.cost_price), 0) as cogs,
                    COALESCE(SUM(t.total_price), 0) - COALESCE(SUM(td.quantity * p.cost_price), 0) as net_profit
                    FROM transactions t
                    LEFT JOIN transaction_details td ON t.id = td.transaction_id AND td.user_id = ?
                    LEFT JOIN tblproduct p ON td.product_id = p.product_id
                    WHERE t.user_id = ? AND t.date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                    GROUP BY DAYOFWEEK(t.date), day_name, day_date
                    ORDER BY DAYOFWEEK(t.date)";

    $weeklyStmt = $conn->prepare($weeklyQuery);
    if ($weeklyStmt) {
        $weeklyStmt->bind_param("ii", $user_id, $user_id);
        $weeklyStmt->execute();
        $weeklyResult = $weeklyStmt->get_result();

        if ($weeklyResult) {
            while ($row = $weeklyResult->fetch_assoc()) {
                $row['best_selling'] = 'N/A';
                $weeklyData[] = $row;
                // If this is today's data, override the today values with the exact day's data
                if (strcasecmp($row['day_name'], $todayDayName) === 0) {
                    $todaySales = $row['day_sales'];
                    $todayCustomers = $row['day_customers'];
                    $todayProfit = $row['net_profit'];
                }
            }
        }
        $weeklyStmt->close();

        // Get best selling products for each day in the week
        $bestDailyQuery = "SELECT 
                            DATE(t.date) as day_date,
                            td.product_name,
                            SUM(td.quantity) as total_qty
                            FROM transaction_details td
                            JOIN transactions t ON td.transaction_id = t.id
                            WHERE t.user_id = ? AND td.user_id = ? AND t.date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                            GROUP BY DATE(t.date), td.product_name
                            ORDER BY DATE(t.date), total_qty DESC";

        $bestDailyStmt = $conn->prepare($bestDailyQuery);
        $bestDailyStmt->bind_param("ii", $user_id, $user_id);
        $bestDailyStmt->execute();
        $bestDailyResult = $bestDailyStmt->get_result();

        $bestProductByDay = [];
        while ($row = $bestDailyResult->fetch_assoc()) {
            $dateKey = $row['day_date'];
            if (!isset($bestProductByDay[$dateKey])) {
                $bestProductByDay[$dateKey] = $row['product_name'];
            }
        }

        // Merge best selling products into weekly data
        foreach ($weeklyData as &$day) {
            if (isset($day['day_date'])) {
                $dateKey = $day['day_date'];
                if (isset($bestProductByDay[$dateKey])) {
                    $day['best_selling'] = $bestProductByDay[$dateKey];
                }
            }
        }
        $bestDailyStmt->close();

        $maxSales = !empty($weeklyData) ? max(array_column($weeklyData, 'day_sales')) : 1;
    }

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
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
                                <div class="menulogo-left">
                                    <img src="../dashboard.png" alt="" class="light">
                                    Dashboard
                                </div>
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
        <div class="stats">
            <div class="card">
                <div class="card-left">₱<?= number_format($todaySales, 2) ?><br><span>Today's Sale</span></div>
                <div class="card-right">
                    <img src="../dashsale.png" alt="">
                </div>
            </div>
            <div class="card">
                <div class="card-left"><?= $todayCustomers ?><br><span>Today's Customer</span></div>
                <div class="card-right">
                    <img src="../customer.png" alt="">
                </div>
            </div>
            <div class="card">
                <div class="card-left">₱<?= number_format($todayProfit, 2) ?><br><span>Today's Profit</span></div>
                <div class="card-right">
                    <img src="../profit.png" alt="">
                </div>
            </div>
        </div>

        <div class="sales-report">
            <div class="sales-report-header">
                <div class="sales-left">
                    <h2>Sales this week</h2>
                    <p>₱<?= number_format(array_sum(array_column($weeklyData, 'day_sales')), 2) ?> Sales</p>
                </div>
                <div class="sales-right">
                    <p><?= date('Y') ?></p>
                    <img src="../date.png" alt="">
                </div>
            </div>

            <div class="chart">
                <?php foreach ($weeklyData as $day): ?>
                    <div class="day">
                        <div class="bar"
                            style="height: <?= ($day['day_sales'] / $maxSales) * 100 ?>%; background-color: #4CE4FF;"></div>
                        <p><span><?= substr($day['day_name'], 0, 3) ?></span></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary">
                <div class="total-customers">
                    <div class="total-left">
                        <img src="../totalcus.png" alt="">
                        <div class="total-middle">
                            <p>Total Customers</p>
                            <p><span><?= array_sum(array_column($weeklyData, 'day_customers')) ?></span></p>
                        </div>
                    </div>
                    <p><span style="color: green;">↑ 3%</span></p>
                </div>
                <div class="total-customers">
                    <div class="total-left">
                        <img src="../totalcus.png" alt="">
                        <div class="total-middle">
                            <p>Best Selling</p>
                            <p><span>
                                    <?php
                                    $bestSelling = array_filter(array_column($weeklyData, 'best_selling'));
                                    if (!empty($bestSelling)) {
                                        $counts = array_count_values($bestSelling);
                                        arsort($counts);
                                        echo key($counts);
                                    } else {
                                        echo "N/A";
                                    }
                                    ?>
                                </span></p>
                        </div>
                    </div>
                    <p><span style="color: green;">↑ 3%</span></p>
                </div>
            </div>
        </div>
        <div class="sales-report">
            <div class="sales-report-header">
                <div class="sales-left">
                    <h2>Sales Report</h2>
                    <p>₱<?= number_format(array_sum(array_column($weeklyData, 'day_sales')), 2) ?> Sales</p>
                </div>
                <div class="sales-right">
                    <p><?= date('Y') ?></p>
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
                    <th>Gross Profit</th>
                </tr>
                <?php foreach ($weeklyData as $day): ?>
                    <tr>
                        <td><?= $day['day_name'] ?></td>
                        <td>₱<?= number_format($day['day_sales'], 2) ?></td>
                        <td><?= $day['day_customers'] ?></td>
                        <td><?= $day['best_selling'] ?? 'N/A' ?></td>
                        <td>₱<?= number_format($day['cogs'], 2) ?></td>
                        <td>₱<?= number_format($day['net_profit'], 2) ?></td>
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
                document.querySelector(".active").text.style.textShadow = "0px 0px 13px rgba(255,255,255,1)";
            } else {
                element.classList.remove("selected-menu");
                arrowImg.src = "../downarrow.png";

                document.querySelector(".light").src = "../dashboard.png";
                document.querySelector(".active").style.background = "#52DCF5";
                document.querySelector(".active").style.color = "#000";
                document.querySelector(".arrow-icon").style.transform = "rotate(0deg)";
                document.querySelector(".active").text.style.textShadow = "0px 0px 13px rgba(0,0,0,1)";
            }
            console.log("After:", element.classList);
        }
    </script>
</body>

</html>