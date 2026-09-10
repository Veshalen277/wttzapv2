<?php
include '../header.php';
?>


<!-- Include Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container py-5">
   
    <div class="row">
        <?php
        // Fetch Categories
        $sql_categories = "SELECT * FROM stock_categories";
        $result_categories = mysqli_query($con, $sql_categories);

        if (mysqli_num_rows($result_categories) > 0) {
            $index = 0; // Initialize index for tracking columns

            while ($category = mysqli_fetch_assoc($result_categories)) {
                $category_name = $category['category_name'];
                $category_id = $category['category_id'];

                // Fetch Items for each Category
                $sql_items = "SELECT item_name, qty_on_hand, order_qty, received_qty FROM stock_items WHERE category_id = $category_id";
                $result_items = mysqli_query($con, $sql_items);

                if (mysqli_num_rows($result_items) > 0) {
                    // Prepare data for Chart.js
                    $item_names = [];
                    $qty_on_hand = [];
                    $order_qty = [];
                    $received_qty = [];

                    while ($item = mysqli_fetch_assoc($result_items)) {
                        $item_names[] = $item['item_name'];
                        $qty_on_hand[] = $item['qty_on_hand'];
                        $order_qty[] = $item['order_qty'];
                        $received_qty[] = $item['received_qty'];
                    }

                    // Convert PHP arrays to JSON
                    $item_names_json = json_encode($item_names);
                    $qty_on_hand_json = json_encode($qty_on_hand);
                    $order_qty_json = json_encode($order_qty);
                    $received_qty_json = json_encode($received_qty);
                    ?>

                    <!-- Column for Chart -->
                    <div class="col-md-4 col-sm-12">
                        <div class="container mt-2">
                            <h2><?php echo $category_name; ?></h2>
                            <canvas id="chart-<?php echo $category_id; ?>" style="max-width: 300px; margin: 0 auto;"></canvas>
                            <script>
                                var ctx = document.getElementById('chart-<?php echo $category_id; ?>').getContext('2d');
                                var chart = new Chart(ctx, {
                                    type: 'pie', // Change to 'pie' for pie chart
                                    data: {
                                        labels: <?php echo $item_names_json; ?>,
                                        datasets: [{
                                            label: 'Stock Overview',
                                            backgroundColor: [
                                                'rgba(75, 192, 192, 0.2)',
                                                'rgba(54, 162, 235, 0.2)',
                                                'rgba(255, 206, 86, 0.2)'
                                            ],
                                            borderColor: [
                                                'rgba(75, 192, 192, 1)',
                                                'rgba(54, 162, 235, 1)',
                                                'rgba(255, 206, 86, 1)'
                                            ],
                                            borderWidth: 1,
                                            data: [
                                                <?php echo implode(',', $qty_on_hand); ?>,
                                                <?php echo implode(',', $order_qty); ?>,
                                                <?php echo implode(',', $received_qty); ?>
                                            ]
                                        }]
                                    },
                                    options: {
                                        responsive: true,
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
                        </div>
                    </div>
                    <?php
                    $index++;

                    // Start a new row after every third column (for three charts per row)
                    if ($index % 3 == 0) {
                        echo '</div><div class="row py-5">';
                    }
                } else {
                    echo "<div class='col-md-4 col-sm-12'><p>No items found in the category: " . $category_name . ".</p></div>";
                }
            }
        } else {
            echo "<div class='col-md-12'><p>No categories found.</p></div>";
        }
        ?>
    </div>
</div>

<?php include '../footer.php'; ?>
