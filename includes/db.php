<?php
// includes/db.php
// Nawiązanie połączenia z bazą danych (PDO)

$host = '127.0.0.1';
$port = '8889'; // MAMP default MySQL port
$db   = 'salon_rezerwacje';
$user = 'root'; // Domyślny użytkownik w XAMPP / MAMP
$pass = 'root'; // UWAGA: Jeśli używasz MAMP na Macu, wpisz 'root'. Jeśli XAMPP/Herd/DBngin, zostaw puste: ''
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Rzuca wyjątek w razie błędu i zapobiega wyciekowi danych
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>