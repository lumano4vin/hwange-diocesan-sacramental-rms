-- Hwange Diocese Records Management System (RMS)
-- Database Schema v1.0

CREATE DATABASE IF NOT EXISTS hwange_diocesan_records;
USE hwange_diocesan_records;

-- 1. Users Table (RBAC)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    role ENUM('admin', 'chancellor', 'priest', 'deacon', 'secretary') NOT NULL,
    parish_id INT DEFAULT NULL, -- NULL for diocesan-wide admins
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- 2. Parishes Table
CREATE TABLE IF NOT EXISTS parishes (
    parish_id INT AUTO_INCREMENT PRIMARY KEY,
    parish_name VARCHAR(100) NOT NULL,
    location VARCHAR(255),
    deanery VARCHAR(50), -- e.g., Hwange Urban, Victoria Falls, etc.
    priest_in_charge_id INT, -- Link back to users table
    contact_number VARCHAR(20)
);

-- 3. Parishioners Table (Core Member Data)
CREATE TABLE IF NOT EXISTS parishioners (
    person_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    other_names VARCHAR(100),
    gender ENUM('Male', 'Female') NOT NULL,
    dob DATE NOT NULL,
    place_of_birth VARCHAR(255),
    father_name VARCHAR(100),
    mother_name VARCHAR(100),
    mother_maiden_name VARCHAR(100), -- Required by Canon 877
    current_parish_id INT,
    title VARCHAR(50), -- e.g., Rev. Fr., Sr., Br.
    status ENUM('Active', 'Deceased', 'Moved') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (current_parish_id) REFERENCES parishes(parish_id)
);

-- 4. Baptisms Table (Canon 877)
CREATE TABLE IF NOT EXISTS baptisms (
    baptism_id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    parish_id INT NOT NULL,
    date_of_baptism DATE NOT NULL,
    minister VARCHAR(100) NOT NULL, -- Priest/Deacon who conferred it
    godparents VARCHAR(255),
    witnesses VARCHAR(255),
    register_book_number VARCHAR(50),
    page_number VARCHAR(50),
    entry_number VARCHAR(50),
    verification_hash VARCHAR(64) UNIQUE,
    status ENUM('Valid', 'Conditional', 'Private') DEFAULT 'Valid',
    FOREIGN KEY (person_id) REFERENCES parishioners(person_id),
    FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
);

-- 5. Confirmations Table
CREATE TABLE IF NOT EXISTS confirmations (
    confirmation_id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    parish_id INT NOT NULL,
    date_of_confirmation DATE NOT NULL,
    minister VARCHAR(100),
    sponsor VARCHAR(100),
    register_book_number VARCHAR(50),
    page_number VARCHAR(50),
    entry_number VARCHAR(50),
    verification_hash VARCHAR(64) UNIQUE,
    FOREIGN KEY (person_id) REFERENCES parishioners(person_id),
    FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
);

-- 6. Marriages Table
CREATE TABLE IF NOT EXISTS marriages (
    marriage_id INT AUTO_INCREMENT PRIMARY KEY,
    groom_person_id INT NOT NULL,
    bride_person_id INT NOT NULL,
    parish_id INT NOT NULL,
    date_of_marriage DATE NOT NULL,
    officiant VARCHAR(100),
    witnesses_names TEXT,
    convalidation_date DATE NULL,
    register_book_number VARCHAR(50),
    page_number VARCHAR(50),
    entry_number VARCHAR(50),
    verification_hash VARCHAR(64) UNIQUE,
    FOREIGN KEY (groom_person_id) REFERENCES parishioners(person_id),
    FOREIGN KEY (bride_person_id) REFERENCES parishioners(person_id),
    FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
);

-- 7. Notations Table (Canon 535)
-- Used for appending canonical milestones to the baptismal record
CREATE TABLE IF NOT EXISTS sacraments_notations (
    notation_id INT AUTO_INCREMENT PRIMARY KEY,
    baptism_id INT NOT NULL,
    sacrament_type ENUM('Confirmation', 'Marriage', 'Holy Orders', 'Death', 'Nullity', 'Other') NOT NULL,
    event_date DATE NOT NULL,
    parish_name VARCHAR(100), -- Where the sacrament occurred
    details TEXT, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (baptism_id) REFERENCES baptisms(baptism_id)
);

-- 8. Audit logs (System Integrity)
CREATE TABLE IF NOT EXISTS audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL, -- CREATE, UPDATE, DELETE, LOGIN
    table_name VARCHAR(50),
    record_id INT,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- 9. Funeral / Death Registry
