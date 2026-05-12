-- =====================================================================
-- Plik instalacyjny bazy danych: database.sql
-- Projekt: Salon fryzjerski / barber – system rezerwacji
-- Autor: Volodymyr Kot
-- Opis:
--   Skrypt jest w pełni REUŻYWALNY – można go uruchomić wielokrotnie
--   bez błędów. Tabele są usuwane (DROP IF EXISTS) w odpowiedniej
--   kolejności (ze względu na klucze obce), a następnie tworzone od nowa
--   wraz z przykładowymi danymi.
-- =====================================================================

-- ----------------------------------------------------------------------
-- 1. Utworzenie bazy danych
-- ----------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS salon_rezerwacje
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE salon_rezerwacje;

-- ----------------------------------------------------------------------
-- 2. Usunięcie istniejących tabel (zachowując kolejność zależności)
--    Dzięki temu skrypt można uruchamiać wielokrotnie.
-- ----------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS workers;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------
-- 3. Tabela: workers (pracownicy / barberzy)
-- ----------------------------------------------------------------------
CREATE TABLE workers (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    full_name    VARCHAR(120)  NOT NULL,
    role         ENUM('barber', 'worker', 'manager') NOT NULL DEFAULT 'barber',
    email        VARCHAR(100)  NOT NULL UNIQUE,
    phone        VARCHAR(30),
    hire_date    DATE          NOT NULL,
    is_active    TINYINT(1)    NOT NULL DEFAULT 1,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_workers_email   CHECK (email LIKE '%@%.%'),
    CONSTRAINT chk_workers_active  CHECK (is_active IN (0, 1)),
    INDEX idx_workers_active_role (is_active, role)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------
-- 4. Tabela: users (zarejestrowani klienci)
-- ----------------------------------------------------------------------
CREATE TABLE users (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(50)  NOT NULL UNIQUE,
    email          VARCHAR(100) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    full_name      VARCHAR(120),
    phone          VARCHAR(30),
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_users_email     CHECK (email LIKE '%@%.%'),
    CONSTRAINT chk_users_username  CHECK (CHAR_LENGTH(username) >= 3)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------
-- 5. Tabela: services (oferowane usługi / zabiegi)
-- ----------------------------------------------------------------------
CREATE TABLE services (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(80)    NOT NULL UNIQUE,
    description       TEXT,
    duration_minutes  INT            NOT NULL,
    price             DECIMAL(8, 2)  NOT NULL,
    is_active         TINYINT(1)     NOT NULL DEFAULT 1,
    created_at        TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_services_duration CHECK (duration_minutes BETWEEN 5 AND 480),
    CONSTRAINT chk_services_price    CHECK (price >= 0)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------
-- 6. Tabela: appointments (wizyty / rezerwacje)
--    Tabela łącząca: users, workers, services
-- ----------------------------------------------------------------------
CREATE TABLE appointments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NULL,
    worker_id   INT NULL,
    service_id  INT NOT NULL,
    visit_date  DATETIME NOT NULL,
    status      ENUM('pending', 'confirmed', 'completed', 'cancelled')
                NOT NULL DEFAULT 'pending',
    notes       TEXT,
    can_review  TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_appointments_review CHECK (can_review IN (0, 1)),
    CONSTRAINT fk_appointments_user
        FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_appointments_worker
        FOREIGN KEY (worker_id)  REFERENCES workers(id)  ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_appointments_service
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_appointments_visit_date (visit_date),
    INDEX idx_appointments_status     (status)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------
-- 7. Tabela: reviews (opinie po wizytach)
--    Jedna opinia na jedną wizytę.
-- ----------------------------------------------------------------------
CREATE TABLE reviews (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id  INT NOT NULL UNIQUE,
    rating          INT NOT NULL,
    comment         TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_reviews_appointment
        FOREIGN KEY (appointment_id) REFERENCES appointments(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- Włączenie harmonogramu zdarzeń (wymagane dla Eventów)
SET GLOBAL event_scheduler = ON;

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

-- =====================================================================
-- 8. PRZYKŁADOWE DANE TESTOWE
-- =====================================================================

-- ----- Pracownicy (workers) -----
INSERT INTO workers (full_name, role, email, phone, hire_date, is_active) VALUES
('Adam Kowalczyk',    'barber',  'adam.barber@salon.pl',     '+48 500 100 200', '2022-03-15', 1),
('Marek Wojciechowski','barber', 'marek.barber@salon.pl',    '+48 500 100 201', '2021-09-01', 1),
('Tomasz Lewandowski','barber',  'tomasz.barber@salon.pl',   '+48 500 100 203', '2023-05-20', 1),
('Kasia Zielińska',   'worker',  'kasia.reception@salon.pl', '+48 500 100 202', '2020-11-10', 1),
('Robert Nowicki',    'manager', 'robert.manager@salon.pl',  '+48 500 100 204', '2019-01-07', 1);

-- ----- Klienci (users) -----
-- Hashe haseł są przykładowe (bcrypt-like). W aplikacji generuje je PHP password_hash().
INSERT INTO users (username, email, password_hash, full_name, phone) VALUES
('jan_kowalski',  'jan@example.com',     '$2y$10$exampleHash1aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'Jan Kowalski',     '+48 600 111 222'),
('anna_nowak',    'anna@example.com',    '$2y$10$exampleHash2bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'Anna Nowak',       '+48 600 111 223'),
('piotr_wis',     'piotr@example.com',   '$2y$10$exampleHash3ccccccccccccccccccccccccccccccccccc', 'Piotr Wiśniewski', '+48 600 111 224'),
('ewa_dabrowska', 'ewa@example.com',     '$2y$10$exampleHash4ddddddddddddddddddddddddddddddddddd', 'Ewa Dąbrowska',    '+48 600 111 225'),
('michal_kam',    'michal@example.com',  '$2y$10$exampleHash5eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', 'Michał Kamiński',  '+48 600 111 226');

-- ----- Usługi (services) -----
INSERT INTO services (name, description, duration_minutes, price, is_active) VALUES
('Signature Cut',   'Klasyczne męskie strzyżenie z konsultacją i stylizacją.',     45,  90.00, 1),
('Executive Reset', 'Strzyżenie premium z masażem skóry głowy i pielęgnacją.',     60, 150.00, 1),
('Beard Ritual',    'Pełna pielęgnacja brody – strzyżenie, golenie, olejki.',      30,  70.00, 1),
('Hot Towel Shave', 'Tradycyjne golenie brzytwą z gorącym ręcznikiem.',            40, 110.00, 1),
('Junior Cut',      'Strzyżenie dla dzieci do 12 lat.',                            25,  50.00, 1);

-- ----- Wizyty (appointments) – 7 rekordów (5 zakończonych + 2 przyszłe) -----
INSERT INTO appointments (user_id, worker_id, service_id, visit_date, status, notes, can_review) VALUES
(1, 1, 1, DATE_SUB(NOW(), INTERVAL 30 DAY), 'completed', 'Klient stały, krótkie boki.',          1),
(2, 2, 2, DATE_SUB(NOW(), INTERVAL 20 DAY), 'completed', 'Pakiet executive – pełna usługa.',     1),
(3, 1, 3, DATE_SUB(NOW(), INTERVAL 15 DAY), 'completed', 'Trymowanie brody, lekka pielęgnacja.', 1),
(2, 1, 3, DATE_SUB(NOW(), INTERVAL 10 DAY), 'completed', 'Druga wizyta tej klientki.',           1),
(3, 2, 1, DATE_SUB(NOW(), INTERVAL  8 DAY), 'completed', 'Krótka korekta strzyżenia.',           1),
(4, 3, 4, DATE_ADD(NOW(), INTERVAL  2 DAY), 'confirmed', 'Pierwsza wizyta klientki.',            0),
(5, 2, 5, DATE_ADD(NOW(), INTERVAL  5 DAY), 'pending',   'Wizyta z dzieckiem, godz. 10:00.',     0);

-- ----- Opinie (reviews) – po jednej na każdą zakończoną wizytę -----
INSERT INTO reviews (appointment_id, rating, comment) VALUES
(1, 5, 'Świetne strzyżenie, bardzo profesjonalna obsługa!'),
(2, 4, 'Dobry zabieg, choć wizyta trwała trochę dłużej niż planowano.'),
(3, 5, 'Mistrzowska robota – broda jak nowa, polecam!'),
(4, 4, 'Solidnie wykonana usługa, miła atmosfera.'),
(5, 5, 'Najlepszy salon w mieście, wracam co miesiąc!');

-- =====================================================================
-- KONIEC SKRYPTU
-- =====================================================================
