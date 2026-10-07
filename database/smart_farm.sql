CREATE DATABASE IF NOT EXISTS smart_farm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_farm;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS shop_order_status_log;DROP TABLE IF EXISTS shop_order_items;DROP TABLE IF EXISTS shop_orders;DROP TABLE IF EXISTS customer_profiles;DROP TABLE IF EXISTS production_collections;DROP TABLE IF EXISTS shop_products;DROP TABLE IF EXISTS job_applications;DROP TABLE IF EXISTS jobs;DROP TABLE IF EXISTS applicant_profiles;DROP TABLE IF EXISTS work_attendance;DROP TABLE IF EXISTS approval_log;DROP TABLE IF EXISTS manager_salary_requests;DROP TABLE IF EXISTS expense_claims;DROP TABLE IF EXISTS manager_cash_ledger;DROP TABLE IF EXISTS manager_cash_requests;DROP TABLE IF EXISTS account_transactions;DROP TABLE IF EXISTS accounts;DROP TABLE IF EXISTS purchase_catalog;DROP TABLE IF EXISTS inventory_movements;DROP TABLE IF EXISTS inventory_usage;DROP TABLE IF EXISTS medicine_usage;DROP TABLE IF EXISTS feed_logs;DROP TABLE IF EXISTS delivery_assignments;DROP TABLE IF EXISTS attendance;DROP TABLE IF EXISTS staff_tasks;DROP TABLE IF EXISTS farm_stocking;DROP TABLE IF EXISTS staff_payments;DROP TABLE IF EXISTS expenses;DROP TABLE IF EXISTS income;DROP TABLE IF EXISTS purchases;DROP TABLE IF EXISTS inventory_items;DROP TABLE IF EXISTS stores;DROP TABLE IF EXISTS staff;DROP TABLE IF EXISTS farms;DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;
CREATE TABLE users(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100) NOT NULL,email VARCHAR(150) NOT NULL UNIQUE,phone VARCHAR(30),password VARCHAR(255) NOT NULL,role ENUM('owner','manager','staff','customer','applicant') NOT NULL,status ENUM('active','inactive') DEFAULT 'active',profile_image VARCHAR(255),nid_front VARCHAR(255),nid_back VARCHAR(255),verification_status ENUM('unverified','pending','verified','rejected') DEFAULT 'unverified',ui_theme VARCHAR(20) NOT NULL DEFAULT 'green',ui_mode ENUM('light','dark') NOT NULL DEFAULT 'light',last_activity DATETIME,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
INSERT INTO users(name,email,phone,password,role,status,verification_status) VALUES ('Farm Owner','owner@farm.local','01700000000','$2y$12$PIpP2aB/SFHPJ0/EOo9ivOxUPon1dBWr6e0719vFoJJsFV8xzpmXm','owner','active','verified');
CREATE TABLE farms(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL,category VARCHAR(120) NOT NULL,location VARCHAR(255),start_date DATE,image VARCHAR(255),status ENUM('active','inactive') DEFAULT 'active',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE staff(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL UNIQUE,staff_type ENUM('farm_worker','delivery_man','multipurpose') NOT NULL,monthly_salary DECIMAL(12,2) DEFAULT 0,shift_start TIME DEFAULT '08:00:00',shift_end TIME DEFAULT '17:00:00',preferred_payment_method ENUM('cash','mfs','bank') DEFAULT 'cash',mfs_provider VARCHAR(50),mfs_number VARCHAR(30),bank_name VARCHAR(100),bank_account_name VARCHAR(120),bank_account_number VARCHAR(80),status ENUM('active','inactive') DEFAULT 'active',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id));
CREATE TABLE stores(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL,purpose VARCHAR(120),farm_id INT NULL,location VARCHAR(255),notes TEXT,status ENUM('active','inactive') DEFAULT 'active',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(farm_id) REFERENCES farms(id));
CREATE TABLE inventory_items(id INT AUTO_INCREMENT PRIMARY KEY,store_id INT NOT NULL,item_name VARCHAR(150) NOT NULL,item_type VARCHAR(80) NOT NULL,for_category VARCHAR(80),unit VARCHAR(30) NOT NULL,current_qty DECIMAL(14,3) DEFAULT 0,minimum_qty DECIMAL(14,3) DEFAULT 0,unit_cost DECIMAL(14,2) DEFAULT 0,expiry_date DATE NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(store_id) REFERENCES stores(id));
CREATE TABLE purchase_catalog(id INT AUTO_INCREMENT PRIMARY KEY,farm_category VARCHAR(80) NOT NULL,purchase_type VARCHAR(80) NOT NULL,item_name VARCHAR(150) NOT NULL,default_unit VARCHAR(30) DEFAULT 'KG',stockable TINYINT(1) DEFAULT 1,status ENUM('active','inactive') DEFAULT 'active');
CREATE TABLE accounts(id INT AUTO_INCREMENT PRIMARY KEY,account_name VARCHAR(120) NOT NULL,account_type ENUM('cash','bank','mfs') NOT NULL,provider VARCHAR(120),account_number VARCHAR(100),balance DECIMAL(14,2) DEFAULT 0,status ENUM('active','inactive') DEFAULT 'active',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE account_transactions(id INT AUTO_INCREMENT PRIMARY KEY,account_id INT NOT NULL,direction ENUM('CREDIT','DEBIT') NOT NULL,amount DECIMAL(14,2) NOT NULL,transaction_type VARCHAR(80),reference_type VARCHAR(80),reference_id INT,note VARCHAR(255),created_by INT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(account_id) REFERENCES accounts(id),FOREIGN KEY(created_by) REFERENCES users(id));
CREATE TABLE purchases(id INT AUTO_INCREMENT PRIMARY KEY,farm_id INT NULL,store_id INT NULL,inventory_item_id INT NULL,catalog_item_id INT NULL,purchase_type VARCHAR(80) NOT NULL,item_name VARCHAR(150) NOT NULL,qty DECIMAL(14,3) NOT NULL,unit VARCHAR(30),unit_price DECIMAL(14,2),total_amount DECIMAL(14,2),supplier VARCHAR(150),purchase_date DATE NOT NULL,receipt VARCHAR(255),notes TEXT,created_by INT NOT NULL,status ENUM('pending','approved','rejected') DEFAULT 'pending',payment_source ENUM('account','manager_cash') DEFAULT 'account',account_id INT NULL,approved_by INT NULL,approved_at DATETIME,rejection_reason VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(farm_id) REFERENCES farms(id),FOREIGN KEY(store_id) REFERENCES stores(id),FOREIGN KEY(inventory_item_id) REFERENCES inventory_items(id),FOREIGN KEY(catalog_item_id) REFERENCES purchase_catalog(id),FOREIGN KEY(created_by) REFERENCES users(id),FOREIGN KEY(account_id) REFERENCES accounts(id),FOREIGN KEY(approved_by) REFERENCES users(id));
CREATE TABLE income(id INT AUTO_INCREMENT PRIMARY KEY,farm_id INT NULL,source VARCHAR(120) NOT NULL,amount DECIMAL(12,2) NOT NULL,income_date DATE NOT NULL,note VARCHAR(255),created_by INT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(farm_id) REFERENCES farms(id),FOREIGN KEY(created_by) REFERENCES users(id));
CREATE TABLE expenses(id INT AUTO_INCREMENT PRIMARY KEY,farm_id INT NULL,category VARCHAR(120) NOT NULL,amount DECIMAL(12,2) NOT NULL,expense_date DATE NOT NULL,note VARCHAR(255),purchase_id INT NULL,claim_id INT NULL,approval_status ENUM('approved') DEFAULT 'approved',approved_by INT,approved_at DATETIME,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(farm_id) REFERENCES farms(id),FOREIGN KEY(purchase_id) REFERENCES purchases(id),FOREIGN KEY(approved_by) REFERENCES users(id));
CREATE TABLE farm_stocking(id INT AUTO_INCREMENT PRIMARY KEY,farm_id INT NOT NULL,stock_type VARCHAR(50),species VARCHAR(120),quantity DECIMAL(14,3),unit VARCHAR(30),purchase_id INT NULL,stock_date DATE,notes TEXT,FOREIGN KEY(farm_id) REFERENCES farms(id),FOREIGN KEY(purchase_id) REFERENCES purchases(id));
CREATE TABLE staff_payments(id INT AUTO_INCREMENT PRIMARY KEY,staff_id INT NOT NULL,amount DECIMAL(12,2) NOT NULL,payment_month CHAR(7) NOT NULL,payment_method ENUM('cash','mfs','bank') NOT NULL,transaction_reference VARCHAR(150),status ENUM('pending','approved','rejected','failed') DEFAULT 'pending',requested_by INT,account_id INT NULL,approved_by INT NULL,approved_at DATETIME,cash_collected TINYINT(1) DEFAULT 0,cash_collected_at DATETIME,note VARCHAR(255),paid_at DATETIME,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(staff_id) REFERENCES staff(id),FOREIGN KEY(requested_by) REFERENCES users(id),FOREIGN KEY(account_id) REFERENCES accounts(id),FOREIGN KEY(approved_by) REFERENCES users(id));
CREATE TABLE staff_tasks(id INT AUTO_INCREMENT PRIMARY KEY,staff_id INT NOT NULL,farm_id INT NULL,title VARCHAR(150) NOT NULL,details TEXT,task_date DATE NOT NULL,task_start TIME,task_end TIME,status ENUM('pending','in_progress','completed') DEFAULT 'pending',created_by INT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(staff_id) REFERENCES staff(id),FOREIGN KEY(farm_id) REFERENCES farms(id),FOREIGN KEY(created_by) REFERENCES users(id));
CREATE TABLE attendance(id INT AUTO_INCREMENT PRIMARY KEY,staff_id INT NOT NULL,attendance_date DATE NOT NULL,check_in DATETIME NULL,check_out DATETIME NULL,status ENUM('present','absent','leave') DEFAULT 'present',UNIQUE KEY uniq_attendance(staff_id,attendance_date),FOREIGN KEY(staff_id) REFERENCES staff(id));
CREATE TABLE delivery_assignments(id INT AUTO_INCREMENT PRIMARY KEY,staff_id INT NOT NULL,customer_name VARCHAR(120),customer_phone VARCHAR(30),address VARCHAR(255),order_ref VARCHAR(80),delivery_date DATE,start_time TIME,end_time TIME,status ENUM('assigned','started','delivered','cancelled') DEFAULT 'assigned',created_by INT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(staff_id) REFERENCES staff(id),FOREIGN KEY(created_by) REFERENCES users(id));
CREATE TABLE feed_logs(id INT AUTO_INCREMENT PRIMARY KEY,farm_id INT NOT NULL,pond_or_area VARCHAR(120),inventory_item_id INT NOT NULL,staff_id INT NOT NULL,qty DECIMAL(14,3),feed_time TIME,feed_date DATE,cost DECIMAL(14,2),status ENUM('submitted','approved','rejected') DEFAULT 'submitted',notes TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(farm_id) REFERENCES farms(id),FOREIGN KEY(inventory_item_id) REFERENCES inventory_items(id),FOREIGN KEY(staff_id) REFERENCES staff(id));
CREATE TABLE medicine_usage(id INT AUTO_INCREMENT PRIMARY KEY,farm_id INT NOT NULL,target_area VARCHAR(120),inventory_item_id INT NOT NULL,staff_id INT NOT NULL,qty DECIMAL(14,3),reason VARCHAR(255),usage_date DATE,cost DECIMAL(14,2),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(farm_id) REFERENCES farms(id),FOREIGN KEY(inventory_item_id) REFERENCES inventory_items(id),FOREIGN KEY(staff_id) REFERENCES staff(id));
CREATE TABLE inventory_usage(id INT AUTO_INCREMENT PRIMARY KEY,inventory_item_id INT NOT NULL,farm_id INT NULL,staff_id INT NULL,qty_used DECIMAL(14,3),usage_date DATE,usage_type VARCHAR(50),reference_id INT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(inventory_item_id) REFERENCES inventory_items(id),FOREIGN KEY(farm_id) REFERENCES farms(id),FOREIGN KEY(staff_id) REFERENCES staff(id));
CREATE TABLE inventory_movements(id INT AUTO_INCREMENT PRIMARY KEY,inventory_item_id INT NOT NULL,movement_type ENUM('IN','OUT','ADJUST') NOT NULL,qty DECIMAL(14,3),reference_type VARCHAR(50),reference_id INT,movement_date DATE,note VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(inventory_item_id) REFERENCES inventory_items(id));
CREATE TABLE manager_cash_requests(id INT AUTO_INCREMENT PRIMARY KEY,manager_user_id INT NOT NULL,amount DECIMAL(14,2) NOT NULL,purpose VARCHAR(255) NOT NULL,farm_id INT NULL,source_account_id INT NULL,status ENUM('pending','approved','rejected') DEFAULT 'pending',requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,approved_by INT NULL,approved_at DATETIME,rejection_reason VARCHAR(255),FOREIGN KEY(manager_user_id) REFERENCES users(id),FOREIGN KEY(farm_id) REFERENCES farms(id),FOREIGN KEY(source_account_id) REFERENCES accounts(id),FOREIGN KEY(approved_by) REFERENCES users(id));
CREATE TABLE manager_cash_ledger(id INT AUTO_INCREMENT PRIMARY KEY,manager_user_id INT NOT NULL,direction ENUM('CREDIT','DEBIT') NOT NULL,amount DECIMAL(14,2) NOT NULL,reference_type VARCHAR(80),reference_id INT,note VARCHAR(255),created_by INT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(manager_user_id) REFERENCES users(id),FOREIGN KEY(created_by) REFERENCES users(id));
CREATE TABLE expense_claims(id INT AUTO_INCREMENT PRIMARY KEY,submitted_by INT NOT NULL,farm_id INT NULL,category VARCHAR(100) NOT NULL,title VARCHAR(150) NOT NULL,amount DECIMAL(14,2) NOT NULL,expense_date DATE NOT NULL,document VARCHAR(255) NOT NULL,notes TEXT,payment_source ENUM('account','manager_cash') DEFAULT 'account',account_id INT NULL,status ENUM('pending','approved','rejected') DEFAULT 'pending',approved_by INT NULL,approved_at DATETIME,rejection_reason VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(submitted_by) REFERENCES users(id),FOREIGN KEY(farm_id) REFERENCES farms(id),FOREIGN KEY(account_id) REFERENCES accounts(id),FOREIGN KEY(approved_by) REFERENCES users(id));
CREATE TABLE manager_salary_requests(id INT AUTO_INCREMENT PRIMARY KEY,manager_user_id INT NOT NULL,payment_month CHAR(7) NOT NULL,amount DECIMAL(14,2) NOT NULL,account_id INT NULL,status ENUM('pending','approved','paid','rejected') DEFAULT 'pending',approved_by INT NULL,approved_at DATETIME,paid_at DATETIME,note VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uniq_mgr_month(manager_user_id,payment_month),FOREIGN KEY(manager_user_id) REFERENCES users(id),FOREIGN KEY(account_id) REFERENCES accounts(id),FOREIGN KEY(approved_by) REFERENCES users(id));
CREATE TABLE approval_log(id INT AUTO_INCREMENT PRIMARY KEY,module_name VARCHAR(80),reference_id INT,action ENUM('submitted','approved','rejected') NOT NULL,action_by INT,note VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(action_by) REFERENCES users(id));
INSERT INTO farms(name,category,location,start_date,status) VALUES('Cow Farm','Cow','Main Farm','2026-01-01','active'),('Rice Production','Rice','Field A','2026-02-01','active'),('Fish Farm','Fish','Pond Zone','2026-03-01','active'),('Poultry Farm','Poultry','Shed A','2026-04-01','active');
INSERT INTO stores(name,purpose,farm_id,location,status) VALUES('Fish Feed Store','Fish Feed',3,'Pond Zone','active'),('Medicine Store','Medicine',NULL,'Main Store','active'),('Rice Seed Store','Rice Seed',2,'Field Warehouse','active'),('Livestock Feed Store','Livestock Feed',1,'Cow Shed','active'),('Poultry Store','Poultry Feed & Medicine',4,'Shed A','active');
INSERT INTO inventory_items(store_id,item_name,item_type,for_category,unit,current_qty,minimum_qty,unit_cost) VALUES(1,'Floating Fish Feed','Feed','Fish','KG',500,150,65),(2,'General Fish Medicine','Medicine','Fish','Litre',30,8,480),(2,'Cattle Medicine','Medicine','Cow','Bottle',20,5,350),(3,'BRRI Rice Seed','Seed','Rice','KG',250,50,80),(4,'Cattle Feed','Feed','Cow','KG',800,200,55),(5,'Poultry Starter Feed','Feed','Poultry','KG',300,80,72);
INSERT INTO purchase_catalog(farm_category,purchase_type,item_name,default_unit,stockable) VALUES
('Cow','Feed','Green Grass','KG',1),('Cow','Feed','Hay / Straw','KG',1),('Cow','Feed','Bran / Bhushi','KG',1),('Cow','Feed','Concentrate Feed','KG',1),('Cow','Medicine','Cattle Medicine','Bottle',1),('Cow','Medicine','Vaccine','Dose',1),('Cow','Livestock','Cow Purchase','Animal',0),('Cow','Equipment','Cow Farm Equipment','Piece',1),
('Fish','Feed','Floating Fish Feed','KG',1),('Fish','Feed','Sinking Fish Feed','KG',1),('Fish','Fish Seed','Fingerling / Fish Seed','Fish',0),('Fish','Medicine','Fish Medicine','Litre',1),('Fish','Medicine','Water Treatment','KG',1),('Fish','Equipment','Net / Pond Equipment','Piece',1),
('Poultry','Feed','Starter Feed','KG',1),('Poultry','Feed','Grower Feed','KG',1),('Poultry','Feed','Layer Feed','KG',1),('Poultry','Poultry','Chick / Bird Purchase','Bird',0),('Poultry','Medicine','Poultry Medicine','Bottle',1),('Poultry','Medicine','Poultry Vaccine','Dose',1),('Poultry','Equipment','Poultry Equipment','Piece',1),
('Rice','Rice Seed','Rice Seed','KG',1),('Rice','Fertilizer','Urea Fertilizer','KG',1),('Rice','Fertilizer','TSP Fertilizer','KG',1),('Rice','Medicine','Pesticide / Herbicide','Litre',1),('Rice','Equipment','Rice Farming Equipment','Piece',1);
INSERT INTO accounts(account_name,account_type,provider,account_number,balance) VALUES('Main Cash','cash','Cash','Office Cash',100000),('Farm Bank','bank','Bank','XXXX-001',500000),('Farm bKash','mfs','bKash','01XXXXXXXXX',75000);


CREATE TABLE work_attendance(
 id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,work_date DATE NOT NULL,check_in DATETIME NULL,check_out DATETIME NULL,status ENUM('present','absent','leave') DEFAULT 'present',UNIQUE KEY uniq_user_day(user_id,work_date),FOREIGN KEY(user_id) REFERENCES users(id)
);
CREATE TABLE applicant_profiles(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL UNIQUE,address VARCHAR(255),skills TEXT,education TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id));
CREATE TABLE jobs(id INT AUTO_INCREMENT PRIMARY KEY,title VARCHAR(150) NOT NULL,job_type VARCHAR(100) NOT NULL,description TEXT,requirements TEXT,application_deadline DATE NOT NULL,status ENUM('open','closed') DEFAULT 'open',created_by INT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(created_by) REFERENCES users(id));
CREATE TABLE job_applications(id INT AUTO_INCREMENT PRIMARY KEY,job_id INT NOT NULL,applicant_user_id INT NOT NULL,cover_note TEXT,cv_file VARCHAR(255) NOT NULL,status ENUM('submitted','under_review','shortlisted','interview_scheduled','selected','rejected') DEFAULT 'submitted',interview_at DATETIME NULL,interview_note VARCHAR(255),reviewed_by INT NULL,applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uniq_job_applicant(job_id,applicant_user_id),FOREIGN KEY(job_id) REFERENCES jobs(id),FOREIGN KEY(applicant_user_id) REFERENCES users(id),FOREIGN KEY(reviewed_by) REFERENCES users(id));
INSERT INTO jobs(title,job_type,description,requirements,application_deadline,status,created_by)
SELECT 'Farm Worker','Farm Worker','Support daily farm operations, feeding, cleaning, stock handling and task reporting.','Basic farm work knowledge and willingness to follow schedules.',DATE_ADD(CURDATE(),INTERVAL 30 DAY),'open',id FROM users WHERE role='owner' LIMIT 1;

