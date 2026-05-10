-- =====================================================================
-- Plik: zapytania.sql
-- Projekt: Salon fryzjerski / barber – system rezerwacji
-- Autor: Volodymyr Kot
-- Opis:
--   Zestaw przykładowych zapytań SELECT pokazujących możliwości bazy.
--   Każde zapytanie ma krótki opis, co zwraca i dlaczego jest przydatne.
-- =====================================================================

USE salon_rezerwacje;


-- ---------------------------------------------------------------------
-- ZAPYTANIE 1 – Proste wyszukanie aktywnych usług w cenniku
-- ---------------------------------------------------------------------
-- Co zwraca:
--   Listę wszystkich aktywnych usług (zabiegów) w cenniku, posortowaną
--   rosnąco po cenie.
-- Do czego przydatne:
--   Bezpośrednio pod stronę "Treatments" / cennik – wyświetla aktualną
--   ofertę z czasem trwania i ceną.
-- ---------------------------------------------------------------------
SELECT id, name, duration_minutes, price
FROM services
WHERE is_active = 1
ORDER BY price ASC;


-- ---------------------------------------------------------------------
-- ZAPYTANIE 2 – JOIN: pełne dane wizyt (klient + pracownik + usługa)
-- ---------------------------------------------------------------------
-- Co zwraca:
--   Wszystkie wizyty z dołączonymi danymi klienta, pracownika i nazwy
--   usługi. Sortowane od najnowszej.
-- Do czego przydatne:
--   Panel administracyjny / widok "Reservations" – jedno zapytanie
--   zwraca wszystko, co potrzebne do wyświetlenia tabeli wizyt bez
--   dodatkowych podzapytań.
-- ---------------------------------------------------------------------
SELECT
    a.id            AS appointment_id,
    u.full_name     AS klient,
    u.email         AS email_klienta,
    w.full_name     AS pracownik,
    s.name          AS usluga,
    s.price         AS cena,
    a.visit_date    AS data_wizyty,
    a.status        AS status
FROM appointments a
LEFT JOIN users    u ON u.id = a.user_id
LEFT JOIN workers  w ON w.id = a.worker_id
INNER JOIN services s ON s.id = a.service_id
ORDER BY a.visit_date DESC;


-- ---------------------------------------------------------------------
-- ZAPYTANIE 3 – JOIN + funkcja agregująca: ranking pracowników
-- ---------------------------------------------------------------------
-- Co zwraca:
--   Dla każdego pracownika (barbera) zwraca liczbę zakończonych wizyt
--   oraz średnią ocenę, jaką otrzymał. Sortowane od najlepiej
--   ocenianych.
-- Do czego przydatne:
--   Raport jakości pracy zespołu – kto ma najwięcej wizyt i najwyższą
--   średnią ocenę. Łączy dwa JOIN-y i agregaty COUNT + AVG.
-- ---------------------------------------------------------------------
SELECT
    w.id,
    w.full_name                         AS pracownik,
    COUNT(DISTINCT a.id)                AS liczba_wizyt,
    ROUND(AVG(r.rating), 2)             AS srednia_ocena,
    COUNT(r.id)                         AS liczba_opinii
FROM workers w
LEFT JOIN appointments a
    ON a.worker_id = w.id
   AND a.status = 'completed'
LEFT JOIN reviews r
    ON r.appointment_id = a.id
WHERE w.role = 'barber'
  AND w.is_active = 1
GROUP BY w.id, w.full_name
ORDER BY srednia_ocena DESC, liczba_wizyt DESC;


-- ---------------------------------------------------------------------
-- ZAPYTANIE 4 – Funkcja agregująca: przychód z usług
-- ---------------------------------------------------------------------
-- Co zwraca:
--   Łączny przychód wygenerowany przez każdą usługę (tylko zakończone
--   wizyty), liczbę takich wizyt oraz średnią cenę.
-- Do czego przydatne:
--   Analiza biznesowa – które usługi są najbardziej dochodowe.
--   Wykorzystuje SUM, COUNT i AVG w jednym zapytaniu z grupowaniem.
-- ---------------------------------------------------------------------
SELECT
    s.name                          AS usluga,
    COUNT(a.id)                     AS liczba_wizyt,
    SUM(s.price)                    AS laczny_przychod,
    ROUND(AVG(s.price), 2)          AS srednia_cena
FROM services s
INNER JOIN appointments a ON a.service_id = s.id
WHERE a.status = 'completed'
GROUP BY s.id, s.name
HAVING liczba_wizyt > 0
ORDER BY laczny_przychod DESC;


-- ---------------------------------------------------------------------
-- ZAPYTANIE 5 – Funkcje daty: nadchodzące wizyty w ciągu 7 dni
-- ---------------------------------------------------------------------
-- Co zwraca:
--   Wszystkie potwierdzone lub oczekujące wizyty zaplanowane od dziś
--   do 7 dni do przodu, wraz z liczbą dni do wizyty oraz numerem dnia
--   tygodnia.
-- Do czego przydatne:
--   Dashboard pracownika – „co mnie czeka w tym tygodniu". Pokazuje
--   użycie funkcji daty: NOW(), DATE_ADD, DATEDIFF, DAYNAME, DATE().
-- ---------------------------------------------------------------------
SELECT
    a.id,
    u.full_name                         AS klient,
    s.name                              AS usluga,
    DATE(a.visit_date)                  AS dzien,
    DAYNAME(a.visit_date)               AS dzien_tygodnia,
    TIME(a.visit_date)                  AS godzina,
    DATEDIFF(a.visit_date, NOW())       AS dni_do_wizyty,
    a.status
FROM appointments a
LEFT JOIN users    u ON u.id = a.user_id
INNER JOIN services s ON s.id = a.service_id
WHERE a.visit_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
  AND a.status IN ('pending', 'confirmed')
ORDER BY a.visit_date ASC;


-- ---------------------------------------------------------------------
-- ZAPYTANIE 6 (BONUS) – Klienci, którzy nie wystawili jeszcze opinii
-- ---------------------------------------------------------------------
-- Co zwraca:
--   Listę zakończonych wizyt, dla których nie ma jeszcze opinii,
--   a od daty wizyty minęło już co najmniej 7 dni (klient ma już
--   możliwość ocenienia).
-- Do czego przydatne:
--   Marketing / CRM – wysyłka maila z prośbą o opinię. Łączy LEFT JOIN
--   z warunkiem IS NULL oraz funkcję daty DATE_SUB.
-- ---------------------------------------------------------------------
SELECT
    a.id            AS appointment_id,
    u.full_name     AS klient,
    u.email         AS email,
    s.name          AS usluga,
    a.visit_date,
    DATEDIFF(NOW(), a.visit_date) AS dni_od_wizyty
FROM appointments a
INNER JOIN users    u ON u.id = a.user_id
INNER JOIN services s ON s.id = a.service_id
LEFT  JOIN reviews  r ON r.appointment_id = a.id
WHERE a.status = 'completed'
  AND a.visit_date <= DATE_SUB(NOW(), INTERVAL 7 DAY)
  AND r.id IS NULL
ORDER BY a.visit_date DESC;

-- =====================================================================
-- KONIEC PLIKU
-- =====================================================================
