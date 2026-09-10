
<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = $_POST['email'];
    $filename = 'subscribers/subscribers.txt';
    
    // Ensure the 'subscribers' directory exists
    if (!file_exists('subscribers')) {
        mkdir('subscribers', 0777, true);
    }
    
    // Append email to the file
    $file = fopen($filename, 'a');
    if ($file) {
        fwrite($file, $email . PHP_EOL);
        fclose($file);
        echo "Thank you for subscribing!";
        echo "<script>
                setTimeout(function(){
                    window.location.href = 'index.php'; 
                }, 3000); // 3000 milliseconds = 3 seconds
              </script>";
    } else {
        echo "Error saving your subscription.";
    }
}
?>


