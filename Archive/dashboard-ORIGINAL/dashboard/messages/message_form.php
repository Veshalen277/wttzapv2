<?php
// Initialize session and common layout
include '../header.php'; 

// Pre-fetch user data to keep HTML clean
$username = "Guest";
if (isset($_SESSION['u_data'])) {
    // Assuming $user[0] is the name; using htmlspecialchars to prevent XSS
    $username = htmlspecialchars(ucwords($_SESSION['u_data'][0]));
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card p-2">
                <?php include '../inc/sidebar.php';?>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card shadow-sm p-4 bg-white">
                
                <?php if (isset($_SESSION['msg'])): ?>
                    <?php 
                        $alertType = ($_SESSION['msg_type'] == "error") ? "alert-danger" : "alert-success";
                    ?>
                    <div class="alert <?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['msg']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php 
                        unset($_SESSION['msg']); 
                        unset($_SESSION['msg_type']); 
                    ?>
                <?php endif; ?>

                <div class="welcome-section mb-4">
                    <h2>Welcome, <?php echo $username; ?>!</h2>
                    <p class="text-muted">Share your thoughts with the community below.</p>
                </div>

                <form action="message_board.php" method="POST" class="border-top pt-3">
                    <div class="mb-3">
                        <label for="message_text" class="form-label fw-bold">Write your message:</label>
                        <textarea 
                            id="message_text" 
                            name="message_text" 
                            class="form-control" 
                            rows="4" 
                            placeholder="What's on your mind?" 
                            required></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send"></i> Post Message
                        </button>
                    </div>
                </form>

                <div class="mt-5">
                    <hr>
                    <h4 class="mb-3">Recent Messages</h4>
                    </div>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>