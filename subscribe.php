<?php
require_once 'dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            // Create newsletter table if it doesn't exist
            $create_table = "CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $dbh->exec($create_table);
            
            // Insert email
            $query = "INSERT INTO newsletter_subscribers (email) VALUES (?)";
            $stmt = $dbh->prepare($query);
            $stmt->execute([$email]);
            
            $message = "Thank you for subscribing to our newsletter!";
            $type = "success";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $message = "This email is already subscribed to our newsletter.";
                $type = "warning";
            } else {
                $message = "An error occurred. Please try again later.";
                $type = "danger";
            }
        }
    } else {
        $message = "Please enter a valid email address.";
        $type = "danger";
    }
} else {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Subscription - Daily News</title>
    <link rel="icon" type="image/png" href="./imgs/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-envelope fa-3x text-<?php echo $type; ?> mb-3"></i>
                        <h3>Newsletter Subscription</h3>
                        <div class="alert alert-<?php echo $type; ?>" role="alert">
                            <?php echo $message; ?>
                        </div>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>