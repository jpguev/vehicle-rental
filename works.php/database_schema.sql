CREATE DATABASE IF NOT EXISTS vehicle_rental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vehicle_rental;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  license_uploaded TINYINT(1) NOT NULL DEFAULT 0,
  license_filename VARCHAR(255) DEFAULT NULL,
  rental_mode VARCHAR(32) DEFAULT 'with_driver',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(50) NOT NULL,
  name VARCHAR(150) NOT NULL,
  model VARCHAR(100) NOT NULL,
  capacity VARCHAR(50) NOT NULL,
  price_per_day DECIMAL(10,2) NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'available',
  icon VARCHAR(16) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS drivers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE,
  experience VARCHAR(50) NOT NULL,
  rating DECIMAL(2,1) NOT NULL,
  specialty VARCHAR(120) NOT NULL,
  license VARCHAR(50) NOT NULL,
  image VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
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
  status ENUM('confirmed','reserved','cancelled') NOT NULL DEFAULT 'confirmed',
  satisfaction_rating TINYINT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
  FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample users
INSERT IGNORE INTO users (username, password, name, email) VALUES
  ('admin', '$2y$10$K0Cj7QSQkfenLu5IiwIwDeo/QIWtnj3CLpI6nV8/UFL0b5lsBSWfS', 'Admin User', 'admin@example.com'),
  ('customer', '$2y$10$3aNY7hXyGdqcZD1b/4uGFuYItmMZhSjvSjuVsJEART1phLCyHT5xK', 'Customer', 'customer@example.com');

-- Sample vehicles
INSERT IGNORE INTO vehicles (type, name, model, capacity, price_per_day, status, icon, image) VALUES
  ('Car', 'Honda Civic 2023', 'Sedan', '5 passengers', 1500, 'available', '🚗', 'The-New-Civic-Back-View-removebg-preview.png'),
  ('Car', 'Toyota Corolla 2023', 'Sedan', '5 passengers', 1300, 'available', '🚗', 'corolla-removebg-preview.png'),
  ('Car', 'Hyundai Tucson 2023', 'SUV', '7 passengers', 2000, 'available', '🚗', 'download__4_-removebg-preview.png'),
  ('Car', 'Mazda CX-5 2023', 'SUV', '7 passengers', 2200, 'available', '🚗', 'trim-2.5-S.png'),
  ('Bike', 'Honda Click 125', 'Scooter', '2 passengers', 300, 'available', '🏍️', 'click.png'),
  ('Bike', 'Yamaha NMax 155', 'Scooter', '2 passengers', 350, 'available', '🏍️', 'nmax-removebg-preview (1).png'),
  ('Bike', 'Honda CB150 Street', 'Motorcycle', '1 passengers', 400, 'available', '🏍️', 'Macho-Black.jpg'),
  ('Car', 'Missubibi Mirage 2023', 'Hatchback', '5 passengers', 1200, 'available', '🚗', 'missubibi-removebg-preview.png'),
  ('Van', 'Ford Transit 2022', 'Van', '12 passengers', 3500, 'available', '🚐', 'ford.jpg'),
  ('Car', 'Honda CR-V 2024', 'SUV', '5 passengers', 2500, 'available', '🚗', 'crv.jpg'),
  ('Truck', 'Isuzu D-Max 2023', 'Pickup', '3 passengers', 2800, 'available', '🛻', 'Isuzu-d-max-1-2024.png'),
  ('Bike', 'Kawasaki Ninja 400', 'Sport', '1 passengers', 600, 'available', '🏍️', 'new.jpg'),
  ('Car', 'Suzuki Swift 2023', 'Hatchback', '5 passengers', 1100, 'available', '🚗', 'swift.png'),
  ('Van', 'Toyota Hiace 2021', 'Van', '15 passengers', 4000, 'available', '🚐', 'hiace.jpg'),
  ('Car', 'Dodge Charger 2023', 'Sports Car', '3 passengers', 6000, 'available', '🚗', '2023-dodge-charger (1).png'),
  ('Car', 'Ferrari SF90', 'Sports Car', '1 passengers', 7000, 'available', '🚗', 'ferrari.webp'),
  ('Car', 'Porsche 911 Carrera 2023', 'Sports Car', '2 passengers', 7500, 'available', '🚗', 'porsche.png'),
  ('Car', 'Lamborghini Huracan EVO', 'Sports Car', '2 passengers', 9800, 'available', '🚗', 'lambo-removebg-preview.png');

-- Sample drivers
INSERT IGNORE INTO drivers (name, experience, rating, specialty, license, image) VALUES
  ('John Paolo Aala', '5 years', 4.9, 'City navigation and customer service', 'Class B', 'creator.jpg'),
  ('Deirck Lopez', '7 years', 4.8, 'Long-distance and heavy vehicles', 'Class C', 'deirick.png'),
  ('Lance Jerich Macaspac', '4 years', 4.7, 'Fast and safe delivery routes', 'Class B', 'erich.jpg'),
  ('Ian Cyrus Nicomedez', '6 years', 4.8, 'VIP transport and event driving', 'Class B', 'ian.jpg');
