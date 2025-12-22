<?php 
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
require_once 'includes/api.php';

// Eğer zaten giriş yapılmışsa ana sayfaya yönlendir
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($full_name) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        $response = registerUser($username, $email, $password, $full_name);
        
        if ($response['success'] && isset($response['data']['data']['token'])) {
            $token = $response['data']['data']['token'];
            $userData = $response['data']['data']['user'] ?? null;
            saveTokenToSession($token, $userData);
            header('Location: index.php');
            exit;
        } else {
            $error = $response['data']['message'] ?? 'Registration failed. Please try again.';
        }
    }
}

include 'includes/header.php'; 
include 'includes/navbar.php'; 
?>

<main class="page auth-page">
    <section class="auth-card">
        <h1>Create Account</h1>
        <p>Join our platform and start bidding or creating auctions.</p>

        <?php if ($error) : ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof showErrorToast === 'function') {
                        showErrorToast('<?php echo addslashes($error); ?>');
                    }
                });
            </script>
        <?php endif; ?>

        <?php if ($success) : ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof showSuccessToast === 'function') {
                        showSuccessToast('<?php echo addslashes($success); ?>');
                    }
                });
            </script>
        <?php endif; ?>

        <form method="POST" class="auth-form">

            <label>
                Full Name
                <input type="text" name="full_name" placeholder="John Doe" required>
            </label>

            <label>
                Email
                <input type="email" name="email" placeholder="user@example.com" required>
            </label>

            <label>
                Username
                <input type="text" name="username" placeholder="yourusername" required>
            </label>

            <label>
                Password
                <input type="password" name="password" placeholder="••••••••" required>
            </label>

            <label>
                Confirm Password
                <input type="password" name="confirm_password" placeholder="••••••••" required>
            </label>

            <button type="submit" class="btn-primary">Create Account</button>
        </form>

        <p class="auth-switch">
            Already have an account?
            <a href="login.php" class="auth-link-strong">Login</a>
        </p>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

        
