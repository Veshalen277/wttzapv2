<?php

function getMenuItems($role) {

    // Standard Work/Profile Dropdown used by multiple roles
    $profileDropdown = [
        'title' => 'Work',
        'link'  => '#',
        'submenu' => [
            ['title' => 'My Work Report',     'link' => '/dashboard/employee/emp_profile.php'],
            ['title' => 'My Profile',         'link' => '/dashboard/employee/my_profile.php'],
            ['title' => 'Manager Profile',    'link' => '/dashboard/admin/admin_profile.php'],
        ]
    ];

    // Statistics dropdown for all roles
    $statisticsDropdown = [
        'title' => 'Statistics',
        'link'  => '#',
        'submenu' => [
            ['title' => 'All Stats', 'link' => '/dashboard/statistics/all-stats.php'],
            ['title' => 'Rankings',  'link' => '/dashboard/statistics/rankings.php'],
        ]
    ];

    $menuItems = [

        // ROLE 0
        0 => [
            $profileDropdown,
            $statisticsDropdown,
            ['title' => 'Orders', 'link' => '#', 'submenu' => [
                ['title' => 'New Order',   'link' => '/dashboard/orders/orderForm.php'],
                ['title' => 'Order Board', 'link' => '/dashboard/orders/orderBoard.php'],
            ]],
        ],

        // ROLE 1
        1 => [
            $profileDropdown,
            $statisticsDropdown,
            ['title' => 'Cashup', 'link' => '/dashboard/reports/report_form.php'],
            ['title' => 'Leave',  'link' => '/dashboard/employee/leave_app.php'],
            ['title' => 'Orders', 'link' => '#', 'submenu' => [
                ['title' => 'New Order', 'link' => '/dashboard/orders/orderForm.php'],
            ]],
        ],

        // ROLE 2
        2 => [
            $profileDropdown,
            $statisticsDropdown,
            ['title' => 'Mgr Cashup', 'link' => '/dashboard/reports/special_daily_report.php'],
            ['title' => 'Cashup',     'link' => '/dashboard/reports/report_form.php'],

            ['title' => 'Manage', 'link' => '#', 'submenu' => [
                ['title' => 'Users',       'link' => '/dashboard/users/users_list.php'],
                ['title' => 'Leave Apps',  'link' => '/dashboard/users/leave-neg.php'],
                ['title' => 'Suggestions', 'link' => '/dashboard/suggestions/suggestion_board.php'],
                ['title' => 'Work Reports','link' => '/dashboard/users/assigned_work.php'],
            ]],

            ['title' => 'Orders', 'link' => '#', 'submenu' => [
                ['title' => 'New Order',   'link' => '/dashboard/orders/orderForm.php'],
                ['title' => 'Order Board', 'link' => '/dashboard/orders/orderBoard.php'],
                ['title' => 'Order Stats', 'link' => '/dashboard/orders/stats.php'],
            ]],

            ['title' => 'Reports', 'link' => '#', 'submenu' => [
                ['title' => 'Income', 'link' => '/dashboard/reports/view_income_reports.php'],
            ]],

            ['title' => 'Stock', 'link' => '#', 'submenu' => [
                ['title' => 'Add Category', 'link' => '/dashboard/stock/sc_add_category.php'],
                ['title' => 'Add Stock',    'link' => '/dashboard/stock/sc_add_stock.php'],
                ['title' => 'Inventory',    'link' => '/dashboard/stock/sc_manage_categories.php'],
                ['title' => 'Daily Usage',  'link' => '/dashboard/stock/sc_update_stock.php'],
                ['title' => 'Dashboard',    'link' => '/dashboard/stock/sc_stock_report_pie.php'],
                ['title' => 'CSV Upload',   'link' => '/dashboard/stock/upload.php'],
            ]],

            ['title' => 'Forum', 'link' => '#', 'submenu' => [
                ['title' => 'New Topic', 'link' => '/dashboard/forums/create_topic.php'],
                ['title' => 'Topics',    'link' => '/dashboard/forums/view_topics.php'],
                ['title' => 'Add User',  'link' => '/dashboard/forums/add_user_to_topic.php'],
                ['title' => 'Threads',   'link' => '/dashboard/forums/threads.php'],
            ]],
        ],

        // ROLE 3
        3 => [
            $profileDropdown,
            $statisticsDropdown,
            ['title' => 'Cashup', 'link' => '/dashboard/reports/report_form.php'],
            ['title' => 'Orders', 'link' => '#', 'submenu' => [
                ['title' => 'New Order', 'link' => '/dashboard/orders/orderForm.php'],
            ]],
            ['title' => 'Stock', 'link' => '#', 'submenu' => [
                ['title' => 'Add Category', 'link' => '/dashboard/stock/sc_add_category.php'],
                ['title' => 'Add Stock',    'link' => '/dashboard/stock/sc_add_stock.php'],
                ['title' => 'Inventory',    'link' => '/dashboard/stock/sc_manage_categories.php'],
                ['title' => 'Daily Usage',  'link' => '/dashboard/stock/sc_update_stock.php'],
            ]],
        ],

        // ROLE 4
        4 => [
            $profileDropdown,
            $statisticsDropdown,
            ['title' => 'Cashup', 'link' => '/dashboard/reports/report_form.php'],
            ['title' => 'Orders', 'link' => '#', 'submenu' => [
                ['title' => 'New Order', 'link' => '/dashboard/orders/orderForm.php'],
            ]],
            ['title' => 'Stock', 'link' => '#', 'submenu' => [
                ['title' => 'Add Category', 'link' => '/dashboard/stock/sc_add_category.php'],
                ['title' => 'Add Stock',    'link' => '/dashboard/stock/sc_add_stock.php'],
                ['title' => 'Inventory',    'link' => '/dashboard/stock/sc_manage_categories.php'],
                ['title' => 'Daily Usage',  'link' => '/dashboard/stock/sc_update_stock.php'],
                ['title' => 'Dashboard',    'link' => '/dashboard/stock/sc_stock_report_pie.php'],
            ]],
        ],

        // ROLE 5
        5 => [
            $profileDropdown,
            $statisticsDropdown,
            ['title' => 'Cashup', 'link' => '/dashboard/reports/report_form.php'],

            ['title' => 'Manage', 'link' => '#', 'submenu' => [
                ['title' => 'Users',       'link' => '/dashboard/users/users_list.php'],
                ['title' => 'Leave Apps',  'link' => '/dashboard/users/leave-neg.php'],
                ['title' => 'Suggestions', 'link' => '/dashboard/suggestions/suggestion_board.php'],
                ['title' => 'Work Reports','link' => '/dashboard/users/assigned_work.php'],
            ]],

            ['title' => 'Orders', 'link' => '#', 'submenu' => [
                ['title' => 'New Order',   'link' => '/dashboard/orders/orderForm.php'],
                ['title' => 'Order Board', 'link' => '/dashboard/orders/orderBoard.php'],
                ['title' => 'Order Stats', 'link' => '/dashboard/orders/stats.php'],
            ]],

            ['title' => 'Messages', 'link' => '#', 'submenu' => [
                ['title' => 'Feed',    'link' => '/dashboard/messages/message_board.php'],
                ['title' => 'New',     'link' => '/dashboard/messages/message_form.php'],
                ['title' => 'Private', 'link' => '/dashboard/messages/404.php'],
                ['title' => 'Inbox',   'link' => '/dashboard/messages/view_private_messages.php'],
            ]],

            ['title' => 'Stock', 'link' => '#', 'submenu' => [
                ['title' => 'Add Category', 'link' => '/dashboard/stock/sc_add_category.php'],
                ['title' => 'Add Stock',    'link' => '/dashboard/stock/sc_add_stock.php'],
                ['title' => 'Inventory',    'link' => '/dashboard/stock/sc_manage_categories.php'],
                ['title' => 'Daily Usage',  'link' => '/dashboard/stock/sc_update_stock.php'],
                ['title' => 'Dashboard',    'link' => '/dashboard/stock/sc_stock_report_pie.php'],
            ]],

            ['title' => 'Reports', 'link' => '#', 'submenu' => [
                ['title' => 'Income', 'link' => '/dashboard/reports/view_income_reports.php'],
            ]],
        ],

        // ROLE 6
        6 => [
            ['title' => 'Dash', 'link' => '/dashboard/charts_admin.php'],
            $profileDropdown,
            $statisticsDropdown,
            ['title' => 'Cashup', 'link' => '/dashboard/reports/report_form.php'],

            ['title' => 'Manage', 'link' => '#', 'submenu' => [
                ['title' => 'Users',       'link' => '/dashboard/users/users_list.php'],
                ['title' => 'Leave Apps',  'link' => '/dashboard/users/leave-neg.php'],
                ['title' => 'Suggestions', 'link' => '/dashboard/suggestions/suggestion_board.php'],
                ['title' => 'Work Reports','link' => '/dashboard/users/assigned_work.php'],
            ]],

            ['title' => 'Orders', 'link' => '#', 'submenu' => [
                ['title' => 'New Order',   'link' => '/dashboard/orders/orderForm.php'],
                ['title' => 'Order Board', 'link' => '/dashboard/orders/orderBoard.php'],
                ['title' => 'Order Stats', 'link' => '/dashboard/orders/stats.php'],
            ]],

            ['title' => 'Messages', 'link' => '#', 'submenu' => [
                ['title' => 'Feed',         'link' => '/dashboard/messages/message_board.php'],
                ['title' => 'New',          'link' => '/dashboard/messages/message_form.php'],
                ['title' => 'Private',      'link' => '/dashboard/messages/private_messages.php'],
                ['title' => 'Inbox',        'link' => '/dashboard/messages/view_private_messages.php'],
                ['title' => 'Group Msg',    'link' => '/dashboard/messages/send_group_message.php'],
                ['title' => 'Create Group', 'link' => '/dashboard/create_group.php'],
                ['title' => 'Invites',      'link' => '/dashboard/group_invite.php'],
                ['title' => 'Manage Group', 'link' => '/dashboard/group_management.php'],
                ['title' => 'Members',      'link' => '/dashboard/manage_group_members.php'],
                ['title' => 'My Groups',    'link' => '/dashboard/view_groups.php'],
            ]],

            ['title' => 'Groups', 'link' => '#', 'submenu' => [
                ['title' => 'Add Group',   'link' => '/dashboard/add_group.php'],
                ['title' => 'Add Member',  'link' => '/dashboard/add_member.php'],
            ]],

            ['title' => 'Reports', 'link' => '#', 'submenu' => [
                ['title' => 'Income', 'link' => '/dashboard/reports/view_income_reports.php'],
            ]],
        ],

        // ROLE 7: Full Access
        7 => [
            $profileDropdown,
            $statisticsDropdown,
            ['title' => 'Cashup',     'link' => '/dashboard/reports/report_form.php'],
            ['title' => 'Mgr Cashup', 'link' => '/dashboard/reports/special_daily_report.php'],

            ['title' => 'Manage', 'link' => '#', 'submenu' => [
                ['title' => 'Users',       'link' => '/dashboard/users/users_list.php'],
                ['title' => 'Leave Apps',  'link' => '/dashboard/users/leave-neg.php'],
                ['title' => 'Suggestions', 'link' => '/dashboard/suggestions/suggestion_board.php'],
                ['title' => 'Work Reports','link' => '/dashboard/users/assigned_work.php'],
            ]],

            ['title' => 'Orders', 'link' => '#', 'submenu' => [
                ['title' => 'New Order',   'link' => '/dashboard/orders/orderForm.php'],
                ['title' => 'Order Board', 'link' => '/dashboard/orders/orderBoard.php'],
                ['title' => 'Order Stats', 'link' => '/dashboard/orders/stats.php'],
            ]],

            ['title' => 'Reports', 'link' => '#', 'submenu' => [
                ['title' => 'Income', 'link' => '/dashboard/reports/view_income_reports.php'],
            ]],

            ['title' => 'Stock', 'link' => '#', 'submenu' => [
                ['title' => 'Add Category', 'link' => '/dashboard/stock/sc_add_category.php'],
                ['title' => 'Add Stock',    'link' => '/dashboard/stock/sc_add_stock.php'],
                ['title' => 'Inventory',    'link' => '/dashboard/stock/sc_manage_categories.php'],
                ['title' => 'Daily Usage',  'link' => '/dashboard/stock/sc_update_stock.php'],
                ['title' => 'Dashboard',    'link' => '/dashboard/stock/sc_stock_report_pie.php'],
                ['title' => 'CSV Upload',   'link' => '/dashboard/stock/upload.php'],
            ]],

            ['title' => 'Forum', 'link' => '#', 'submenu' => [
                ['title' => 'New Topic', 'link' => '/dashboard/forums/create_topic.php'],
                ['title' => 'Topics',    'link' => '/dashboard/forums/view_topics.php'],
                ['title' => 'Add User',  'link' => '/dashboard/forums/add_user_to_topic.php'],
                ['title' => 'Threads',   'link' => '/dashboard/forums/threads.php'],
            ]],

            ['title' => 'Messages', 'link' => '#', 'submenu' => [
                ['title' => 'Feed',         'link' => '/dashboard/messages/message_board.php'],
                ['title' => 'New',          'link' => '/dashboard/messages/message_form.php'],
                ['title' => 'Private',      'link' => '/dashboard/messages/view_private_messages.php'],
                // ['title' => 'Group Msg',    'link' => '/dashboard/messages/send_group_message.php'],
                // ['title' => 'Create Group', 'link' => '/dashboard/create_group.php'],
                // ['title' => 'Manage Group', 'link' => '/dashboard/group_management.php'],
            ]],

            // ['title' => 'Groups', 'link' => '#', 'submenu' => [
            //     ['title' => 'Add Group',  'link' => '/dashboard/add_group.php'],
            //     ['title' => 'Add Member', 'link' => '/dashboard/add_member.php'],
            // ]],
        ],
    ];

    return isset($menuItems[$role]) ? $menuItems[$role] : [];
}
