<?php

include '../header.php'; // Include header with database connection



// Fetch data

$total_orders_sql = "SELECT COUNT(*) AS total_orders FROM orders";

$total_orders_result = $con->query($total_orders_sql);

$total_orders = $total_orders_result->fetch_assoc()['total_orders'];



$orders_by_product_sql = "SELECT product, COUNT(*) AS count FROM orders GROUP BY product";

$orders_by_product_result = $con->query($orders_by_product_sql);



$orders_by_department_sql = "SELECT department, COUNT(*) AS count FROM orders GROUP BY department";

$orders_by_department_result = $con->query($orders_by_department_sql);



$recent_orders_sql = "SELECT fullname, product, quantity, department, submitted_at FROM orders ORDER BY submitted_at DESC LIMIT 5";

$recent_orders_result = $con->query($recent_orders_sql);



$last_order_per_product_sql = "SELECT product, MAX(submitted_at) AS last_order FROM orders GROUP BY product";

$last_order_per_product_result = $con->query($last_order_per_product_sql);



$average_quantity_sql = "SELECT AVG(quantity) AS avg_quantity FROM orders";

$average_quantity_result = $con->query($average_quantity_sql);

$average_quantity = $average_quantity_result->fetch_assoc()['avg_quantity'];



$top_products_sql = "SELECT product, SUM(quantity) AS total_quantity FROM orders GROUP BY product ORDER BY total_quantity DESC LIMIT 5";

$top_products_result = $con->query($top_products_sql);



$orders_by_day_sql = "SELECT DATE(submitted_at) AS date, COUNT(*) AS count FROM orders GROUP BY DATE(submitted_at)";

$orders_by_day_result = $con->query($orders_by_day_sql);



$quantity_by_month_sql = "SELECT MONTH(submitted_at) AS month, SUM(quantity) AS total_quantity FROM orders GROUP BY MONTH(submitted_at)";

$quantity_by_month_result = $con->query($quantity_by_month_sql);



$most_frequent_customer_sql = "SELECT fullname, COUNT(*) AS count FROM orders GROUP BY fullname ORDER BY count DESC LIMIT 1";

$most_frequent_customer_result = $con->query($most_frequent_customer_sql);

$most_frequent_customer = $most_frequent_customer_result->fetch_assoc();



$orders_by_hour_sql = "SELECT HOUR(submitted_at) AS hour, COUNT(*) AS count FROM orders GROUP BY HOUR(submitted_at)";

$orders_by_hour_result = $con->query($orders_by_hour_sql);

?>



