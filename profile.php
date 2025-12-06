<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page">

    <div class="home-container">
        <!-- SAYFA BAŞLIĞI -->
        <section class="page-header">
            <h1>My Profile</h1>
            <p>View and update your account information.</p>
        </section>

        <!-- LOGIN UYARISI (her zaman görünsün) -->
        <div class="warning-banner">
            This is a demo view. To see and edit your real profile data, please
            <a href="login.php" class="warning-link">log in to your account</a>.
        </div>

        <!-- PROFIL LAYOUT: SOL (avatar kartı) + SAĞ (form) -->
        <div class="profile-layout">

            <!-- SOL KISIM: AVATAR + KISA BILGI -->
            <aside class="profile-sidebar">
                <div class="avatar-wrapper">
                    <!-- DYNAMIC: Kullanıcı avatarı -->
                    <img src="assets/img/avatar-placeholder.png" class="avatar-img">
                </div>

                <div class="profile-basic-info">
                    <!-- DYNAMIC: İsim / username -->
                    <div class="profile-name">Example User</div>
                    <div class="profile-username">@exampleuser</div>
                </div>

                <div class="avatar-upload">
                    <label class="avatar-upload-label">
                        Change Photo
                        <!-- Formun içindeki file input; profile formu ile post edilecek -->
                        <input type="file" name="avatar" form="profile-form" accept="image/*">
                    </label>
                    <div class="avatar-hint">
                        JPG, PNG • Max 2MB
                    </div>
                </div>

                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-label">Auctions</span>
                        <span class="stat-value">3</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Bids</span>
                        <span class="stat-value">12</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Won</span>
                        <span class="stat-value">4</span>
                    </div>
                </div>
            </aside>

            <!-- SAĞ KISIM: PROFIL FORMU -->
            <section class="profile-main">

                <section class="form-section">

                    <!-- DYNAMIC: başarı / hata mesajı -->
                    <!--
                    <?php if(isset($success)) : ?>
                        <p class="success-message"><?php echo $success; ?></p>
                    <?php endif; ?>
                    -->

                    <form method="POST" id="profile-form" class="profile-form" enctype="multipart/form-data">
                        <h2>Profile Info</h2>

                        <label>
                            Full Name
                            <!-- DYNAMIC: value="<?php echo htmlspecialchars($userFullName); ?>" -->
                            <input type="text" name="full_name" value="Example User">
                        </label>

                        <label>
                            Email
                            <!-- DYNAMIC: value="<?php echo htmlspecialchars($userEmail); ?>" -->
                            <input type="email" name="email" value="user@example.com">
                        </label>

                        <label>
                            Username
                            <!-- DYNAMIC: value="<?php echo htmlspecialchars($userUsername); ?>" -->
                            <input type="text" name="username" value="exampleuser" readonly>
                        </label>

                        <h2>Change Password</h2>

                        <label>
                            Current Password
                            <input type="password" name="current_password">
                        </label>

                        <label>
                            New Password
                            <input type="password" name="new_password">
                        </label>

                        <label>
                            Confirm New Password
                            <input type="password" name="confirm_new_password">
                        </label>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Save Changes</button>
                        </div>
                    </form>
                </section>
            </section>
        </div><!-- .profile-layout -->

    </div><!-- .home-container -->

</main>

<?php include 'includes/footer.php'; ?>
