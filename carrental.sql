CREATE DATABASE IF NOT EXISTS carrental
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE carrental;

CREATE TABLE IF NOT EXISTS admin (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  UserName VARCHAR(100) NOT NULL,
  Password VARCHAR(255) NOT NULL,
  updationDate TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_username (UserName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tblbrands (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  BrandName VARCHAR(120) NOT NULL,
  CreationDate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UpdationDate TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tblbrands_name (BrandName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tblusers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  FullName VARCHAR(120) NOT NULL,
  EmailId VARCHAR(254) NOT NULL,
  Password VARCHAR(255) NOT NULL,
  ContactNo VARCHAR(24) DEFAULT NULL,
  dob DATE DEFAULT NULL,
  Address VARCHAR(255) DEFAULT NULL,
  City VARCHAR(100) DEFAULT NULL,
  Country VARCHAR(100) DEFAULT NULL,
  RegDate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UpdationDate TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tblusers_email (EmailId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tblvehicles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  VehiclesTitle VARCHAR(150) NOT NULL,
  VehiclesBrand INT UNSIGNED NOT NULL,
  VehiclesOverview TEXT NOT NULL,
  PricePerDay DECIMAL(10, 2) UNSIGNED NOT NULL,
  FuelType VARCHAR(40) NOT NULL,
  ModelYear SMALLINT UNSIGNED NOT NULL,
  SeatingCapacity TINYINT UNSIGNED NOT NULL,
  Vimage1 VARCHAR(512) DEFAULT NULL,
  Vimage2 VARCHAR(512) DEFAULT NULL,
  Vimage3 VARCHAR(512) DEFAULT NULL,
  Vimage4 VARCHAR(512) DEFAULT NULL,
  Vimage5 VARCHAR(512) DEFAULT NULL,
  AirConditioner TINYINT(1) NOT NULL DEFAULT 1,
  PowerDoorLocks TINYINT(1) NOT NULL DEFAULT 1,
  AntiLockBrakingSystem TINYINT(1) NOT NULL DEFAULT 1,
  BrakeAssist TINYINT(1) NOT NULL DEFAULT 1,
  PowerSteering TINYINT(1) NOT NULL DEFAULT 1,
  DriverAirbag TINYINT(1) NOT NULL DEFAULT 1,
  PassengerAirbag TINYINT(1) NOT NULL DEFAULT 1,
  PowerWindows TINYINT(1) NOT NULL DEFAULT 1,
  CDPlayer TINYINT(1) NOT NULL DEFAULT 0,
  CentralLocking TINYINT(1) NOT NULL DEFAULT 1,
  CrashSensor TINYINT(1) NOT NULL DEFAULT 1,
  LeatherSeats TINYINT(1) NOT NULL DEFAULT 0,
  RegDate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UpdationDate TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_tblvehicles_brand (VehiclesBrand),
  KEY idx_tblvehicles_fuel (FuelType),
  CONSTRAINT fk_tblvehicles_brand FOREIGN KEY (VehiclesBrand)
    REFERENCES tblbrands (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tblbooking (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  BookingNumber BIGINT UNSIGNED NOT NULL,
  userEmail VARCHAR(254) NOT NULL,
  VehicleId INT UNSIGNED NOT NULL,
  FromDate DATE NOT NULL,
  ToDate DATE NOT NULL,
  message VARCHAR(1000) DEFAULT NULL,
  Status TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PostingDate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  LastUpdationDate TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tblbooking_number (BookingNumber),
  KEY idx_tblbooking_vehicle_dates (VehicleId, Status, FromDate, ToDate),
  KEY idx_tblbooking_user (userEmail, PostingDate),
  CONSTRAINT fk_tblbooking_user FOREIGN KEY (userEmail)
    REFERENCES tblusers (EmailId),
  CONSTRAINT fk_tblbooking_vehicle FOREIGN KEY (VehicleId)
    REFERENCES tblvehicles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tblcontactusinfo (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  Address TINYTEXT DEFAULT NULL,
  EmailId VARCHAR(255) DEFAULT NULL,
  ContactNo VARCHAR(24) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tblcontactusquery (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) DEFAULT NULL,
  EmailId VARCHAR(254) DEFAULT NULL,
  ContactNumber VARCHAR(24) DEFAULT NULL,
  Message TEXT DEFAULT NULL,
  PostingDate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status TINYINT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tblpages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  PageName VARCHAR(255) DEFAULT NULL,
  type VARCHAR(255) NOT NULL DEFAULT '',
  detail LONGTEXT NOT NULL,
  PRIMARY KEY (id),
  KEY idx_tblpages_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tblsubscribers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  SubscriberEmail VARCHAR(254) NOT NULL,
  PostingDate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tblsubscribers_email (SubscriberEmail)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tbltestimonial (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  UserEmail VARCHAR(254) NOT NULL,
  Testimonial TEXT NOT NULL,
  PostingDate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_tbltestimonial_status (status, PostingDate),
  CONSTRAINT fk_tbltestimonial_user FOREIGN KEY (UserEmail)
    REFERENCES tblusers (EmailId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO tblbrands (BrandName) VALUES
  ('Toyota'),
  ('Honda'),
  ('Nissan'),
  ('BMW'),
  ('Audi');

INSERT INTO tblvehicles
  (VehiclesTitle, VehiclesBrand, VehiclesOverview, PricePerDay, FuelType, ModelYear, SeatingCapacity)
SELECT 'Corolla', id, 'A comfortable, fuel-efficient sedan for everyday journeys.', 55.00, 'Petrol', 2022, 5
FROM tblbrands WHERE BrandName = 'Toyota'
  AND NOT EXISTS (SELECT 1 FROM tblvehicles WHERE VehiclesTitle = 'Corolla');

INSERT INTO tblvehicles
  (VehiclesTitle, VehiclesBrand, VehiclesOverview, PricePerDay, FuelType, ModelYear, SeatingCapacity)
SELECT 'RAV4', id, 'A practical SUV with room for passengers and luggage.', 78.00, 'Hybrid', 2023, 5
FROM tblbrands WHERE BrandName = 'Toyota'
  AND NOT EXISTS (SELECT 1 FROM tblvehicles WHERE VehiclesTitle = 'RAV4');

INSERT INTO tblvehicles
  (VehiclesTitle, VehiclesBrand, VehiclesOverview, PricePerDay, FuelType, ModelYear, SeatingCapacity)
SELECT 'Civic', id, 'A refined compact car with a smooth, comfortable ride.', 58.00, 'Petrol', 2023, 5
FROM tblbrands WHERE BrandName = 'Honda'
  AND NOT EXISTS (SELECT 1 FROM tblvehicles WHERE VehiclesTitle = 'Civic');

INSERT INTO tblvehicles
  (VehiclesTitle, VehiclesBrand, VehiclesOverview, PricePerDay, FuelType, ModelYear, SeatingCapacity)
SELECT 'Qashqai', id, 'A versatile crossover suited to city trips and longer drives.', 72.00, 'Petrol', 2022, 5
FROM tblbrands WHERE BrandName = 'Nissan'
  AND NOT EXISTS (SELECT 1 FROM tblvehicles WHERE VehiclesTitle = 'Qashqai');

INSERT INTO tblvehicles
  (VehiclesTitle, VehiclesBrand, VehiclesOverview, PricePerDay, FuelType, ModelYear, SeatingCapacity)
SELECT '3 Series', id, 'A premium sedan offering a composed and engaging drive.', 110.00, 'Petrol', 2023, 5
FROM tblbrands WHERE BrandName = 'BMW'
  AND NOT EXISTS (SELECT 1 FROM tblvehicles WHERE VehiclesTitle = '3 Series');

INSERT INTO tblvehicles
  (VehiclesTitle, VehiclesBrand, VehiclesOverview, PricePerDay, FuelType, ModelYear, SeatingCapacity)
SELECT 'A4', id, 'A spacious premium sedan with a quiet, comfortable cabin.', 105.00, 'Diesel', 2022, 5
FROM tblbrands WHERE BrandName = 'Audi'
  AND NOT EXISTS (SELECT 1 FROM tblvehicles WHERE VehiclesTitle = 'A4');
