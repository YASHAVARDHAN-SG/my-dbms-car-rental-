<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

$vehicleId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$vehicle = null;

if ($vehicleId === false || $vehicleId === null) {
    http_response_code(404);
} elseif ($dbh instanceof PDO) {
    try {
        $statement = $dbh->prepare(
            'SELECT vehicles.*, brands.BrandName
             FROM tblvehicles AS vehicles
             INNER JOIN tblbrands AS brands ON brands.id = vehicles.VehiclesBrand
             WHERE vehicles.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $vehicleId]);
        $vehicle = $statement->fetch();
        if ($vehicle === false) {
            $vehicle = null;
            http_response_code(404);
        }
    } catch (PDOException $exception) {
        error_log('Vehicle details query failed: ' . $exception->getMessage());
        $databaseError = 'Vehicle details could not be loaded. Please try again later.';
        http_response_code(503);
    }
}

$returnTo = $vehicleId !== false && $vehicleId !== null
    ? 'vehical-details.php?id=' . $vehicleId
    : 'car-listing.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $vehicle ? h($vehicle['VehiclesTitle']) . ' | Car Rental Portal' : 'Vehicle details | Car Rental Portal' ?></title>
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
        <a href="my-booking.php">My bookings</a>
      </nav>
    </div>
  </header>
  <main class="page-main">
    <?php if ($databaseError !== null): ?>
      <p class="page-message page-message-error" role="alert"><?= h($databaseError) ?></p>
    <?php elseif ($vehicle === null): ?>
      <section class="page-message">
        <h1>Vehicle not found</h1>
        <p>This vehicle may have been removed. Browse the current listings to choose another.</p>
        <a class="primary-button" href="car-listing.php">Browse cars</a>
      </section>
    <?php else: ?>
      <p class="breadcrumb"><a href="car-listing.php">Car listings</a> / <?= h($vehicle['VehiclesTitle']) ?></p>
      <article class="vehicle-detail">
        <div class="vehicle-detail-image">
          <img
            src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&amp;fit=crop&amp;w=1400&amp;q=85"
            alt="<?= h($vehicle['BrandName'] . ' ' . $vehicle['VehiclesTitle']) ?>"
          >
        </div>
        <div class="vehicle-detail-content">
          <p class="dialog-eyebrow"><?= h($vehicle['BrandName']) ?> · <?= (int) $vehicle['ModelYear'] ?></p>
          <h1><?= h($vehicle['VehiclesTitle']) ?></h1>
          <p class="vehicle-detail-price">$<?= number_format((float) $vehicle['PricePerDay'], 2) ?> <span>/ day</span></p>
          <p class="vehicle-detail-overview"><?= nl2br(h($vehicle['VehiclesOverview'])) ?></p>
          <dl class="vehicle-specifications">
            <div><dt>Fuel</dt><dd><?= h($vehicle['FuelType']) ?></dd></div>
            <div><dt>Seats</dt><dd><?= (int) $vehicle['SeatingCapacity'] ?></dd></div>
            <div><dt>Model year</dt><dd><?= (int) $vehicle['ModelYear'] ?></dd></div>
          </dl>

          <?php if (empty($_SESSION['user_id'])): ?>
            <a class="primary-button" href="account.php?return_to=<?= rawurlencode($returnTo) ?>">Sign in to request a booking</a>
            <p class="auth-intro">New here? Create an account in the next step.</p>
          <?php else: ?>
            <form class="booking-form" action="booking.php" method="post">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="vehicle_id" value="<?= (int) $vehicle['id'] ?>">
              <h2>Request a booking</h2>
              <label for="from-date">Pickup date</label>
              <input id="from-date" name="from_date" type="date" min="<?= date('Y-m-d') ?>" required>
              <label for="to-date">Return date</label>
              <input id="to-date" name="to_date" type="date" min="<?= date('Y-m-d') ?>" required>
              <label for="booking-message">Note (optional)</label>
              <textarea id="booking-message" name="message" rows="3" maxlength="1000" placeholder="Add any details for the rental team"></textarea>
              <button class="primary-button" type="submit">Send booking request</button>
              <p class="auth-intro">Your request is subject to availability and confirmation.</p>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endif; ?>
  </main>
</body>
</html>
