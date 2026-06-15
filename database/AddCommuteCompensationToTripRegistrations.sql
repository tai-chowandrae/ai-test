ALTER TABLE tripregistrations
  ADD COLUMN ApplyCommuteCompensation TINYINT(1) NOT NULL DEFAULT 0 AFTER IsRoundTrip;
