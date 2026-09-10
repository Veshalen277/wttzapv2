<?php
include "../header.php";

// Move logins older than 30 days to archive table
$archiveSql = "
    INSERT INTO user_logins_archive
    SELECT * FROM user_logins
    WHERE login_time < NOW() - INTERVAL 30 DAY
";

// Then delete them from the main table
$deleteSql = "
    DELETE FROM user_logins
    WHERE login_time < NOW() - INTERVAL 30 DAY
";

// Run queries
mysqli_query($con, $archiveSql);
mysqli_query($con, $deleteSql);

echo "Archived and deleted old login records successfully.";

include "../footer.php";
?>
