


<style>
    li.nav-item{
        font-size:14px;
    }
</style>

<?php
// if (isset($_SESSION['u_data'])) {
//     $data = $_SESSION['u_data'];
//     $role = $data[4]; // Assuming role information is stored at index 4
//     $currentPage = basename($_SERVER['PHP_SELF']); // Get the current page filename

//     // Define menu items for different roles
//     function getMenuItems($role) {
//         $menuItems = [
//             0 => [
//                 ['title' => 'Home', 'link' => '/dashboard/emp_profile.php'],
//                 ['title' => 'Leave Application Form', 'link' => '/dashboard/leave_app.php'],
//                 ['title' => 'Submit Cashup', 'link' => '/dashboard/report_form.php'],
//                 [
//                     'title' => 'Submit TFM Cashup',
//                     'link' => '/dashboard/tfm_report_form.php',
//                     'attributes' => [
//                         'data-bs-toggle' => 'tooltip',
//                         'title' => 'TFM Users only!'
//                     ]
//                 ],
//                 ['title' => 'Edit Profile', 'link' => '/dashboard/my_profile.php'],
//                 ['title' => 'Order Form', 'link' => '/dashboard/orderForm.php'],
//                 ['title' => 'Logout', 'link' => '/dashboard/logout.php']
//             ],
//             1 => [
//                 ['title' => 'Print Reports', 'link' => '/dashboard/admin_profile.php'],
//                 ['title' => 'View Work Reports', 'link' => '/dashboard/assigned_work.php'],
//                 ['title' => 'User List', 'link' => '/dashboard/users_list.php'],
//                 ['title' => 'Edit Profile', 'link' => '/dashboard/my_profile.php'],
//                 ['title' => 'Leave Applications', 'link' => '/dashboard/leave-neg.php'],
//                 ['title' => 'Employee Profile', 'link' => '/dashboard/emp_profile.php'],
//                 ['title' => 'Reports', 'link' => '#', 'submenu' => [
//                     ['title' => 'General Cash Reports', 'link' => '/dashboard/view_reports.php'],
//                     ['title' => 'General TFM Cash Reports', 'link' => '/dashboard/view_tfm_reports.php'],
//                     ['title' => 'Monthly Reports', 'link' => '/dashboard/monthly_reports.php'],
//                     ['title' => 'Archived TFM Reports', 'link' => '/dashboard/tfm_archive_reports.php']
//                 ]],
//                 ['title' => 'Orders', 'link' => '#', 'submenu' => [
//                     ['title' => 'Order Form', 'link' => '/dashboard/orderForm.php'],
//                     ['title' => 'Orders', 'link' => '/dashboard/orderBoard.php']
//                 ]],
//                 ['title' => 'Logout', 'link' => '/dashboard/logout.php']
//             ],
//             2 => [
//                 ['title' => 'Dashboard', 'link' => '/dashboard/charts_admin.php'],
//                 ['title' => 'Orders', 'link' => '#', 'submenu' => [
//                     ['title' => 'Order Form', 'link' => '/dashboard/orderForm.php'],
//                     ['title' => 'Orders', 'link' => '/dashboard/orderBoard.php']
//                 ]],
//                 ['title' => 'Submit Cashup', 'link' => '/dashboard/report_form.php'],
//                 ['title' => 'Submit TFM Cashup', 'link' => '/dashboard/tfm_report_form.php'],
//                 ['title' => 'Employee Profile', 'link' => '/dashboard/emp_profile.php'],
//                 ['title' => 'Manage Users', 'link' => '#', 'submenu' => [
//                     ['title' => 'User List', 'link' => '/dashboard/users_list.php'],
//                     ['title' => 'Leave Applications', 'link' => '/dashboard/leave-neg.php'],
//                     ['title' => 'Suggestions', 'link' => '/dashboard/suggestion_board.php'],
//                     ['title' => 'Print Work Reports', 'link' => '/dashboard/admin_profile.php'],
//                     ['title' => 'View Work Reports', 'link' => '/dashboard/assigned_work.php']
//                 ]],
//                 ['title' => 'User Profile', 'link' => '#', 'submenu' => [
//                     ['title' => 'Msgs', 'link' => '/dashboard/message_board.php'],
//                     ['title' => 'Send', 'link' => '/dashboard/message_form.php'],
//                     ['title' => 'Private Messages', 'link' => '/dashboard/private_messages.php'],
//                     ['title' => 'View Private Messages', 'link' => '/dashboard/view_private_messages.php'],
//                     ['title' => 'Send Group Message', 'link' => '/dashboard/send_group_message.php'],
//                     ['title' => 'Create Group', 'link' => '/dashboard/create_group.php'],
//                     ['title' => 'Group Invite', 'link' => '/dashboard/group_invite.php'],
//                     ['title' => 'Manage Group', 'link' => '/dashboard/group_management.php'],
//                     ['title' => 'Manage Members', 'link' => '/dashboard/manage_group_members.php'],
//                      ['title' => 'View Groups', 'link' => '/dashboard/view_groups.php']
//                 ]],

