<?php
include '../header.php'; 

// Define how many categories per page
$categories_per_page = 10;

// Determine the current page number
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start_from = ($page - 1) * $categories_per_page;

// Get selected filter category
$filter_category = isset($_GET['filter_category']) ? intval($_GET['filter_category']) : 0;

// Prepare and execute query to fetch categories with pagination and filter
$sql_categories = "SELECT * FROM sc_stock_categories" . ($filter_category > 0 ? " WHERE category_id = ?" : "") . " LIMIT ?, ?";
$stmt_categories = $con->prepare($sql_categories);
if ($filter_category > 0) {
    $stmt_categories->bind_param("iii", $filter_category, $start_from, $categories_per_page);
} else {
    $stmt_categories->bind_param("ii", $start_from, $categories_per_page);
}
$stmt_categories->execute();
$result_categories = $stmt_categories->get_result();

// Prepare and execute a query to fetch all items for the categories in the current page
$category_ids = [];
while ($category = $result_categories->fetch_assoc()) {
    $category_ids[] = intval($category['category_id']);
}

$category_ids_placeholder = implode(',', array_fill(0, count($category_ids), '?'));
$sql_items = "SELECT item_name, qty_on_hand, cost_price, retail_price FROM sc_stock_items WHERE category_id IN ($category_ids_placeholder)";
$stmt_items = $con->prepare($sql_items);
$stmt_items->bind_param(str_repeat('i', count($category_ids)), ...$category_ids);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

// Prepare data for charts
$chartData = [
    'labels' => [],
    'quantities' => [],
    'costs' => [],
    'retail_prices' => []
];

$pieChartData = [
    'labels' => [],
    'quantities' => []
];

$total_qty = 0;
$total_cost = 0;
$total_retail = 0;

while ($item = $result_items->fetch_assoc()) {
    $item_name = htmlspecialchars($item['item_name']);
    $qty_on_hand = intval($item['qty_on_hand']);
    $cost_price = floatval($item['cost_price']);
    $retail_price = floatval($item['retail_price']);
    
    // Populate chart data
    $chartData['labels'][] = $item_name;
    $chartData['quantities'][] = $qty_on_hand;
    $chartData['costs'][] = $cost_price * $qty_on_hand;
    $chartData['retail_prices'][] = $retail_price * $qty_on_hand;
    $pieChartData['labels'][] = $item_name;
    $pieChartData['quantities'][] = $qty_on_hand;
    
    // Accumulate totals
    $total_qty += $qty_on_hand;
    $total_cost += $cost_price * $qty_on_hand;
    $total_retail += $retail_price * $qty_on_hand;
}

// Count the total number of categories for pagination
$total_categories_query = "SELECT COUNT(*) FROM sc_stock_categories" . ($filter_category > 0 ? " WHERE category_id = ?" : "");
$stmt_total_categories = $con->prepare($total_categories_query);
if ($filter_category > 0) {
    $stmt_total_categories->bind_param("i", $filter_category);
}
$stmt_total_categories->execute();
$total_categories_result = $stmt_total_categories->get_result();
$total_categories_row = $total_categories_result->fetch_row();
$total_categories = $total_categories_row[0];

// Calculate total pages
$total_pages = ceil($total_categories / $categories_per_page);

// Fetch all categories for filter options
$sql_all_categories = "SELECT category_id, category_name FROM sc_stock_categories";
$result_all_categories = $con->query($sql_all_categories);
?>

<div class="container-fluid">
    <!-- Filter Form -->
    <form class="mb-3" method="get" action="">
        <div class="row align-items-center">
            <div class="col-md-4">
                <select name="filter_category" class="form-select" onchange="this.form.submit()">
                    <option value="0" <?php if ($filter_category == 0) echo 'selected'; ?>>All Categories</option>
                    <?php while ($cat = $result_all_categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php if ($filter_category == $cat['category_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
<div class="col-md-8 text-end">
    <a href="sc_add_category.php" class="btn btn-primary">Add Category</a>
    <a href="https://workplace.bethelinternational.co.za/dashboard/orders/stats.php" class="btn btn-primary">Order Statistics</a>
    <!-- Placeholder for future features -->

</div>
        </div>
    </form>

    <!-- Charts -->
    <div class="row">
        <div class="col-md-6">
            <h3>Stock Distribution by Item</h3>
            <canvas id="stockDistributionChart"></canvas>
            <p>Total Quantity: <?php echo number_format($total_qty); ?></p>
            <p>Total Cost: <?php echo number_format($total_cost, 2); ?></p>
            <p>Total Retail Value: <?php echo number_format($total_retail, 2); ?></p>
        </div>
        <div class="col-md-6">
            <h3>Stock Quantities</h3>
            <canvas id="stockQuantitiesChart"></canvas>
        </div>
    </div>

    <!-- Pagination controls -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&filter_category=<?php echo $filter_category; ?>">Previous</a></li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&filter_category=<?php echo $filter_category; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&filter_category=<?php echo $filter_category; ?>">Next</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Pie Chart: Stock Distribution by Item
    const ctxDistribution = document.getElementById('stockDistributionChart').getContext('2d');
    const stockDistributionChart = new Chart(ctxDistribution, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($pieChartData['labels']); ?>,
            datasets: [{
                label: 'Stock Distribution',
                data: <?php echo json_encode($pieChartData['quantities']); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
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
                            const dataLabel = tooltipItem.label || '';
                            const value = tooltipItem.raw || 0;
                            return `${dataLabel}: ${value} units`;
                        }
                    }
                }
            }
        }
    });

    // Bar Chart: Stock Quantities
    const ctxQuantities = document.getElementById('stockQuantitiesChart').getContext('2d');
    const stockQuantitiesChart = new Chart(ctxQuantities, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chartData['labels']); ?>,
            datasets: [{
                label: 'Quantity on Hand',
                data: <?php echo json_encode($chartData['quantities']); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    stacked: true,
                    beginAtZero: true
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return `Item: ${tooltipItem.label}, Qty: ${tooltipItem.raw}`;
                        }
                    }
                }
            }
        }
    });
</script>
<?php include '../footer.php';?>