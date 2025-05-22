<?php
require_once __DIR__ . '/db.php';

function generateUniqueSerialNumber($pdo, $maxAttempts = 1000) {
    $prefixes = ['ECE', 'EBY', 'ECB', 'EOD', 'EKS'];
    $attempts = 0;
    do {
        if ($attempts >= $maxAttempts) {
            throw new Exception("Unable to generate a unique serial number after $maxAttempts attempts.");
        }
        $prefix = $prefixes[array_rand($prefixes)];
        $number = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $serial = $prefix . $number;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM passport_serials WHERE serial_number = ?");
        $stmt->execute([$serial]);
        $exists = $stmt->fetchColumn();
        $attempts++;
    } while ($exists > 0);
    return $serial;
}

function generateUniquePassportNumber($pdo, $maxAttempts = 1000) {
    $prefixes = ['HH', 'HK', 'PE', 'HB', 'AH', 'EG', 'WN', 'GJ', 'GA', 'AN', 'VE'];
    $attempts = 0;
    do {
        if ($attempts >= $maxAttempts) {
            throw new Exception("Unable to generate a unique passport number after $maxAttempts attempts.");
        }
        $prefix = $prefixes[array_rand($prefixes)];
        $number = rand(100000, 999999);
        $passport = $prefix . $number;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM passport_serials WHERE passport_number = ?");
        $stmt->execute([$passport]);
        $exists = $stmt->fetchColumn();
        $attempts++;
    } while ($exists > 0);
    return $passport;
}

function savePassportAndSerial($pdo, $passport, $serial) {
    try {
        $stmt = $pdo->prepare("INSERT INTO passport_serials (passport_number, serial_number) VALUES (?, ?)");
        $stmt->execute([$passport, $serial]);
    } catch (PDOException $e) {
        // Handle duplicate entry error (SQLSTATE 23000)
        if ($e->getCode() == 23000) {
            throw new Exception("Duplicate entry detected for passport number or serial number.");
        } else {
            throw $e; // Re-throw exception if it's a different error
        }
    }
}

?>


