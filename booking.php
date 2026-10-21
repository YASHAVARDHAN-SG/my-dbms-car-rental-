<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('car-listing.php');
}

if (empty($_SESSION['user_id']) || empty($_SESSION['user_email'])) {
    redirect('account.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('This form expired. Return to the vehicle details page and try again.');
}

if (!($dbh instanceof PDO)) {
    http_response_code(503);
    exit('Booking requests are temporarily unavailable because the database is not connected.');
}

$vehicleId = filter_var($_POST['vehicle_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$fromDateValue = (string) ($_POST['from_date'] ?? '');
$toDateValue = (string) ($_POST['to_date'] ?? '');
$message = trim((string) ($_POST['message'] ?? ''));
$fromDate = DateTimeImmutable::createFromFormat('!Y-m-d', $fromDateValue);
$toDate = DateTimeImmutable::createFromFormat('!Y-m-d', $toDateValue);

if (
    $vehicleId === false
    || $vehicleId === null
    || !$fromDate
    || !$toDate
    || $fromDate->format('Y-m-d') !== $fromDateValue
    || $toDate->format('Y-m-d') !== $toDateValue
    || $fromDateValue < date('Y-m-d')
    || $toDateValue < $fromDateValue
    || strlen($message) > 1000
) {
    http_response_code(400);
    exit('Check the vehicle, dates, and note, then submit the request again.');
}

try {
    $dbh->beginTransaction();
    $vehicleStatement = $dbh->prepare('SELECT id FROM tblvehicles WHERE id = :id FOR UPDATE');
    $vehicleStatement->execute(['id' => $vehicleId]);

    if ($vehicleStatement->fetchColumn() === false) {
        $dbh->rollBack();
        http_response_code(404);
        exit('This vehicle is no longer available.');
    }

    $availabilityStatement = $dbh->prepare(
        'SELECT id FROM tblbooking
         WHERE VehicleId = :vehicle_id
           AND Status IN (0, 1)
           AND FromDate <= :requested_to
           AND ToDate >= :requested_from
         LIMIT 1'
    );
    $availabilityStatement->execute([
        'vehicle_id' => $vehicleId,
        'requested_to' => $toDateValue,
        'requested_from' => $fromDateValue,
    ]);

    if ($availabilityStatement->fetchColumn() !== false) {
        $dbh->rollBack();
        http_response_code(409);
        exit('This car is already requested for some of those dates. Please choose another date range.');
    }

    $bookingNumber = random_int(1000000000, 9999999999);
    $bookingStatement = $dbh->prepare(
        'INSERT INTO tblbooking (BookingNumber, userEmail, VehicleId, FromDate, ToDate, message, Status)
         VALUES (:booking_number, :user_email, :vehicle_id, :from_date, :to_date, :message, 0)'
    );
    $bookingStatement->execute([
        'booking_number' => $bookingNumber,
        'user_email' => $_SESSION['user_email'],
        'vehicle_id' => $vehicleId,
        'from_date' => $fromDateValue,
        'to_date' => $toDateValue,
        'message' => $message !== '' ? $message : null,
    ]);
    $dbh->commit();
    redirect('my-booking.php?request=sent');
} catch (PDOException $exception) {
    if ($dbh->inTransaction()) {
        $dbh->rollBack();
    }
    error_log('Booking request failed: ' . $exception->getMessage());
    http_response_code(503);
    exit('The booking request could not be saved. Please try again later.');
}