//   ['title' => 'Work Groups', 'link' => '#', 'submenu' => [
//                   // ['title' => 'Create Group', 'link' => '/dashboard/create_group.php'],
//                    ['title' => 'Add Group', 'link' => '/dashboard/add_group.php'],
//                     ['title' => 'Add Member', 'link' => '/dashboard/add_member.php'],
//                      ['title' => 'Delete Group', 'link' => '/dashboard/delete_group.php'],

//                     ['title' => 'Group Invite', 'link' => '/dashboard/group_invite.php'],
//                     ['title' => 'Manage Group', 'link' => '/dashboard/group_management.php'],
//                     ['title' => 'Manage Members', 'link' => '/dashboard/manage_group_members.php'],
//                      ['title' => 'View Groups', 'link' => '/dashboard/view_groups.php']
//    ]],



//                 ['title' => 'Income/Expense Reports', 'link' => '#', 'submenu' => [
//                     ['title' => 'General Income Reports', 'link' => '/dashboard/view_income_reports.php'],
//                     ['title' => 'Archived Reports', 'link' => '/dashboard/archive_reports.php'],
//                     ['title' => 'Monthly Reports', 'link' => '/dashboard/monthly_reports.php'],
//                     ['title' => 'General TFM Cash Reports', 'link' => '/dashboard/view_tfm_reports.php'],
//                     ['title' => 'Archived TFM Reports', 'link' => '/dashboard/tfm_archive_reports.php']
//                 ]],
//                 ['title' => 'Smoking Coffee', 'link' => '#', 'submenu' => [
//                     ['title' => 'Add Category Item', 'link' => '/dashboard/sc_add_category.php'],
//                     ['title' => 'Add Stock Item', 'link' => '/dashboard/sc_add_stock.php'],
//                     ['title' => 'View Inventory', 'link' => '/dashboard/sc_stock_report.php'],
//                     ['title' => 'Day-End Report', 'link' => '/dashboard/smoking_coffee_daily_reports.php'],
//                     ['title' => 'View Day-End Reports', 'link' => '/dashboard/view_smoking_coffee_daily_reports.php'],
//                     ['title' => 'View Monthly Reports', 'link' => '/dashboard/sc_monthly_reports.php'],
//                     ['title' => 'Upload CSV file', 'link' => '/dashboard/upload.php'],
//                     ['title' => 'View SC pie Chart', 'link' => '/dashboard/sc_view_daily_reports.php']
//                 ]],
//                 ['title' => 'Laletsa', 'link' => '#', 'submenu' => [
//                     ['title' => 'Add Category Item', 'link' => '/dashboard/lal_add_category.php'],
//                     ['title' => 'Add Stock Item', 'link' => '/dashboard/lal_add_stock.php'],
//                     ['title' => 'Add Inventory', 'link' => '/dashboard/lal_stock_form.php'],
//                     ['title' => 'View Inventory', 'link' => '/dashboard/lal_stock_report.php'],
//                     ['title' => 'Stock Report Pie Chart', 'link' => '/dashboard/lal_stock_report_pie.php'],
//                     ['title' => 'Day-End Report', 'link' => '/dashboard/laletsa_report_form.php'],
//                     ['title' => 'View Day-End Reports', 'link' => '/dashboard/laletsa_daily_reports.php'],
//                     ['title' => 'View Monthly Reports', 'link' => '/dashboard/laletsa_monthly_reports.php']
//                 ]],
//                 ['title' => 'Factory Shop', 'link' => '#', 'submenu' => [
//                     ['title' => 'Add Category Item', 'link' => '/dashboard/fs_add_category.php'],
//                     ['title' => 'Add Stock Item', 'link' => '/dashboard/fs_add_stock.php'],
//                     ['title' => 'Add Inventory', 'link' => '/dashboard/fs_stock_form.php'],
//                     ['title' => 'View Inventory', 'link' => '/dashboard/fs_stock_report.php']
//                 ]],
//                 ['title' => 'Logout', 'link' => '/dashboard/logout.php']
//             ],
//             3 => [
//               //  ['title' => 'Print Reports', 'link' => '/dashboard/admin_profile.php'],
//                 ['title' => 'Edit Profile', 'link' => '/dashboard/my_profile.php'],
//                 ['title' => 'Employee Profile', 'link' => '/dashboard/emp_profile.php'],
//                 ['title' => 'Submit Cashup', 'link' => '/dashboard/report_form.php'],
//                 ['title' => 'Orders', 'link' => '#', 'submenu' => [
//                    ['title' => 'Order Form', 'link' => '/dashboard/orderForm.php']
//              ]],
//                 ['title' => 'Stock Management', 'link' => '#', 'submenu' => [
//                     ['title' => 'Add Category Item', 'link' => '/dashboard/sc_add_category.php'],
                    