<div class="container-fluid">
    <div class="row">
        <div class="col-md-9 col-sm-12">
            <div class="container mt-5">
                <h1 class="mb-4">Order Dashboard</h1>

                <!-- Export Button -->
                <div class="mb-4">
                    <a href="export_data.php" class="btn btn-primary">Export Data</a>
                </div>

                <div class="row">
                    <!-- Statistics Overview -->
                    <div class="col-md-12 mb-4">
                        <div class="row">

                            <!-- Total Orders -->
                            <div class="col-md-3 mb-4">
                                <div class="card text-white bg-primary">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Orders</h5>
                                        <p class="card-text"><?php echo $total_orders; ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Average Quantity per Order -->
                            <div class="col-md-3 mb-4">
                                <div class="card text-white bg-info">
                                    <div class="card-body">
                                        <h5 class="card-title">Average Quantity per Order</h5>
                                        <p class="card-text"><?php echo number_format($average_quantity, 2); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Most Frequent Customer -->
                            <div class="col-md-3 mb-4">
                                <div class="card text-white bg-success">
                                    <div class="card-body">
                                        <h5 class="card-title">Most Frequent Customer</h5>
                                        <p class="card-text"><?php echo htmlspecialchars($most_frequent_customer['fullname']); ?> (<?php echo $most_frequent_customer['count']; ?> orders)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Top 5 Products by Quantity Ordered -->
                            <div class="col-md-3 mb-4">
                                <div class="card text-white bg-warning">
                                    <div class="card-body">
                                        <h5 class="card-title">Top 5 Products by Quantity Ordered</h5>
                                        <ul class="list-group">
                                            <?php while ($row = $top_products_result->fetch_assoc()): ?>
                                                <li class="list-group-item">
                                                    <strong><?php echo htmlspecialchars($row['product']); ?>:</strong>
                                                    <?php echo htmlspecialchars($row['total_quantity']); ?> units
                                                </li>
                                            <?php endwhile; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Pie Charts -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Orders by Product</h5>
                                <canvas id="ordersByProductChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Orders by Department</h5>
                                <canvas id="ordersByDepartmentChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Charts -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Orders by Day</h5>
                                <canvas id="ordersByDayChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Quantity Ordered by Month</h5>
                                <canvas id="quantityByMonthChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Orders by Hour</h5>
                                <canvas id="ordersByHourChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Recent Orders</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Full Name</th>
                                                <th>Product</th>
                                                <th>Quantity</th>
                                                <th>Department</th>
                                                <th>Submitted At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $recent_orders_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['product']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['submitted_at']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Last Order Per Product -->
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Last Order Per Product</h5>
                                <ul class="list-group">
                                    <?php while ($row = $last_order_per_product_result->fetch_assoc()): ?>
                                        <li class="list-group-item">
                                            <strong><?php echo htmlspecialchars($row['product']); ?>:</strong>
                                            Last Ordered on <?php echo htmlspecialchars($row['last_order']); ?>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Empty Sidebar Column (Optional) -->
        <div class="col-md-3 col-sm-12"></div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!--script>

    document.addEventListener('DOMContentLoaded', function () {

        // Data for Orders by Product

        const ordersByProductData = {

            labels: [</?php 

                $product_labels = [];

                while ($row = $orders_by_product_result->fetch_assoc()) {

                    $product_labels[] = '"' . htmlspecialchars($row['product']) . '"';

                }

                echo implode(', ', $product_labels); 

            ?>],

            datasets: [{

                label: 'Orders by Product',

                data: [</?php 

                    $product_data = [];

                    $orders_by_product_result->data_seek(0); // Reset the result pointer

                    while ($row = $orders_by_product_result->fetch_assoc()) {

                        $product_data[] = $row['count'];

                    }

                    echo implode(', ', $product_data); 

                ?>],

                backgroundColor: [

                    'rgba(75, 192, 192, 0.2)',

                    'rgba(255, 99, 132, 0.2)',

                    'rgba(255, 159, 64, 0.2)',

                    'rgba(153, 102, 255, 0.2)',

                    'rgba(255, 205, 86, 0.2)'

                ],

                borderColor: [

                    'rgba(75, 192, 192, 1)',

                    'rgba(255, 99, 132, 1)',

                    'rgba(255, 159, 64, 1)',

                    'rgba(153, 102, 255, 1)',

                    'rgba(255, 205, 86, 1)'

                ],

                borderWidth: 1

            }]

        };



        // Data for Orders by Department

        const ordersByDepartmentData = {

            labels: [</?php 

                $department_labels = [];

                while ($row = $orders_by_department_result->fetch_assoc()) {

                    $department_labels[] = '"' . htmlspecialchars($row['department']) . '"';

                }

                echo implode(', ', $department_labels); 

            ?>],

            datasets: [{

                label: 'Orders by Department',

                data: [</?php 

                    $department_data = [];

                    $orders_by_department_result->data_seek(0); // Reset the result pointer

                    while ($row = $orders_by_department_result->fetch_assoc()) {

                        $department_data[] = $row['count'];

                    }

                    echo implode(', ', $department_data); 

                ?>],

                backgroundColor: [

                    'rgba(75, 192, 192, 0.2)',

                    'rgba(255, 99, 132, 0.2)',

                    'rgba(255, 159, 64, 0.2)',

                    'rgba(153, 102, 255, 0.2)',

                    'rgba(255, 205, 86, 0.2)'

                ],

                borderColor: [

                    'rgba(75, 192, 192, 1)',

                    'rgba(255, 99, 132, 1)',

                    'rgba(255, 159, 64, 1)',

                    'rgba(153, 102, 255, 1)',

                    'rgba(255, 205, 86, 1)'

                ],

                borderWidth: 1

            }]

        };



        // Data for Orders by Day

        const ordersByDayData = {

            labels: [</?php 

                $day_labels = [];

                while ($row = $orders_by_day_result->fetch_assoc()) {

                    $day_labels[] = '"' . htmlspecialchars($row['date']) . '"';

                }

                echo implode(', ', $day_labels); 

            ?>],

            datasets: [{

                label: 'Orders by Day',

                data: [</?php 

                    $day_data = [];

                    $orders_by_day_result->data_seek(0); // Reset the result pointer

                    while ($row = $orders_by_day_result->fetch_assoc()) {

                        $day_data[] = $row['count'];

                    }

                    echo implode(', ', $day_data); 

                ?>],

                backgroundColor: 'rgba(75, 192, 192, 0.2)',

                borderColor: 'rgba(75, 192, 192, 1)',

                borderWidth: 1

            }]

        };



        // Data for Quantity Ordered by Month

        const quantityByMonthData = {

            labels: [</?php 

                $month_labels = [];

                while ($row = $quantity_by_month_result->fetch_assoc()) {

                    $month_labels[] = '"' . htmlspecialchars(date('F', mktime(0, 0, 0, $row['month'], 10))) . '"';

                }

                echo implode(', ', $month_labels); 

            ?>],

            datasets: [{

                label: 'Quantity Ordered by Month',

                data: [</?php 

                    $month_data = [];

                    $quantity_by_month_result->data_seek(0); // Reset the result pointer

                    while ($row = $quantity_by_month_result->fetch_assoc()) {

                        $month_data[] = $row['total_quantity'];

                    }

                    echo implode(', ', $month_data); 

                ?>],

                backgroundColor: 'rgba(153, 102, 255, 0.2)',

                borderColor: 'rgba(153, 102, 255, 1)',

                borderWidth: 1

            }]

        };



        // Data for Orders by Hour

        const ordersByHourData = {

            labels: [</?php 

                $hour_labels = [];

                while ($row = $orders_by_hour_result->fetch_assoc()) {

                    $hour_labels[] = '"' . htmlspecialchars($row['hour']) . ':00"';

                }

                echo implode(', ', $hour_labels); 

            ?>],

            datasets: [{

                label: 'Orders by Hour',

                data: [</?php 

                    $hour_data = [];

                    $orders_by_hour_result->data_seek(0); // Reset the result pointer

                    while ($row = $orders_by_hour_result->fetch_assoc()) {

                        $hour_data[] = $row['count'];

                    }

                    echo implode(', ', $hour_data); 

                ?>],

                backgroundColor: 'rgba(255, 159, 64, 0.2)',

                borderColor: 'rgba(255, 159, 64, 1)',

                borderWidth: 1

            }]

        };



        // Create Charts

        const ctxProduct = document.getElementById('ordersByProductChart').getContext('2d');

        new Chart(ctxProduct, {

            type: 'pie',

            data: ordersByProductData

        });



        const ctxDepartment = document.getElementById('ordersByDepartmentChart').getContext('2d');

        new Chart(ctxDepartment, {

            type: 'pie',

            data: ordersByDepartmentData

        });



        const ctxDay = document.getElementById('ordersByDayChart').getContext('2d');

        new Chart(ctxDay, {

            type: 'line',

            data: ordersByDayData

        });



        const ctxMonth = document.getElementById('quantityByMonthChart').getContext('2d');

        new Chart(ctxMonth, {

            type: 'bar',

            data: quantityByMonthData

        });



        const ctxHour = document.getElementById('ordersByHourChart').getContext('2d');

        new Chart(ctxHour, {

            type: 'line',

            data: ordersByHourData

        });

    });

