ALTER TABLE locations
  ADD COLUMN DefaultTripDescription TEXT NULL AFTER FormattedAddress;

ALTER TABLE tripregistrations
  ADD COLUMN TripDescription TEXT NULL AFTER IsRoundTrip;
