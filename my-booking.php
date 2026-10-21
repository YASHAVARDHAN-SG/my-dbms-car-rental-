<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['user_email'])) {
    redirect('account.php');
}

$bookings = [];
if ($dbh instanceof PDO) {
    try {
        $statement = $dbh->prepare(
            'SELECT bookings.BookingNumber, bookings.FromDate, bookings.ToDate,
                    bookings.Status, bookings.PostingDate, vehicles.VehiclesTitle,
                    brands.BrandName
             FROM tblbooking AS bookings
             INNER JOIN tblvehicles AS vehicles ON vehicles.id = bookings.VehicleId
             INNER JOIN tblbrands AS brands ON brands.id = vehicles.VehiclesBrand
             WHERE bookings.userEmail = :email
             ORDER BY bookings.PostingDate DESC'
        );
        $statement->execute(['email' => $_SESSION['user_email']]);
        $bookings = $statement->fetchAll();
    } catch (PDOException $exception) {
        error_log('Booking history query failed: ' . $exception->getMessage());
        $databaseError = 'Your bookings could not be loaded. Please try again later.';
        http_response_code(503);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My bookings | Car Rental Portal</title>
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
        <form action="logout.php" method="post">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <button class="text-button" type="submit">Sign out</button>
        </form>
      </nav>
    </div>
  </header>
  <main class="page-main">
    <section class="page-heading">
      <p class="dialog-eyebrow">YOUR ACCOUNT</p>
      <h1>My booking requests</h1>
      <p>Signed in as <?= h($_SESSION['user_email']) ?></p>
    </section>
    <?php if (($_GET['request'] ?? '') === 'sent'): ?>
      <p class="page-message" role="status">Your request was sent. The rental team will confirm availability.</p>
    <?php endif; ?>
    <?php if ($databaseError !== null): ?>
      <p class="page-message page-message-error" role="alert"><?= h($databaseError) ?></p>
    <?php elseif ($bookings === []): ?>
      <section class="page-message">
        <h2>No booking requests yet</h2>
        <p>Browse the vehicle catalogue and send a request when you find a car.</p>
        <a class="primary-button" href="car-listing.php">Browse cars</a>
      </section>
    <?php else: ?>
      <div class="booking-list">
        <?php foreach ($bookings as $booking): ?>
          <?php
          $status = match ((int) $booking['Status']) {
              1 => 'Confirmed',
              2 => 'Declined',
              default => 'Pending',
          };
          ?>
          <article class="booking-card">
            <div>
              <p class="vehicle-brand">Booking #<?= h((string) $booking['BookingNumber']) ?></p>
              <h2><?= h($booking['BrandName'] . ' ' . $booking['VehiclesTitle']) ?></h2>
              <p><?= h($booking['FromDate']) ?> to <?= h($booking['ToDate']) ?></p>
            </div>
            <span class="booking-status booking-status-<?= strtolower($status) ?>"><?= h($status) ?></span>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
