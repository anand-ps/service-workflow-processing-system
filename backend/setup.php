<?php
// One-Click Database Setup & Sample Data Generator
// For On-Demand Local Service Booking Application (BCSP-064)

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'service_booking_db';

$messages = [];
$success = true;

try {
    // 1. Connect to MySQL Server (without selecting database yet)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $messages[] = "Connected to MySQL server successfully.";

    // 2. Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $messages[] = "Database '$dbname' verified / created.";

    // 3. Connect to the specific database
    $pdo->exec("USE `$dbname`");

    // 4. Create Tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(120) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(20) NOT NULL,
            `role` ENUM('user', 'provider', 'admin') DEFAULT 'user',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `services` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL UNIQUE,
            `description` TEXT,
            `icon` VARCHAR(50) DEFAULT 'wrench',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `providers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL UNIQUE,
            `service_id` INT NOT NULL,
            `experience_years` INT DEFAULT 1,
            `hourly_rate` DECIMAL(10, 2) NOT NULL DEFAULT 250.00,
            `location` VARCHAR(150) NOT NULL,
            `is_approved` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `bookings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `provider_id` INT NOT NULL,
            `service_id` INT NOT NULL,
            `booking_date` DATE NOT NULL,
            `booking_time` VARCHAR(20) NOT NULL,
            `address` TEXT NOT NULL,
            `problem_desc` TEXT NOT NULL,
            `status` ENUM('Pending', 'Accepted', 'On the Way', 'Completed', 'Rejected') DEFAULT 'Pending',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`provider_id`) REFERENCES `providers`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `feedback` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `booking_id` INT NOT NULL UNIQUE,
            `user_id` INT NOT NULL,
            `provider_id` INT NOT NULL,
            `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
            `comments` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`provider_id`) REFERENCES `providers`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $messages[] = "Database tables (users, services, providers, bookings, feedback) are ready.";

    // 5. Seed Services
    $servicesData = [
        [1, 'Electrician', 'Wiring repair, appliance installation, switches, and short-circuit repair', 'zap'],
        [2, 'Plumber', 'Pipe leak fixes, tap fittings, drain unclogging, and sanitary installations', 'droplet'],
        [3, 'Carpenter', 'Door and window lock fixes, wooden furniture repair, and custom shelves', 'hammer'],
        [4, 'Painter', 'Interior room painting, exterior wall coating, and touch-up fixes', 'brush'],
        [5, 'Mechanic', 'On-spot breakdown repair, two-wheeler servicing, and battery jumpstarts', 'tool'],
        [6, 'Home Cleaning', 'Deep house cleaning, kitchen degreasing, and bathroom sanitation', 'sparkles']
    ];

    $stmtService = $pdo->prepare("INSERT INTO `services` (`id`, `name`, `description`, `icon`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `description`=VALUES(`description`), `icon`=VALUES(`icon`)");
    foreach ($servicesData as $s) {
        $stmtService->execute($s);
    }
    $messages[] = "Service categories seeded successfully.";

    // 6. Seed Admin Account
    $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
    $stmtAdmin = $pdo->prepare("
        INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`)
        VALUES ('System Administrator', 'admin@services.com', ?, '9876543200', 'admin')
        ON DUPLICATE KEY UPDATE `password`=VALUES(`password`), `role`='admin'
    ");
    $stmtAdmin->execute([$adminPass]);

    // 7. Seed Sample Customers
    $userPass = password_hash('user123', PASSWORD_BCRYPT);
    $usersData = [
        ['Rahul Sharma', 'rahul@gmail.com', $userPass, '9876543210', 'user'],
        ['Priya Patel', 'priya@gmail.com', $userPass, '9876543211', 'user']
    ];
    $stmtUser = $pdo->prepare("
        INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE `name`=VALUES(`name`)
    ");
    foreach ($usersData as $u) {
        $stmtUser->execute($u);
    }

    // 8. Seed Service Providers
    $provPass = password_hash('provider123', PASSWORD_BCRYPT);
    $providersUser = [
        ['Rajesh Kumar (Electrician)', 'rajesh@electrician.com', $provPass, '9876543220', 'provider'],
        ['Suresh Verma (Plumber)', 'suresh@plumber.com', $provPass, '9876543221', 'provider'],
        ['Anil Sharma (Carpenter)', 'anil@carpenter.com', $provPass, '9876543222', 'provider'],
        ['Manoj Singh (Painter)', 'manoj@painter.com', $provPass, '9876543223', 'provider']
    ];
    foreach ($providersUser as $pu) {
        $stmtUser->execute($pu);
    }

    // Get user IDs for providers
    $stmtFind = $pdo->prepare("SELECT id, email FROM users WHERE role='provider'");
    $stmtFind->execute();
    $provMap = [];
    while ($row = $stmtFind->fetch(PDO::FETCH_ASSOC)) {
        $provMap[$row['email']] = $row['id'];
    }

    // Insert Provider Specific Info
    // [email, service_id, experience, rate, location, is_approved]
    $providerDetails = [
        ['rajesh@electrician.com', 1, 8, 350.00, 'Sector 14, Metro Area', 1],
        ['suresh@plumber.com', 2, 5, 300.00, 'Civil Lines, Central Area', 1],
        ['anil@carpenter.com', 3, 10, 400.00, 'Model Town, West District', 1],
        ['manoj@painter.com', 4, 3, 250.00, 'Green Park, South District', 0] // Pending approval demonstration
    ];

    $stmtProvDetail = $pdo->prepare("
        INSERT INTO `providers` (`user_id`, `service_id`, `experience_years`, `hourly_rate`, `location`, `is_approved`)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE `experience_years`=VALUES(`experience_years`), `hourly_rate`=VALUES(`hourly_rate`), `is_approved`=VALUES(`is_approved`)
    ");

    foreach ($providerDetails as $pd) {
        if (isset($provMap[$pd[0]])) {
            $stmtProvDetail->execute([
                $provMap[$pd[0]],
                $pd[1],
                $pd[2],
                $pd[3],
                $pd[4],
                $pd[5]
            ]);
        }
    }
    $messages[] = "Demonstration accounts (Admin, Customers, and Providers) created.";

    // 9. Seed Sample Bookings & Reviews for instant evaluation display
    $stmtUserCheck = $pdo->query("SELECT id FROM users WHERE email='rahul@gmail.com'")->fetch();
    $stmtProvCheck = $pdo->query("SELECT id FROM providers WHERE service_id=1 LIMIT 1")->fetch();

    if ($stmtUserCheck && $stmtProvCheck) {
        $uid = $stmtUserCheck['id'];
        $pid = $stmtProvCheck['id'];

        $pdo->exec("
            INSERT INTO `bookings` (`id`, `user_id`, `provider_id`, `service_id`, `booking_date`, `booking_time`, `address`, `problem_desc`, `status`)
            VALUES (1, $uid, $pid, 1, CURDATE(), '10:00 AM', 'Flat 402, Sunshine Apartments, Main Road', 'Main MCB tripping frequently when AC turns on', 'Completed')
            ON DUPLICATE KEY UPDATE `status`='Completed';
        ");

        $pdo->exec("
            INSERT INTO `feedback` (`booking_id`, `user_id`, `provider_id`, `rating`, `comments`)
            VALUES (1, $uid, $pid, 5, 'Quick response and fixed the MCB issue within 30 minutes. Very professional.')
            ON DUPLICATE KEY UPDATE `rating`=5;
        ");

        // Seed a pending booking to demonstrate provider queue
        $pdo->exec("
            INSERT INTO `bookings` (`id`, `user_id`, `provider_id`, `service_id`, `booking_date`, `booking_time`, `address`, `problem_desc`, `status`)
            VALUES (2, $uid, $pid, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '02:30 PM', 'Flat 402, Sunshine Apartments', 'Ceiling fan regulator replacement and light installation', 'Pending')
            ON DUPLICATE KEY UPDATE `status`='Pending';
        ");
        $messages[] = "Sample booking lifecycle and customer feedback seeded.";
    }

} catch (PDOException $e) {
    $success = false;
    $messages[] = "Error during setup: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Service Booking System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #333;
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 680px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        h1 {
            font-size: 24px;
            margin-top: 0;
            color: <?php echo $success ? '#2b8a3e' : '#c92a2a'; ?>;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success { background: #ebfbee; color: #2b8a3e; border: 1px solid #b2f2bb; }
        .alert-error { background: #fff5f5; color: #c92a2a; border: 1px solid #ffc9c9; }
        ul.log-list {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        ul.log-list li {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        ul.log-list li:before {
            content: "✓ ";
            color: #2b8a3e;
            font-weight: bold;
        }
        .creds-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        .creds-table th, .creds-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .creds-table th {
            background: #f8f9fa;
        }
        .btn-group {
            margin-top: 25px;
            display: flex;
            gap: 12px;
        }
        .btn {
            display: inline-block;
            background: #1971c2;
            color: #fff;
            padding: 10px 18px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }
        .btn-secondary {
            background: #495057;
        }
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
<div class="container">
    <h1><?php echo $success ? 'Setup Completed Successfully' : 'Setup Encountered Errors'; ?></h1>

    <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>">
        <?php echo $success ? 'Database schema and demo records are fully initialized!' : 'Please check MySQL service in XAMPP.'; ?>
    </div>

    <h3>Installation Log:</h3>
    <ul class="log-list">
        <?php foreach ($messages as $msg): ?>
            <li><?php echo htmlspecialchars($msg); ?></li>
        <?php endforeach; ?>
    </ul>

    <?php if ($success): ?>
        <h3>Demo Accounts for Testing:</h3>
        <table class="creds-table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Password</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Administrator</strong></td>
                    <td>admin@services.com</td>
                    <td>admin123</td>
                </tr>
                <tr>
                    <td><strong>Service Provider (Approved)</strong></td>
                    <td>rajesh@electrician.com</td>
                    <td>provider123</td>
                </tr>
                <tr>
                    <td><strong>Service Provider (Pending)</strong></td>
                    <td>manoj@painter.com</td>
                    <td>provider123</td>
                </tr>
                <tr>
                    <td><strong>Customer / User</strong></td>
                    <td>rahul@gmail.com</td>
                    <td>user123</td>
                </tr>
            </tbody>
        </table>

        <div class="btn-group">
            <a href="../frontend/index.html" class="btn">Launch Application (Frontend)</a>
            <a href="../frontend/login.html" class="btn btn-secondary">Go to Login</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