-- PUBLIC SHOP / CUSTOMER MODULE
CREATE TABLE shop_products(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(140) NOT NULL,category VARCHAR(80) NOT NULL,icon VARCHAR(30),image_path VARCHAR(255) NULL,description TEXT NULL,staff_discount_eligible TINYINT(1) DEFAULT 1,unit VARCHAR(40) NOT NULL,price DECIMAL(12,2) DEFAULT 0,stock_qty DECIMAL(12,2) DEFAULT 0,status ENUM('draft','active','hidden','out_of_stock') DEFAULT 'draft',created_by INT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(created_by) REFERENCES users(id));
CREATE TABLE production_collections(id INT AUTO_INCREMENT PRIMARY KEY,product_id INT NOT NULL,staff_user_id INT NOT NULL,qty DECIMAL(12,2) NOT NULL,fish_weight_kg DECIMAL(10,2) NULL,note VARCHAR(255),collected_at DATETIME DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(product_id) REFERENCES shop_products(id),FOREIGN KEY(staff_user_id) REFERENCES users(id));
CREATE TABLE customer_profiles(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT UNIQUE NOT NULL,address TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id));
CREATE TABLE shop_orders(id INT AUTO_INCREMENT PRIMARY KEY,customer_user_id INT NOT NULL,total_amount DECIMAL(12,2) NOT NULL,status ENUM('pending','confirmed','preparing','ready_for_delivery','out_for_delivery','delivered','cancelled') DEFAULT 'pending',delivery_address TEXT,phone VARCHAR(30),assigned_staff_id INT NULL,delivery_accepted_at DATETIME NULL,delivered_at DATETIME NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(customer_user_id) REFERENCES users(id),FOREIGN KEY(assigned_staff_id) REFERENCES staff(id));
CREATE TABLE shop_order_items(id INT AUTO_INCREMENT PRIMARY KEY,order_id INT NOT NULL,product_id INT NOT NULL,product_name VARCHAR(140),qty DECIMAL(12,2),unit_price DECIMAL(12,2),line_total DECIMAL(12,2),FOREIGN KEY(order_id) REFERENCES shop_orders(id),FOREIGN KEY(product_id) REFERENCES shop_products(id));


