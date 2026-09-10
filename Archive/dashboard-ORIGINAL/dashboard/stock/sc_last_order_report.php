<?php
include '../header.php';













// Define how many orders per page
$orders_per_page = 10;

// Determine the current page number
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start_from = ($page - 1) * $orders_per_page;

// Prepare and execute query to fetch the most recent orders with pagination
$sql_orders = "SELECT * FROM sc_stock_items ORDER BY last_order_date DESC LIMIT ?, ?";
$stmt_orders = $con->prepare($sql_orders);
$stmt_orders->bind_param("ii", $start_from, $orders_per_page);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();

// Count the total number of orders for pagination
$total_orders_query = "SELECT COUNT(*) FROM sc_stock_items";
$total_orders_result = $con->query($total_orders_query);
$total_orders_row = $total_orders_result->fetch_row();
$total_orders = $total_orders_row[0];

// Calculate total pages
$total_pages = ceil($total_orders / $orders_per_page);
?>






<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Confirm Stock Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Please confirm your entries:</p>
                <div id="modal-summary"></div> <!-- Summary will be dynamically populated -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="confirmSubmit">Confirm</button>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid">
    <h1>Last Orders Report</h1>

    <!-- Orders Table -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Item Name</th>
                <th>Quantity Ordered</th>
                <th>Quantity Received</th>
                <th>Order Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($order = $result_orders->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['id']); ?></td>
                    <td><?php echo htmlspecialchars($order['item_name']); ?></td>
                    <td><?php echo number_format($order['order_qty']); ?></td>
                    <td><?php echo number_format($order['received_qty']); ?></td>
                    <td><?php echo htmlspecialchars($order['last_order_date']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Pagination controls -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
<script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('stockForm'); // Assuming your form has id 'stockForm'
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const confirmSubmit = document.getElementById('confirmSubmit');
    
    form.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        // Populate the modal summary with form data
        const formData = new FormData(form);
        let summary = '<ul>';
        for (const [key, value] of formData.entries()) {
            if (key !== 'submit') { // Exclude submit button if included
                summary += `<li><strong>${key}:</strong> ${value}</li>`;
            }
        }
        summary += '</ul>';
        document.getElementById('modal-summary').innerHTML = summary;

        // Show the modal
        confirmModal.show();
    });

    confirmSubmit.addEventListener('click', function() {
        form.submit(); // Submit the form if confirmed
    });
});
</script>

</script>
<?php include '../footer.php'; ?>
