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

// Fetch transaction history for authenticated user only
$stmt = $conn->prepare("SELECT id, date, total_quantity, total_price, received FROM transactions WHERE user_id = ? ORDER BY date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
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
                    <li class="inactive">
                        <div class="inactive-box">
                            <div class="menu">
                                <a href="dashboard.php">
                                    <div class="menulogo-left">
                                        <img src="../dash.png" alt="">
                                        <p>Dashboard</p>
                                    </div>
                                </a>
                                <div class="menulogo-right">
                                    <img src="../arrow.png" alt="" class="arrow-icon">
                                </div>
                            </div>
                        </div>
                        <ul class="dropdown">
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

                    <li class="menu-item">
                        <div class="active">
                            <div class="menu" id="pisteyawa">
                                <a href="#" class="damndashboard">
                                    <div class="menulogo-left">
                                        <img src="../sale.png" alt="" class="light">
                                        Sales
                                    </div>
                                </a>
                                <div class="menulogo-right arrow">
                                    <div class="menu" onclick="toggleActive(this)"><img src="../downarrow.png" alt=""
                                            class="arrow-icon" id="arrow-piste"></div>
                                </div>
                            </div>
                        </div>
                        <ul class="dropdown1">
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
            <div class="history">
                <div class="history-up">
                    <div class="sales-report-header">
                        <div class="sales-left">
                            <h2>History Report</h2>
                            <p>This Month</p>
                        </div>

                        <div class="right-middle">
                            <div class="search-box">
                                <img src="../search.png" alt="">
                                <input type="text" id="history-search" placeholder="Search by date or amount...">
                            </div>
                            <div class="date-range">
                                <input type="date" id="start-date" placeholder="Start date">
                                <span>to</span>
                                <input type="date" id="end-date" placeholder="End date">
                                <button id="apply-date-filter">Apply</button>
                            </div>
                            <div class="sales-right">
                                <p>2025</p>
                                <img src="../date.png" alt="">
                            </div>
                        </div>
                    </div>

                    <table>
                        <tr>
                            <th>Date</th>
                            <th>Total Quantity</th>
                            <th>Total Price</th>
                            <th>Recieved</th>
                            <th>Action</th>
                        </tr>

                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo date('F j Y', strtotime($transaction['date'])); ?></td>
                                <td><?php echo $transaction['total_quantity']; ?></td>
                                <td>₱<?php echo number_format($transaction['total_price'], 2); ?></td>
                                <td>₱<?php echo number_format($transaction['received'], 2); ?></td>
                                <td><button class="Edit" data-id="<?php echo $transaction['id']; ?>">View</button></td>
                            </tr>
                        <?php endforeach; ?>

                    </table>

                    <!-- Div to show the details when "View" button is clicked -->
                    <!-- Transaction Details Modal -->
                    <div id="transaction-modal" class="modal">
                        <div class="modal-content">
                            <span class="close-modal">&times;</span>
                            <h3>Transaction Details</h3>
                            <div class="modal-body">
                                <table class="modal-table">
                                    <thead>
                                        <tr>
                                            <th>Product ID</th>
                                            <th>Product Name</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modal-details-body">
                                        <!-- Details will be inserted here by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="history-down">
                    <p>Showing 1 to 2 of 2 entries</p>
                    <div class="entries">
                        <button class="previous">
                            <p>Previous</p>
                        </button>
                        <div class="current">
                            <p>1</p>
                        </div>
                        <button class="next">
                            <p>Next</p>
                        </button>
                    </div>
                </div>

            </div>

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

        document.getElementById('apply-date-filter').addEventListener('click', function () {
            const startDate = new Date(document.getElementById('start-date').value);
            const endDate = new Date(document.getElementById('end-date').value);

            if (!startDate || !endDate) return;

            const rows = document.querySelectorAll('table tr:not(:first-child)');

            rows.forEach(row => {
                const rowDate = new Date(row.cells[0].textContent);
                const isInRange = rowDate >= startDate && rowDate <= endDate;
                row.style.display = isInRange ? '' : 'none';
            });
        });

        document.getElementById('history-search').addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('table tr:not(:first-child)'); // Skip header row

            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let rowMatches = false;

                // Check each cell in the row (except the action cell)
                for (let i = 0; i < cells.length - 1; i++) {
                    const cellText = cells[i].textContent.toLowerCase();
                    if (cellText.includes(searchTerm)) {
                        rowMatches = true;
                        break;
                    }
                }

                // Show/hide row based on search
                row.style.display = rowMatches ? '' : 'none';
            });
        });

        let searchTimeout;

        document.getElementById('history-search').addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('table tr:not(:first-child)');

                // If empty search, show all rows
                if (!searchTerm) {
                    rows.forEach(row => row.style.display = '');
                    return;
                }

                // Check each row
                rows.forEach(row => {
                    const date = row.cells[0].textContent.toLowerCase();
                    const quantity = row.cells[1].textContent;
                    const price = row.cells[2].textContent;
                    const received = row.cells[3].textContent;

                    // Check if any field matches
                    const matches = date.includes(searchTerm) ||
                        quantity.includes(searchTerm) ||
                        price.includes(searchTerm) ||
                        received.includes(searchTerm);

                    row.style.display = matches ? '' : 'none';
                });
            }, 300); // 300ms delay after typing stops
        });

        const modal = document.getElementById('transaction-modal');
        const closeBtn = document.querySelector('.close-modal');
        const modalBody = document.getElementById('modal-details-body');

        // Improved View button handler
        document.querySelectorAll('.Edit').forEach(button => {
            button.addEventListener('click', async function () {
                const transactionId = this.dataset.id;
                console.log("Fetching transaction:", transactionId);

                // Show loading state
                modalBody.innerHTML = '<tr><td colspan="6" class="loading">Loading details...</td></tr>';
                modal.style.display = 'block';

                try {
                    const response = await fetch(`fetch_transaction_details.php?transaction_id=${transactionId}`);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();
                    console.log("Received data:", data);

                    if (!data || data.error) {
                        throw new Error(data?.error || 'No data received');
                    }

                    renderTransactionDetails(data);

                } catch (error) {
                    console.error("Fetch error:", error);
                    modalBody.innerHTML = `
                <tr>
                    <td colspan="6" class="error">
                        Error loading details: ${error.message}<br>
                        Please check console for details
                    </td>
                </tr>
            `;
                }
            });
        });

        function renderTransactionDetails(products) {
            modalBody.innerHTML = '';

            if (products.length === 0) {
                modalBody.innerHTML = '<tr><td colspan="6">No Record found in this transaction</td></tr>';
                return;
            }

            // Add products
            products.forEach(product => {
                const row = document.createElement('tr');
                row.innerHTML = `
            <td>${product.product_id || 'N/A'}</td>
            <td>${product.product_name || 'N/A'}</td>
            <td>${product.category || 'N/A'}</td>
            <td>₱${parseFloat(product.price || 0).toFixed(2)}</td>
            <td>${product.quantity || 0}</td>
            <td>₱${parseFloat(product.subtotal || 0).toFixed(2)}</td>
        `;
                modalBody.appendChild(row);
            });

            // Add total row
            const total = products.reduce((sum, p) => sum + parseFloat(p.subtotal || 0), 0);
            const totalRow = document.createElement('tr');
            totalRow.className = 'total-row';
            totalRow.innerHTML = `
        <td colspan="5"><strong>Total:</strong></td>
        <td><strong>₱${total.toFixed(2)}</strong></td>
    `;
            modalBody.appendChild(totalRow);
        }

        // Close modal when X is clicked
        closeBtn.addEventListener('click', function () {
            modal.style.display = 'none';
        });

        // Close modal when clicking outside
        window.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

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
            let arrowImg = element.querySelector("#arrow-yawa");
            let isActive = element.classList.contains("selected-menu");

            document.querySelectorAll(".menu").forEach(menu => menu.classList.remove("selected-menu"));

            if (!isActive) {
                element.classList.add("selected-menu");
                arrowImg.src = "../uparrow.png";

                document.querySelector(".light").src = "../light.svg";
                document.querySelector("#arrow-yawa").style.transform = "rotate(360deg)";
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
                document.querySelector("#arrow-yawa").style.transform = "rotate(0deg)";
                document.querySelector(".active").text.style.textShadow = "0px 0px 13px rgba(0,0,0,1)";
            }
            console.log("After:", element.classList);
        }
    </script>
</body>

</html>