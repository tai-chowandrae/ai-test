-- KM2WORK live migration - 2026-10-06
-- Maakt geen tabellen leeg en verwijdert geen gebruikers, locaties of ritten.
-- Maak voor uitvoering altijd eerst een volledige back-up van de live database.

SET @DatabaseName = DATABASE();

-- Koppel locaties aan hun eigenaar. Bestaande locaties worden van gebruiker 1.
SET @HasLocationUserId = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @DatabaseName
    AND TABLE_NAME = 'locations'
    AND COLUMN_NAME = 'UserId'
);
SET @Sql = IF(
  @HasLocationUserId = 0,
  'ALTER TABLE locations ADD COLUMN UserId INT NULL AFTER LocationId',
  'SELECT 1'
);
PREPARE Statement FROM @Sql;
EXECUTE Statement;
DEALLOCATE PREPARE Statement;

UPDATE locations
SET UserId = 1
WHERE UserId IS NULL;

-- Alleen bij de eerste migratie worden de reeds aanwezige ritten aan gebruiker 1 gekoppeld.
-- Bij herhaald uitvoeren blijven later aangemaakte ritten van andere gebruikers ongemoeid.
UPDATE tripregistrations
SET UserId = 1
WHERE @HasLocationUserId = 0;

ALTER TABLE locations
  MODIFY UserId INT NOT NULL;

-- Dezelfde Google-locatie mag meerdere keren en door meerdere gebruikers worden gebruikt.
SET @HasOldGooglePlaceIndex = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @DatabaseName
    AND TABLE_NAME = 'locations'
    AND INDEX_NAME = 'UniqueLocationsGooglePlaceId'
);
SET @Sql = IF(
  @HasOldGooglePlaceIndex > 0,
  'ALTER TABLE locations DROP INDEX UniqueLocationsGooglePlaceId',
  'SELECT 1'
);
PREPARE Statement FROM @Sql;
EXECUTE Statement;
DEALLOCATE PREPARE Statement;

SET @HasCompositeGooglePlaceIndex = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @DatabaseName
    AND TABLE_NAME = 'locations'
    AND INDEX_NAME = 'UniqueLocationsUserGooglePlaceId'
);
SET @Sql = IF(
  @HasCompositeGooglePlaceIndex > 0,
  'ALTER TABLE locations DROP INDEX UniqueLocationsUserGooglePlaceId',
  'SELECT 1'
);
PREPARE Statement FROM @Sql;
EXECUTE Statement;
DEALLOCATE PREPARE Statement;

SET @HasLocationUserIndex = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @DatabaseName
    AND TABLE_NAME = 'locations'
    AND INDEX_NAME = 'ForeignLocationsUserId'
);
SET @Sql = IF(
  @HasLocationUserIndex = 0,
  'ALTER TABLE locations ADD KEY ForeignLocationsUserId (UserId)',
  'SELECT 1'
);
PREPARE Statement FROM @Sql;
EXECUTE Statement;
DEALLOCATE PREPARE Statement;

SET @HasLocationUserConstraint = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = @DatabaseName
    AND TABLE_NAME = 'locations'
    AND CONSTRAINT_NAME = 'ForeignLocationsUserId'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @Sql = IF(
  @HasLocationUserConstraint = 0,
  'ALTER TABLE locations ADD CONSTRAINT ForeignLocationsUserId FOREIGN KEY (UserId) REFERENCES users (UserId)',
  'SELECT 1'
);
PREPARE Statement FROM @Sql;
EXECUTE Statement;
DEALLOCATE PREPARE Statement;

-- Gebruikers kunnen worden gedeactiveerd zonder gegevens te verwijderen.
SET @HasUserIsActive = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @DatabaseName
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'IsActive'
);
SET @Sql = IF(
  @HasUserIsActive = 0,
  'ALTER TABLE users ADD COLUMN IsActive TINYINT(1) NOT NULL DEFAULT 1 AFTER IsAdmin',
  'SELECT 1'
);
PREPARE Statement FROM @Sql;
EXECUTE Statement;
DEALLOCATE PREPARE Statement;

-- Persoonlijke instellingen voor woon-werkcompensatie.
SET @HasCommuteEnabled = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @DatabaseName
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'IsCommuteCompensationEnabled'
);
SET @Sql = IF(
  @HasCommuteEnabled = 0,
  'ALTER TABLE users ADD COLUMN IsCommuteCompensationEnabled TINYINT(1) NOT NULL DEFAULT 1 AFTER IsActive',
  'SELECT 1'
);
PREPARE Statement FROM @Sql;
EXECUTE Statement;
DEALLOCATE PREPARE Statement;

SET @HasCommuteKilometers = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @DatabaseName
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'CommuteCompensationKilometers'
);
SET @Sql = IF(
  @HasCommuteKilometers = 0,
  'ALTER TABLE users ADD COLUMN CommuteCompensationKilometers DECIMAL(10,2) NOT NULL DEFAULT 20.00 AFTER IsCommuteCompensationEnabled',
  'SELECT 1'
);
PREPARE Statement FROM @Sql;
EXECUTE Statement;
DEALLOCATE PREPARE Statement;

-- Bestaande records krijgen de afgesproken beginwaarden.
UPDATE users
SET IsActive = COALESCE(IsActive, 1),
    IsCommuteCompensationEnabled = COALESCE(IsCommuteCompensationEnabled, 1),
    CommuteCompensationKilometers = COALESCE(CommuteCompensationKilometers, 20.00);

-- Controleoverzicht na uitvoering.
SELECT COUNT(*) AS UsersTotal,
       SUM(IsActive = 1) AS ActiveUsers,
       SUM(IsCommuteCompensationEnabled = 1) AS CommuteCompensationEnabledUsers
FROM users;

SELECT UserId, COUNT(*) AS LocationCount
FROM locations
GROUP BY UserId
ORDER BY UserId;

SELECT UserId, COUNT(*) AS TripCount
FROM tripregistrations
GROUP BY UserId
ORDER BY UserId;
