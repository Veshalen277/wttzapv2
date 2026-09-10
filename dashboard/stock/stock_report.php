<?php include '../header.php'; ?>

<!-- Include Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid">
    <?php
    // Fetch Categories from the database
    $sql_categories = "SELECT * FROM stock_categories";
    $result_categories = mysqli_query($con, $sql_categories);

    if (mysqli_num_rows($result_categories) > 0) {
        while ($category = mysqli_fetch_assoc($result_categories)) {
            $category_name = htmlspecialchars($category['category_name']);
            $category_id = intval($category['category_id']);

            // Fetch Items for each Category
            $sql_items = "SELECT item_name, qty_on_hand, order_qty, received_qty FROM stock_items WHERE category_id = $category_id";
            $result_items = mysqli_query($con, $sql_items);

            // Initialize arrays for Chart.js data
            $item_names = [];
            $qty_on_hand = [];
            $order_qty = [];
            $received_qty = [];

            // Process items data
            if (mysqli_num_rows($result_items) > 0) {
                while ($item = mysqli_fetch_assoc($result_items)) {
                    $item_names[] = htmlspecialchars($item['item_name']);
                    $qty_on_hand[] = intval($item['qty_on_hand']);
                    $order_qty[] = intval($item['order_qty']);
                    $received_qty[] = intval($item['received_qty']);
                }
            }

            // Convert PHP arrays to JSON for Chart.js
            $item_names_json = json_encode($item_names);
            $qty_on_hand_json = json_encode($qty_on_hand);
            $order_qty_json = json_encode($order_qty);
            $received_qty_json = json_encode($received_qty);
            ?>

            <div class="row mb-4">
                <div class="col-lg-6">
                    <div class="container mt-2">
                        <h2><?php echo $category_name; ?></h2>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Qty on Hand</th>
                                    <th>Order Qty</th>
                                    <th>Received Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($item_names)): ?>
                                    <?php foreach ($item_names as $index => $name): ?>
                                        <tr>
                                            <td><?php echo $name; ?></td>
                                            <td><?php echo $qty_on_hand[$index]; ?></td>
                                            <td><?php echo $order_qty[$index]; ?></td>
                                            <td><?php echo $received_qty[$index]; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No data available for <?php echo $category_name; ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-6">
                    <?php if (!empty($item_names)): ?>
                        <canvas id="chart-<?php echo $category_id; ?>" style="max-width: 100%; height: 250px; margin: 0 auto;"></canvas>
                        <script>
                            var ctx = document.getElementById('chart-<?php echo $category_id; ?>').getContext('2d');
                            var chart = new Chart(ctx, {
                                type: 'bar', // Type of chart: bar chart
                                data: {
                                    labels: <?php echo $item_names_json; ?>,
                                    datasets: [{
                                        label: 'Quantity on Hand',
                                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                                        borderColor: 'rgba(54, 162, 235, 1)',
                                        borderWidth: 1,
                                        data: <?php echo $qty_on_hand_json; ?>
                                    }, {
                                        label: 'Order Quantity',
                                        backgroundColor: 'rgba(255, 206, 86, 0.6)',
                                        borderColor: 'rgba(255, 206, 86, 1)',
                                        borderWidth: 1,
                                        data: <?php echo $order_qty_json; ?>
                                    }, {
                                        label: 'Received Quantity',
                                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                                        borderColor: 'rgba(75, 192, 192, 1)',
                                        borderWidth: 1,
                                        data: <?php echo $received_qty_json; ?>
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false, // Allows the chart to adjust its height
                                    plugins: {
                                        legend: {
                                            position: 'top',
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function(tooltipItem) {
                                                    return tooltipItem.label + ': ' + tooltipItem.raw.toLocaleString();
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        </script>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <hr>
                </div>
            </div>

            <?php
        }
    } else {
        echo "<div class='col-md-12'><p>No categories found.</p></div>";
    }
    ?>
</div>

<?php include '../footer.php'; ?>
