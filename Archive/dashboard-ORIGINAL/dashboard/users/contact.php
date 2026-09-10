<?php
include '../header.php'; // Ensure this path is correct

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form fields
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    // $phone = htmlspecialchars($_POST['phone']);
    // $website = htmlspecialchars($_POST['website']);
    $message = htmlspecialchars($_POST['message']);

    // Email configuration
    $to = 'admin@timefliesza.co.za';
    $subject = 'Contact Form Submission from ' . $name;
    $headers = "From: " . $email . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Email body
    $emailBody = "<html><body>";
    $emailBody .= "<h2>Contact Form Submission</h2>";
    $emailBody .= "<p><strong>Name:</strong> $name</p>";
    $emailBody .= "<p><strong>Email:</strong> $email</p>";
    // $emailBody .= "<p><strong>Phone:</strong> $phone</p>";
    // $emailBody .= "<p><strong>Website:</strong> $website</p>";
    $emailBody .= "<p><strong>Message:</strong><br>$message</p>";
    $emailBody .= "</body></html>";

    // Send email
    if (mail($to, $subject, $emailBody, $headers)) {
        echo "<p>Thank you for contacting us, $name. We will get back to you soon.</p>";
    } else {
        echo "<p>Sorry, there was an error sending your message. Please try again later.</p>";
    }
} else {
?>
<div class="row">
  <div class="col-md-9 col-sm-12">
        <div class="container">
        <form id="contact" action="contact.php" method="post">
            <h3>Quick Contact</h3>
            <h4>Contact us today, and get reply within 24 hours!</h4>
            <fieldset>
                <input placeholder="Your name" type="text" name="name" tabindex="1" required autofocus>
            </fieldset>
            <fieldset>
                <input placeholder="Your Email Address" type="email" name="email" tabindex="2" required>
            </fieldset>
            <!-- <fieldset>
                <input placeholder="Your Phone Number" type="tel" name="phone" tabindex="3" required>
            </fieldset>
            <fieldset>
                <input placeholder="Your Web Site starts with http://" type="url" name="website" tabindex="4" required>
            </fieldset> -->
            <fieldset>
                <textarea placeholder="Type your Message Here...." name="message" tabindex="5" required></textarea>
            </fieldset>
            <fieldset>
                <button name="submit" type="submit" id="contact-submit" data-submit="...Sending">Submit</button>
            </fieldset>
        </form>
    </div>
  </div>
</div>
<?php
}

include '../footer.php'; // Ensure this path is correct
?>
