<?php
/**
 * Database helper for the vehicle rental website.
 * Update DB_USER and DB_PASS with your MySQL credentials.
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'vehicle_rental');
define('DB_USER', 'root');
define('DB_PASS', '');

define('DB_CHARSET', 'utf8mb4');

define('DB_DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET);

function db_connect(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        if (stripos($exception->getMessage(), 'Unknown database') !== false) {
            create_database();
            $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } else {
            throw $exception;
        }
    }

    return $pdo;
}

function create_database(): void
{
    $dsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET ' . DB_CHARSET . ' COLLATE ' . DB_CHARSET . '_unicode_ci');
}

function db_init(): void
{
    $pdo = db_connect();

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            license_uploaded TINYINT(1) NOT NULL DEFAULT 0,
            license_filename VARCHAR(255) DEFAULT NULL,
            license_verified TINYINT(1) NOT NULL DEFAULT 0,
            rental_mode VARCHAR(32) DEFAULT "with_driver",
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE ' . DB_CHARSET . '_unicode_ci'
    );

    // ensure existing installations get the new column if missing
    try {
        $pdo->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS license_verified TINYINT(1) NOT NULL DEFAULT 0');
    } catch (Exception $e) {
        // ignore if ALTER not supported by server version
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS vehicles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            name VARCHAR(150) NOT NULL,
            model VARCHAR(100) NOT NULL,
            capacity VARCHAR(50) NOT NULL,
            price_per_day DECIMAL(10,2) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT "available",
            icon VARCHAR(16) DEFAULT NULL,
            image VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE ' . DB_CHARSET . '_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS drivers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL UNIQUE,
            experience VARCHAR(50) NOT NULL,
            rating DECIMAL(2,1) NOT NULL,
            specialty VARCHAR(120) NOT NULL,
            license VARCHAR(50) NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE ' . DB_CHARSET . '_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            vehicle_id INT NOT NULL,
            driver_id INT DEFAULT NULL,
            customer_name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL,
            driver_name VARCHAR(150) DEFAULT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            days INT NOT NULL,
            total_cost DECIMAL(10,2) NOT NULL,
            status ENUM("confirmed", "reserved", "cancelled") NOT NULL DEFAULT "confirmed",
            satisfaction_rating TINYINT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
            FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE ' . DB_CHARSET . '_unicode_ci'
    );

    ensure_default_data($pdo);
}

function db_fetch(string $sql, array $params = []): ?array
{
    $statement = db_connect()->prepare($sql);
    $statement->execute($params);
    $row = $statement->fetch();
    return $row === false ? null : $row;
}

function db_fetch_all(string $sql, array $params = []): array
{
    $statement = db_connect()->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function db_execute(string $sql, array $params = []): bool
{
    $statement = db_connect()->prepare($sql);
    return $statement->execute($params);
}

function ensure_default_data(PDO $pdo): void
{
    $defaultUsers = [
        [
            'username' => 'admin',
            'password' => password_hash('rental123', PASSWORD_DEFAULT),
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ],
        [
            'username' => 'customer',
            'password' => password_hash('rentme456', PASSWORD_DEFAULT),
            'name' => 'Customer',
            'email' => 'customer@example.com',
        ],
    ];

    foreach ($defaultUsers as $user) {
        $exists = db_fetch('SELECT id FROM users WHERE username = :username OR email = :email', [
            ':username' => $user['username'],
            ':email' => $user['email'],
        ]);
        if ($exists === null) {
            db_execute(
                'INSERT INTO users (username, password, name, email) VALUES (:username, :password, :name, :email)',
                [
                    ':username' => $user['username'],
                    ':password' => $user['password'],
                    ':name' => $user['name'],
                    ':email' => $user['email'],
                ]
            );
        }
    }

    $defaultVehicles = [
        ['type' => 'Car', 'name' => 'Honda Civic 2023', 'model' => 'Sedan', 'capacity' => '5 passengers', 'price_per_day' => 1500, 'status' => 'available', 'icon' => '🚗', 'image' => 'The-New-Civic-Back-View-removebg-preview.png'],
        ['type' => 'Car', 'name' => 'Toyota Corolla 2023', 'model' => 'Sedan', 'capacity' => '5 passengers', 'price_per_day' => 1300, 'status' => 'available', 'icon' => '🚗', 'image' => 'corolla-removebg-preview.png'],
        ['type' => 'Car', 'name' => 'Hyundai Tucson 2023', 'model' => 'SUV', 'capacity' => '7 passengers', 'price_per_day' => 2000, 'status' => 'available', 'icon' => '🚗', 'image' => 'download__4_-removebg-preview.png'],
        ['type' => 'Car', 'name' => 'Mazda CX-5 2023', 'model' => 'SUV', 'capacity' => '7 passengers', 'price_per_day' => 2200, 'status' => 'available', 'icon' => '🚗', 'image' => 'trim-2.5-S.png'],
        ['type' => 'Bike', 'name' => 'Honda Click 125', 'model' => 'Scooter', 'capacity' => '2 passengers', 'price_per_day' => 300, 'status' => 'available', 'icon' => '🏍️', 'image' => 'click.png'],
        ['type' => 'Bike', 'name' => 'Yamaha NMax 155', 'model' => 'Scooter', 'capacity' => '2 passengers', 'price_per_day' => 350, 'status' => 'available', 'icon' => '🏍️', 'image' => 'nmax-removebg-preview (1).png'],
        ['type' => 'Bike', 'name' => 'Honda CB150 Street', 'model' => 'Motorcycle', 'capacity' => '1 passengers', 'price_per_day' => 400, 'status' => 'available', 'icon' => '🏍️', 'image' => 'Macho-Black.jpg'],
        ['type' => 'Car', 'name' => 'Missubibi Mirage 2023', 'model' => 'Hatchback', 'capacity' => '5 passengers', 'price_per_day' => 1200, 'status' => 'available', 'icon' => '🚗', 'image' => 'missubibi-removebg-preview.png'],
        ['type' => 'Van', 'name' => 'Ford Transit 2022', 'model' => 'Van', 'capacity' => '12 passengers', 'price_per_day' => 3500, 'status' => 'available', 'icon' => '🚐', 'image' => 'ford.jpg'],
        ['type' => 'Car', 'name' => 'Honda CR-V 2024', 'model' => 'SUV', 'capacity' => '5 passengers', 'price_per_day' => 2500, 'status' => 'available', 'icon' => '🚗', 'image' => 'crv.jpg'],
        ['type' => 'Truck', 'name' => 'Isuzu D-Max 2023', 'model' => 'Pickup', 'capacity' => '3 passengers', 'price_per_day' => 2800, 'status' => 'available', 'icon' => '🛻', 'image' => 'Isuzu-d-max-1-2024.png'],
        ['type' => 'Bike', 'name' => 'Kawasaki Ninja 400', 'model' => 'Sport', 'capacity' => '1 passengers', 'price_per_day' => 600, 'status' => 'available', 'icon' => '🏍️', 'image' => 'new.jpg'],
        ['type' => 'Car', 'name' => 'Suzuki Swift 2023', 'model' => 'Hatchback', 'capacity' => '5 passengers', 'price_per_day' => 1100, 'status' => 'available', 'icon' => '🚗', 'image' => 'swift.png'],
        ['type' => 'Van', 'name' => 'Toyota Hiace 2021', 'model' => 'Van', 'capacity' => '15 passengers', 'price_per_day' => 4000, 'status' => 'available', 'icon' => '🚐', 'image' => 'hiace.jpg'],
        ['type' => 'Car', 'name' => 'Dodge Charger 2023', 'model' => 'Sports Car', 'capacity' => '3 passengers', 'price_per_day' => 6000, 'status' => 'available', 'icon' => '🚗', 'image' => '2023-dodge-charger (1).png'],
        ['type' => 'Car', 'name' => 'Ferrari SF90', 'model' => 'Sports Car', 'capacity' => '1 passengers', 'price_per_day' => 7000, 'status' => 'available', 'icon' => '🚗', 'image' => 'ferrari.webp'],
        ['type' => 'Car', 'name' => 'Porsche 911 Carrera 2023', 'model' => 'Sports Car', 'capacity' => '2 passengers', 'price_per_day' => 7500, 'status' => 'available', 'icon' => '🚗', 'image' => 'porsche.png'],
        ['type' => 'Car', 'name' => 'Lamborghini Huracan EVO', 'model' => 'Sports Car', 'capacity' => '2 passengers', 'price_per_day' => 9800, 'status' => 'available', 'icon' => '🚗', 'image' => 'lambo-removebg-preview.png'],
    ];

    foreach ($defaultVehicles as $vehicle) {
        $exists = db_fetch('SELECT id FROM vehicles WHERE name = :name', [':name' => $vehicle['name']]);
        if ($exists === null) {
            db_execute(
                'INSERT INTO vehicles (type, name, model, capacity, price_per_day, status, icon, image) VALUES (:type, :name, :model, :capacity, :price_per_day, :status, :icon, :image)',
                [
                    ':type' => $vehicle['type'],
                    ':name' => $vehicle['name'],
                    ':model' => $vehicle['model'],
                    ':capacity' => $vehicle['capacity'],
                    ':price_per_day' => $vehicle['price_per_day'],
                    ':status' => $vehicle['status'],
                    ':icon' => $vehicle['icon'],
                    ':image' => $vehicle['image'],
                ]
            );
        }
    }

    $defaultDrivers = [
        ['name' => 'John Paolo Aala', 'experience' => '5 years', 'rating' => 4.9, 'specialty' => 'City navigation and customer service', 'license' => 'Class B', 'image' => 'creator.jpg'],
        ['name' => 'Deirck Lopez', 'experience' => '7 years', 'rating' => 4.8, 'specialty' => 'Long-distance and heavy vehicles', 'license' => 'Class C', 'image' => 'deirick.png'],
        ['name' => 'Lance Jerich Macaspac', 'experience' => '4 years', 'rating' => 4.7, 'specialty' => 'Fast and safe delivery routes', 'license' => 'Class B', 'image' => 'erich.jpg'],
        ['name' => 'Ian Cyrus Nicomedez', 'experience' => '6 years', 'rating' => 4.8, 'specialty' => 'VIP transport and event driving', 'license' => 'Class B', 'image' => 'ian.jpg'],
    ];

    foreach ($defaultDrivers as $driver) {
        $exists = db_fetch('SELECT id FROM drivers WHERE name = :name', [':name' => $driver['name']]);
        if ($exists === null) {
            db_execute(
                'INSERT INTO drivers (name, experience, rating, specialty, license, image) VALUES (:name, :experience, :rating, :specialty, :license, :image)',
                [
                    ':name' => $driver['name'],
                    ':experience' => $driver['experience'],
                    ':rating' => $driver['rating'],
                    ':specialty' => $driver['specialty'],
                    ':license' => $driver['license'],
                    ':image' => $driver['image'],
                ]
            );
        }
    }
}

function get_user_by_username(string $username): ?array
{
    return db_fetch('SELECT * FROM users WHERE username = :username', [':username' => $username]);
}

function get_user_by_email(string $email): ?array
{
    return db_fetch('SELECT * FROM users WHERE email = :email', [':email' => $email]);
}

function get_user_by_id(int $id): ?array
{
    return db_fetch('SELECT * FROM users WHERE id = :id', [':id' => $id]);
}

function create_user(string $username, string $passwordHash, string $name, string $email): int
{
    db_execute(
        'INSERT INTO users (username, password, name, email) VALUES (:username, :password, :name, :email)',
        [
            ':username' => $username,
            ':password' => $passwordHash,
            ':name' => $name,
            ':email' => $email,
        ]
    );
    return (int) db_connect()->lastInsertId();
}

function update_user_license(int $userId, string $filename): bool
{
    return db_execute('UPDATE users SET license_uploaded = 1, license_filename = :filename WHERE id = :id', [':filename' => $filename, ':id' => $userId]);
}

function verify_user_license(int $userId): bool
{
    return db_execute('UPDATE users SET license_verified = 1 WHERE id = :id', [':id' => $userId]);
}

function reject_user_license(int $userId): bool
{
    return db_execute('UPDATE users SET license_uploaded = 0, license_filename = NULL, license_verified = 0 WHERE id = :id', [':id' => $userId]);
}

function update_user_rental_mode(int $userId, string $mode): bool
{
    return db_execute('UPDATE users SET rental_mode = :mode WHERE id = :id', [':mode' => $mode, ':id' => $userId]);
}

function get_all_vehicles(): array
{
    return db_fetch_all('SELECT * FROM vehicles ORDER BY id ASC');
}

function get_vehicle_by_id(int $id): ?array
{
    return db_fetch('SELECT * FROM vehicles WHERE id = :id', [':id' => $id]);
}

function get_all_drivers(): array
{
    return db_fetch_all('SELECT * FROM drivers ORDER BY id ASC');
}

function get_driver_by_name(string $name): ?array
{
    return db_fetch('SELECT * FROM drivers WHERE name = :name', [':name' => $name]);
}

function get_driver_by_id(int $id): ?array
{
    return db_fetch('SELECT * FROM drivers WHERE id = :id', [':id' => $id]);
}

function get_bookings_for_user(?int $userId, ?string $email): array
{
    if ($userId !== null) {
        return db_fetch_all('SELECT b.*, b.id AS booking_id FROM bookings b WHERE user_id = :user_id ORDER BY created_at DESC', [':user_id' => $userId]);
    }

    if ($email !== null) {
        return db_fetch_all('SELECT b.*, b.id AS booking_id FROM bookings b WHERE email = :email ORDER BY created_at DESC', [':email' => $email]);
    }

    return [];
}

function get_booking_by_id(int $bookingId): ?array
{
    return db_fetch('SELECT b.*, b.id AS booking_id FROM bookings b WHERE id = :booking_id', [':booking_id' => $bookingId]);
}

function create_booking(array $bookingData): int
{
    db_execute(
        'INSERT INTO bookings (user_id, vehicle_id, driver_id, customer_name, email, driver_name, start_date, end_date, days, total_cost, status) VALUES (:user_id, :vehicle_id, :driver_id, :customer_name, :email, :driver_name, :start_date, :end_date, :days, :total_cost, :status)',
        [
            ':user_id' => $bookingData['user_id'],
            ':vehicle_id' => $bookingData['vehicle_id'],
            ':driver_id' => $bookingData['driver_id'],
            ':customer_name' => $bookingData['customer_name'],
            ':email' => $bookingData['email'],
            ':driver_name' => $bookingData['driver_name'],
            ':start_date' => $bookingData['start_date'],
            ':end_date' => $bookingData['end_date'],
            ':days' => $bookingData['days'],
            ':total_cost' => $bookingData['total_cost'],
            ':status' => $bookingData['status'],
        ]
    );
    return (int) db_connect()->lastInsertId();
}

function delete_booking(int $bookingId, ?int $userId, ?string $email): bool
{
    $conditions = [];
    $params = [':booking_id' => $bookingId];

    if ($userId !== null) {
        $conditions[] = 'user_id = :user_id';
        $params[':user_id'] = $userId;
    }

    if ($email !== null) {
        $conditions[] = 'email = :email';
        $params[':email'] = $email;
    }

    if (empty($conditions)) {
        return false;
    }

    $sql = 'DELETE FROM bookings WHERE id = :booking_id AND (' . implode(' OR ', $conditions) . ')';
    return db_execute($sql, $params);
}

function rate_booking(int $bookingId, int $rating, ?int $userId, ?string $email): bool
{
    $conditions = [];
    $params = [':booking_id' => $bookingId, ':rating' => $rating];

    if ($userId !== null) {
        $conditions[] = 'user_id = :user_id';
        $params[':user_id'] = $userId;
    }

    if ($email !== null) {
        $conditions[] = 'email = :email';
        $params[':email'] = $email;
    }

    if (empty($conditions)) {
        return false;
    }

    $sql = 'UPDATE bookings SET satisfaction_rating = :rating WHERE id = :booking_id AND (' . implode(' OR ', $conditions) . ')';
    return db_execute($sql, $params);
}

db_init();

function vehicle_has_active_booking(int $vehicleId): bool
{
    return db_fetch('SELECT id FROM bookings WHERE vehicle_id = :vehicle_id AND status IN ("confirmed", "reserved") LIMIT 1', [':vehicle_id' => $vehicleId]) !== null;
}

function is_vehicle_available(int $vehicleId, string $startDate, string $endDate): bool
{
    $rows = db_fetch_all('SELECT start_date, end_date FROM bookings WHERE vehicle_id = :vehicle_id AND status IN ("confirmed", "reserved")', [':vehicle_id' => $vehicleId]);

    $reqStart = strtotime($startDate);
    $reqEnd = strtotime($endDate);

    foreach ($rows as $booking) {
        $bookingStart = strtotime($booking['start_date']);
        $bookingEnd = strtotime($booking['end_date']);

        if (!($reqEnd < $bookingStart || $reqStart > $bookingEnd)) {
            return false;
        }
    }

    return true;
}
?>