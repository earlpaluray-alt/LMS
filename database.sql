CREATE DATABASE IF NOT EXISTS marine_biology_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE marine_biology_lms;

CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(150) NOT NULL,
 id_number VARCHAR(80) NOT NULL UNIQUE,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','user') NOT NULL DEFAULT 'user',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE equipment (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(180) NOT NULL,
 category VARCHAR(100) NOT NULL,
 variant VARCHAR(150) NULL,
 total_qty INT UNSIGNED NULL,
 damaged_qty INT UNSIGNED NOT NULL DEFAULT 0,
 quantity_note VARCHAR(255) NULL,
 image VARCHAR(255) NOT NULL DEFAULT 'photo.png',
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_equipment_name(name), INDEX idx_equipment_category(category)
) ENGINE=InnoDB;

CREATE TABLE reservations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 room VARCHAR(120) NOT NULL,
 purpose VARCHAR(255) NULL,
 reserved_from DATETIME NOT NULL,
 reserved_until DATETIME NOT NULL,
 status ENUM('pending','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE RESTRICT,
 INDEX idx_reservation_dates(reserved_from,reserved_until), INDEX idx_reservation_user(user_id)
) ENGINE=InnoDB;

CREATE TABLE reservation_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 reservation_id BIGINT UNSIGNED NOT NULL,
 equipment_id INT UNSIGNED NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 FOREIGN KEY(reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
 FOREIGN KEY(equipment_id) REFERENCES equipment(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE loans (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 room VARCHAR(120) NOT NULL,
 purpose VARCHAR(255) NULL,
 borrowed_at DATETIME NULL,
 due_at DATETIME NULL,
 returned_at DATETIME NULL,
 status ENUM('pending','approved','borrowed','returned','rejected') NOT NULL DEFAULT 'pending',
 reservation_id BIGINT UNSIGNED NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE RESTRICT,
 FOREIGN KEY(reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,
 INDEX idx_loan_status(status), INDEX idx_loan_user(user_id)
) ENGINE=InnoDB;

CREATE TABLE loan_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 loan_id BIGINT UNSIGNED NOT NULL,
 equipment_id INT UNSIGNED NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 returned_quantity INT UNSIGNED NOT NULL DEFAULT 0,
 damaged_quantity INT UNSIGNED NOT NULL DEFAULT 0,
 FOREIGN KEY(loan_id) REFERENCES loans(id) ON DELETE CASCADE,
 FOREIGN KEY(equipment_id) REFERENCES equipment(id) ON DELETE RESTRICT,
 INDEX idx_loan_item_equipment(equipment_id)
) ENGINE=InnoDB;

CREATE TABLE damage_reports (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 loan_id BIGINT UNSIGNED NULL,
 equipment_id INT UNSIGNED NOT NULL,
 reported_by INT UNSIGNED NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 description TEXT NOT NULL,
 status ENUM('reported','resolved') NOT NULL DEFAULT 'reported',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(loan_id) REFERENCES loans(id) ON DELETE SET NULL,
 FOREIGN KEY(equipment_id) REFERENCES equipment(id) ON DELETE RESTRICT,
 FOREIGN KEY(reported_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NULL,
 action VARCHAR(120) NOT NULL,
 entity_type VARCHAR(80) NULL,
 entity_id BIGINT UNSIGNED NULL,
 details TEXT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_audit_created(created_at)
) ENGINE=InnoDB;

INSERT INTO users(name,id_number,email,password_hash,role) VALUES
('System Administrator','ADMIN-001','admin@lms.local','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4QhW0j5z2Yf9d7b1K9h1xR6m8pYqQqG','admin');

-- Password above is intentionally replaced by setup.php on first run.

INSERT INTO equipment(name,category,variant,total_qty,quantity_note) VALUES
('Erlenmeyer Flask','Glassware','1000 mL',5,NULL),('Erlenmeyer Flask','Glassware','500 mL',4,NULL),('Erlenmeyer Flask','Glassware','250 mL',8,NULL),('Erlenmeyer Flask','Glassware','125 mL',4,'Source: 2 + 1 + 1'),
('Beaker','Glassware','150 mL',4,NULL),('Beaker','Glassware','250 mL',21,NULL),('Beaker','Glassware','400 mL',4,NULL),('Beaker','Glassware','1000 mL',7,NULL),('Beaker','Glassware','100 mL',8,NULL),('Beaker','Glassware','50 mL (entry 1)',3,NULL),('Beaker','Glassware','600 mL',2,NULL),('Beaker','Glassware','50 mL (entry 2)',8,NULL),
('Graduated Cylinder','Glassware','250 mL',4,NULL),('Graduated Cylinder','Glassware','100 mL',5,NULL),('Graduated Cylinder','Glassware','50 mL',3,NULL),('Graduated Cylinder','Glassware','10 mL',1,NULL),
('Test Tube','Glassware','40 mL',6,NULL),('Test Tube','Glassware','25 mL',35,NULL),('Funnel','Glassware',NULL,8,NULL),('Test Tube Rack','Laboratory Tools',NULL,NULL,'Source quantity: [?]'),('Alcohol Lamp','Laboratory Tools',NULL,23,NULL),
('Volumetric Flask','Glassware','500 mL',3,NULL),('Volumetric Flask','Glassware','250 mL',5,NULL),('Volumetric Flask','Glassware','100 mL',3,NULL),('Volumetric Flask','Glassware','125 mL w/ reglo',1,NULL),('Volumetric Flask','Glassware','250 mL w/ reglo',1,NULL),
('Florence','Glassware','250 mL',2,NULL),('Reagent Bottle','Glassware',NULL,3,NULL),('Erlenmeyer Flask w/ Nozzle','Glassware',NULL,4,NULL),('Desiccator','Glassware',NULL,2,NULL),('Cover Slip','Microscopy','Square',4,NULL),('Cover Slip','Microscopy','Rectangular',2,NULL),('Glass Slides','Microscopy','6 boxes',6,NULL),('Magnifying Glass','Tools',NULL,3,NULL),('Tally Counter','Tools',NULL,3,NULL),('Dissecting Kit','Tools',NULL,14,NULL),('Thermometer','Instruments',NULL,5,NULL),
('Pipette','Glassware','10 mL',6,NULL),('Pipette','Glassware','5 mL',3,NULL),('Pipette','Glassware','1 mL',6,NULL),('Pipette','Glassware','0.5 mL',2,NULL),('Pipette','Glassware','25 mL',3,NULL),('Dropper','Glassware','5 mL',8,NULL),('Dropper','Glassware','3 mL',2,NULL),('Dropper','Glassware','2 mL',1,NULL),('Petri Dish (Glass)','Glassware','Pairs',60,NULL),('PH and CL Test Kit','Test Kits',NULL,NULL,'Source quantity: [?]'),('Petri Dish (Plastic)','Plasticware',NULL,10,NULL),('Petri Dish (botter)','Plasticware',NULL,2,NULL),('Digital Scale','Instruments',NULL,1,NULL),('Aspirator','Tools',NULL,4,NULL),
('Prepared Slides','Microscopy','Histology and Bacteriology',NULL,'Source lists category only; quantity not specified'),('Prepared Slides','Microscopy','Non-flagellated & non-filamentous algae',NULL,'Source lists category only; quantity not specified'),('Prepared Slides','Microscopy','Flagellated algae',NULL,'Source lists category only; quantity not specified'),('Prepared Slides','Microscopy','Cyanobacteria',NULL,'Source lists category only; quantity not specified'),('Prepared Slides','Microscopy','Filamentous algae',NULL,'Source lists category only; quantity not specified'),('Prepared Slides','Microscopy','diatoms',NULL,'Source lists category only; quantity not specified'),
('Eyepiece','Microscopy','5x',2,NULL),('Eyepiece','Microscopy','10x',1,NULL),('Eyepiece','Microscopy','20x',2,NULL),('Eyepiece','Microscopy','25x',1,NULL),('Eyepiece','Microscopy','Eyepiece cam',1,NULL),('Stirring rod','Glassware',NULL,16,NULL),('Vials','Glassware','1 box',1,NULL),('Coplin Jar','Glassware','Standard',2,NULL),('Coplin Jar','Glassware','Rectangular',6,NULL),('Mortar and Pestle','Tools',NULL,1,NULL),('Inoculating Loop','Tools','entry 1',35,NULL),('Inoculating Loop','Tools','entry 2',4,NULL),('L-Rod','Tools',NULL,16,NULL),
('T-Tube','Glassware','2–3 mL',24,NULL),('T-Tube','Glassware','15 mL',28,NULL),('T-Tube','Glassware','70 mL',12,NULL),('T-Tube','Glassware','unspecified',7,'Source: T-Tube [?] – 5 + 2'),('T-Tube','Glassware','Stainless steel (small)',6,'Source: 5 + 1'),('T-Tube','Glassware','Stainless steel (large)',7,'Source: 5 + 2'),('Beam Balance','Instruments',NULL,1,NULL),('Electronic Balance (Lutron)','Instruments',NULL,2,NULL),('Weighing Balance','Instruments',NULL,NULL,'Source quantity: [?]'),('pH Meter (flat)','Instruments',NULL,1,NULL),('Slotted Masses','Instruments','1 box',1,NULL),('Eloc Stovo','Instruments',NULL,3,NULL),('pH Meter','Instruments',NULL,NULL,'Source quantity: [?]'),('Turbidity Meter','Instruments',NULL,NULL,'Source quantity: [?]'),('Multiparameter','Instruments',NULL,NULL,'Source quantity: [?]'),('Caliper','Tools',NULL,3,NULL),
('Petri Dish (Glass)','Donated Items',NULL,36,'Donated items'),('Inoculating Loop (Plastic)','Donated Items',NULL,4,'Donated items'),('Replacement','Donated Items',NULL,NULL,'Source quantity: [?]'),('Petri Dish (Plastic)','Donated Items',NULL,10,'Donated items'),('Inoculating Loop','Donated Items',NULL,14,'Donated items'),('L-Rod','Donated Items',NULL,14,'Donated items'),
('Triple Beam Balance','Instruments',NULL,9,NULL),('Wash Bottle','Plasticware','Small',4,NULL),('Wash Bottle','Plasticware','Large',2,NULL),('Acid Burette','Glassware',NULL,9,NULL),('Base Burette','Glassware',NULL,NULL,'Source quantity: [?]'),('Column Separator','Glassware',NULL,3,NULL),('Burette Clamp','Tools',NULL,NULL,'Source quantity: [?]'),('Bunsen Burner','Laboratory Tools',NULL,12,NULL),('Spot Plate','Glassware',NULL,6,NULL),('Porcelain Dish','Glassware',NULL,9,NULL),('Transect Tape','Field Equipment',NULL,20,NULL),('Magnifying Glass','Tools','Donated',5,NULL),('tong','Tools',NULL,3,NULL),('T-Tube','Glassware','Donated',5,NULL),('Florence or Volumetric','Glassware','100 mL',1,NULL),('Iron Stand Clamp','Tools',NULL,4,NULL),('Test Tube Rack, Round Plastic','Plasticware',NULL,3,NULL),('T-Tube','Glassware','5m',5,NULL),('Glass','Glassware',NULL,NULL,'Source quantity: [?]'),('Watch Glass','Glassware',NULL,7,NULL),('T-Tube Brush','Tools',NULL,2,NULL),('T-Tube Holder','Tools',NULL,2,NULL),('pipette tips','Consumables','3 boxes',3,NULL),('pipette tips','Consumables','2 packs',2,NULL),('Endo agar base','Consumables','1 bottle',1,NULL),('portable signal generator','Instruments',NULL,3,NULL),('portable electronic scale','Instruments',NULL,8,NULL),('Air stone','Aquaculture Equipment','1 box',1,NULL),('Air stone connector','Aquaculture Equipment',NULL,NULL,'Source quantity: [?]');
