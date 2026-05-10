<?php
// includes/functions.php
// Plik zawierający funkcje wbudowane i własne, użycie zmiennych, pętli i tablic w PHP

/**
 * Funkcja własna do formatowania daty na polski format.
 * @param string $datetime
 * @return string
 */
function formatujDateNaPolski($datetime) {
    // Użycie funkcji wbudowanej strtotime i date
    $timestamp = strtotime($datetime);
    return date('d.m.Y H:i', $timestamp);
}

/**
 * Funkcja własna pobierająca wszystkie aktywne opinie.
 * Wykorzystuje tablice do zwracania danych i zmienne obiektowe bazy (PDO).
 * @param PDO $pdo
 * @return array
 */
function pobierzWszystkieOpinie($pdo) {
    $sql = "SELECT r.rating, r.comment, a.client_name, a.visit_date 
            FROM reviews r 
            JOIN appointments a ON r.appointment_id = a.id 
            ORDER BY r.created_at DESC";
            
    $stmt = $pdo->query($sql);
    // Zwraca tablicę (Array) wszystkich wierszy - funkcja wbudowana fetchAll
    return $stmt->fetchAll();
}

/**
 * Funkcja weryfikująca, czy dany klient ma możliwość wystawienia opinii.
 * @param PDO $pdo
 * @param string $email
 * @return array|bool Zwraca tablicę z danymi wizyty lub false
 */
function sprawdzMozliwoscOpinii($pdo, $email) {
    // Zabezpieczenie przed SQL Injection przez bindowanie (funkcja wbudowana PDO)
    $sql = "SELECT id, visit_date, service_type 
            FROM appointments 
            WHERE client_email = :email 
              AND can_review = 1 
              AND id NOT IN (SELECT appointment_id FROM reviews) 
            ORDER BY visit_date ASC LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $wizyta = $stmt->fetch();
    
    return $wizyta ? $wizyta : false;
}

/**
 * Zapisuje rezerwację do bazy danych.
 * @param PDO $pdo
 * @param array $dane Tablica z danymi klienta i wizyty
 * @return bool
 */
function zapiszRezerwacje($pdo, $dane) {
    $sql = "INSERT INTO appointments (worker_id, client_name, client_email, service_type, visit_date) 
            VALUES (:worker_id, :name, :email, :service, :date)";
            
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        'worker_id' => $dane['worker_id'] ?? null,
        'name'    => $dane['name'],
        'email'   => $dane['email'],
        'service' => $dane['service'],
        'date'    => $dane['date']
    ]);
}

/**
 * Zapisuje opinię do bazy danych.
 * @param PDO $pdo
 * @param int $appointmentId
 * @param int $rating
 * @param string $comment
 * @return bool
 */
function zapiszOpinie($pdo, $appointmentId, $rating, $comment) {
    $sql = "INSERT INTO reviews (appointment_id, rating, comment) VALUES (:aid, :rating, :comment)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        'aid'     => $appointmentId,
        'rating'  => $rating,
        'comment' => $comment
    ]);
}

function znajdzUzytkownikaPoEmail($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    return $user ? $user : null;
}

function pobierzRezerwacjePoEmail($pdo, $email) {
    $stmt = $pdo->prepare("SELECT a.id, a.client_name, a.client_email, a.service_type, a.visit_date, w.full_name AS worker_name, w.role AS worker_role
        FROM appointments a
        LEFT JOIN workers w ON a.worker_id = w.id
        WHERE a.client_email = :email
        ORDER BY a.visit_date DESC");
    $stmt->execute(['email' => $email]);
    return $stmt->fetchAll();
}

function pobierzAktywnychPracownikow($pdo, $role = null) {
    if ($role !== null) {
        $stmt = $pdo->prepare("SELECT id, full_name, role FROM workers WHERE is_active = 1 AND role = :role ORDER BY full_name ASC");
        $stmt->execute(['role' => $role]);
        return $stmt->fetchAll();
    }

    $stmt = $pdo->query("SELECT id, full_name, role FROM workers WHERE is_active = 1 ORDER BY role ASC, full_name ASC");
    return $stmt->fetchAll();
}

function anulujRezerwacje($pdo, $appointmentId, $email) {
    $stmt = $pdo->prepare("SELECT id FROM appointments WHERE id = :id AND client_email = :email AND visit_date > NOW() LIMIT 1");
    $stmt->execute(['id' => $appointmentId, 'email' => $email]);
    $row = $stmt->fetch();
    if (!$row) return false;

    $del = $pdo->prepare("DELETE FROM appointments WHERE id = :id AND client_email = :email");
    return $del->execute(['id' => $appointmentId, 'email' => $email]);
}
?>
