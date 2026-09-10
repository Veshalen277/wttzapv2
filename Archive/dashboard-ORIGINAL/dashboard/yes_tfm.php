<?php 


// Fetch user data from session
$user = $_SESSION['u_data'] ?? null; 

// Check if user data is available
if ($user === null) {
    header("Location: /dashboard/404.php");
    exit;
}

// Extract user role and scale from the session data
$user_role = $user[4] ?? null; // Assuming user role is stored in $user[4]
$user_scale = $user[2] ?? null; // Assuming user scale is stored in $user[2]

// Define allowed roles
$allowed_roles = ['5', '6', '7']; // Roles that should have access

// Check access based on user role and scale
if (in_array($user_role, $allowed_roles)) {
    // If user role is in the allowed roles, they are allowed to access the page
} else if ($user_role == '1' && $user_scale == 'TFM') {
    // If user role is 1 and scale is TFM, allow access
    // Proceed with the rest of the form processing
} else {
    // If none of the conditions are met, deny access
    header("Location: /dashboard/404.php");
    exit;
}


?>