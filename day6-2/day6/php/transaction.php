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


        <div class="sales-report" id="transaction">
            <form action="save_transaction.php" method="POST" onsubmit="return validateTransaction(event)"
                id="transactionForm">
                <input type="hidden" name="totalQuantity" id="totalQuantity">
                <input type="hidden" name="totalPrice" id="totalPrice">
                <input type="hidden" name="received" id="received-hidden">
                <input type="hidden" name="products" id="products" value='[]'>
                <div class="transaction-up">

                    <div class="sales-report-header">
                        <div class="sales-left">
                            <h2>New Transaction</h2>
                        </div>


                    </div>

                    <table>
                        <tr>
                            <th>Product Id</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Actions</th>
                        </tr>
                        <tr>
                            <td>
                                <input type="text" id="product_id" name="product_id" placeholder="Product Id" disabled>
                            </td>

                            <td>
                                <input type="text" id="product_name" name="product_name" placeholder="Product Name">
                            </td>

                            <td>
                                <input type="text" id="category" name="category" placeholder="Category" disabled>
                            </td>

                            <td>
                                <input type="number" id="price" name="price" placeholder="Price" disabled>
                            </td>

                            <td>
                                <input type="number" id="quantity" name="quantity" placeholder="Quantity" min="1"
                                    value="1">
                            </td>

                            <td>
                                <input type="number" id="subtotal" name="subtotal" placeholder="Subtotal" disabled>
                            </td>

                            <td>
                                <button type="button" class='edit' onclick="addProduct()">Add</button>
                            </td>
                        </tr>

                    </table>

                    <table class="transact-table">
                        <tr>
                            <th>Product Id</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Actions</th>
                        </tr>


                    </table>
                </div>

                <div class="transaction-down">
                    <div class="tra-down-up">

                        <div class="total" id="total-box">
                            <p>Total: ₱</p>
                            <P></P>
                        </div>

                        <div class="total">
                            <label>Recieve</label>
                            <div class="input-box">
                                <p>₱</p>
                                <input type="number" id="received" name="received" placeholder="Recieve" min="1"
                                    required oninput="validatePayment()">
                            </div>
                            <small id="payment-error" style="color: red; display: none;">
                                Received amount must be ≥ Total
                            </small>
                        </div>

                        <div class="total">
                            <label>Return</label>
                            <div class="input-box">
                                <p>₱</p>
                                <p id="return-display"></p>
                            </div>
                        </div>

                    </div>
                    <div class="tra-down-down">
                        <button type="submit" class="transaction-btn" id="yawas">Save Transaction</button>
                    </div>
                </div>
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
        function validateTransaction(event) {
            event.preventDefault();

            const rows = document.querySelectorAll('.transact-table tr');
            if (rows.length <= 1) {
                showError("Please add at least one product before saving!");
                return false;
            }

            const total = parseFloat(document.querySelector('#total-box p:last-child').textContent) || 0;
            const received = parseFloat(document.getElementById('received').value) || 0;

            if (received <= 0) {
                showError("Please enter the received amount!");
                return false;
            }

            if (received < total) {
                showError("Received amount cannot be less than the total amount!");
                return false;
            }

            prepareData();
            document.getElementById("transactionForm").submit();
            return true;
        }
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            document.body.appendChild(errorDiv);

            setTimeout(() => {
                errorDiv.remove();
            }, 3000);
        }

        function updateSaveButtonState() {
            const saveBtn = document.querySelector('.transaction-btn');
            const rows = document.querySelectorAll('.transact-table tr');
            saveBtn.disabled = rows.length <= 1;
        }


        function validatePayment() {
            const total = parseFloat(document.querySelector('#total-box p:last-child').textContent) || 0;
            const received = parseFloat(document.getElementById('received').value) || 0;
            const errorElement = document.getElementById('payment-error');
            const saveBtn = document.querySelector('.transaction-btn');

            if (received > 0 && received < total) {
                errorElement.style.display = 'block';
                saveBtn.disabled = true;
            } else {
                errorElement.style.display = 'none';
                saveBtn.disabled = false;
            }
        }

        // Enable/disable receive input
        function toggleReceiveInput() {
            const rows = document.querySelectorAll('.transact-table tr');
            const receiveInput = document.getElementById('received');

            if (rows.length <= 1) { // Only header row exists
                receiveInput.disabled = true;
                receiveInput.value = '';
                clearReturnDisplay();
            } else {
                receiveInput.disabled = false;
            }
        }

        // Clear the return display
        function clearReturnDisplay() {
            const returnBox = document.querySelector('.total .input-box p:last-child');
            returnBox.textContent = '';
            returnBox.style.color = '';
        }

        // Calculate and display return change
        function calculateReturn() {
            const total = parseFloat(document.querySelector('#total-box p:last-child').textContent) || 0;
            const receivedInput = document.getElementById('received');
            const received = parseFloat(receivedInput.value) || 0;
            const returnDisplay = document.getElementById('return-display');
            const hiddenReceived = document.getElementById('received-hidden');

            // Update hidden field 
            hiddenReceived.value = received;

            if (received > 0) {
                const change = received - total;
                if (change < 0) {
                    returnDisplay.textContent = "Insufficient Balance Please Top";
                    returnDisplay.style.color = "red";
                } else {
                    returnDisplay.textContent = change.toFixed(2);
                    returnDisplay.style.color = "#000";
                }
            } else {
                returnDisplay.textContent = "";
            }
        }

        document.getElementById('received').addEventListener('input', function () {
            calculateReturn();
        });


        document.addEventListener("DOMContentLoaded", function () {
            toggleReceiveInput();

            // Receive amount input listener
            const receivedInput = document.getElementById('received');
            receivedInput.addEventListener('input', function () {
                // Update both the display and form value
                calculateReturn();
                document.getElementById('received').value = this.value;
            });

        });

        // Calculate subtotal 
        function calculateSubtotal() {
            const price = parseFloat(document.getElementById('price').value) || 0;
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            document.getElementById('subtotal').value = (price * quantity).toFixed(2);
        }

        // Calculate grand total 
        function calculateTotal() {
            let totalPrice = 0;
            let totalQuantity = 0;
            const rows = document.querySelectorAll('.transact-table tr');

            rows.forEach((row, index) => {
                if (index === 0) return;

                const cells = row.cells;
                const quantity = parseInt(cells[4].textContent) || 0;
                const subtotal = parseFloat(cells[5].textContent.replace('₱', '')) || 0;

                totalQuantity += quantity;
                totalPrice += subtotal;
            });

            document.querySelector('#total-box p:last-child').textContent = totalPrice.toFixed(2);

            document.getElementById('totalQuantity').value = totalQuantity;
            document.getElementById('totalPrice').value = totalPrice.toFixed(2);

            // Update return calculation
            calculateReturn();
            validatePayment();
        }

        function clearInputs() {
            document.getElementById('product_id').value = '';
            document.getElementById('product_name').value = '';
            document.getElementById('category').value = '';
            document.getElementById('price').value = '';
            document.getElementById('quantity').value = 1;
            document.getElementById('quantity').max = '';
            document.getElementById('quantity').removeAttribute('data-stock');
            document.getElementById('subtotal').value = '';
        }

        // Update DOMContentLoaded
        document.addEventListener("DOMContentLoaded", function () {
            toggleReceiveInput();
            updateSaveButtonState();


        });

        function prepareData() {
            alert("Transaction Complete!");
            const products = [];
            document.querySelectorAll('.transact-table tr').forEach((row, index) => {
                if (index === 0) return;

                const cells = row.cells;
                products.push({
                    id: cells[0].textContent,
                    name: cells[1].textContent,
                    category: cells[2].textContent,
                    price: parseFloat(cells[3].textContent.replace('₱', '')),
                    quantity: parseInt(cells[4].textContent),
                    subtotal: parseFloat(cells[5].textContent.replace('₱', ''))
                });
            });

            document.getElementById('products').value = JSON.stringify(products);
            document.getElementById('received-hidden').value = document.getElementById('received').value;
        }


        document.addEventListener("DOMContentLoaded", function () {
            toggleReceiveInput();

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
        });


        // Flag to prevent multiple simultaneous fetches
        let isFetching = false;

        // Function to fetch product details
        function fetchProductDetails() {
            if (isFetching) return; // Prevent multiple simultaneous fetches

            const productName = document.getElementById('product_name').value.trim();
            if (productName === '') {
                return;
            }

            isFetching = true;

            fetch(`fetch_product.php?product_name=${encodeURIComponent(productName)}`)
                .then(response => response.json())
                .then(data => {
                    isFetching = false;

                    if (data.error) {
                        alert(data.error);
                        clearInputs();
                    } else {
                        document.getElementById('product_id').value = data.product_id;
                        document.getElementById('category').value = data.category;
                        document.getElementById('price').value = data.price;
                        document.getElementById('quantity').max = data.stock;
                        document.getElementById('quantity').setAttribute('data-stock', data.stock);
                        calculateSubtotal();
                        alert(`Available stock: ${data.stock}`);
                    }
                })
                .catch(error => {
                    isFetching = false;
                    console.error('Fetch error:', error);
                });
        }

        // Only trigger on Enter key press (not blur to avoid conflicts)
        document.getElementById('product_name').addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Prevent form submission or page reload
                fetchProductDetails();
            }
        });

        // Quantity and receive input updates subtotal
        document.getElementById('quantity').addEventListener('input', calculateSubtotal);

        document.getElementById('received').addEventListener('input', calculateReturn);

        function isProductAlreadyAdded(productId) {
            const rows = document.querySelectorAll('.transact-table tr');
            for (let i = 1; i < rows.length; i++) { // Skip header row
                const rowProductId = rows[i].cells[0].textContent;
                if (rowProductId === productId) {
                    return true;
                }
            }
            return false;
        }
        // Add product 
        function addProduct() {
            const id = document.getElementById('product_id').value;
            const name = document.getElementById('product_name').value;
            const category = document.getElementById('category').value;
            const price = parseFloat(document.getElementById('price').value).toFixed(2);
            const qty = parseInt(document.getElementById('quantity').value) || 1;
            const subtotal = parseFloat(document.getElementById('subtotal').value).toFixed(2);
            const availableStock = parseInt(document.getElementById('quantity').getAttribute('data-stock')) || 0;

            if (!id || !name || !category || !price || !qty || !subtotal) {
                alert("Please complete the product details first.");
                return;
            }

            if (isProductAlreadyAdded(id)) {
                alert("This product is already in the transaction. Please modify the quantity instead.");
                return;
            }

            if (qty > availableStock) {
                alert(`Only ${availableStock} items available in stock!`);
                return;
            }


            if (qty < 1) {
                alert("Quantity must be at least 1");
                return;
            }

            const row = document.createElement('tr');
            row.innerHTML = `
        <td>${id}</td>
        <td>${name}</td>
        <td>${category}</td>
        <td>₱${price}</td>
        <td>${qty}</td>
        <td>₱${subtotal}</td>
        <td><button class='Delete'>Delete</button></td>
    `;

            document.querySelector('.transact-table').appendChild(row);
            calculateTotal();
            clearInputs();
            toggleReceiveInput();
            updateSaveButtonState();

        }

        document.querySelector('.transact-table').addEventListener('click', function (e) {
            if (e.target.classList.contains('Delete')) {
                e.preventDefault();
                e.target.closest('tr').remove();
                calculateTotal();
                toggleReceiveInput();
                updateSaveButtonState();
            }
        });

        // Delete product 
        document.querySelector('.transact-table').addEventListener('click', function (e) {
            if (e.target.classList.contains('Delete')) {
                e.target.closest('tr').remove();
                calculateTotal();
                toggleReceiveInput();
            }
        });


        document.querySelector('.transact-table').addEventListener('DOMSubtreeModified', toggleReceiveInput);

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
            let arrowImg = element.querySelector("#arrow-piste");
            let isActive = element.classList.contains("selected-menu");

            document.querySelectorAll(".menu").forEach(menu => menu.classList.remove("selected-menu"));

            if (!isActive) {
                element.classList.add("selected-menu");
                arrowImg.src = "../uparrow.png";

                document.querySelector(".light").src = "../sales.png";
                document.querySelector("#arrow-piste").style.transform = "rotate(360deg)";
                document.querySelector(".active").style.background = "#39727C";
                document.querySelector(".active").style.color = "#fff";
                document.querySelector(".damndashboard").style.color = "#fff";
                document.querySelector(".active").text.style.textShadow = "0px 0px 13px rgba(255,255,255,1)";
            } else {
                element.classList.remove("selected-menu");
                arrowImg.src = "../downarrow.png";

                document.querySelector(".light").src = "../sale.png";
                document.querySelector(".active").style.background = "#52DCF5";
                document.querySelector(".active").style.color = "#000";
                document.querySelector(".damndashboard").style.color = "#000";
                document.querySelector("#arrow-piste").style.transform = "rotate(0deg)";
                document.querySelector(".active").text.style.textShadow = "0px 0px 13px rgba(0,0,0,1)";
            }
            console.log("After:", element.classList);
        }
    </script>
</body>

</html>