//                     ['title' => 'Add Stock Item', 'link' => '/dashboard/sc_add_stock.php'],
                   
//                     ['title' => 'Inventory', 'link' => '/dashboard/sc_manage_categories.php'],
//                     // ['title' => 'Old Inventory', 'link' => '/dashboard/sc_stock_report.php'],
//                     ['title' => 'Daily Stock Usage', 'link' => '/dashboard/sc_update_stock.php']
//                 ]],
//                 ['title' => 'Logout', 'link' => '/dashboard/logout.php']
//             ]
//         ];
//         return $menuItems[$role] ?? [];
//     }

//     // Function to render menu items
//     function renderMenuItems($items, $currentPage) {
//         foreach ($items as $item) {
//             if (isset($item['submenu'])) {
//                 echo '<li class="nav-item dropdown">';
//                 echo '<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">' . htmlspecialchars($item['title']) . '</a>';
//                 echo '<ul class="dropdown-menu">';
//                 renderMenuItems($item['submenu'], $currentPage);
//                 echo '</ul>';
//                 echo '</li>';
//             } else {
//                 $activeClass = ($item['link'] === $currentPage) ? 'active' : '';
//                 $attributes = '';
//                 if (isset($item['attributes'])) {
//                     foreach ($item['attributes'] as $attr => $value) {
//                         $attributes .= "$attr=\"$value\" ";
//                     }
//                 }
//                 echo "<li class=\"nav-item\"><a class=\"nav-link $activeClass\" href=\"" . htmlspecialchars($item['link']) . "\" $attributes>" . htmlspecialchars($item['title']) . "</a></li>";
//             }
//         }
//     }

//     // Get menu items based on user role
//     $menuItems = getMenuItems($role);
// //d-inline-block align-text-top
//     // Render the menu
//     echo '    <nav class="navbar bg-light">';
//     echo '<div class="container-fluid">';
//     echo ' <a class="navbar-brand" href="#"><img src="https://wttzap.co.za/assets/img8.png" alt="Logo" width="auto" height="74" class=""></a>';
//     echo '<ul class="nav">';
//     renderMenuItems($menuItems, $currentPage);
//     echo '</ul>';
//     echo '  </div>';
//     echo '</nav>';


//}



