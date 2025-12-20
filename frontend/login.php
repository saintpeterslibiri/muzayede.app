<?php 
session_start();
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
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $response = loginUser($username, $password);
        
        if ($response['success'] && isset($response['data']['data']['token'])) {
            $token = $response['data']['data']['token'];
            $userData = $response['data']['data']['user'] ?? null;
            saveTokenToSession($token, $userData);
            header('Location: index.php');
            exit;
        } else {
            $error = $response['data']['message'] ?? 'Login failed. Please check your credentials.';
        }
    }
}

include 'includes/header.php'; 
include 'includes/navbar.php'; 
?>

<main class="page auth-page">
    <section class="auth-card">
        <h1>Login</h1>
        <p>Sign in to participate in auctions.</p>

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

        <form method="POST" class="auth-form" autocomplete="on">
            <label>
                Username or Email
                <input type="text" name="username" required autocomplete="username">
            </label>

            <label>
                Password
                <input type="password" name="password" required autocomplete="current-password">
            </label>

            <div class="auth-extra">
                <label class="remember-me">
                    <input type="checkbox" name="remember_me">
                    <span>Remember me</span>
                </label>

                <!-- İleride gerçek sayfa eklersen href'i güncelleyebilirsin -->
                <a href="forgot_password.php" class="auth-link">Forgot password?</a>
            </div>

            <button type="submit" class="btn-primary">Login</button>
        </form>

        <p class="auth-switch">
            Don't have an account?
            <a href="register.php" class="auth-link-strong">Register</a>
        </p>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
            <label>
                Username or Email
                <input type="text" name="username" required autocomplete="username">
            </label>

            <label>
                Password
                <input type="password" name="password" required autocomplete="current-password">
            </label>

            <div class="auth-extra">
                <label class="remember-me">
                    <input type="checkbox" name="remember_me">
                    <span>Remember me</span>
                </label>

                <!-- İleride gerçek sayfa eklersen href'i güncelleyebilirsin -->
                <a href="forgot_password.php" class="auth-link">Forgot password?</a>
            </div>

            <button type="submit" class="btn-primary">Login</button>
        </form>

        <p class="auth-switch">
            Don't have an account?
            <a href="register.php" class="auth-link-strong">Register</a>
        </p>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
