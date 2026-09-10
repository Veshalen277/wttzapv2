<?php 
// functions.php

/**
 * Fetches work reports from the database.
 * @param mysqli $con Database connection object.
 * @return array Array of work reports.
 */
// function fetchWorkReports($con) {
//     $sql = "SELECT * FROM work_tbl";
//     $query = mysqli_query($con, $sql);
//     $reports = [];
//     while ($row = mysqli_fetch_assoc($query)) {
//         $reports[] = $row;
//     }
//     return $reports;
// }

/**
 * Fetches employee name by user_id.
 * @param mysqli $con Database connection object.
 * @param int $user_id User ID.
 * @return string Employee name.
 */
// function getEmployeeName($con, $user_id) {
//     $employee_query = mysqli_query($con, "SELECT * FROM users_tbl WHERE id = $user_id");
//     $employee = mysqli_fetch_assoc($employee_query);
//     return $employee ? $employee['fullname'] : 'Unknown';
// }

?>



<?php

/**
 * Adds a new category to the database.
 * @param mysqli $con Database connection object.
 * @param string $category_name Category name to insert.
 * @return bool True if insertion succeeds, false otherwise.
 */
// function addCategory($con, $category_name) {
//     $sql = "INSERT INTO stock_categories (category_name) VALUES (?)";
//     $stmt = $con->prepare($sql);
//     $stmt->bind_param("s", $category_name);

//     $result = $stmt->execute();

//     $stmt->close();

//     return $result;
// }

?>
 <?php

/**
 * Add a new employee to the database.
 * @param mysqli $con Database connection object.
 * @param array $data Array containing employee data.
 * @return bool True if insertion succeeds, false otherwise.
 */
function addEmployee($con, $data) {
    $fullname = mysqli_real_escape_string($con, $data['user_name']);
    $user_des = mysqli_real_escape_string($con, $data['user_des']);
    $user_res = mysqli_real_escape_string($con, $data['user_res']);
    $user_scale = mysqli_real_escape_string($con, $data['user_scale']);
    $user_id = mysqli_real_escape_string($con, $data['user_id']);
    $pass = mysqli_real_escape_string($con, sha1($data['user_pass']));
    $role = mysqli_real_escape_string($con, $data['user_role']);
    $date_started = mysqli_real_escape_string($con, $data['date_started']);
    $id_number = mysqli_real_escape_string($con, $data['id_number']);
    $address = mysqli_real_escape_string($con, $data['address']);
    $contact_number = mysqli_real_escape_string($con, $data['contact_number']);
    $email = mysqli_real_escape_string($con, $data['email']);
    $next_of_kin = mysqli_real_escape_string($con, $data['next_of_kin']);
    $next_of_kin_number = mysqli_real_escape_string($con, $data['next_of_kin_number']);
    $job_functions = mysqli_real_escape_string($con, $data['job_functions']);

    // Validate password length
    if (strlen($pass) < 4) {
        return "Password length must be at least 4 characters.";
    }

    // Check if user_id already exists
    $check_sql = "SELECT * FROM users_tbl WHERE user_id='$user_id'";
    $check_query = mysqli_query($con, $check_sql);
    $rows = mysqli_num_rows($check_query);

    if ($rows > 0) {
        return "User ID already exists.";
    }

    // Insert employee data
    $sql = "INSERT INTO users_tbl(fullname, user_des, user_res, user_scale, user_id, user_pass, user_role, date_started, id_number, email, address, contact_number, next_of_kin, next_of_kin_number, job_functions) 
            VALUES ('$fullname', '$user_des', '$user_res', '$user_scale', '$user_id', '$pass', '$role', '$date_started', '$id_number', '$email', '$address', '$contact_number', '$next_of_kin', '$next_of_kin_number', '$job_functions')";
    $query = mysqli_query($con, $sql);

    return $query;
}

?>



 <?php

//  Assigned work Function Operations are closed here , fucntion moved to the page 
// function fetchWorkRecords($start_from, $records_per_page, $date_filter, $task_filter, $con) {
//     $sql = "SELECT w.*, u.task_datetime AS user_task_datetime
//             FROM work_tbl w
//             LEFT JOIN users_tbl u ON w.employee_id = u.id
//             WHERE (w.work_date LIKE ? OR ? = '')
//               AND (w.task LIKE ? OR ? = '')
//             LIMIT ?, ?";
    
