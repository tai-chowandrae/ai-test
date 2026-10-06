ALTER TABLE users
  ADD COLUMN IsCommuteCompensationEnabled TINYINT(1) NOT NULL DEFAULT 1 AFTER IsActive,
  ADD COLUMN CommuteCompensationKilometers DECIMAL(10,2) NOT NULL DEFAULT 20.00 AFTER IsCommuteCompensationEnabled;

UPDATE users
SET IsCommuteCompensationEnabled = 1,
    CommuteCompensationKilometers = 20.00;
