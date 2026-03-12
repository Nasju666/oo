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

// Get product ID from URL
$product_id = $_GET['id'] ?? 0;

// Fetch product details - verify ownership
$stmt = $conn->prepare("SELECT * FROM tblproduct WHERE product_id = ? AND user_id = ?");
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $conn->prepare("UPDATE tblproduct SET 
        product_name = ?, 
        category = ?, 
        cost_price = ?, 
        selling_price = ?, 
        stock = ? 
        WHERE product_id = ? AND user_id = ?");

    $stmt->bind_param(
        "ssddiii",
        $_POST['product_name'],
        $_POST['category'],
        $_POST['cost_price'],
        $_POST['selling_price'],
        $_POST['stock'],
        $product_id,
        $user_id
    );

    if ($stmt->execute()) {
        header("Location: viewproduct.php?success=1");
        exit();
    } else {
        $error = "Error updating product: " . $stmt->error;
    }
}
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sari-Sari Store</title>
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
                    <li class="menu-item">
                        <div class="active">
                            <div class="menu" id="piste">
                                <a href="#" class="damndashboard">
                                    <div class="menulogo-left">
                                        <img src="../prod.png" alt="" class="light">
                                        Product
                                    </div>
                                </a>
                                <div class="menulogo-right arrow">
                                    <div class="menu" onclick="toggleActive(this)"><img src="../downarrow.png" alt=""
                                            class="arrow-icon" id="arrow-yawa"></div>
                                </div>
                            </div>
                        </div>
                        <ul class="dropdown1">
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

        <div class="form-container">
            <h2>Edit Product Form</h2>
            <form method="POST" action="edit_product.php?id=<?= $product_id ?>">
                <table>
                    <tr>
                        <td><label>Product Name:</label></td>
                        <td><input type="text" name="product_name"
                                value="<?= htmlspecialchars($product['product_name']) ?>" required></td>
                    </tr>
                    <tr>
                        <td><label>Category:</label></td>
                        <td><input type="text" name="category" value="<?= htmlspecialchars($product['category']) ?>"
                                required></td>
                    </tr>
                    <tr>
                        <td><label>Cost Price:</label></td>
                        <td><input type="number" step="0.01" name="cost_price" value="<?= $product['cost_price'] ?>"
                                required></td>
                    </tr>
                    <tr>
                        <td><label>Selling Price:</label></td>
                        <td><input type="number" step="0.01" name="selling_price"
                                value="<?= $product['selling_price'] ?>" required></td>
                    </tr>
                    <tr>
                        <td><label>Stock:</label></td>
                        <td><input type="number" name="stock" value="<?= $product['stock'] ?>" required></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <button type="submit" class="feedback-btn">Update Product</button>
                            <a href="viewproduct.php" class="feedback-btn"
                                style="background-color: #ccc; margin-left: 10px;">Cancel</a>
                        </td>
                    </tr>
                </table>
            </form>
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

        function showAlert(event) {
            event.preventDefault();
            alert("Product added successfully!");
            document.getElementById("productForm").submit();
        }

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

                document.querySelector(".light").src = "../product.png";
                document.querySelector("#arrow-yawa").style.transform = "rotate(360deg)";
                document.querySelector(".active").style.background = "#39727C";
                document.querySelector(".active").style.color = "#fff";
                document.querySelector(".damndashboard").style.color = "#fff";
                document.querySelector(".active").text.style.textShadow = "0px 0px 13px rgba(255,255,255,1)";
            } else {
                element.classList.remove("selected-menu");
                arrowImg.src = "../downarrow.png";

                document.querySelector(".light").src = "../prod.png";
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