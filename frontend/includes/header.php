<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Online Auction Platform</title>

    <!-- CSS dosyan -->
    <link rel="stylesheet" href="assets/css/style.css">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        window.CONFIG = {
            API_BASE_URL: "<?php echo getenv('PUBLIC_API_URL') ?: 'http://localhost:3000'; ?>/api"
        };
    </script>
</head>
<body>
    <!-- Toast Container -->
    <div id="toast-container"></div>