</script-->





    <script>

        document.addEventListener('DOMContentLoaded', function () {

            // Data for Orders by Product

            const ordersByProductData = {

                labels: [<?php 

                    $product_labels = [];

                    while ($row = $orders_by_product_result->fetch_assoc()) {

                        $product_labels[] = '"' . htmlspecialchars($row['product']) . '"';

                    }

                    echo implode(', ', $product_labels); 

                ?>],

                datasets: [{

                    label: 'Orders by Product',

                    data: [<?php 

                        $product_data = [];

                        $orders_by_product_result->data_seek(0); // Reset the result pointer

                        while ($row = $orders_by_product_result->fetch_assoc()) {

                            $product_data[] = $row['count'];

                        }

                        echo implode(', ', $product_data); 

                    ?>],

                    backgroundColor: [

                        'rgba(75, 192, 192, 0.2)',

                        'rgba(255, 99, 132, 0.2)',

                        'rgba(255, 159, 64, 0.2)',

                        'rgba(153, 102, 255, 0.2)',

                        'rgba(255, 205, 86, 0.2)'

                    ],

                    borderColor: [

                        'rgba(75, 192, 192, 1)',

                        'rgba(255, 99, 132, 1)',

                        'rgba(255, 159, 64, 1)',

                        'rgba(153, 102, 255, 1)',

                        'rgba(255, 205, 86, 1)'

                    ],

                    borderWidth: 1

                }]

            };



            // Data for Orders by Department

            const ordersByDepartmentData = {

                labels: [<?php 

                    $department_labels = [];

                    while ($row = $orders_by_department_result->fetch_assoc()) {

                        $department_labels[] = '"' . htmlspecialchars($row['department']) . '"';

                    }

                    echo implode(', ', $department_labels); 

                ?>],

                datasets: [{

                    label: 'Orders by Department',

                    data: [<?php 

                        $department_data = [];

                        $orders_by_department_result->data_seek(0); // Reset the result pointer

                        while ($row = $orders_by_department_result->fetch_assoc()) {

                            $department_data[] = $row['count'];

                        }

                        echo implode(', ', $department_data); 

                    ?>],

                    backgroundColor: [

                        'rgba(75, 192, 192, 0.2)',

                        'rgba(255, 99, 132, 0.2)',

                        'rgba(255, 159, 64, 0.2)',

                        'rgba(153, 102, 255, 0.2)',

                        'rgba(255, 205, 86, 0.2)'

                    ],

                    borderColor: [

                        'rgba(75, 192, 192, 1)',

                        'rgba(255, 99, 132, 1)',

                        'rgba(255, 159, 64, 1)',

                        'rgba(153, 102, 255, 1)',

                        'rgba(255, 205, 86, 1)'

                    ],

                    borderWidth: 1

                }]

            };



            // Data for Orders by Day

            const ordersByDayData = {

                labels: [<?php 

                    $day_labels = [];

                    while ($row = $orders_by_day_result->fetch_assoc()) {

                        $day_labels[] = '"' . htmlspecialchars($row['date']) . '"';

                    }

                    echo implode(', ', $day_labels); 

                ?>],

                datasets: [{

                    label: 'Orders by Day',

                    data: [<?php 

                        $day_data = [];

                        $orders_by_day_result->data_seek(0); // Reset the result pointer

                        while ($row = $orders_by_day_result->fetch_assoc()) {

                            $day_data[] = $row['count'];

                        }

                        echo implode(', ', $day_data); 

                    ?>],

                    backgroundColor: 'rgba(75, 192, 192, 0.2)',

                    borderColor: 'rgba(75, 192, 192, 1)',

                    borderWidth: 1

                }]

            };



            // Data for Quantity Ordered by Month

            const quantityByMonthData = {

                labels: [<?php 

                    $month_labels = [];

                    while ($row = $quantity_by_month_result->fetch_assoc()) {

                        $month_labels[] = '"' . htmlspecialchars(date('F', mktime(0, 0, 0, $row['month'], 10))) . '"';

                    }

                    echo implode(', ', $month_labels); 

                ?>],

                datasets: [{

                    label: 'Quantity Ordered by Month',

                    data: [<?php 

                        $month_data = [];

                        $quantity_by_month_result->data_seek(0); // Reset the result pointer

                        while ($row = $quantity_by_month_result->fetch_assoc()) {

                            $month_data[] = $row['total_quantity'];

                        }

                        echo implode(', ', $month_data); 

                    ?>],

                    backgroundColor: 'rgba(153, 102, 255, 0.2)',

                    borderColor: 'rgba(153, 102, 255, 1)',

                    borderWidth: 1

                }]

            };



            // Data for Orders by Hour

            const ordersByHourData = {

                labels: [<?php 

                    $hour_labels = [];

                    while ($row = $orders_by_hour_result->fetch_assoc()) {

                        $hour_labels[] = '"' . htmlspecialchars($row['hour']) . ':00"';

                    }

                    echo implode(', ', $hour_labels); 

                ?>],

                datasets: [{

                    label: 'Orders by Hour',

                    data: [<?php 

                        $hour_data = [];

                        $orders_by_hour_result->data_seek(0); // Reset the result pointer

                        while ($row = $orders_by_hour_result->fetch_assoc()) {

                            $hour_data[] = $row['count'];

                        }

                        echo implode(', ', $hour_data); 

                    ?>],

                    backgroundColor: 'rgba(255, 159, 64, 0.2)',

                    borderColor: 'rgba(255, 159, 64, 1)',

                    borderWidth: 1

                }]

            };



            // Create Charts

            const ctxProduct = document.getElementById('ordersByProductChart').getContext('2d');

            new Chart(ctxProduct, {

                type: 'pie',

                data: ordersByProductData

            });



            const ctxDepartment = document.getElementById('ordersByDepartmentChart').getContext('2d');

            new Chart(ctxDepartment, {

                type: 'pie',

                data: ordersByDepartmentData

            });



            const ctxDay = document.getElementById('ordersByDayChart').getContext('2d');

            new Chart(ctxDay, {

                type: 'line',

                data: ordersByDayData

            });



            const ctxMonth = document.getElementById('quantityByMonthChart').getContext('2d');

            new Chart(ctxMonth, {

                type: 'bar',

                data: quantityByMonthData

            });



            const ctxHour = document.getElementById('ordersByHourChart').getContext('2d');

            new Chart(ctxHour, {

                type: 'line',

                data: ordersByHourData

            });

        });

    </script>



<?php include '../footer.php'; ?>

