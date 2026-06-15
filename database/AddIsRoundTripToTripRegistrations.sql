ALTER TABLE tripregistrations
  ADD COLUMN IsRoundTrip TINYINT(1) NOT NULL DEFAULT 0 AFTER EndLocationId;