-- ORDER STATUS HISTORY
CREATE TABLE shop_order_status_log(id INT AUTO_INCREMENT PRIMARY KEY,order_id INT NOT NULL,status VARCHAR(50) NOT NULL,note VARCHAR(255),changed_by INT NULL,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(order_id) REFERENCES shop_orders(id),FOREIGN KEY(changed_by) REFERENCES users(id));


-- SMART OPS / INCIDENT / NOTIFICATION / COUPON MODULE
CREATE TABLE incident_reports(
 id INT AUTO_INCREMENT PRIMARY KEY,
 reporter_user_id INT NOT NULL,
 farm_id INT NULL,
 incident_type ENUM('animal_health','fish_health','crop_problem','product_problem','equipment','delivery','other') DEFAULT 'other',
 title VARCHAR(160) NOT NULL,
 description TEXT NOT NULL,
 image_path VARCHAR(255) NULL,
 urgency ENUM('low','medium','high','critical') DEFAULT 'medium',
 ai_summary TEXT NULL,
 ai_initial_steps TEXT NULL,
 status ENUM('reported','reviewing','action_required','resolved','closed') DEFAULT 'reported',
 manager_user_id INT NULL,
 created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(reporter_user_id) REFERENCES users(id),
 FOREIGN KEY(farm_id) REFERENCES farms(id),
 FOREIGN KEY(manager_user_id) REFERENCES users(id)
);