//     $stmt = mysqli_prepare($con, $sql);
//     $date_filter = '%' . mysqli_real_escape_string($con, $date_filter) . '%';
//     $task_filter = '%' . mysqli_real_escape_string($con, $task_filter) . '%';
    
//     mysqli_stmt_bind_param($stmt, "ssssii", $date_filter, $date_filter, $task_filter, $task_filter, $start_from, $records_per_page);
//     mysqli_stmt_execute($stmt);
    
//     $result = mysqli_stmt_get_result($stmt);
//     return $result;
// }

function countTotalRecords($con) {
    $total_sql = "SELECT COUNT(*) AS total FROM work_tbl";
    $total_result = mysqli_query($con, $total_sql);
    $total_records = mysqli_fetch_assoc($total_result)['total'];
    return $total_records;
}

function deleteRecord($delete_id, $con) {
    $delete_sql = "DELETE FROM work_tbl WHERE id = ?";
    $stmt = mysqli_prepare($con, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    $delete_result = mysqli_stmt_execute($stmt);
    return $delete_result;
}
?>



 <?php
/* edit_emp.php

// Function to fetch user details by ID
function get_user_by_id($con, $id) {
    $id = mysqli_real_escape_string($con, $id); // Sanitize input
    
    $sql = "SELECT * FROM users_tbl WHERE id = ?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    return $row; // Return user details
}

// Function to fetch distinct user scales
function get_distinct_user_scales($con) {
    $scale_sql = "SELECT DISTINCT user_scale FROM users_tbl";
    $scale_query = mysqli_query($con, $scale_sql);
    $scales = mysqli_fetch_all($scale_query, MYSQLI_ASSOC);
    
    return $scales; // Return distinct user scales
}

// Function to update user details
function update_user_details($con, $post_data) {
    // Sanitize input data
    $id = mysqli_real_escape_string($con, $post_data['id']);
    $fullname = mysqli_real_escape_string($con, $post_data['user_name']);
    $des = mysqli_real_escape_string($con, $post_data['user_des']);
    $res = mysqli_real_escape_string($con, $post_data['user_res']);
    $job_functions = mysqli_real_escape_string($con, $post_data['job_functions']);
    $scale = mysqli_real_escape_string($con, $post_data['user_scale']);
    $user_id = mysqli_real_escape_string($con, $post_data['user_id']);
    $user_role = mysqli_real_escape_string($con, $post_data['user_role']);
    $id_number = mysqli_real_escape_string($con, $post_data['id_number']);
    $email = mysqli_real_escape_string($con, $post_data['email']);
    $address = mysqli_real_escape_string($con, $post_data['address']);
    $contact_number = mysqli_real_escape_string($con, $post_data['contact_number']);
    $next_of_kin = mysqli_real_escape_string($con, $post_data['next_of_kin']);
    $next_of_kin_number = mysqli_real_escape_string($con, $post_data['next_of_kin_number']);

    // Check if password is provided and hash it
    if (!empty($post_data['user_pass'])) {
        $user_pass = mysqli_real_escape_string($con, sha1($post_data['user_pass']));
    } else {
        // If no new password provided, retain the existing hashed password from database
        $user_pass = ''; // Assuming $user_pass is fetched from database in the form
    }

    // Update query
    $update_sql = "UPDATE users_tbl SET 
                    fullname = ?, 
                    user_des = ?, 
                    user_res = ?, 
                    job_functions = ?,
                    user_scale = ?, 
                    user_id = ?, 
                    user_pass = ?, 
                    user_role = ?, 
                    id_number = ?, 
                    email = ?, 
                    address = ?, 
                    contact_number = ?, 
                    next_of_kin = ?, 
                    next_of_kin_number = ? 
                    WHERE id = ?";
    
    $stmt = mysqli_prepare($con, $update_sql);
    mysqli_stmt_bind_param($stmt, "ssssssssssssssi", 
        $fullname, $des, $res, $job_functions, $scale, $user_id, $user_pass,
        $user_role, $id_number, $email, $address, $contact_number,
        $next_of_kin, $next_of_kin_number, $id);

    $query = mysqli_stmt_execute($stmt);

    return $query; // Return true or false based on query execution
}*/


// Function to fetch the current password hash for a user
function get_current_password($con, $id) {
    $id = mysqli_real_escape_string($con, $id);
    
    $sql = "SELECT user_pass FROM users_tbl WHERE id = ?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    return $row['user_pass']; // Return the current password hash
}


//The edit emp page users this code below ,  unlike the predecessors below i have left out the roles and password on the edit page 

function update_user_details($con, $data) {
    $id = $data['id'];
    $name = mysqli_real_escape_string($con, $data['user_name']);
    $designation = mysqli_real_escape_string($con, $data['user_des']);
    $responsibilities = mysqli_real_escape_string($con, $data['user_res']);
    $job_functions = mysqli_real_escape_string($con, $data['job_functions']);
    $date_started = mysqli_real_escape_string($con, $data['date_started']);
    $id_number = mysqli_real_escape_string($con, $data['id_number']);
    $email = mysqli_real_escape_string($con, $data['email']);
    $user_scale = mysqli_real_escape_string($con, $data['user_scale']);
    $user_id = mysqli_real_escape_string($con, $data['user_id']);
    $address = mysqli_real_escape_string($con, $data['address']);
    $contact_number = mysqli_real_escape_string($con, $data['contact_number']);
    $next_of_kin = mysqli_real_escape_string($con, $data['next_of_kin']);
    $next_of_kin_number = mysqli_real_escape_string($con, $data['next_of_kin_number']);
    
    // Construct the query excluding password and role
    $query = "UPDATE users_tbl SET 
        fullname='$name',
        user_des='$designation',
        user_res='$responsibilities',
        job_functions='$job_functions',
        date_started='$date_started',
        id_number='$id_number',
        email='$email',
        user_scale='$user_scale',
        user_id='$user_id',
        address='$address',
        contact_number='$contact_number',
        next_of_kin='$next_of_kin',
        next_of_kin_number='$next_of_kin_number'
        WHERE id='$id'";
    
    return mysqli_query($con, $query);
}




 //Function to update user details wORKS BUT ALTERS PASSWORD SO UPDATED VERION IS BELOW 
/*function update_user_details($con, $post_data) {
    Sanitize input data
    $id = mysqli_real_escape_string($con, $post_data['id']);
    $fullname = mysqli_real_escape_string($con, $post_data['user_name']);
    $des = mysqli_real_escape_string($con, $post_data['user_des']);
    $res = mysqli_real_escape_string($con, $post_data['user_res']);
    $job_functions = mysqli_real_escape_string($con, $post_data['job_functions']);
    $scale = mysqli_real_escape_string($con, $post_data['user_scale']);
    $user_id = mysqli_real_escape_string($con, $post_data['user_id']);
    $user_role = mysqli_real_escape_string($con, $post_data['user_role']);
    $id_number = mysqli_real_escape_string($con, $post_data['id_number']);
    $email = mysqli_real_escape_string($con, $post_data['email']);
    $address = mysqli_real_escape_string($con, $post_data['address']);
    $contact_number = mysqli_real_escape_string($con, $post_data['contact_number']);
    $next_of_kin = mysqli_real_escape_string($con, $post_data['next_of_kin']);
    $next_of_kin_number = mysqli_real_escape_string($con, $post_data['next_of_kin_number']);
    
    Fetch the current password hash
    $current_password = get_current_password($con, $id);

    Check if a new password is provided
    if (!empty($post_data['user_pass'])) {
        Hash the new password
        $user_pass = mysqli_real_escape_string($con, sha1($post_data['user_pass']));
    } else {
        Retain the existing password if no new password is provided
        $user_pass = $current_password;
    }

    Update query
    $update_sql = "UPDATE users_tbl SET 
                    fullname = ?, 
                    user_des = ?, 
                    user_res = ?, 
                    job_functions = ?,
                    user_scale = ?, 
                    user_id = ?, 
                  user_pass = ?, 
                    user_role = ?, 
                    id_number = ?, 
                    email = ?, 
                    address = ?, 
                    contact_number = ?, 
                    next_of_kin = ?, 
                    next_of_kin_number = ? 
                    WHERE id = ?";
    
    $stmt = mysqli_prepare($con, $update_sql);
    mysqli_stmt_bind_param($stmt, "ssssssssssssssi", 
        $fullname, $des, $res, $job_functions, $scale, $user_id, $user_pass,
        $user_role, $id_number, $email, $address, $contact_number,
        $next_of_kin, $next_of_kin_number, $id);

    $query = mysqli_stmt_execute($stmt);

    return $query; // Return true or false based on query execution
}*/
/*
function update_user_details($con, $post_data) {
    Sanitize input data
    $id = mysqli_real_escape_string($con, $post_data['id']);
    $fullname = mysqli_real_escape_string($con, $post_data['user_name']);
    $des = mysqli_real_escape_string($con, $post_data['user_des']);
    $res = mysqli_real_escape_string($con, $post_data['user_res']);
    $job_functions = mysqli_real_escape_string($con, $post_data['job_functions']);
    $scale = mysqli_real_escape_string($con, $post_data['user_scale']);
    $user_id = mysqli_real_escape_string($con, $post_data['user_id']);
    $user_role = mysqli_real_escape_string($con, $post_data['user_role']);
    $id_number = mysqli_real_escape_string($con, $post_data['id_number']);
    $email = mysqli_real_escape_string($con, $post_data['email']);
    $address = mysqli_real_escape_string($con, $post_data['address']);
    $contact_number = mysqli_real_escape_string($con, $post_data['contact_number']);
    $next_of_kin = mysqli_real_escape_string($con, $post_data['next_of_kin']);
    $next_of_kin_number = mysqli_real_escape_string($con, $post_data['next_of_kin_number']);
    
    Fetch the current password hash
    $current_password = get_current_password($con, $id);

    Check if a new password is provided
    if (!empty($post_data['user_pass'])) {
        Hash the new password
        $user_pass = mysqli_real_escape_string($con, sha1($post_data['user_pass']));
    } else {
        Retain the existing password if no new password is provided
        $user_pass = $current_password;
    }

    Update query //       user_pass = ?, 
    $update_sql = "UPDATE users_tbl SET 
                    fullname = ?, 
                    user_des = ?, 
                    user_res = ?, 
                    job_functions = ?,
                    user_scale = ?, 
                    user_id = ?, 
           
                    user_role = ?, 
                    id_number = ?, 
                    email = ?, 
                    address = ?, 
                    contact_number = ?, 
                    next_of_kin = ?, 
                    next_of_kin_number = ? 
                    WHERE id = ?";
    
    $stmt = mysqli_prepare($con, $update_sql);
    mysqli_stmt_bind_param($stmt, "sssssssssssssi", 
        $fullname, $des, $res, $job_functions, $scale, $user_id, 
        $user_role, $id_number, $email, $address, $contact_number,
        $next_of_kin, $next_of_kin_number, $id);

    $query = mysqli_stmt_execute($stmt);
    $user_pass, s

    return $query; // Return true or false based on query execution
}
*/






/* also brokem 
function update_user_details($con, $data) {
    Start with the base query
    $query = "UPDATE users_tbl SET 
              user_name = ?, 
              user_des = ?, 
              user_res = ?, 
              job_functions = ?, 
              date_started = ?, 
              id_number = ?, 
              email = ?, 
              user_scale = ?, 
              user_id = ?, 
              address = ?, 
              contact_number = ?, 
              next_of_kin = ?, 
              next_of_kin_number = ?";

    Append user_pass to the query if it's set
    if (isset($data['user_pass'])) {
        $query .= ", user_pass = ?";
    }

    $query .= " WHERE id = ?";

    Prepare the statement
    if ($stmt = mysqli_prepare($con, $query)) {
        Bind parameters dynamically
        $params = [
            $data['fullname'],
            $data['user_des'],
            $data['user_res'],
            $data['job_functions'],
            $data['date_started'],
            $data['id_number'],
            $data['email'],
            $data['user_scale'],
            $data['user_id'],
            $data['address'],
            $data['contact_number'],
            $data['next_of_kin'],
            $data['next_of_kin_number']
        ];

        if (isset($data['user_pass'])) {
            $params[] = $data['user_pass'];
        }

        $params[] = $data['id'];

        Define the parameter types
        $types = str_repeat('s', count($params) - 1); // All parameters are strings except for ID
        if (isset($data['user_pass'])) {
            $types .= 's'; // Append 's' for user_pass if it's set
        }
        $types .= 'i'; // Append 'i' for ID

        Bind the parameters
        mysqli_stmt_bind_param($stmt, $types, ...$params);

        Execute the statement
        return mysqli_stmt_execute($stmt);
    } else {
        If the statement couldn't be prepared, return an error
        throw new Exception("Failed to prepare SQL statement: " . mysqli_error($con));
    }
}
*/
/* broken function update_user_details($con, $data) {
    Start with the base query
    $query = "UPDATE users_tbl SET 
              user_name = ?, 
              user_des = ?, 
              user_res = ?, 
              job_functions = ?, 
              user_scale = ?, 
              user_id = ?, 
              user_pass = ?, 
              user_role = ?, 
              id_number = ?, 
              email = ?, 
              address = ?, 
              contact_number = ?, 
              next_of_kin = ?, 
              next_of_kin_number = ? 
              WHERE id = ?";

    Prepare the statement
    if ($stmt = mysqli_prepare($con, $query)) {
        Prepare parameter array
        $params = [
            $data['fullname'],
            $data['user_des'],
            $data['user_res'],
            $data['job_functions'],
            $data['user_scale'],
            $data['user_id'],
            isset($data['user_pass']) ? sha1($data['user_pass']) : '', // Hash password if set, otherwise empty string
            $data['user_role'],
            $data['id_number'],
            $data['email'],
            $data['address'],
            $data['contact_number'],
            $data['next_of_kin'],
            $data['next_of_kin_number'],
            $data['id'] // For the WHERE clause
        ];

        // Define the parameter types
        $types = str_repeat('s', count($params) - 1) . 'i'; // All parameters are strings except for ID

        // Bind the parameters
        mysqli_stmt_bind_param($stmt, $types, ...$params);

        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else {
            mysqli_stmt_close($stmt);
            throw new Exception("Failed to execute SQL statement: " . mysqli_stmt_error($stmt));
        }
    } else {
        throw new Exception("Failed to prepare SQL statement: " . mysqli_error($con));
    }
}*/

// Define the function to get distinct user scales
function get_distinct_user_scales($con) {
    // Escape the connection string if needed
    $query = "SELECT DISTINCT user_scale FROM users_tbl";
    $result = mysqli_query($con, $query);
    $scales = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $scales[] = $row;
    }
    return $scales;
}

