<?php
session_start();

$authenticated = $_SESSION['authenticated'] ?? false;
$current_user = $_SESSION['user'] ?? null;

if (!$authenticated) {
    header('Location: login.php');
    exit;
}

$default_customer_name = $current_user['name'] ?? $current_user['username'] ?? '';
$default_email = $current_user['email'] ?? '';


$section = $_POST['section'] ?? ($_GET['section'] ?? 'vehicles');
$action = $_POST['action'] ?? 'book';
if (!in_array($action, ['book', 'reserve'], true)) {
    $action = 'book';
}
$errors = [];
$messages = [];


$vehicles = [
    ['id' => 1, 'type' => 'Car', 'name' => 'Honda Civic 2023', 'model' => 'Sedan', 'capacity' => '5 passengers', 'price_per_day' => 1500, 'status' => 'available', 'icon' => '🚗', 'image' => 'The-New-Civic-Back-View-removebg-preview.png'],
    ['id' => 2, 'type' => 'Car', 'name' => 'Toyota Corolla 2023', 'model' => 'Sedan', 'capacity' => '5 passengers', 'price_per_day' => 1300, 'status' => 'available', 'icon' => '🚗', 'image' => 'corolla-removebg-preview.png'],
    ['id' => 3, 'type' => 'Car', 'name' => 'Hyundai Tucson 2023', 'model' => 'SUV', 'capacity' => '7 passengers', 'price_per_day' => 2000, 'status' => 'available', 'icon' => '🚗', 'image' => 'download__4_-removebg-preview.png'],
    ['id' => 4, 'type' => 'Car', 'name' => 'Mazda CX-5 2023', 'model' => 'SUV', 'capacity' => '7 passengers', 'price_per_day' => 2200, 'status' => 'available', 'icon' => '🚗', 'image' => 'trim-2.5-S.png'],
    ['id' => 5, 'type' => 'Bike', 'name' => 'Honda Click 125', 'model' => 'Scooter', 'capacity' => '2 passengers', 'price_per_day' => 300, 'status' => 'available', 'icon' => '🏍️', 'image' => 'click.png'],
    ['id' => 6, 'type' => 'Bike', 'name' => 'Yamaha NMax 155', 'model' => 'Scooter', 'capacity' => '2 passengers', 'price_per_day' => 350, 'status' => 'available', 'icon' => '🏍️', 'image' => 'nmax-removebg-preview (1).png'],
    ['id' => 7, 'type' => 'Bike', 'name' => 'Honda CB150 Street', 'model' => 'Motorcycle', 'capacity' => '1 passengers', 'price_per_day' => 400, 'status' => 'available', 'icon' => '🏍️', 'image' => 'Macho-Black.jpg'],
    ['id' => 8, 'type' => 'Car', 'name' => 'Missubibi Mirage 2023', 'model' => 'Hatchback', 'capacity' => '5 passengers', 'price_per_day' => 1200, 'status' => 'available', 'icon' => '🚗', 'image' => 'missubibi-removebg-preview.png'],
    ['id' => 9, 'type' => 'Van', 'name' => 'Ford Transit 2022', 'model' => 'Van', 'capacity' => '12 passengers', 'price_per_day' => 3500, 'status' => 'available', 'icon' => '🚐', 'image' => 'ford.jpg'],
    ['id' => 10, 'type' => 'Car', 'name' => 'Honda CR-V 2024', 'model' => 'SUV', 'capacity' => '5 passengers', 'price_per_day' => 2500, 'status' => 'available', 'icon' => '🚗', 'image' => 'crv.jpg'],
    ['id' => 11, 'type' => 'Truck', 'name' => 'Isuzu D-Max 2023', 'model' => 'Pickup', 'capacity' => '3 passengers', 'price_per_day' => 2800, 'status' => 'available', 'icon' => '🛻', 'image' => 'Isuzu-d-max-1-2024.png'],
    ['id' => 12, 'type' => 'Bike', 'name' => 'Kawasaki Ninja 400', 'model' => 'Sport', 'capacity' => '1 passengers', 'price_per_day' => 600, 'status' => 'available', 'icon' => '🏍️', 'image' => 'new.jpg'],
    ['id' => 13, 'type' => 'Car', 'name' => 'Suzuki Swift 2023', 'model' => 'Hatchback', 'capacity' => '5 passengers', 'price_per_day' => 1100, 'status' => 'available', 'icon' => '🚗', 'image' => 'swift.png'],
    ['id' => 14, 'type' => 'Van', 'name' => 'Toyota Hiace 2021', 'model' => 'Van', 'capacity' => '15 passengers', 'price_per_day' => 4000, 'status' => 'available', 'icon' => '🚐', 'image' => 'hiace.jpg'],
    ['id' => 15, 'type' => 'Car', 'name' => 'Dodge Charger 2023', 'model' => 'Sports Car', 'capacity' => '3 passengers', 'price_per_day' => 6000, 'status' => 'available', 'icon' => '🚗', 'image' => '2023-dodge-charger (1).png'],
    ['id' => 16, 'type' => 'Car', 'name' => 'Ferrari SF90', 'model' => 'Sports Car', 'capacity' => '1 passengers', 'price_per_day' => 7000, 'status' => 'available', 'icon' => '🚗', 'image' => 'ferrari.webp'],
    ['id' => 17, 'type' => 'Car', 'name' => 'Porsche 911 Carrera 2023', 'model' => 'Sports Car', 'capacity' => '2 passengers', 'price_per_day' => 7500, 'status' => 'available', 'icon' => '🚗', 'image' => 'porsche.png'],
    ['id' => 18, 'type' => 'Car', 'name' => 'Lamborghini Huracan EVO', 'model' => 'Sports Car', 'capacity' => '2 passengers', 'price_per_day' => 9800, 'status' => 'available', 'icon' => '🚗', 'image' => 'lambo-removebg-preview.png'],
];