CREATE TABLE notifications(
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 notification_type VARCHAR(50) NOT NULL,
 title VARCHAR(180) NOT NULL,
 message TEXT NOT NULL,
 link_url VARCHAR(255) NULL,
 reference_type VARCHAR(50) NULL,
 reference_id INT NULL,
 status ENUM('unread','read','accepted','resolved') DEFAULT 'unread',
 created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 read_at DATETIME NULL,
 FOREIGN KEY(user_id) REFERENCES users(id)
);

CREATE TABLE coupons(
 id INT AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(50) UNIQUE NOT NULL,
 discount_type ENUM('percent','fixed') DEFAULT 'percent',
 discount_value DECIMAL(10,2) NOT NULL,
 minimum_order DECIMAL(12,2) DEFAULT 0,
 starts_at DATETIME NULL,
 ends_at DATETIME NULL,
 usage_limit INT NULL,
 used_count INT DEFAULT 0,
 status ENUM('active','inactive') DEFAULT 'active',
 created_by INT NULL,
 created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(created_by) REFERENCES users(id)
);

CREATE TABLE staff_shop_orders(
 id INT AUTO_INCREMENT PRIMARY KEY,
 staff_user_id INT NOT NULL,
 subtotal DECIMAL(12,2) NOT NULL,
 staff_discount_percent DECIMAL(5,2) DEFAULT 10,
 discount_amount DECIMAL(12,2) DEFAULT 0,
 coupon_code VARCHAR(50) NULL,
 coupon_discount DECIMAL(12,2) DEFAULT 0,
 total_amount DECIMAL(12,2) NOT NULL,
 status ENUM('pending','approved','prepared','collected','cancelled') DEFAULT 'pending',
 note VARCHAR(255) NULL,
 created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(staff_user_id) REFERENCES users(id)
);

