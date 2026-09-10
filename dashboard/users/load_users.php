<?php
include '../header.php';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * 50;

// Similar filtering and fetching logic as before
$sql = "SELECT * FROM users_tbl LIMIT 50 OFFSET $offset";
$query = mysqli_query($con, $sql);

if ($rows = mysqli_num_rows($query) > 0) {
    while ($result = mysqli_fetch_assoc($query)) {
        echo "<tr>
                <td>{$result['fullname']}</td>
                <td>{$result['user_des']}</td>
                <td>{$result['user_scale']}</td>
                <td>{$result['user_role']}</td>
                <td>{$result['date_started']}</td>
                <td>{$result['id_number']}</td>
                <td>{$result['email']}</td>
                <td>{$result['address']}</td>
                <td>{$result['contact_number']}</td>
                <td>{$result['next_of_kin']}</td>
                <td>{$result['next_of_kin_number']}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='13'>No more records found</td></tr>";
}
?>