// Make sure other functions are defined here as well
function get_user_by_id($con, $id) {
    $id = mysqli_real_escape_string($con, $id);
    $query = "SELECT * FROM users_tbl WHERE id = '$id'";
    $result = mysqli_query($con, $query);
    return mysqli_fetch_assoc($result);
}






//Charts Admin
function getCount($table) {
    global $con;
    $query = "SELECT COUNT(*) AS count FROM $table";
    $result = mysqli_query($con, $query);
    if (!$result) {
        die("Database query failed: " . mysqli_error($con));
    }
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

// Fetch counts
$leave_count = getCount('leave_applications');
$report_count = getCount('reports');
$task_count = getCount('orders');
$user_count = getCount('users_tbl');
$work_count = getCount('work_tbl');
$suggestion = getCount('suggestions');

// Fetch work statistics
function getWorkStatistics() {
    global $con;
    $query = "SELECT COUNT(*) AS total_tasks, COUNT(DISTINCT employee_id) AS total_employees FROM work_tbl"; // Adjust if necessary
    $result = mysqli_query($con, $query);
    if (!$result) {
        die("Database query failed: " . mysqli_error($con));
    }
    return mysqli_fetch_assoc($result);
}

$work_stats = getWorkStatistics();
$total_tasks = $work_stats['total_tasks'];
$total_employees = $work_stats['total_employees'];
//End Charts Admin
















?>
