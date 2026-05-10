-- Plik instalacyjny bazy danych: database.sql
-- Tworzenie bazy danych
CREATE DATABASE IF NOT EXISTS salon_rezerwacje
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE salon_rezerwacje;

-- Tworzenie tabeli pracowników (workers) - barber/worker
CREATE TABLE IF NOT EXISTS workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    role ENUM('barber', 'worker') NOT NULL DEFAULT 'barber',
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(30),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_workers_active_role (is_active, role)
);

-- Tworzenie tabeli wizyt (appointments)
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NULL,
    client_name VARCHAR(100) NOT NULL,
    client_email VARCHAR(100) NOT NULL,
    service_type VARCHAR(50) NOT NULL,
    visit_date DATETIME NOT NULL,
    can_review TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_appointments_worker_id (worker_id),
    CONSTRAINT fk_appointments_worker
        FOREIGN KEY (worker_id) REFERENCES workers(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- Tworzenie tabeli opinii (reviews)
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
);

-- Włączenie harmonogramu zdarzeń (wymagane dla Eventów)
SET GLOBAL event_scheduler = ON;

-- Tworzenie zdarzenia (Event) MySQL "na 6" (7 dni po wizycie)
-- Codziennie sprawdza i uaktywnia możliwość opinii dla wizyt, które odbyły się minimum 7 dni temu
DELIMITER //

CREATE EVENT IF NOT EXISTS update_review_status
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    UPDATE appointments
    SET can_review = 1
    WHERE visit_date <= DATE_SUB(NOW(), INTERVAL 7 DAY)
      AND can_review = 0;
END //

DELIMITER ;

-- Przykładowe dane do testowania (Opcjonalne)
INSERT INTO workers (full_name, role, email, phone, is_active) VALUES
('Adam Barber', 'barber', 'adam.barber@example.com', '+48 500 100 200', 1),
('Marek Barber', 'barber', 'marek.barber@example.com', '+48 500 100 201', 1),
('Kasia Reception', 'worker', 'kasia.reception@example.com', '+48 500 100 202', 1)
ON DUPLICATE KEY UPDATE
full_name = VALUES(full_name),
role = VALUES(role),
phone = VALUES(phone),
is_active = VALUES(is_active),
updated_at = CURRENT_TIMESTAMP;

INSERT INTO appointments (worker_id, client_name, client_email, service_type, visit_date, can_review) VALUES
(1, 'Jan Kowalski', 'jan@example.com', 'Signature Cut', DATE_SUB(NOW(), INTERVAL 8 DAY), 1),
(2, 'Anna Nowak', 'anna@example.com', 'Executive Reset', DATE_SUB(NOW(), INTERVAL 2 DAY), 0),
(1, 'Piotr Wisniewski', 'piotr@example.com', 'Beard Ritual', DATE_SUB(NOW(), INTERVAL 10 DAY), 1);

INSERT INTO reviews (appointment_id, rating, comment) VALUES
(1, 5, 'Świetne strzyżenie, polecam!'),
(3, 4, 'Dobra robota, choć trochę długo czekałem.');

-- Tworzenie tabeli użytkowników (users)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