CREATE TABLE IF NOT EXISTS deaths (
    death_id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    parish_id INT NOT NULL,
    date_of_death DATE NOT NULL,
    date_of_burial DATE,
    place_of_burial VARCHAR(255),
    minister VARCHAR(255),
    register_book_number VARCHAR(50),
    page_number VARCHAR(50),
    entry_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES parishioners(person_id),
    FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
);

-- 10. Ordination & Religious Profession Registry (Canon 1053)
CREATE TABLE IF NOT EXISTS ordinations_professions (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    parish_id INT NOT NULL, -- Parish of celebration
    record_type ENUM('Diaconate', 'Priesthood', 'Episcopate', 'First Vows', 'Perpetual Profession') NOT NULL,
    congregation VARCHAR(255), -- For religious brothers/sisters
    event_date DATE NOT NULL,
    celebrant_superior VARCHAR(255),
    place VARCHAR(255),
    register_book_number VARCHAR(50),
    page_number VARCHAR(50),
    entry_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES parishioners(person_id),
    FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
);

-- 11. First Holy Communion Registry
CREATE TABLE IF NOT EXISTS first_holy_communions (
    communion_id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    parish_id INT NOT NULL,
    date_of_communion DATE NOT NULL,
    minister_name VARCHAR(255),
    register_book_number VARCHAR(50),
    page_number VARCHAR(50),
    entry_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES parishioners(person_id),
    FOREIGN KEY (parish_id) REFERENCES parishes(parish_id)
);

-- Initial Inserts (Official Parishes and Missions)
INSERT INTO parishes (parish_name, location, deanery) VALUES 
('St. Ignatius Cathedral', 'Hwange', 'Hwange Urban'),
('Holy Family', 'Hwange', 'Hwange Urban'),
('Our Lady of Peace', 'Hwange', 'Hwange Urban'),
('Mary Help of Christians', 'Don Bosco, Hwange', 'Hwange Urban'),
('Ss. Peter and Paul', 'Hwange', 'Hwange Urban'),
('St. Charles Lwanga', 'Hwange', 'Hwange Urban'),
('St. Francis Xavier', 'Dete', 'Dete'),
('St. Francis of Assisi', 'Cross Dete', 'Dete'),
('St. George', 'Hwange', 'Hwange Urban'),
('St. Joachim', 'Hwange', 'Hwange Urban'),
('St. Joseph', 'Hwange', 'Hwange Urban'),
('St. Josephine Bakhita', 'Victoria Falls', 'Victoria Falls'),
('St. Kizito', 'Hwange', 'Hwange Urban'),
('St. Monica', 'Hwange', 'Hwange Urban'),
('St. Teresa', 'Hwange', 'Hwange Urban'),
('All Souls', 'Binga', 'Binga'),
('Divine Mercy', 'Lubimbi', 'Binga'),
('Holy Cross', 'Lusulu', 'Binga'),
('Mary Immaculate', 'Gomoza', 'Lupane'),
('Our Lady of Fatima', 'Fatima', 'Lupane'),
('Sacred Heart', 'Jambezi', 'Jambezi'),
('St. Augustine', 'Mzola', 'Lupane'),
('St. Cecilia', 'Tshongokwe', 'Lupane'),
('St. Faustine', 'Matetsi', 'Victoria Falls'),
('St. John the Baptist (Dandanda)', 'Dandanda', 'Lupane'),
('St. John the Baptist (Makwa)', 'Makwa', 'Makwa'),
('St. John Vianney', 'Kariangwe', 'Binga'),
('St. Luke', 'Chisuma', 'Victoria Falls'),
('St. Mathew', 'Dambwamkulu', 'Binga'),
('St. Mark', 'Nagangala', 'Binga'),
('St. Martin de Porres', 'Jotsholo', 'Lupane'),
('St. Mary', 'Lukosi', 'Hwange Urban'),
('St. Michael', 'Kasibo', 'Hwange Urban'),
('St. Padre Pio', 'Siacilaba', 'Binga'),
('St. Therese', 'Kamativi', 'Dete');

-- Mock Admin (password: password123)
-- In real app, we use password_hash(password, PASSWORD_DEFAULT)
INSERT INTO users (username, password_hash, full_name, role) VALUES 
('admin', '$2y$10$4g.zZSQ9VN/0CI7ZfXgvcu2hcn22nM9bNrZMzxCH5QjK58o6PlAg.', 'Diocesan Administrator', 'admin');
