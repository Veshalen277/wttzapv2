<?php
// Include the header file
include '../header.php';

?>


        <h1>Sitemap</h1>
        <ul>
            <li><a href="/dashboard/admin/admin_profile.php">Admin Home</a></li>
            <li><a href="/dashboard/employee/emp_profile.php">Employee Home</a></li>
            <li><a href="/dashboard/users/checklist.php">Checklist</a></li>
            <li><a href="/dashboard/employee/my_profile.php">Edit Profile</a></li>

            <li>Cashup
                <ul>
                    <li><a href="/dashboard/reports/report_form.php">Submit Cashup</a></li>
                </ul>
            </li>

            <li>Orders
                <ul>
                    <li><a href="/dashboard/orders/orderForm.php">Order Form</a></li>
                    <li><a href="/dashboard/orders/orderBoard.php">Orders</a></li>
                    <li><a href="/dashboard/orders/stats.php">Order Stats</a></li>
                </ul>
            </li>

            <li>Stock Management
                <ul>
                    <li><a href="/dashboard/stock/sc_add_category.php">Add Category</a></li>
                    <li><a href="/dashboard/stock/sc_add_stock.php">Add Stock</a></li>
                    <li><a href="/dashboard/stock/sc_manage_categories.php">Inventory</a></li>
                    <li><a href="/dashboard/stock/sc_update_stock.php">Daily Stock Usage</a></li>
                    <li><a href="/dashboard/stock/sc_stock_report_pie.php">SC Dashboard</a></li>
                    <li><a href="/dashboard/stock/upload.php">CSV Upload</a></li>
                </ul>
            </li>

            <li>Management
                <ul>
                    <li><a href="/dashboard/users/users_list.php">User List</a></li>
                    <li><a href="/dashboard/users/leave-neg.php">Leave Applications</a></li>
                    <li><a href="/dashboard/suggestions/suggestion_board.php">Suggestions</a></li>
                    <li><a href="/dashboard/users/assigned_work.php">Work Reports</a></li>
                </ul>
            </li>

            <li>Forum
                <ul>
                    <li><a href="/dashboard/forums/create_topic.php">Create Forum Topic</a></li>
                    <li><a href="/dashboard/forums/view_topics.php">View Forum Topics</a></li>
                    <li><a href="/dashboard/forums/add_user_to_topic.php">Add User to Topic</a></li>
                    <li><a href="/dashboard/forums/threads.php">Threads</a></li>
                </ul>
            </li>
        </ul>


<?php
// Include the footer file
include '../footer.php';

?>
