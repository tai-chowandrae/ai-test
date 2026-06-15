CREATE TABLE Locations (
  LocationId INT AUTO_INCREMENT PRIMARY KEY,
  Name VARCHAR(150) NOT NULL,
  GooglePlaceId VARCHAR(255) NOT NULL,
  FormattedAddress VARCHAR(500) NOT NULL,
  DefaultTripDescription TEXT NULL,
  IsActive TINYINT(1) NOT NULL DEFAULT 1,
  Latitude DECIMAL(10, 7) NULL,
  Longitude DECIMAL(10, 7) NULL,
  CreatedAt DATETIME NOT NULL,
  UNIQUE KEY UniqueLocationsGooglePlaceId (GooglePlaceId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE TripRegistrations (
  TripRegistrationId INT AUTO_INCREMENT PRIMARY KEY,
  UserId INT NOT NULL,
  TripDate DATE NOT NULL,
  StartLocationId INT NOT NULL,
  EndLocationId INT NOT NULL,
  IsRoundTrip TINYINT(1) NOT NULL DEFAULT 0,
  ApplyCommuteCompensation TINYINT(1) NOT NULL DEFAULT 0,
  TripDescription TEXT NULL,
  DistanceMeters INT NOT NULL,
  DistanceKilometers DECIMAL(10, 2) NOT NULL,
  CreatedAt DATETIME NOT NULL,
  CONSTRAINT ForeignTripRegistrationsUserId FOREIGN KEY (UserId) REFERENCES Users (UserId),
  CONSTRAINT ForeignTripRegistrationsStartLocationId FOREIGN KEY (StartLocationId) REFERENCES Locations (LocationId),
  CONSTRAINT ForeignTripRegistrationsEndLocationId FOREIGN KEY (EndLocationId) REFERENCES Locations (LocationId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
