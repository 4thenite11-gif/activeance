USE activeance_db;

ALTER TABLE settings
    ADD COLUMN stat_years     VARCHAR(20) DEFAULT '10+'  AFTER phone,
    ADD COLUMN stat_cars      VARCHAR(20) DEFAULT '500+' AFTER stat_years,
    ADD COLUMN stat_inspected VARCHAR(20) DEFAULT '100%' AFTER stat_cars,
    ADD COLUMN stat_support   VARCHAR(20) DEFAULT '24/7' AFTER stat_inspected;