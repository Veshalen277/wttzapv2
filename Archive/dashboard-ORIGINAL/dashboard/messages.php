<!-- messages.php
</?php
if (isset($_SESSION['msg'])) {
    $msg_type = $_SESSION['msg_type'] == "error" ? "text-danger" : "text-success";
    echo "<p class='$msg_type'>{$_SESSION['msg']}</p>";
    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
}
?> 
</?php
if (isset($_SESSION['msg'])) {
    $msg_type = $_SESSION['msg_type'] == "error" ? "text-danger" : "text-success";
    $message_content = $_SESSION['msg'];
    $modal_script = "
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var messageModal = new bootstrap.Modal(document.getElementById('messageModal'), {
            keyboard: false
        });
        document.getElementById('messageModalLabel').innerText = 'Notification';
        document.querySelector('#messageModal .modal-body').innerHTML = '<p class=\"$msg_type\">$message_content</p>';
        messageModal.show();
    });
    </script>
    ";
    echo $modal_script;
    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
}
?-->
<!-- messages.php -->
<?php
if (isset($_SESSION['msg'])) {
    $msg_type = isset($_SESSION['msg_type']) && $_SESSION['msg_type'] == "error" ? "text-danger" : "text-success";
    $message_content = $_SESSION['msg'];
    
    $modal_script = "
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var messageModal = new bootstrap.Modal(document.getElementById('messageModal'), {
            keyboard: false
        });
        document.getElementById('messageModalLabel').innerText = 'Notification';
        document.querySelector('#messageModal .modal-body').innerHTML = '<p class=\"$msg_type\">$message_content</p>';
        messageModal.show();
    });
    </script>
    ";
    
    echo $modal_script;
    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
}
?>