INSERT INTO coupons(code,discount_type,discount_value,minimum_order,status)
VALUES ('WELCOME10','percent',10,0,'active');


-- FINAL ECOMMERCE EXTENSIONS
ALTER TABLE shop_products ADD COLUMN farm_id INT NULL AFTER id;
ALTER TABLE shop_products ADD CONSTRAINT fk_shop_product_farm FOREIGN KEY(farm_id) REFERENCES farms(id);
ALTER TABLE shop_products ADD COLUMN delivery_scope ENUM('local10km','nationwide','pickup') DEFAULT 'nationwide' AFTER staff_discount_eligible;
ALTER TABLE shop_products ADD COLUMN preorder_enabled TINYINT(1) DEFAULT 0 AFTER delivery_scope;
ALTER TABLE shop_products ADD COLUMN available_after TIME NULL AFTER preorder_enabled;
ALTER TABLE shop_orders ADD COLUMN alternate_phone VARCHAR(30) NULL AFTER phone;
ALTER TABLE shop_orders ADD COLUMN delivery_method ENUM('local_delivery','courier','pickup') DEFAULT 'local_delivery' AFTER alternate_phone;
ALTER TABLE shop_orders ADD COLUMN delivery_charge DECIMAL(10,2) DEFAULT 0 AFTER delivery_method;
ALTER TABLE shop_orders ADD COLUMN payment_method ENUM('cash_on_delivery','cash','bank','mfs') DEFAULT 'cash_on_delivery' AFTER delivery_charge;
ALTER TABLE shop_orders ADD COLUMN payment_status ENUM('unpaid','paid') DEFAULT 'unpaid' AFTER payment_method;
ALTER TABLE shop_orders ADD COLUMN preorder TINYINT(1) DEFAULT 0 AFTER payment_status;
ALTER TABLE shop_orders ADD COLUMN customer_note VARCHAR(255) NULL AFTER preorder;
ALTER TABLE shop_orders ADD COLUMN preorder_date DATE NULL AFTER preorder;
ALTER TABLE shop_orders ADD COLUMN payment_provider ENUM('cash','bkash','nagad','rocket','bank') DEFAULT 'cash' AFTER payment_method;
ALTER TABLE shop_orders ADD COLUMN payment_reference VARCHAR(120) NULL AFTER payment_provider;
CREATE TABLE live_chat_threads(id INT AUTO_INCREMENT PRIMARY KEY,customer_user_id INT NOT NULL,product_id INT NULL,status ENUM('open','closed') DEFAULT 'open',created_at DATETIME DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(customer_user_id) REFERENCES users(id),FOREIGN KEY(product_id) REFERENCES shop_products(id));
CREATE TABLE live_chat_messages(id INT AUTO_INCREMENT PRIMARY KEY,thread_id INT NOT NULL,sender_user_id INT NOT NULL,message TEXT NOT NULL,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(thread_id) REFERENCES live_chat_threads(id),FOREIGN KEY(sender_user_id) REFERENCES users(id));
CREATE TABLE delivery_earnings(id INT AUTO_INCREMENT PRIMARY KEY,staff_user_id INT NOT NULL,order_id INT NOT NULL UNIQUE,amount DECIMAL(10,2) NOT NULL,status ENUM('pending','credited') DEFAULT 'credited',created_at DATETIME DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(staff_user_id) REFERENCES users(id),FOREIGN KEY(order_id) REFERENCES shop_orders(id));