if (!isset($_SESSION['bookings_no_driver'])) {
    $_SESSION['bookings_no_driver'] = [];
    $_SESSION['booking_counter_no_driver'] = 0;
}

$booking_counter = $_SESSION['booking_counter_no_driver'] ?? 2;
$bookings = $_SESSION['bookings_no_driver'] ?? [];

function safe($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function get_vehicle_by_id($id, $vehicles)
{
    foreach ($vehicles as $vehicle) {
        if ($vehicle['id'] == $id) {
            return $vehicle;
        }
    }
    return null;
}

function is_vehicle_available($vehicle_id, $start_date, $end_date, $bookings)
{
    foreach ($bookings as $booking) {
        if ($booking['vehicle_id'] == $vehicle_id && in_array($booking['status'], ['confirmed', 'reserved'], true)) {
            $booking_start = strtotime($booking['start_date']);
            $booking_end = strtotime($booking['end_date']);
            $req_start = strtotime($start_date);
            $req_end = strtotime($end_date);

            if (!($req_end < $booking_start || $req_start > $booking_end)) {
                return false;
            }
        }
    }
    return true;
}

function vehicle_has_active_booking($vehicle_id, $bookings)
{
    foreach ($bookings as $booking) {
        if ($booking['vehicle_id'] == $vehicle_id && in_array($booking['status'], ['confirmed', 'reserved'], true)) {
            return true;
        }
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($section) {
        case 'book_vehicle':
            $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
            $customer_name = trim($_POST['customer_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $start_date = trim($_POST['start_date'] ?? '');
            $end_date = trim($_POST['end_date'] ?? '');
            $action = $_POST['action'] ?? 'book';
            if (!in_array($action, ['book', 'reserve'], true)) {
                $action = 'book';
            }
            $status = $action === 'reserve' ? 'reserved' : 'confirmed';

            if ($vehicle_id <= 0) {
                $errors[] = 'Please select a valid vehicle.';
            }
            if ($customer_name === '') {
                $errors[] = 'Customer name is required.';
            }
            if ($email === '') {
                $errors[] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }
            if ($start_date === '') {
                $errors[] = 'Start date is required.';
            }
            if ($end_date === '') {
                $errors[] = 'End date is required.';
            }

            if (!empty($start_date) && !empty($end_date)) {
                $start = strtotime($start_date);
                $end = strtotime($end_date);
                $today = strtotime(date('Y-m-d'));

                if ($start === false || $end === false) {
                    $errors[] = 'Invalid date format.';
                } elseif ($start < $today) {
                    $errors[] = 'Start date cannot be in the past.';
                } elseif ($end < $start) {
                    $errors[] = 'End date must be after start date.';
                } elseif ($start === $end) {
                    $errors[] = 'Rental period must be at least 1 day.';
                }
            }

            if (empty($errors) && $vehicle_id > 0 && !empty($start_date) && !empty($end_date)) {
                if (!is_vehicle_available($vehicle_id, $start_date, $end_date, $bookings)) {
                    $errors[] = 'Vehicle is not available for the selected dates.';
                }
            }

            if (empty($errors)) {
                $vehicle = get_vehicle_by_id($vehicle_id, $vehicles);
                if ($vehicle) {
                    $start = strtotime($start_date);
                    $end = strtotime($end_date);
                    $days = ($end - $start) / (60 * 60 * 24);
                    $total_cost = $days * $vehicle['price_per_day'];

                    $new_booking = [
                        'booking_id' => $booking_counter + 1,
                        'vehicle_id' => $vehicle_id,
                        'customer_name' => $customer_name,
                        'email' => $email,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'days' => $days,
                        'total_cost' => $total_cost,
                        'status' => $status
                    ];

                    $_SESSION['bookings_no_driver'][] = $new_booking;
                    $_SESSION['booking_counter_no_driver'] = $booking_counter + 1;
                    $booking_counter++;
                    $bookings[] = $new_booking;

                    if ($status === 'reserved') {
                        $messages[] = 'Reservation created! Reservation ID: ' . $new_booking['booking_id'] . '. A confirmation has been sent to ' . safe($email) . '.';
                    } else {
                        $messages[] = 'Booking confirmed! Booking ID: ' . $new_booking['booking_id'] . '. A confirmation has been sent to ' . safe($email) . '.';
                    }

                    $_POST = [];
                    $section = 'bookings';
                } else {
                    $errors[] = 'Vehicle not found.';
                }
            }
            break;

        case 'cancel_booking':
            $booking_id = (int)($_POST['booking_id'] ?? 0);

            if ($booking_id > 0) {
                $found = false;
                foreach ($_SESSION['bookings_no_driver'] as $key => $booking) {
                    $is_owner = false;
                    if (isset($current_user['email']) && $booking['email'] === $current_user['email']) {
                        $is_owner = true;
                    } elseif (isset($current_user['name']) && $booking['customer_name'] === $current_user['name']) {
                        $is_owner = true;
                    }

                    if ($booking['booking_id'] == $booking_id && $is_owner) {
                        unset($_SESSION['bookings_no_driver'][$key]);
                        $_SESSION['bookings_no_driver'] = array_values($_SESSION['bookings_no_driver']);
                        $messages[] = 'Booking #' . $booking_id . ' has been removed.';
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $errors[] = 'Booking not found or you are not authorized to cancel it.';
                }

                $bookings = $_SESSION['bookings_no_driver'];
                $section = 'bookings';
            }
            break;

        case 'rate_booking':
            $booking_id = (int)($_POST['booking_id'] ?? 0);
            $rating = (int)($_POST['satisfaction_rating'] ?? 0);

            if ($booking_id > 0 && $rating >= 1 && $rating <= 5) {
                $found = false;
                foreach ($_SESSION['bookings_no_driver'] as $key => $booking) {
                    $is_owner = false;
                    if (isset($current_user['email']) && $booking['email'] === $current_user['email']) {
                        $is_owner = true;
                    } elseif (isset($current_user['name']) && $booking['customer_name'] === $current_user['name']) {
                        $is_owner = true;
                    }

                    if ($booking['booking_id'] == $booking_id && $is_owner) {
                        $_SESSION['bookings_no_driver'][$key]['satisfaction_rating'] = $rating;
                        $messages[] = 'Thank you! Your satisfaction rating has been saved.';
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $errors[] = 'Booking not found or you are not authorized to rate it.';
                }

                $bookings = $_SESSION['bookings_no_driver'];
                $section = 'bookings';
            } else {
                $errors[] = 'Invalid rating. Please provide a rating between 1 and 5.';
                $section = 'bookings';
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self-Drive Rental | Vehicle Rental System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 28%),
                        radial-gradient(circle at bottom right, rgba(255,255,255,0.08), transparent 24%),
                        linear-gradient(135deg, #16c6a6 0%, #20c997 100%);
            min-height: 100vh;
            padding: 20px;
            background-size: 220% 220%;
            animation: gradientBG 14s ease infinite;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(16px);
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 30px 90px rgba(0, 0, 0, 0.16);
            padding: 24px;
        }

        header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        header h1 {
            color: #28a745;
            margin-bottom: 10px;
            font-size: 2.5em;
        }

        header p {
            color: #666;
            font-size: 1.1em;
        }

        .badge-self-drive {
            display: inline-block;
            background: #d4edda;
            color: #155724;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9em;
            margin-top: 10px;
            font-weight: bold;
        }

        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .nav-tab {
            padding: 12px 24px;
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s;
            color: #333;
        }

        .nav-tab:hover {
            background: #f0f0f0;
        }

        .nav-tab.active {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }

        .user-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 0 0;
            gap: 10px;
        }

        .user-bar span {
            color: #333;
            font-size: 1em;
            font-weight: 600;
        }

        .logout-button {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.25s ease;
        }

        .logout-button:hover {
            background: #c82333;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .card {
            background: rgba(255,255,255,0.98);
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 18px 60px rgba(0, 0, 0, 0.12);
            margin-bottom: 20px;
            animation: fadeInUp 0.9s ease both;
        }

        .card h2 {
            color: #28a745;
            margin-bottom: 20px;
            font-size: 1.8em;
        }

        .card h3 {
            color: #28a745;
            margin-bottom: 15px;
            margin-top: 20px;
        }

        .vehicle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .vehicle-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .vehicle-card.unavailable {
            opacity: 0.55;
            cursor: not-allowed;
            pointer-events: none;
            border-color: #d1d1d1;
            filter: grayscale(30%);
        }

        .vehicle-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.2);
            border-color: #28a745;
        }

        .vehicle-image {
            width: 100%;
            height: 180px;
            object-fit: contain;
            object-position: center;
            border-radius: 8px;
            background: #ffffff;
            margin-bottom: 10px;
        }

        .vehicle-name {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .vehicle-details {
            color: #666;
            font-size: 0.95em;
            margin-bottom: 5px;
        }

        .vehicle-price {
            color: #28a745;
            font-size: 1.5em;
            font-weight: bold;
            margin-top: 10px;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            margin-bottom: 10px;
        }

        .badge-available {
            background: #d4edda;
            color: #155724;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .badge-type {
            background: #cfe2ff;
            color: #084298;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        input[type="text"],
        input[type="email"],
        input[type="date"],
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="date"]:focus,
        select:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 5px rgba(40, 167, 69, 0.3);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        button {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            transition: background 0.3s;
        }

        button:hover {
            background: #218838;
        }

        button.secondary {
            background: #6c757d;
        }

        button.secondary:hover {
            background: #5a6268;
        }

        button.danger {
            background: #dc3545;
        }

        button.danger:hover {
            background: #c82333;
        }

        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #842029;
        }

        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .message ul {
            margin-top: 10px;
            margin-left: 20px;
        }

        .message li {
            margin-bottom: 5px;
        }

        .booking-card {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .booking-id {
            color: #28a745;
            font-weight: bold;
        }

        .booking-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-reserved {
            background: #fff3cd;
            color: #856404;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #842029;
            text-decoration: line-through;
        }

        .booking-details {
            color: #666;
            margin-bottom: 10px;
        }

        .booking-details dt {
            font-weight: 600;
            display: inline;
        }

        .booking-details dd {
            display: inline;
            margin-right: 20px;
        }

        .booking-vehicle-photo {
            width: 140px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 12px;
            float: left;
            border: 1px solid #e9ecef;
            background: #fff;
        }

        .total-cost {
            color: #28a745;
            font-size: 1.3em;
            font-weight: bold;
            margin-top: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 4em;
            margin-bottom: 15px;
        }

        .sidebar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .sidebar h3 {
            color: #28a745;
            margin-bottom: 15px;
        }

        .sidebar-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .sidebar-item:last-child {
            border-bottom: none;
        }

        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-bar input[type="text"],
        .search-bar select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            flex: 1;
            min-width: 200px;
        }

        .search-bar button {
            padding: 12px 24px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            transition: background 0.3s;
            white-space: nowrap;
        }

        .search-bar button:hover {
            background: #218838;
        }

        .search-bar button.reset {
            background: #6c757d;
            padding: 12px 18px;
        }

        .search-bar button.reset:hover {
            background: #5a6268;
        }

        .search-results-count {
            color: #666;
            font-size: 0.95em;
            margin-bottom: 10px;
        }

        .rating-section {
            background: #f0f4ff;
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .rating-section h4 {
            color: #4338ca;
            margin-bottom: 12px;
            font-size: 1em;
        }

        .star-rating {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 12px;
        }

        .star-rating input[type="radio"] {
            display: none;
        }

        .star-rating label {
            display: inline-block;
            font-size: 2em;
            cursor: pointer;
            color: #ddd;
            transition: color 0.2s;
            margin: 0;
            padding: 0;
        }

        .star-rating input[type="radio"]:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            width: fit-content;
        }

        .rating-text {
            color: #666;
            font-size: 0.9em;
            margin-top: 8px;
        }

        .rating-display {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            padding: 10px;
            color: #155724;
            margin-top: 10px;
            font-size: 0.95em;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>🏎️ Self-Drive Rental System</h1>
            <p>Rent a vehicle and drive it yourself</p>
            <div class="badge-self-drive">Self-Drive Mode (No Driver Required)</div>
            <div class="user-bar">
                <span>Welcome, <?php echo safe($current_user['name'] ?? $current_user['username'] ?? 'Guest'); ?></span>
                <a href="logout.php" class="logout-button">Log out</a>
            </div>
        </header>

        <div class="nav-tabs">
            <button class="nav-tab active" data-section="vehicles" onclick="showSection('vehicles', this)">Available Vehicles</button>
            <button class="nav-tab" data-section="book" onclick="showSection('book', this)">Book a Vehicle</button>
            <button class="nav-tab" data-section="bookings" onclick="showSection('bookings', this)">My Bookings</button>
        </div>

        <div id="vehicles" class="content-section active">
            <div class="card">
                <h2>Available Vehicles</h2>
                <p>Browse our fleet of vehicles available for self-drive rental</p>

                <div class="search-bar">
                    <input type="text" id="vehicleSearch" placeholder="Search by vehicle name or type..." onkeyup="filterVehicles()">
                    <select id="typeFilter" onchange="filterVehicles()">
                        <option value="">All Types</option>
                        <option value="Car">Car</option>
                        <option value="Bike">Bike</option>
                        <option value="Van">Van</option>
                        <option value="Truck">Truck</option>
                    </select>
                    <button onclick="resetVehicleSearch();" class="reset">Reset</button>
                </div>

                <div class="search-results-count">
                    Showing <span id="resultCount"><?php echo count($vehicles); ?></span> vehicle(s)
                </div>

                <div class="vehicle-grid" id="vehicleGrid">
                    <?php foreach ($vehicles as $vehicle): ?>
                        <?php $is_unavailable = vehicle_has_active_booking($vehicle['id'], $bookings); ?>
                        <div class="vehicle-card<?php echo $is_unavailable ? ' unavailable' : ''; ?>" <?php if (!$is_unavailable): ?>onclick="bookVehicle(<?php echo $vehicle['id']; ?>, '<?php echo safe($vehicle['name']); ?>')"<?php endif; ?> data-vehicle-type="<?php echo safe($vehicle['type']); ?>" data-vehicle-name="<?php echo safe(strtolower($vehicle['name'])); ?>">
                            <img src="<?php echo safe($vehicle['image']); ?>" alt="<?php echo safe($vehicle['name']); ?>" class="vehicle-image">
                            <div class="badge badge-type"><?php echo safe($vehicle['type']); ?></div>
                            <?php if ($is_unavailable): ?>
                                <div class="badge badge-cancelled">Unavailable</div>
                            <?php else: ?>
                                <div class="badge badge-available">Available</div>
                            <?php endif; ?>
                            <div class="vehicle-name"><?php echo safe($vehicle['name']); ?></div>
                            <div class="vehicle-details">
                                <strong>Model:</strong> <?php echo safe($vehicle['model']); ?><br>
                                <strong>Capacity:</strong> <?php echo safe($vehicle['capacity']); ?>
                            </div>
                            <div class="vehicle-price">₱<?php echo number_format($vehicle['price_per_day']); ?>/day</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="book" class="content-section">
            <div class="card">
                <h2>Book a Vehicle</h2>

                <?php if (!empty($errors)): ?>
                    <div class="message error">
                        <strong>Please fill in all required fields:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo safe($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($messages)): ?>
                    <div class="message success">
                        <strong>Success!</strong>
                        <ul>
                            <?php foreach ($messages as $message): ?>
                                <li><?php echo safe($message); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" onsubmit="return confirmBooking();">
                    <input type="hidden" name="section" value="book_vehicle">

                    <div class="form-group">
                        <label>Select Vehicle</label>
                        <select name="vehicle_id" required>
                            <option value="">-- Choose a Vehicle --</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <?php $is_unavailable = vehicle_has_active_booking($vehicle['id'], $bookings); ?>
                                <option value="<?php echo $vehicle['id']; ?>" <?php echo $is_unavailable ? 'disabled' : ''; ?>>
                                    <?php echo safe($vehicle['name'] . ' - ₱' . number_format($vehicle['price_per_day']) . '/day') . ($is_unavailable ? ' (Unavailable)' : ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" value="<?php echo isset($_POST['start_date']) ? safe($_POST['start_date']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" value="<?php echo isset($_POST['end_date']) ? safe($_POST['end_date']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Your Name</label>
                            <input type="text" name="customer_name" value="<?php echo isset($_POST['customer_name']) ? safe($_POST['customer_name']) : safe($default_customer_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo isset($_POST['email']) ? safe($_POST['email']) : safe($default_email); ?>" required>
                        </div>
                    </div>

                    <div class="message success" style="margin-bottom: 20px;">
                        <strong>✓ No driver needed!</strong> You'll be driving this vehicle yourself. Make sure you have a valid driver's license.
                    </div>

                    <div style="display:flex; gap: 12px; flex-wrap: wrap;">
                        <button type="submit" name="action" value="book">Complete Booking</button>
                        <button type="submit" name="action" value="reserve" class="secondary">Reserve Vehicle</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="bookings" class="content-section">
            <div class="card">
                <h2>My Bookings</h2>

                <?php if (!empty($errors)): ?>
                    <div class="message error">
                        <strong>Error:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo safe($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($messages)): ?>
                    <div class="message success">
                        <strong>Success!</strong>
                        <ul>
                            <?php foreach ($messages as $message): ?>
                                <li><?php echo safe($message); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($bookings)): ?>
                    <?php foreach ($bookings as $booking): ?>
                        <?php $vehicle = get_vehicle_by_id($booking['vehicle_id'], $vehicles); ?>
                        <div class="booking-card">
                            <div class="booking-header">
                                <span class="booking-id">Booking #<?php echo $booking['booking_id']; ?></span>
                                <span class="booking-status <?php echo 'status-' . strtolower($booking['status']); ?>"><?php echo ucfirst($booking['status']); ?></span>
                            </div>
                            <?php if ($vehicle && !empty($vehicle['image'])): ?>
                                <img src="<?php echo safe($vehicle['image']); ?>" alt="<?php echo safe($vehicle['name']); ?>" class="booking-vehicle-photo">
                            <?php endif; ?>
                            <div class="booking-details">
                                <dt>Vehicle:</dt>
                                <dd><?php echo $vehicle ? safe($vehicle['name']) : 'Unknown'; ?></dd><br>
                                <dt>Customer:</dt>
                                <dd><?php echo safe($booking['customer_name']); ?></dd><br>
                                <dt>Email:</dt>
                                <dd><?php echo safe($booking['email']); ?></dd><br>
                                <dt>Start Date:</dt>
                                <dd><?php echo safe($booking['start_date']); ?></dd><br>
                                <dt>End Date:</dt>
                                <dd><?php echo safe($booking['end_date']); ?></dd><br>
                                <dt>Rental Days:</dt>
                                <dd><?php echo (int)$booking['days']; ?> day<?php echo $booking['days'] != 1 ? 's' : ''; ?></dd>
                            </div>
                            <div style="clear: both;"></div>
                            <div class="total-cost">Total Cost: ₱<?php echo number_format($booking['total_cost']); ?></div>

                            <?php if (in_array($booking['status'], ['confirmed', 'reserved'], true)): ?>
                                <form method="post" style="display: inline; margin-top: 10px;">
                                    <input type="hidden" name="section" value="cancel_booking">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                    <button type="submit" class="danger" onclick="return confirm('Are you sure you want to cancel this booking?');">Cancel Booking</button>
                                </form>
                            <?php endif; ?>

                            <div class="rating-section">
                                <h4>How satisfied are you with this rental?</h4>
                                <?php if (isset($booking['satisfaction_rating'])): ?>
                                    <div class="rating-display">
                                        ★ Your rating: <strong><?php echo $booking['satisfaction_rating']; ?> / 5 stars</strong>
                                    </div>
                                <?php else: ?>
                                    <form method="post" style="margin-top: 10px;">
                                        <input type="hidden" name="section" value="rate_booking">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                        
                                        <div class="star-rating">
                                            <input type="radio" id="rating-5-<?php echo $booking['booking_id']; ?>" name="satisfaction_rating" value="5" required>
                                            <label for="rating-5-<?php echo $booking['booking_id']; ?>">★</label>
                                            
                                            <input type="radio" id="rating-4-<?php echo $booking['booking_id']; ?>" name="satisfaction_rating" value="4">
                                            <label for="rating-4-<?php echo $booking['booking_id']; ?>">★</label>
                                            
                                            <input type="radio" id="rating-3-<?php echo $booking['booking_id']; ?>" name="satisfaction_rating" value="3">
                                            <label for="rating-3-<?php echo $booking['booking_id']; ?>">★</label>
                                            
                                            <input type="radio" id="rating-2-<?php echo $booking['booking_id']; ?>" name="satisfaction_rating" value="2">
                                            <label for="rating-2-<?php echo $booking['booking_id']; ?>">★</label>
                                            
                                            <input type="radio" id="rating-1-<?php echo $booking['booking_id']; ?>" name="satisfaction_rating" value="1">
                                            <label for="rating-1-<?php echo $booking['booking_id']; ?>">★</label>
                                        </div>
                                        <div class="rating-text">Click on the stars to rate your satisfaction</div>
                                        <button type="submit" style="margin-top: 10px;">Submit Rating</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <p>No bookings yet. Start by booking a vehicle!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="sidebar">
            <h3>System Information</h3>
            <div class="sidebar-item">
                <strong>Total Vehicles:</strong> <?php echo count($vehicles); ?>
            </div>
            <div class="sidebar-item">
                <strong>Cars Available:</strong> <?php echo count(array_filter($vehicles, fn($v) => $v['type'] === 'Car')); ?>
            </div>
            <div class="sidebar-item">
                <strong>Vans Available:</strong> <?php echo count(array_filter($vehicles, fn($v) => $v['type'] === 'Van')); ?>
            </div>
            <div class="sidebar-item">
                <strong>Pickups Available:</strong> <?php echo count(array_filter($vehicles, fn($v) => $v['model'] === 'Pickup')); ?>
            </div>
            <div class="sidebar-item">
                <strong>Bikes Available:</strong> <?php echo count(array_filter($vehicles, fn($v) => $v['type'] === 'Bike')); ?>
            </div>
            <div class="sidebar-item">
                <strong>Total Confirmed Bookings:</strong> <?php echo count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed')); ?>
            </div>
            <div class="sidebar-item">
                <strong>Total Reserved Bookings:</strong> <?php echo count(array_filter($bookings, fn($b) => $b['status'] === 'reserved')); ?>
            </div>
            <div class="sidebar-item">
                <strong>Current Date:</strong> <?php echo date('F d, Y'); ?>
            </div>
        </div>
    </div>

    <script>
        function showSection(sectionId, activeButton = null) {
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => section.classList.remove('active'));

            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.classList.add('active');
            }

            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => tab.classList.remove('active'));

            const activeTab = activeButton || document.querySelector(`.nav-tab[data-section="${sectionId}"]`);
            if (activeTab) {
                activeTab.classList.add('active');
            }
        }

        function bookVehicle(vehicleId, vehicleName) {
            showSection('book');
            document.querySelector('select[name="vehicle_id"]').value = vehicleId;
            document.querySelector('select[name="vehicle_id"]').focus();
        }

        function filterVehicles() {
            const searchInput = document.getElementById('vehicleSearch').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter').value;
            const vehicleCards = document.querySelectorAll('.vehicle-card');
            let visibleCount = 0;

            vehicleCards.forEach(card => {
                const vehicleName = card.dataset.vehicleName;
                const vehicleType = card.dataset.vehicleType;
                
                const matchesSearch = vehicleName.includes(searchInput) || 
                                     vehicleType.toLowerCase().includes(searchInput);
                const matchesType = typeFilter === '' || vehicleType === typeFilter;
                
                if (matchesSearch && matchesType) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            document.getElementById('resultCount').textContent = visibleCount;
        }

        function resetVehicleSearch() {
            document.getElementById('vehicleSearch').value = '';
            document.getElementById('typeFilter').value = '';
            filterVehicles();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const requestedSection = '<?php echo in_array($section, ["vehicles", "book", "bookings"]) ? $section : ($section === "book_vehicle" ? "book" : ($section === "rate_booking" ? "bookings" : "vehicles")); ?>';
            showSection(requestedSection);

            const successMessage = document.querySelector('.message.success');
            if (successMessage && requestedSection !== 'bookings') {
                showSection('bookings');
            }
        });

        function confirmBooking() {
            return confirm('Are you sure you want to complete this booking?');
        }
    </script>
</body>

</html>
