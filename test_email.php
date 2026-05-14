<?php
$to = "kenpublio12@gmail.com";
$subject = "Test Email from EVSU";
$message = "This is a test email to verify SMTP configuration.";
$headers = "From: kenpublio12@gmail.com";

if(mail($to, $subject, $message, $headers)) {
    echo "Email sent successfully!";
} else {
    echo "Email sending failed.";
}
?>