?>
<?php
if (isset($_SESSION['u_data'])) {
    $data = $_SESSION['u_data'];
    $role = $data[4]; // Assuming role information is stored at index 4
    $currentPage = basename($_SERVER['PHP_SELF']); // Get the current page filename

    // Define menu items for different roles
    function getMenuItems($role) {
        $menuItems = [
            0 => [
                ['title' => 'Home', 'link' => '/dashboard/emp_profile.php'],
                ['title' => 'Leave Application Form', 'link' => '/dashboard/leave_app.php'],
                ['title' => 'Submit Cashup', 'link' => '/dashboard/report_form.php'],
                [
                    'title' => 'Submit TFM Cashup',
                    'link' => '/dashboard/tfm_report_form.php',
                    'attributes' => [
                        'data-bs-toggle' => 'tooltip',
                        'title' => 'TFM Users only!'
                    ]
                ],
                ['title' => 'Edit Profile', 'link' => '/dashboard/my_profile.php'],
                ['title' => 'Order Form', 'link' => '/dashboard/orderForm.php'],
                ['title' => 'Logout', 'link' => '/dashboard/logout.php']
            ],
            1 => [
                ['title' => 'Print Reports', 'link' => '/dashboard/admin_profile.php'],
                ['title' => 'View Work Reports', 'link' => '/dashboard/assigned_work.php'],
                ['title' => 'User List', 'link' => '/dashboard/users_list.php'],
                ['title' => 'Edit Profile', 'link' => '/dashboard/my_profile.php'],
                ['title' => 'Leave Applications', 'link' => '/dashboard/leave-neg.php'],
                ['title' => 'Employee Profile', 'link' => '/dashboard/emp_profile.php'],
                ['title' => 'Reports', 'link' => '#', 'submenu' => [
                    ['title' => 'General Cash Reports', 'link' => '/dashboard/view_reports.php'],
                    ['title' => 'General TFM Cash Reports', 'link' => '/dashboard/view_tfm_reports.php'],
                    ['title' => 'Monthly Reports', 'link' => '/dashboard/monthly_reports.php'],
                    ['title' => 'Archived TFM Reports', 'link' => '/dashboard/tfm_archive_reports.php']
                ]],
                ['title' => 'Orders', 'link' => '#', 'submenu' => [
                    ['title' => 'Order Form', 'link' => '/dashboard/orderForm.php'],
                    ['title' => 'Orders', 'link' => '/dashboard/orderBoard.php']
                ]],
                ['title' => 'Logout', 'link' => '/dashboard/logout.php']
            ],
            2 => [
                ['title' => 'Dashboard', 'link' => '/dashboard/charts_admin.php'],
                ['title' => 'Orders', 'link' => '#', 'submenu' => [
                    ['title' => 'Order Form', 'link' => '/dashboard/orderForm.php'],
                    ['title' => 'Orders', 'link' => '/dashboard/orderBoard.php']
                ]],
                ['title' => 'Submit Cashup', 'link' => '/dashboard/report_form.php'],
                ['title' => 'Submit TFM Cashup', 'link' => '/dashboard/tfm_report_form.php'],
                ['title' => 'Employee Profile', 'link' => '/dashboard/emp_profile.php'],
                ['title' => 'Manage Users', 'link' => '#', 'submenu' => [
                    ['title' => 'User List', 'link' => '/dashboard/users_list.php'],
                    ['title' => 'Leave Applications', 'link' => '/dashboard/leave-neg.php'],
                    ['title' => 'Suggestions', 'link' => '/dashboard/suggestion_board.php'],
                    ['title' => 'Print Work Reports', 'link' => '/dashboard/admin_profile.php'],
                    ['title' => 'View Work Reports', 'link' => '/dashboard/assigned_work.php']
                ]],
                ['title' => 'User Profile', 'link' => '#', 'submenu' => [
                    ['title' => 'Msgs', 'link' => '/dashboard/message_board.php'],
                    ['title' => 'Send', 'link' => '/dashboard/message_form.php'],
                    ['title' => 'Private Messages', 'link' => '/dashboard/private_messages.php'],
                    ['title' => 'View Private Messages', 'link' => '/dashboard/view_private_messages.php'],
                    ['title' => 'Send Group Message', 'link' => '/dashboard/send_group_message.php'],
                    ['title' => 'Create Group', 'link' => '/dashboard/create_group.php'],
                    ['title' => 'Group Invite', 'link' => '/dashboard/group_invite.php'],
                    ['title' => 'Manage Group', 'link' => '/dashboard/group_management.php'],
                    ['title' => 'Manage Members', 'link' => '/dashboard/manage_group_members.php'],
                    ['title' => 'View Groups', 'link' => '/dashboard/view_groups.php']
                ]],
                ['title' => 'Work Groups', 'link' => '#', 'submenu' => [
                    ['title' => 'Add Group', 'link' => '/dashboard/add_group.php'],
                    ['title' => 'Add Member', 'link' => '/dashboard/add_member.php'],
                    ['title' => 'Delete Group', 'link' => '/dashboard/delete_group.php'],
                    ['title' => 'Group Invite', 'link' => '/dashboard/group_invite.php'],
                    ['title' => 'Manage Group', 'link' => '/dashboard/group_management.php'],
                    ['title' => 'Manage Members', 'link' => '/dashboard/manage_group_members.php'],
                    ['title' => 'View Groups', 'link' => '/dashboard/view_groups.php']
                ]],
                ['title' => 'Income/Expense Reports', 'link' => '#', 'submenu' => [
                    ['title' => 'General Income Reports', 'link' => '/dashboard/view_income_reports.php'],
                    ['title' => 'Archived Reports', 'link' => '/dashboard/archive_reports.php'],
                    ['title' => 'Monthly Reports', 'link' => '/dashboard/monthly_reports.php'],
                    ['title' => 'General TFM Cash Reports', 'link' => '/dashboard/view_tfm_reports.php'],
                    ['title' => 'Archived TFM Reports', 'link' => '/dashboard/tfm_archive_reports.php']
                ]],
                ['title' => 'Smoking Coffee', 'link' => '#', 'submenu' => [
                    ['title' => 'Add Category Item', 'link' => '/dashboard/sc_add_category.php'],
                    ['title' => 'Add Stock Item', 'link' => '/dashboard/sc_add_stock.php'],
                    ['title' => 'View Inventory', 'link' => '/dashboard/sc_stock_report.php'],
                    ['title' => 'Day-End Report', 'link' => '/dashboard/smoking_coffee_daily_reports.php'],
                    ['title' => 'View Day-End Reports', 'link' => '/dashboard/view_smoking_coffee_daily_reports.php'],
                    ['title' => 'View Monthly Reports', 'link' => '/dashboard/sc_monthly_reports.php'],
                    ['title' => 'Upload CSV file', 'link' => '/dashboard/upload.php'],
                    ['title' => 'View SC pie Chart', 'link' => '/dashboard/sc_view_daily_reports.php']
                ]],
                ['title' => 'Laletsa', 'link' => '#', 'submenu' => [
                    ['title' => 'Add Category Item', 'link' => '/dashboard/lal_add_category.php'],
                    ['title' => 'Add Stock Item', 'link' => '/dashboard/lal_add_stock.php'],
                    ['title' => 'Add Inventory', 'link' => '/dashboard/lal_stock_form.php'],
                    ['title' => 'View Inventory', 'link' => '/dashboard/lal_stock_report.php'],
                    ['title' => 'Stock Report Pie Chart', 'link' => '/dashboard/lal_stock_report_pie.php'],
                    ['title' => 'Day-End Report', 'link' => '/dashboard/laletsa_report_form.php'],
                    ['title' => 'View Day-End Reports', 'link' => '/dashboard/laletsa_daily_reports.php'],
                    ['title' => 'View Monthly Reports', 'link' => '/dashboard/laletsa_monthly_reports.php']
                ]],
                ['title' => 'Factory Shop', 'link' => '#', 'submenu' => [
                    ['title' => 'Add Category Item', 'link' => '/dashboard/fs_add_category.php'],
                    ['title' => 'Add Stock Item', 'link' => '/dashboard/fs_add_stock.php'],
                    ['title' => 'Add Inventory', 'link' => '/dashboard/fs_stock_form.php'],
                    ['title' => 'View Inventory', 'link' => '/dashboard/fs_stock_report.php']
                ]],
                ['title' => 'Logout', 'link' => '/dashboard/logout.php']
            ],
            3 => [
                ['title' => 'Edit Profile', 'link' => '/dashboard/my_profile.php'],
                ['title' => 'Employee Profile', 'link' => '/dashboard/emp_profile.php'],
                ['title' => 'Submit Cashup', 'link' => '/dashboard/report_form.php'],
                ['title' => 'Orders', 'link' => '#', 'submenu' => [
                    ['title' => 'Order Form', 'link' => '/dashboard/orderForm.php']
                ]],
                ['title' => 'Stock Management', 'link' => '#', 'submenu' => [
                    ['title' => 'Add Category Item', 'link' => '/dashboard/sc_add_category.php'],
                    ['title' => 'Add Stock Item', 'link' => '/dashboard/sc_add_stock.php'],
                    ['title' => 'Inventory', 'link' => '/dashboard/sc_manage_categories.php'],
                    ['title' => 'Daily Stock Usage', 'link' => '/dashboard/sc_update_stock.php']
                ]],
                ['title' => 'Logout', 'link' => '/dashboard/logout.php']
            ]
        ];
        return $menuItems[$role] ?? [];
    }
?>


<style>
    /* Make sure dropdowns work properly on smaller screens */
.navbar-nav .dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    display: none;
}

