ALTER TABLE locations
  ADD COLUMN UserId INT NULL AFTER LocationId;

UPDATE locations
SET UserId = 1
WHERE UserId IS NULL;

ALTER TABLE locations
  MODIFY UserId INT NOT NULL,
  DROP INDEX UniqueLocationsGooglePlaceId,
  ADD KEY ForeignLocationsUserId (UserId),
  ADD CONSTRAINT ForeignLocationsUserId
    FOREIGN KEY (UserId) REFERENCES users (UserId);
