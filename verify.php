<?php
require_once 'config.php';
$token = trim($_GET['token'] ?? '');
$verified = false;
if ($token !== '') {
    $stmt = db()->prepare('UPDATE users SET email_verified_at=NOW(), verification_token=NULL WHERE verification_token=?');
    $stmt->execute([$token]);
    $verified = $stmt->rowCount() > 0;
}
$pageTitle = 'Email verification'; include 'includes/header.php';
?>
<section class="section"><div class="container" style="max-width:600px"><div class="empty-state"><div class="empty-icon"><?= $verified ? '✓' : '!' ?></div><h1><?= $verified ? 'Email verified.' : 'Link expired.' ?></h1><p><?= $verified ? 'Your account is ready. You can now sign in and checkout.' : 'This verification link is invalid or has already been used.' ?></p><a class="btn btn-primary" href="login.php"><?= $verified ? 'Sign in →' : 'Return to sign in' ?></a></div></div></section>
<?php include 'includes/footer.php'; ?>