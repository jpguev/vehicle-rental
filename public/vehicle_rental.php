<?php
// Force absolute error visibility at the engine level
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../src/db.php'; // Adjust path if db.php is in a different directory

$authenticated = $_SESSION['authenticated'] ?? false;
$current_user = $_SESSION['user'] ?? null;

if (!$authenticated) {
    header('Location: login.php');
    exit;
}

// Redirect back if user manually hits the wrong mode URL
if (isset($current_user['id'])) {
    $fresh_user = get_user_by_id((int)$current_user['id']); //
    if ($fresh_user && $fresh_user['rental_mode'] === 'without_driver') { //
        header('Location: vehicle_rental_no_driver.php');
        exit;
    }
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

// Fetch native dataset values from MySQL tables
try {
    $vehicles = get_all_vehicles(); //
    $drivers = get_all_drivers(); //
} catch (PDOException $e) {
    echo "<div style='padding:20px; background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; font-family:sans-serif; border-radius:8px; margin:20px;'>";
    echo "<h3 style='margin-top:0;'>⚠️ Database Fetch Connection Failure</h3>";
    echo "<p>Your PHP code is sound, but it cannot talk to your local database engine. Make sure the <strong>MySQL module is started in XAMPP</strong>.</p>";
    echo "<p><strong>Error Trace:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    exit;
}

function safe($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Wrapper for UI state logic compatibility
function vehicle_has_active_booking_ui($vehicle_id) {
    return vehicle_has_active_booking((int)$vehicle_id); //
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($section) {
        case 'book_vehicle':
            $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
            $customer_name = trim($_POST['customer_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $driver_name = trim($_POST['driver'] ?? '');
            $start_date = trim($_POST['start_date'] ?? '');
            $end_date = trim($_POST['end_date'] ?? '');
            $action = $_POST['action'] ?? 'book';
            if (!in_array($action, ['book', 'reserve'], true)) {
                $action = 'book';
            }
            $status = $action === 'reserve' ? 'reserved' : 'confirmed';

            if ($vehicle_id <= 0) $errors[] = 'Please select a valid vehicle.';
            if ($customer_name === '') $errors[] = 'Customer name is required.';
            if ($email === '') {
                $errors[] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }
            if ($driver_name === '') $errors[] = 'Please choose a driver.';
            if ($start_date === '') $errors[] = 'Start date is required.';
            if ($end_date === '') $errors[] = 'End date is required.';

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
                if (!is_vehicle_available($vehicle_id, $start_date, $end_date)) { //
                    $errors[] = 'Vehicle is not available for the selected dates.';
                }
            }

            if (empty($errors)) {
                $vehicle = get_vehicle_by_id($vehicle_id); //
                if ($vehicle) {
                    $start = strtotime($start_date);
                    $end = strtotime($end_date);
                    $days = ($end - $start) / (60 * 60 * 24);
                    $total_cost = $days * $vehicle['price_per_day'];

                    $selected_driver = get_driver_by_name($driver_name); //
                    $driver_id = $selected_driver ? $selected_driver['id'] : null;

                    $bookingData = [
                        'user_id' => $current_user['id'] ?? null,
                        'vehicle_id' => $vehicle_id,
                        'driver_id' => $driver_id,
                        'customer_name' => $customer_name,
                        'email' => $email,
                        'driver_name' => $driver_name,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'days' => $days,
                        'total_cost' => $total_cost,
                        'status' => $status
                    ];

                    $booking_id = create_booking($bookingData); //

                    $messages[] = $status === 'reserved' ? 'Reservation created! Ref ID: ' . $booking_id : 'Booking confirmed! Ref ID: ' . $booking_id;
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
                $deleted = delete_booking($booking_id, $current_user['id'] ?? null, $current_user['email'] ?? null); //
                if ($deleted) {
                    $messages[] = 'Booking #' . $booking_id . ' has been removed.';
                } else {
                    $errors[] = 'Booking not found or you are not authorized to cancel it.';
                }
                $section = 'bookings';
            }
            break;

        case 'rate_booking':
            $booking_id = (int)($_POST['booking_id'] ?? 0);
            $rating = (int)($_POST['satisfaction_rating'] ?? 0);

            if ($booking_id > 0 && $rating >= 1 && $rating <= 5) {
                $rated = rate_booking($booking_id, $rating, $current_user['id'] ?? null, $current_user['email'] ?? null); //
                if ($rated) {
                    $messages[] = 'Thank you! Your satisfaction rating has been saved.';
                } else {
                    $errors[] = 'Booking not found or you are not authorized to rate it.';
                }
                $section = 'bookings';
            }
            break;
    }
}

// Fetch historical bookings ledger from DB
$bookings = get_bookings_for_user($current_user['id'] ?? null, $current_user['email'] ?? null); //
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chauffeur Fleet | EcoTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #10b981; --primary-dark: #059669; --bg: #f8fafc;
            --surface: #ffffff; --text: #0f172a; --text-muted: #64748b;
            --border: #e2e8f0; --sidebar-w: 260px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
        
        .sidebar { width: var(--sidebar-w); background: var(--surface); border-right: 1px solid var(--border); display: flex; flex-direction: column; position: fixed; height: 100vh; top: 0; left: 0; z-index: 50; }
        .logo-area { padding: 1.5rem; border-bottom: 1px solid var(--border); font-size: 1.5rem; font-weight: 800; color: var(--primary); display: flex; align-items: center; gap: 0.5rem; }
        .nav-menu { padding: 1.5rem 1rem; flex-grow: 1; display: flex; flex-direction: column; gap: 0.5rem; }
        .nav-item { padding: 0.75rem 1rem; border-radius: 0.5rem; color: var(--text-muted); text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 0.75rem; cursor: pointer; transition: 0.2s; background: transparent; border: none; text-align: left; font-size: 1rem; width: 100%; }
        .nav-item:hover { background: #f1f5f9; color: var(--text); }
        .nav-item.active { background: #ecfdf5; color: var(--primary-dark); }
        .user-area { padding: 1.5rem; border-top: 1px solid var(--border); }
        .user-name { font-weight: 600; color: var(--text); margin-bottom: 0.25rem; }
        .logout-btn { display: inline-block; color: #ef4444; text-decoration: none; font-weight: 600; font-size: 0.9rem; margin-top: 0.5rem; }
        
        .main-content { flex: 1; margin-left: var(--sidebar-w); padding: 2rem 3rem; }
        .page-header { margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end; }
        .page-title { font-size: 2rem; font-weight: 800; color: var(--text); }
        .page-subtitle { color: var(--text-muted); margin-top: 0.25rem; }
        
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .kpi-card { background: var(--surface); padding: 1.5rem; border-radius: 1rem; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .kpi-label { color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
        .kpi-value { font-size: 1.75rem; font-weight: 800; color: var(--text); }
        
        .content-section { display: none; animation: fadeIn 0.3s ease; }
        .content-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .panel { background: var(--surface); border-radius: 1rem; border: 1px solid var(--border); padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .panel h2 { font-size: 1.25rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem; }
        
        .search-bar { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .search-bar input, .search-bar select, select, input { padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 0.5rem; font-size: 0.95rem; background: var(--bg); outline: none; transition: border 0.2s; }
        .search-bar input:focus, select:focus, input:focus { border-color: var(--primary); }
        .search-bar input { flex: 1; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: 0.2s; border: none; font-size: 0.95rem; color: white; text-decoration: none; display: inline-block; }
        .btn-primary { background: var(--primary); }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: var(--text-muted); }
        .btn-danger { background: #ef4444; }
        
        .vehicle-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .vehicle-card { background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; overflow: hidden; cursor: pointer; transition: 0.2s; display: flex; flex-direction: column; }
        .vehicle-card:hover:not(.unavailable) { border-color: var(--primary); transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .vehicle-card.unavailable { opacity: 0.6; filter: grayscale(1); cursor: not-allowed; }
        .v-image-container { height: 160px; padding: 1rem; background: white; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: center; }
        .v-image-container img { max-height: 100%; max-width: 100%; object-fit: contain; }
        .v-details { padding: 1.5rem; flex: 1; display: flex; flex-direction: column; }
        .v-tags { display: flex; gap: 0.5rem; margin-bottom: 0.75rem; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 99px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .badge-type { background: #e0e7ff; color: #3730a3; }
        .badge-status { background: #d1fae5; color: #065f46; }
        .badge-unavail { background: #fee2e2; color: #991b1b; }
        .v-name { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text); }
        .v-specs { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1rem; flex: 1; }
        .v-price { font-size: 1.25rem; font-weight: 800; color: var(--primary-dark); }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
        @media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text); }
        .form-group input, .form-group select { width: 100%; }
        .driver-profile { background: #ecfdf5; border: 1px dashed var(--primary); padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; gap: 1.5rem; align-items: center; }
        .driver-profile img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        
        .alert { padding: 1rem 1.5rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.95rem; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }

        .booking-card { background: var(--surface); border: 1px solid var(--border); border-left: 4px solid var(--primary); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1rem; display: flex; flex-direction: column; gap: 1rem; }
        .b-header { width: 100%; display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; }
        .b-id { font-weight: 700; color: var(--text); }
        .b-body { display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: flex-start; }
        .b-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.9rem; flex: 1; min-width: 280px; }
        @media (max-width: 480px) { .b-grid { grid-template-columns: 1fr; } }
        .b-label { color: var(--text-muted); font-weight: 600; }
        .b-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 0.75rem; margin-top: 0.5rem; }

        .star-rating { display: flex; flex-direction: row-reverse; gap: 0.25rem; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 1.5rem; color: #cbd5e1; cursor: pointer; transition: color 0.2s; }
        .star-rating input:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: #f59e0b; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo-area">🌿 EcoTrack</div>
        <div class="nav-menu">
            <button class="nav-item active" data-section="vehicles" onclick="showSection('vehicles', this)">
                <span>🚙</span> Chauffeur Fleet
            </button>
            <button class="nav-item" data-section="book" onclick="showSection('book', this)">
                <span>📝</span> Book Ride
            </button>
            <button class="nav-item" data-section="bookings" onclick="showSection('bookings', this)">
                <span>📋</span> My Bookings
            </button>
        </div>
        <div class="user-area">
            <div class="user-name"><?php echo safe($current_user['name'] ?? 'User'); ?></div>
            <a href="selection.php" class="logout-btn" style="color: var(--primary);">Change Mode</a><br>
            <?php if (isset($current_user['username']) && $current_user['username'] === 'admin'): ?>
                <a href="admin_bookings.php" class="logout-btn" style="color: #667eea; display: block; margin-top: 0.5rem; font-weight: 700;">📊 Control Panel</a>
            <?php endif; ?>
            <a href="logout.php" class="logout-btn">Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Chauffeur Service</h1>
                <p class="page-subtitle">Optimize your travel footprint. Fleet managed via EcoTrack's green-routing algorithms.</p>
            </div>
            <div style="font-weight: 500; color: var(--text-muted);"><?php echo date('F d, Y'); ?></div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Available Vehicles</div>
                <div class="kpi-value" id="availCount"><?php echo count($vehicles); ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Active Services</div>
                <div class="kpi-value"><?php echo count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed' || $b['status'] === 'reserved')); ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Driver Status</div>
                <div class="kpi-value" style="color: var(--primary-dark);">All Online</div>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?php echo safe($error); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <?php if (!empty($messages)): ?>
            <div class="alert alert-success"><ul><?php foreach ($messages as $message): ?><li><?php echo safe($message); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <div id="vehicles" class="content-section active">
            <div class="panel">
                <h2>Explore Fleet Categories</h2>
                <div class="search-bar">
                    <input type="text" id="vehicleSearch" placeholder="Search by model name..." onkeyup="filterVehicles()">
                    <select id="typeFilter" onchange="filterVehicles()">
                        <option value="">All Vehicle Categories</option>
                        <option value="Car">Sedans & SUVs</option>
                        <option value="Bike">Bikes & Scooters</option>
                        <option value="Van">Vans</option>
                        <option value="Truck">Pickups & Cargo</option>
                    </select>
                    <button onclick="resetVehicleSearch()" class="btn btn-secondary">Reset</button>
                </div>
                
                <div class="vehicle-grid">
                    <?php foreach ($vehicles as $vehicle): ?>
                        <?php $is_unavailable = vehicle_has_active_booking_ui($vehicle['id']); ?>
                        <div class="vehicle-card <?php echo $is_unavailable ? 'unavailable' : ''; ?>" <?php if (!$is_unavailable): ?>onclick="bookVehicle(<?php echo $vehicle['id']; ?>)"<?php endif; ?> data-vehicle-type="<?php echo safe($vehicle['type']); ?>" data-vehicle-name="<?php echo safe(strtolower($vehicle['name'])); ?>">
                            <div class="v-image-container">
                                <img src="assets/images/<?php echo safe($vehicle['image']); ?>" alt="<?php echo safe($vehicle['name']); ?>">
                            </div>
                            <div class="v-details">
                                <div class="v-tags">
                                    <span class="badge badge-type"><?php echo safe($vehicle['type']); ?></span>
                                    <span class="badge <?php echo $is_unavailable ? 'badge-unavail' : 'badge-status'; ?>"><?php echo $is_unavailable ? 'Booked' : 'Available'; ?></span>
                                </div>
                                <div class="v-name"><?php echo safe($vehicle['name']); ?></div>
                                <div class="v-specs"><?php echo safe($vehicle['model']); ?> • Max Capacity: <?php echo safe($vehicle['capacity']); ?></div>
                                <div class="v-price">₱<?php echo number_format($vehicle['price_per_day']); ?><span style="font-size:0.8rem; font-weight:500; color:var(--text-muted);"> /day</span></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="book" class="content-section">
            <div class="panel">
                <h2>Set Up Your Booking</h2>
                <form method="post" onsubmit="return confirmBooking();">
                    <input type="hidden" name="section" value="book_vehicle">
                    
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label>Assigned Vehicle</label>
                        <select name="vehicle_id" required>
                            <option value="">-- Choose a Fleet Vehicle --</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <?php $is_unavailable = vehicle_has_active_booking_ui($vehicle['id']); ?>
                                <option value="<?php echo $vehicle['id']; ?>" <?php echo $is_unavailable ? 'disabled' : ''; ?>>
                                    <?php echo safe($vehicle['name'] . ' - ₱' . number_format($vehicle['price_per_day']) . '/day') . ($is_unavailable ? ' (Unavailable)' : ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" required>
                        </div>
                        <div class="form-group">
                            <label>Passenger Name</label>
                            <input type="text" name="customer_name" value="<?php echo safe($default_customer_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Contact Email Address</label>
                            <input type="email" name="email" value="<?php echo safe($default_email); ?>" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label>Select Your Chauffeur</label>
                        <select name="driver" id="driverSelect" onchange="updateDriverProfile()" required>
                            <option value="">-- Assign a Verified Driver --</option>
                            <?php foreach ($drivers as $driverOption): ?>
                                <option value="<?php echo safe($driverOption['name']); ?>"><?php echo safe($driverOption['name']); ?> (★ <?php echo $driverOption['rating']; ?>/5)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="driverProfile" class="driver-profile" style="display:none;"></div>

                    <div style="display:flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" name="action" value="book" class="btn btn-primary">Complete Booking</button>
                        <button type="submit" name="action" value="reserve" class="btn btn-secondary">Place On Hold</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="bookings" class="content-section">
            <div class="panel">
                <h2>Current Bookings & Holds</h2>
                <?php if (empty($bookings)): ?>
                    <div style="text-align: center; padding: 4rem 1rem; color: var(--text-muted);">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
                        <p>No active reservations found. Ready to create your first eco-trip?</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <?php $vehicle = get_vehicle_by_id((int)$booking['vehicle_id']); // ?> 
                        <div class="booking-card">
                            <div class="b-header">
                                <span class="b-id">Reservation Account #<?php echo $booking['booking_id']; ?></span>
                                <span class="badge <?php echo $booking['status'] == 'confirmed' ? 'badge-status' : ($booking['status'] == 'reserved' ? 'badge-type' : 'badge-unavail'); ?>"><?php echo strtoupper($booking['status']); ?></span>
                            </div>
                            <div class="b-body">
                                <div class="b-grid">
                                    <div><span class="b-label">Vehicle Option:</span> <?php echo $vehicle ? safe($vehicle['name']) : 'Fleet Item'; ?></div>
                                    <div><span class="b-label">Schedule:</span> <?php echo safe($booking['start_date']); ?> to <?php echo safe($booking['end_date']); ?> (<?php echo (int)$booking['days']; ?> Days)</div>
                                    <div><span class="b-label">Chauffeur:</span> <?php echo safe($booking['driver_name'] ?? 'Unassigned'); ?></div>
                                    <div><span class="b-label">Renter:</span> <?php echo safe($booking['customer_name']); ?></div>
                                </div>
                            </div>
                            <div class="b-footer">
                                <div class="v-price" style="font-size:1.15rem;">Total: ₱<?php echo number_format($booking['total_cost']); ?></div>
                                <div style="display:flex; gap: 1rem; align-items:center;">
                                    <?php if (in_array($booking['status'], ['confirmed', 'reserved'], true)): ?>
                                        <form method="post">
                                            <input type="hidden" name="section" value="cancel_booking">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                            <button type="submit" class="btn btn-danger" style="padding:0.5rem 1rem; font-size:0.85rem;" onclick="return confirm('Cancel this reservation portfolio?');">Cancel Ride</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div style="background:var(--bg); padding:1rem; border-radius:0.5rem; margin-top:0.5rem;">
                                <h4 style="font-size:0.9rem; margin-bottom:0.5rem;">Service Feedback Evaluation</h4>
                                <?php if (isset($booking['satisfaction_rating'])): ?>
                                    <div style="font-size:0.9rem; color:var(--primary-dark); font-weight:600;">★ Evaluation Submitted: <?php echo $booking['satisfaction_rating']; ?> / 5 Stars</div>
                                <?php else: ?>
                                    <form method="post" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                                        <input type="hidden" name="section" value="rate_booking">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                        <div class="star-rating">
                                            <input type="radio" id="r5-<?php echo $booking['booking_id']; ?>" name="satisfaction_rating" value="5" required><label for="r5-<?php echo $booking['booking_id']; ?>">★</label>
                                            <input type="radio" id="r4-<?php echo $booking['booking_id']; ?>" name="satisfaction_rating" value="4"><label for="r4-<?php echo $booking['booking_id']; ?>">★</label>
                                            <input type="radio" id="r3-<?php echo $booking['booking_id']; ?>" name="satisfaction_rating" value="3"><label for="r3-<?php echo $booking['booking_id']; ?>">★</label>
                                            <input type="radio" id="r2-<?php echo $booking['booking_id']; ?>" name="satisfaction_rating" value="2"><label for="r2-<?php echo $booking['booking_id']; ?>">★</label>
                                            <input type="radio" id="r1-<?php echo $booking['booking_id']; ?>" name="satisfaction_rating" value="1"><label for="r1-<?php echo $booking['booking_id']; ?>">★</label>
                                        </div>
                                        <button type="submit" class="btn btn-primary" style="padding:0.4rem 1rem; font-size:0.8rem;">Submit Rating</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function showSection(id, btn) {
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            if(btn) btn.classList.add('active');
            else {
                const targetTab = document.querySelector(`[data-section="${id}"]`);
                if(targetTab) targetTab.classList.add('active');
            }
        }

        function bookVehicle(id) {
            showSection('book');
            document.querySelector('select[name="vehicle_id"]').value = id;
        }

        function filterVehicles() {
            const term = document.getElementById('vehicleSearch').value.toLowerCase();
            const type = document.getElementById('typeFilter').value;
            let count = 0;
            document.querySelectorAll('.vehicle-card').forEach(card => {
                const matchName = card.dataset.vehicleName.includes(term);
                const matchType = type === '' || card.dataset.vehicleType === type;
                if(matchName && matchType) { card.style.display = ''; count++; }
                else card.style.display = 'none';
            });
            document.getElementById('availCount').textContent = count;
        }

        function resetVehicleSearch() {
            document.getElementById('vehicleSearch').value = '';
            document.getElementById('typeFilter').value = '';
            filterVehicles();
        }

        const drivers = <?php echo json_encode(array_column($drivers, null, 'name')); ?>;
        function updateDriverProfile() {
            const val = document.getElementById('driverSelect').value;
            const box = document.getElementById('driverProfile');
            if(!val || !drivers[val]) { box.style.display = 'none'; return; }
            const d = drivers[val];
            box.style.display = 'flex';
            box.innerHTML = `
                ${d.image ? `<img src="assets/images/${d.image}">` : ''}
                <div>
                    <h4 style="font-weight:700; color:var(--text);">${d.name}</h4>
                    <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.25rem;">
                        Rating: ★ ${d.rating} | Experience: ${d.experience}<br>Specialty: ${d.specialty}
                    </p>
                </div>
            `;
        }

        function confirmBooking() { return confirm('Confirm your current application reservation details?'); }

        document.addEventListener('DOMContentLoaded', () => {
            const currentSec = '<?php echo in_array($section, ["vehicles", "book", "bookings"]) ? $section : "vehicles"; ?>';
            showSection(currentSec);
            if(document.querySelector('.alert-success') && currentSec !== 'bookings') showSection('bookings');
        });
    </script>
</body>
</html>