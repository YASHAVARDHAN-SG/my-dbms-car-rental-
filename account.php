<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

$mode = ($_GET['mode'] ?? 'login') === 'register' ? 'register' : 'login';
$errorMessage = '';
$successMessage = '';
$email = '';
$fullName = '';
$returnTo = (string) ($_GET['return_to'] ?? $_POST['return_to'] ?? '');
if (!preg_match('/\Avehical-details\.php\?id=[1-9]\d*\z/', $returnTo)) {
    $returnTo = 'my-booking.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedMode = (string) ($_POST['mode'] ?? '');
    $mode = $postedMode === 'register' ? 'register' : 'login';
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'This form expired. Refresh the page and try again.';
    } elseif (!($dbh instanceof PDO)) {
        $errorMessage = 'Accounts are temporarily unavailable because the database is not connected.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $errorMessage = 'Enter a valid email address.';
    } elseif ($mode === 'register') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $contactNo = trim((string) ($_POST['contact_no'] ?? ''));

        if ($fullName === '' || strlen($fullName) > 120) {
            $errorMessage = 'Enter your name (up to 120 characters).';
        } elseif (strlen($password) < 12 || strlen($password) > 4096) {
            $errorMessage = 'Choose a password between 12 and 4096 characters.';
        } elseif ($contactNo !== '' && !preg_match('/\A[0-9+(). -]{7,24}\z/', $contactNo)) {
            $errorMessage = 'Enter a valid contact number.';
        } else {
            try {
                $statement = $dbh->prepare(
                    'INSERT INTO tblusers (FullName, EmailId, Password, ContactNo)
                     VALUES (:name, :email, :password, :contact)'
                );
                $statement->execute([
                    'name' => $fullName,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'contact' => $contactNo !== '' ? $contactNo : null,
                ]);

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $dbh->lastInsertId();
                $_SESSION['user_email'] = $email;
                $_SESSION['login'] = $email;
                redirect($returnTo);
            } catch (PDOException $exception) {
                if ((string) $exception->getCode() === '23000') {
                    $errorMessage = 'An account with this email already exists. Sign in instead.';
                } else {
                    error_log('Account registration failed: ' . $exception->getMessage());
                    $errorMessage = 'Your account could not be created. Please try again.';
                }
            }
        }
    } elseif ($password === '' || strlen($password) > 4096) {
        $errorMessage = 'Enter your email and password.';
    } else {
        try {
            $statement = $dbh->prepare(
                'SELECT id, FullName, EmailId, Password FROM tblusers WHERE EmailId = :email LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            if (!is_array($user) || !password_verify($password, $user['Password'])) {
                $errorMessage = 'The email or password is incorrect.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_email'] = $user['EmailId'];
                $_SESSION['login'] = $user['EmailId'];

                if (password_needs_rehash($user['Password'], PASSWORD_DEFAULT)) {
                    $updatePassword = $dbh->prepare(
                        'UPDATE tblusers SET Password = :password WHERE id = :id'
                    );
                    $updatePassword->execute([
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'id' => (int) $user['id'],
                    ]);
                }

                redirect($returnTo);
            }
        } catch (PDOException $exception) {
            error_log('Account sign-in failed: ' . $exception->getMessage());
            $errorMessage = 'Sign-in is temporarily unavailable. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $mode === 'register' ? 'Create account' : 'Sign in' ?> | Car Rental Portal</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <div class="header-inner">
      <a class="brand" href="index.php" aria-label="Car Rental Portal home">
        <span class="brand-mark" aria-hidden="true">C</span>
        <span class="brand-name">Car Rental<br>Portal</span>
      </a>
      <nav class="page-nav" aria-label="Main navigation">
        <a href="index.php">Home</a>
        <a href="car-listing.php">Browse cars</a>
      </nav>
    </div>
  </header>
  <main class="page-main">
    <section class="auth-card">
      <p class="dialog-eyebrow">CAR RENTAL PORTAL</p>
      <h1><?= $mode === 'register' ? 'Create your account' : 'Welcome back' ?></h1>
      <p class="auth-intro">Sign in or create an account to request a vehicle booking.</p>

      <?php if ($errorMessage !== ''): ?>
        <p class="page-message page-message-error" role="alert"><?= h($errorMessage) ?></p>
      <?php endif; ?>
      <?php if ($successMessage !== ''): ?>
        <p class="page-message" role="status"><?= h($successMessage) ?></p>
      <?php endif; ?>

      <?php if (!($dbh instanceof PDO)): ?>
        <p class="page-message page-message-error" role="status"><?= h($databaseError) ?></p>
      <?php endif; ?>

      <form class="auth-form" action="account.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="mode" value="<?= h($mode) ?>">
        <input type="hidden" name="return_to" value="<?= h($returnTo) ?>">
        <?php if ($mode === 'register'): ?>
          <label for="full-name">Full name</label>
          <input id="full-name" name="full_name" type="text" maxlength="120" autocomplete="name" value="<?= h($fullName) ?>" required>
        <?php endif; ?>
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" maxlength="254" autocomplete="email" value="<?= h($email) ?>" required>
        <?php if ($mode === 'register'): ?>
          <label for="contact-no">Phone number (optional)</label>
          <input id="contact-no" name="contact_no" type="tel" maxlength="24" autocomplete="tel">
        <?php endif; ?>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="<?= $mode === 'register' ? 'new-password' : 'current-password' ?>" <?= $mode === 'register' ? 'minlength="12"' : '' ?> maxlength="4096" required>
        <button class="primary-button" type="submit" <?= !($dbh instanceof PDO) ? 'disabled' : '' ?>>
          <?= $mode === 'register' ? 'Create account' : 'Sign in' ?>
        </button>
      </form>
      <p class="auth-switch">
        <?php if ($mode === 'register'): ?>
          Already have an account? <a href="account.php?mode=login&amp;return_to=<?= rawurlencode($returnTo) ?>">Sign in</a>
        <?php else: ?>
          New to Car Rental Portal? <a href="account.php?mode=register&amp;return_to=<?= rawurlencode($returnTo) ?>">Create an account</a>
        <?php endif; ?>
      </p>
    </section>
  </main>
</body>
</html>
