ALTER TABLE Locations
  ADD COLUMN DefaultTripDescription TEXT NULL AFTER FormattedAddress;

ALTER TABLE TripRegistrations
  ADD COLUMN TripDescription TEXT NULL AFTER IsRoundTrip;
