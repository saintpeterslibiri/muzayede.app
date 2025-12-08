<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page auth-page">
    <section class="auth-card">
        <h1>Forgot Password</h1>
        <p>Enter your email address and we&apos;ll send you a password reset link.</p>

        <!-- DYNAMIC: hata / başarı mesajları -->
        <!--
        <?php if(isset($error)) : ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>

        <?php if(isset($success)) : ?>
            <p class="success-message"><?php echo $success; ?></p>
        <?php endif; ?>
        -->

        <form method="POST" class="auth-form">
            <label>
                Email address
                <input type="email" name="email" required>
            </label>

            <button type="submit" class="btn-primary">Send Reset Link</button>
        </form>

        <p class="auth-switch">
            Remembered your password?
            <a href="login.php" class="auth-link-strong">Back to Login</a>
        </p>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
