<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

$queryText = trim((string) ($_GET['q'] ?? ''));
$brandId = filter_var($_GET['brand'] ?? null, FILTER_VALIDATE_INT);
$fuelType = trim((string) ($_GET['fuel'] ?? ''));
$brands = [];
$cars = [];

if ($dbh instanceof PDO) {
    try {
        $brands = $dbh->query('SELECT id, BrandName FROM tblbrands ORDER BY BrandName')->fetchAll();

        $sql = 'SELECT vehicles.id, vehicles.VehiclesTitle, vehicles.VehiclesOverview,
                       vehicles.PricePerDay, vehicles.FuelType, vehicles.ModelYear,
                       vehicles.SeatingCapacity, brands.BrandName
                FROM tblvehicles AS vehicles
                INNER JOIN tblbrands AS brands ON brands.id = vehicles.VehiclesBrand
                WHERE 1 = 1';
        $parameters = [];

        if ($queryText !== '') {
            $sql .= ' AND (vehicles.VehiclesTitle LIKE :search_title
                      OR vehicles.VehiclesOverview LIKE :search_overview
                      OR brands.BrandName LIKE :search_brand
                      OR vehicles.FuelType LIKE :search_fuel)';
            $searchTerm = '%' . $queryText . '%';
            $parameters['search_title'] = $searchTerm;
            $parameters['search_overview'] = $searchTerm;
            $parameters['search_brand'] = $searchTerm;
            $parameters['search_fuel'] = $searchTerm;
        }

        if ($brandId !== false && $brandId !== null) {
            $sql .= ' AND vehicles.VehiclesBrand = :brand_id';
            $parameters['brand_id'] = $brandId;
        }

        if ($fuelType !== '') {
            $sql .= ' AND vehicles.FuelType = :fuel_type';
            $parameters['fuel_type'] = $fuelType;
        }

        $sql .= ' ORDER BY vehicles.id DESC LIMIT 60';
        $statement = $dbh->prepare($sql);
        $statement->execute($parameters);
        $cars = $statement->fetchAll();
    } catch (PDOException $exception) {
        error_log('Car listing query failed: ' . $exception->getMessage());
        $databaseError = 'Vehicle listings could not be loaded. Please try again later.';
        http_response_code(503);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Browse and search available rental cars.">
  <title>Car listings | Car Rental Portal</title>
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
        <a href="car-listing.php" aria-current="page">Car listing</a>
        <a href="account.php">Login / Register</a>
      </nav>
    </div>
  </header>
  <main class="page-main">
    <section class="page-heading">
      <p class="dialog-eyebrow">FIND YOUR NEXT RIDE</p>
      <h1>Available cars</h1>
      <p>Search our current vehicle catalogue and open a car to see its details.</p>
    </section>
    <section class="catalogue" aria-label="Vehicle catalogue">
      <form class="catalogue-filters" action="car-listing.php" method="get">
        <label>
          Search
          <input type="search" name="q" value="<?= h($queryText) ?>" placeholder="Make, model, fuel type">
        </label>
        <label>
          Brand
          <select name="brand">
            <option value="">All brands</option>
            <?php foreach ($brands as $brand): ?>
              <option value="<?= (int) $brand['id'] ?>" <?= $brandId == $brand['id'] ? 'selected' : '' ?>>
                <?= h($brand['BrandName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          Fuel
          <select name="fuel">
            <option value="">Any fuel</option>
            <?php foreach (['Petrol', 'Diesel', 'Hybrid', 'Electric', 'CNG'] as $fuel): ?>
              <option value="<?= h($fuel) ?>" <?= $fuelType === $fuel ? 'selected' : '' ?>><?= h($fuel) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <button class="primary-button" type="submit">Apply filters</button>
        <a class="secondary-button" href="car-listing.php">Clear</a>
      </form>

      <?php if ($databaseError !== null): ?>
        <p class="page-message page-message-error" role="alert"><?= h($databaseError) ?></p>
      <?php elseif ($cars === []): ?>
        <p class="page-message">No vehicles match this search. Try another keyword or clear the filters.</p>
      <?php else: ?>
        <p class="catalogue-count"><?= count($cars) ?> car<?= count($cars) === 1 ? '' : 's' ?> found</p>
        <div class="vehicle-grid">
          <?php foreach ($cars as $car): ?>
            <article class="vehicle-card">
              <a class="vehicle-card-image" href="vehical-details.php?id=<?= (int) $car['id'] ?>">
                <img
                  src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&amp;fit=crop&amp;w=900&amp;q=80"
                  alt="<?= h($car['BrandName'] . ' ' . $car['VehiclesTitle']) ?>"
                  loading="lazy"
                >
                <span><?= h($car['FuelType']) ?></span>
              </a>
              <div class="vehicle-card-body">
                <p class="vehicle-brand"><?= h($car['BrandName']) ?> · <?= (int) $car['ModelYear'] ?></p>
                <h2><a href="vehical-details.php?id=<?= (int) $car['id'] ?>"><?= h($car['VehiclesTitle']) ?></a></h2>
                <p class="vehicle-summary"><?= h($car['VehiclesOverview']) ?></p>
                <div class="vehicle-card-footer">
                  <span><strong>$<?= number_format((float) $car['PricePerDay'], 2) ?></strong> / day</span>
                  <span><?= (int) $car['SeatingCapacity'] ?> seats</span>
                </div>
                <a class="vehicle-details-link" href="vehical-details.php?id=<?= (int) $car['id'] ?>">View details <span aria-hidden="true">→</span></a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
