<?php
include '../header.php';
include '../functions.php';
require '../config.php'; // Ensure this includes your database connection

$project_id = $_GET['project_id'];

// Fetch project messages
$messages_sql = "SELECT m.message_id, m.message, u.fullname, m.created_at FROM project_messages m LEFT JOIN users_tbl u ON m.user_id = u.id WHERE m.project_id = ? ORDER BY m.created_at DESC";
$stmt = $con->prepare($messages_sql);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$messages_result = $stmt->get_result();

// Fetch project details
$project_sql = "SELECT project_name, project_description FROM projects_tbl WHERE project_id = ?";
$stmt = $con->prepare($project_sql);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project_result = $stmt->get_result();
$project = $project_result->fetch_assoc();




// Fetch associated users
$users_sql = "SELECT u.fullname FROM user_project up JOIN users_tbl u ON up.user_id = u.id WHERE up.project_id = ?";
$stmt = $con->prepare($users_sql);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$users_result = $stmt->get_result();
?>


<div class="container">
    <h4 class="mb-4">Messages for Project: <?= htmlspecialchars($project['project_name']) ?></h4>
<p><strong>Description:</strong> <?= nl2br(htmlspecialchars($project['project_description'])) ?></p>

        <!-- Display associated users -->
    <div class="mb-4">
        <h5>Associated Users:</h5>
        <ul>
            <?php while ($user = $users_result->fetch_assoc()): ?>
                <li><?= htmlspecialchars($user['fullname']) ?></li>
            <?php endwhile; ?>
        </ul>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="messages">
                <?php while ($message = $messages_result->fetch_assoc()): ?>
                <div class="card mb-2">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($message['fullname']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($message['message']) ?></p>
                        <p class="card-text"><small class="text-muted"><?= htmlspecialchars($message['created_at']) ?></small></p>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <form action="post_message.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="project_id" value="<?= $project_id ?>">
                <div class="form-group">
                    <label for="message">Post a Message</label>
                    <textarea class="form-control" id="message" name="message" required></textarea>
                </div>
                <div class="form-group">
                    <label for="files">Upload Files</label>
                    <input type="file" class="form-control-file" id="files" name="files[]" multiple>
                </div>
                <button type="submit" class="btn btn-primary">Post Message</button>
                      <a href="project_dashboard.php" class="btn btn-success">Back to dashboard</a>
                     
            </form>
       
        </div>
        <div class="col-md-4">
            <h5>Associated Files</h5>
            <div class="files">
                <?php
                // Fetch associated files for the project messages
                $files_sql = "SELECT file_id, file_name, file_path FROM project_message_files WHERE message_id IN (SELECT message_id FROM project_messages WHERE project_id = ?)";
                $stmt = $con->prepare($files_sql);
                $stmt->bind_param("i", $project_id);
                $stmt->execute();
                $files_result = $stmt->get_result();

                while ($file = $files_result->fetch_assoc()):
                ?>
                <div class="card mb-2">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank"><?= htmlspecialchars($file['file_name']) ?></a>
                        <form action="delete_file.php" method="post" onsubmit="return confirm('Are you sure you want to delete this file?');">
                            <input type="hidden" name="file_id" value="<?= $file['file_id'] ?>">
                            <input type="hidden" name="project_id" value="<?= $project_id ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>


<!-- </?php
include '../header.php';
include '../functions.php';
require '../config.php'; // Ensure this includes your database connection

$project_id = $_GET['project_id'];

// Fetch project messages
$messages_sql = "SELECT m.message_id, m.message, u.fullname, m.created_at FROM project_messages m LEFT JOIN users_tbl u ON m.user_id = u.id WHERE m.project_id = ? ORDER BY m.created_at DESC";
$stmt = $con->prepare($messages_sql);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$messages_result = $stmt->get_result();

// Fetch project details
$project_sql = "SELECT project_name FROM projects_tbl WHERE project_id = ?";
$stmt = $con->prepare($project_sql);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project_result = $stmt->get_result();
$project = $project_result->fetch_assoc();
?>

<div class="container">
    <h4 class="mb-4">Messages for Project: </?= htmlspecialchars($project['project_name']) ?></h4>
    <div class="row">
        <div class="col-md-8">
            <div class="messages">
                </?php while ($message = $messages_result->fetch_assoc()): ?>
                <div class="card mb-2">
                    <div class="card-body">
                        <h5 class="card-title"></?= htmlspecialchars($message['fullname']) ?></h5>
                        <p class="card-text"></?= htmlspecialchars($message['message']) ?></p>
                        <p class="card-text"><small class="text-muted"></?= htmlspecialchars($message['created_at']) ?></small></p>
                    </div>
                </div>
                </?php endwhile; ?>
            </div>
            <form action="post_message.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="project_id" value="</?= $project_id ?>">
                <div class="form-group">
                    <label for="message">Post a Message</label>
                    <textarea class="form-control" id="message" name="message" required></textarea>
                </div>
                <div class="form-group">
                    <label for="files">Upload Files</label>
                    <input type="file" class="form-control-file" id="files" name="files[]" multiple>
                </div>
                <button type="submit" class="btn btn-primary">Post Message</button>
            </form>
        </div>
        <div class="col-md-4">
            <h5>Associated Files</h5>
            <div class="files">
                </?php
                // Fetch associated files for the project messages
                $files_sql = "SELECT file_id, file_name, file_path FROM project_message_files WHERE message_id IN (SELECT message_id FROM project_messages WHERE project_id = ?)";
                $stmt = $con->prepare($files_sql);
                $stmt->bind_param("i", $project_id);
                $stmt->execute();
                $files_result = $stmt->get_result();

                while ($file = $files_result->fetch_assoc()):
                ?>
                <div class="card mb-2">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <a href="</?= htmlspecialchars($file['file_path']) ?>" target="_blank"></?= htmlspecialchars($file['file_name']) ?></a>
                        <form action="delete_file.php" method="post" onsubmit="return confirm('Are you sure you want to delete this file?');">
                            <input type="hidden" name="file_id" value="</?= $file['file_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </div>
                </?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

</?php include '../footer.php'; ?> -->