-- FINAL DELIVERY COMMERCE EXTENSION
CREATE TABLE IF NOT EXISTS delivery_chat_messages (
 id INT AUTO_INCREMENT PRIMARY KEY,
 order_id INT NOT NULL,
 sender_user_id INT NOT NULL,
 message TEXT NOT NULL,
 created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 INDEX(order_id), FOREIGN KEY(order_id) REFERENCES shop_orders(id),
 FOREIGN KEY(sender_user_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS delivery_product_offers (
 id INT AUTO_INCREMENT PRIMARY KEY,
 order_id INT NOT NULL, delivery_user_id INT NOT NULL, customer_user_id INT NOT NULL,
 product_id INT NOT NULL, qty DECIMAL(10,2) NOT NULL DEFAULT 1,
 unit_price DECIMAL(10,2) NOT NULL, status ENUM('offered','accepted','declined','fulfilled') DEFAULT 'offered',
 created_at DATETIME DEFAULT CURRENT_TIMESTAMP, responded_at DATETIME NULL,
 INDEX(order_id), FOREIGN KEY(order_id) REFERENCES shop_orders(id),
 FOREIGN KEY(delivery_user_id) REFERENCES users(id), FOREIGN KEY(customer_user_id) REFERENCES users(id),
 FOREIGN KEY(product_id) REFERENCES shop_products(id)
);
CREATE TABLE IF NOT EXISTS delivery_tips (
 id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL UNIQUE, customer_user_id INT NOT NULL,
 delivery_user_id INT NOT NULL, amount DECIMAL(10,2) NOT NULL DEFAULT 0,
 payment_method ENUM('cash','bkash','nagad','other_mfs') DEFAULT 'cash',
 reference_no VARCHAR(100) NULL, status ENUM('pending','confirmed') DEFAULT 'confirmed',
 created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(order_id) REFERENCES shop_orders(id), FOREIGN KEY(customer_user_id) REFERENCES users(id),
 FOREIGN KEY(delivery_user_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS delivery_collections (
 id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL UNIQUE, delivery_user_id INT NOT NULL,
 bill_amount DECIMAL(10,2) NOT NULL, collected_amount DECIMAL(10,2) NOT NULL,
 payment_method ENUM('cash','bkash','nagad','other_mfs') DEFAULT 'cash',
 reference_no VARCHAR(100) NULL, collected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 handover_status ENUM('held','submitted','verified') DEFAULT 'held',
 submitted_at DATETIME NULL, verified_at DATETIME NULL,
 FOREIGN KEY(order_id) REFERENCES shop_orders(id), FOREIGN KEY(delivery_user_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS delivery_settlements (
 id INT AUTO_INCREMENT PRIMARY KEY, delivery_user_id INT NOT NULL,
 collection_total DECIMAL(10,2) NOT NULL DEFAULT 0, retained_delivery_earning DECIMAL(10,2) NOT NULL DEFAULT 0,
 retained_tips DECIMAL(10,2) NOT NULL DEFAULT 0, amount_forwarded DECIMAL(10,2) NOT NULL DEFAULT 0,
 method ENUM('cash_handover','bank','bkash','nagad','other_mfs') DEFAULT 'cash_handover',
 reference_no VARCHAR(100) NULL, status ENUM('submitted','verified','rejected') DEFAULT 'submitted',
 manager_user_id INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, verified_at DATETIME NULL,
 FOREIGN KEY(delivery_user_id) REFERENCES users(id), FOREIGN KEY(manager_user_id) REFERENCES users(id)
);


-- FARM-MANAGER OPERATING MODEL 2026
CREATE TABLE IF NOT EXISTS farm_managers(id INT AUTO_INCREMENT PRIMARY KEY,farm_id INT NOT NULL,manager_user_id INT NOT NULL,assigned_by INT NULL,assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,status ENUM('active','inactive') DEFAULT 'active',FOREIGN KEY(farm_id) REFERENCES farms(id),FOREIGN KEY(manager_user_id) REFERENCES users(id));
CREATE TABLE IF NOT EXISTS farm_workers(id INT AUTO_INCREMENT PRIMARY KEY,farm_id INT NOT NULL,manager_user_id INT NOT NULL,worker_code VARCHAR(30) UNIQUE,name VARCHAR(120) NOT NULL,phone VARCHAR(30) NOT NULL,nid_number VARCHAR(50),identification_number VARCHAR(80),address VARCHAR(255),nid_image VARCHAR(255),pay_type ENUM('daily','monthly') DEFAULT 'daily',pay_rate DECIMAL(12,2) DEFAULT 0,join_date DATE NOT NULL,leave_date DATE NULL,status ENUM('active','left','inactive') DEFAULT 'active',notes VARCHAR(255),created_at DATETIME DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(farm_id) REFERENCES farms(id),FOREIGN KEY(manager_user_id) REFERENCES users(id));
CREATE TABLE IF NOT EXISTS farm_worker_attendance(id INT AUTO_INCREMENT PRIMARY KEY,worker_id INT NOT NULL,attendance_date DATE NOT NULL,status ENUM('present','absent','leave') DEFAULT 'present',note VARCHAR(255),marked_by INT NOT NULL,UNIQUE KEY uniq_worker_day(worker_id,attendance_date),FOREIGN KEY(worker_id) REFERENCES farm_workers(id),FOREIGN KEY(marked_by) REFERENCES users(id));
CREATE TABLE IF NOT EXISTS farm_worker_payments(id INT AUTO_INCREMENT PRIMARY KEY,worker_id INT NOT NULL,farm_id INT NOT NULL,pay_period VARCHAR(30) NOT NULL,days_count DECIMAL(8,2) DEFAULT 0,amount DECIMAL(12,2) NOT NULL,payment_method ENUM('cash','mfs','bank') DEFAULT 'cash',reference_no VARCHAR(100),paid_by INT NOT NULL,paid_at DATETIME DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(worker_id) REFERENCES farm_workers(id),FOREIGN KEY(farm_id) REFERENCES farms(id),FOREIGN KEY(paid_by) REFERENCES users(id));
CREATE TABLE IF NOT EXISTS farm_daily_capacity(id INT AUTO_INCREMENT PRIMARY KEY,farm_id INT NOT NULL,product_id INT NOT NULL,capacity_date DATE NOT NULL,expected_qty DECIMAL(12,2) NOT NULL,preorder_limit DECIMAL(12,2) NOT NULL,pickup_start TIME,pickup_end TIME,created_by INT NOT NULL,UNIQUE KEY uniq_capacity(product_id,capacity_date),FOREIGN KEY(farm_id) REFERENCES farms(id),FOREIGN KEY(product_id) REFERENCES shop_products(id));
CREATE TABLE IF NOT EXISTS marketplace_seller_applications(id INT AUTO_INCREMENT PRIMARY KEY,farm_name VARCHAR(150) NOT NULL,applicant_name VARCHAR(120) NOT NULL,phone VARCHAR(30) NOT NULL,email VARCHAR(150),address VARCHAR(255) NOT NULL,product_types VARCHAR(255) NOT NULL,description TEXT,nid_or_trade VARCHAR(100),document_path VARCHAR(255),status ENUM('submitted','under_review','approved','rejected') DEFAULT 'submitted',reviewed_by INT,reviewed_at DATETIME,created_at DATETIME DEFAULT CURRENT_TIMESTAMP);


CREATE TABLE IF NOT EXISTS manager_permissions(
 id INT AUTO_INCREMENT PRIMARY KEY,
 manager_user_id INT NOT NULL,
 permission_key VARCHAR(60) NOT NULL,
 allowed TINYINT(1) NOT NULL DEFAULT 1,
 updated_by INT NULL,
 updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uniq_manager_permission(manager_user_id,permission_key),
 FOREIGN KEY(manager_user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ATTENDANCE/OWNER WORKFORCE UPDATE 2026
ALTER TABLE farm_workers ADD COLUMN profile_image VARCHAR(255) NULL AFTER address;