.navbar-nav .nav-item.dropdown:hover .dropdown-menu {
    display: block;
}

.navbar-toggler {
    border: none;
}

.nav-link.active {
    font-weight: bold;
}

</style>

<?php
    // Function to render menu items
    function renderMenuItems($items, $currentPage) {
        foreach ($items as $item) {
            if (isset($item['submenu'])) {
                echo '<li class="nav-item dropdown">';
                echo '<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">' . htmlspecialchars($item['title']) . '</a>';
                echo '<ul class="dropdown-menu">';
                renderMenuItems($item['submenu'], $currentPage);
                echo '</ul>';
                echo '</li>';
            } else {
                $activeClass = ($item['link'] === $currentPage) ? 'active' : '';
                $attributes = '';
                if (isset($item['attributes'])) {
                    foreach ($item['attributes'] as $attr => $value) {
                        $attributes .= "$attr=\"$value\" ";
                    }
                }
                echo "<li class=\"nav-item\"><a class=\"nav-link $activeClass\" href=\"" . htmlspecialchars($item['link']) . "\" $attributes>" . htmlspecialchars($item['title']) . "</a></li>";
            }
        }
    }

    // Get menu items based on user role
    $menuItems = getMenuItems($role);

    // Render the menu
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Responsive Menu</title>';
    echo '<link href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">';
    echo '<style>';
    echo '/* Additional custom styles */';
    echo '.navbar-nav .dropdown-menu {';
    echo '    position: absolute;';
    echo '    top: 100%;';
    echo '    left: 0;';
    echo '    z-index: 1000;';
    echo '    display: none;';
    echo '}';
    echo '.navbar-nav .nav-item.dropdown:hover .dropdown-menu {';
    echo '    display: block;';
    echo '}';
    echo '.navbar-toggler {';
    echo '    border: none;';
    echo '}';
    echo '.nav-link.active {';
    echo '    font-weight: bold;';
    echo '}';
    echo '</style>';
    echo '</head>';
    echo '<body>';

    echo '<nav class="navbar navbar-expand-lg navbar-light bg-light">';
    echo '<div class="container-fluid">';
    echo '<a class="navbar-brand" href="#"><img src="https://wttzap.co.za/assets/img8.png" alt="Logo" width="auto" height="74" class=""></a>';
    echo '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">';
    echo '<span class="navbar-toggler-icon"></span>';
    echo '</button>';
    echo '<div class="collapse navbar-collapse" id="navbarNav">';
    echo '<ul class="navbar-nav">';
    renderMenuItems($menuItems, $currentPage);
    echo '</ul>';
    echo '</div>';
    echo '</div>';
    echo '</nav>';

    echo '</body>';
    echo '<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>';
    echo '</html>';
}
?>
