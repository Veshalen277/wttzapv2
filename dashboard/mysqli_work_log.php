<?php
include 'header.php';

// MySQLi connection
$host = 'localhost';
$dbname = 'wttzap_tasks';
$username = 'wttzap_tasks';
$password = '!Mv130369$';

$mysqli = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("MySQLi Connection failed: " . $mysqli->connect_error);
}

// Example MySQLi query
$id = 1;
$stmt = $mysqli->prepare("SELECT * FROM users_tbl WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo "User Name (MySQLi): " . $user['username'] . "<br>";

// Error handling for query execution
if (!$result) {
    die("Query failed: " . $mysqli->error);
}

// Example MySQLi query for the second part
$retrieve = $mysqli->query("SELECT * FROM time_diff WHERE activity = 'babysit' ORDER BY date DESC LIMIT 1");

if (!$retrieve) {
    die("Query failed: " . $mysqli->error);
}

$activities = $retrieve->fetch_all(MYSQLI_ASSOC);

?>

<div class="container">
    <h3>Work Log</h3>
    <div class="container p-5">
        <div class="row py-3">
            <div class="col-md-4 col-xxl-4 my-5 mx-auto">
                <div class="d-grid gap-2">
                    <a href="process_wl.php?activity=babysit&status=start" class="btn btn-success w-100 mt-2" type="button">Start</a>
                    <a href="process_wl.php?activity=babysit&status=end" class="btn btn-danger w-100 mt-2" type="button">Stop</a>
                </div>
            </div>

            <div class="col-md-8 border border-danger">
                <h4>Activity</h4>
                <ol>
                    <?php foreach ($activities as $activity) : ?>
                        <li>
                            <?php $date = DateTime::createFromFormat('Y-m-d H:i:s', $activity['date']); ?>
                            <?php echo strtoupper($activity['activity']); ?>
                            at <?php echo $date->format('H:i:s, d-m-Y'); ?>
                            <br>
                            <?php if ($activity['hour'] == null) {
                                echo "Started at: <span>" . $activity['date'] . "</span>";
                            } else {
                                echo "Babysitter for fafsfagag : <span>" . $activity['hour'] . "</span>";
                            } ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
