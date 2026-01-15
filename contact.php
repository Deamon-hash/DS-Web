<?php

// Load Composer's autoloader
equire 'vendor/autoload.php';

// Load rate limiting configuration
$rateLimitConfig = json_decode(file_get_contents('data/rl_contact.json'), true);

// Check for honeypot
if (!empty($_POST['honeypot'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Honeypot triggered.']);
    exit;
}

// Rate limiting
$ip = $_SERVER['REMOTE_ADDR'];
if (!isset($rateLimitConfig[$ip])) {
    $rateLimitConfig[$ip] = 0;
}
if ($rateLimitConfig[$ip] >= 5) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded.']);
    exit;
}
$rateLimitConfig[$ip]++;
file_put_contents('data/rl_contact.json', json_encode($rateLimitConfig, JSON_PRETTY_PRINT));

// Prepare email
$mail = new PHPMailer\PHPMailer\PHPMailer();
try {
    //Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.example.com';  // Set the SMTP server to send through
    $mail->SMTPAuth = true;
    $mail->Username = 'your_email@example.com'; // SMTP username
    $mail->Password = 'your_email_password'; // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
    $mail->Port = 587; // TCP port to connect to

    //Recipients
    $mail->setFrom('from@example.com', 'Mailer');
    $mail->addAddress('receiver@example.com', 'Receiver Name'); // Add a recipient

    // Content
    $mail->isHTML(true); // Set email format to HTML
    $mail->Subject = 'Subject Line';
    $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    // Send email
    if (!$mail->send()) {
        http_response_code(500);
        echo json_encode(['error' => 'Mail could not be sent. Mailer Error: ' . $mail->ErrorInfo]);
        exit;
    }
    echo json_encode(['success' => 'Mail sent successfully.']);
} catch (Exception $e) {
    // Fallback to PHP mail()
    if (!mail('receiver@example.com', 'Subject Line', 'This is the body in plain text for non-HTML mail clients')) {
        http_response_code(500);
        echo json_encode(['error' => 'Mail could not be sent via mail().']);
        exit;
    }
    echo json_encode(['success' => 'Mail sent successfully via fall back.']);
}
?>