<?php
include '../header.php'; // Handles database connection and session



// Handle acknowledgment submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acknowledge_terms'])) {
    $userId = $_SESSION['user_id'];

    // Update the user's acknowledgment in the database
    $sql = "UPDATE users_tbl SET terms_acknowledged = TRUE WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('i', $userId);
    
    if ($stmt->execute()) {
        // Send email notifications
        $subject = "User Agreement Notification";
        $message = "User ID $userId has acknowledged the Terms and Conditions.";
        sendEmail('admin@timefliesza.co.za', $subject, $message);
        sendEmail('wok@wtt.co.za', $subject, $message);
        
        // Redirect to acknowledgment page
        header('Location: acknowledge_copyright.php');
        exit();
    } else {
        echo "Error updating acknowledgment: " . $con->error;
    }
    $stmt->close();
}
?>


<div class="container my-5">
    <h3 class="mb-4">Terms of Use</h3>
    <p><strong>Effective Date:</strong> 11/9/2024</p>

    <h4>1. Introduction</h4>
    <p>Welcome to the Watches Tell Time Employee Portal. By accessing or using our portal, you agree to comply with and be bound by these Terms of Use. Please read these terms carefully before using our services—it's like a safety briefing, but for the digital world.</p>

    <h4>2. User Obligations</h4>
    <p>As a user of the portal, you agree to:</p>
    <ul>
        <li><strong>Provide Accurate Information:</strong> Make sure all information you provide is accurate, current, and complete. No room for "I forgot my password" excuses here!</li>
        <li><strong>Maintain Security:</strong> Keep your login credentials confidential and notify us immediately if you suspect any unauthorized use of your account. Think of it as protecting your digital treasure chest.</li>
        <li><strong>Use the Portal Responsibly:</strong> Avoid unlawful or prohibited activities, such as spamming, data mining, or unauthorized access. Basically, don't be the digital equivalent of a cat burglar.</li>
    </ul>

    <h4>3. Intellectual Property</h4>
    <p>The content and materials available on the portal—including text, graphics, logos, and software—are the property of Watches Tell Time and are protected by intellectual property laws. Feel free to enjoy, but don’t reproduce, distribute, or modify any content without our express written permission. After all, sharing is caring, but not without asking first!</p>

    <h4>4. Limitation of Liability</h4>
    <p>We provide the portal "as is" and make no representations or warranties regarding its availability, accuracy, or reliability. To the fullest extent permitted by law, we disclaim all liability for any damages arising out of or related to your use of the portal. Think of it as a "use at your own risk" sign, but less literal.</p>

    <h4>5. Termination</h4>
    <p>We reserve the right to suspend or terminate your access to the portal at any time, without prior notice, for any reason, including violations of these Terms of Use. It’s like having a “no shoes, no service” policy, but for your digital behavior.</p>

    <h4>6. Changes to Terms</h4>
    <p>We may update these Terms of Use from time to time. Any changes will be posted on this page with an updated effective date. We encourage you to review these terms periodically—consider it your regular digital check-up.</p>

    <h4>7. Governing Law</h4>
    <p>These Terms of Use are governed by and construed in accordance with the laws of [Your Jurisdiction]. Any disputes will be subject to the exclusive jurisdiction of the courts in [Your Jurisdiction]. In other words, we're all about local rules and local courts.</p>

    <h4>8. Contact Us</h4>
    <p>If you have any questions about these Terms of Use, please contact us at:</p>
    <address>
        <strong>Watches Tell Time</strong><br>
        96 Canada Road<br>
        Durban, KZN, 4000<br>
        <a href="mailto:info@wtt.co.za">Email us</a><br>
    </address>


</div>

<?php include '../footer.php'; ?>

