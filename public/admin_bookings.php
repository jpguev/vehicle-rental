<?php
session_start();
require_once '../src/db.php'; // Corrected path to match your src/ directory layout

$authenticated = $_SESSION['authenticated'] ?? false;
$current_user = $_SESSION['user'] ?? null;

if (!$authenticated || !isset($current_user['username']) || $current_user['username'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle license verification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_license' && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    verify_user_license($user_id);
    header('Location: admin_bookings.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject_license' && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $user = get_user_by_id($user_id);
    if ($user && !empty($user['license_filename'])) {
        // Corrected filesystem path mapping to your public/uploads/licenses/ directory
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'licenses' . DIRECTORY_SEPARATOR . $user['license_filename'];
        if (is_file($path)) {
            @unlink($path);
        }
    }
    reject_user_license($user_id);
    header('Location: admin_bookings.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    db_execute('DELETE FROM bookings WHERE id = :id', [':id' => $booking_id]);
    header('Location: admin_bookings.php');
    exit;
}

$all_bookings = db_fetch_all(
    'SELECT b.*, b.id AS booking_id, u.username AS username, v.name AS vehicle_name, d.name AS driver_name, d.image AS driver_image
     FROM bookings b
     LEFT JOIN users u ON b.user_id = u.id
     LEFT JOIN vehicles v ON b.vehicle_id = v.id
     LEFT JOIN drivers d ON b.driver_id = d.id
     ORDER BY b.created_at DESC'
);

// Summary stats calculation
$total = count($all_bookings);
$confirmed = count(array_filter($all_bookings, fn($r) => $r['status'] === 'confirmed'));
$reserved = count(array_filter($all_bookings, fn($r) => $r['status'] === 'reserved'));
$cancelled = count(array_filter($all_bookings, fn($r) => $r['status'] === 'cancelled'));

// Pending license uploads for admin verification
$pending_licenses = db_fetch_all('SELECT id, username, name, email, license_filename, license_verified FROM users WHERE license_uploaded = 1 ORDER BY created_at DESC');

function safe($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Dashboard | EcoTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
        
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .kpi-card { background: var(--surface); padding: 1.5rem; border-radius: 1rem; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .kpi-label { color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
        .kpi-value { font-size: 1.75rem; font-weight: 800; color: var(--text); }
        
        .panel { background: var(--surface); border-radius: 1rem; border: 1px solid var(--border); padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .panel h2 { font-size: 1.25rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem; color: #0f172a; }
        
        .search-bar { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .search-bar input { padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 0.5rem; font-size: 0.95rem; background: var(--bg); outline: none; transition: border 0.2s; flex: 1; }
        .search-bar input:focus { border-color: var(--primary); }
        
        .btn { padding: 0.6rem 1.25rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: 0.2s; border: none; font-size: 0.9rem; color: white; text-decoration: none; display: inline-block; text-align: center; }
        .btn-primary { background: var(--primary); }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        .btn-secondary { background: #64748b; }
        .btn-secondary:hover { background: #475569; }

        table { width: 100%; border-collapse: collapse; background: var(--surface); margin-top: 0.5rem; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        th { background: #f8fafc; color: var(--text); font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
        tr:hover td { background-color: #f8fafc; }
        
        .badge { padding: 0.25rem 0.75rem; border-radius: 99px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .badge-confirmed { background: #d1fae5; color: #065f46; }
        .badge-reserved { background: #fef3c7; color: #92400e; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        
        .driver-thumb { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border); vertical-align: middle; margin-right: 8px; }
        .small-txt { font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem; }
        
        .detail-modal { position: fixed; left: 0; top: 0; width: 100%; height: 100%; display: none; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); align-items: center; justify-content: center; z-index: 100; }
        .detail-card { background: var(--surface); padding: 2rem; border-radius: 1rem; max-width: 600px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); border: 1px solid var(--border); }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1.5rem; }
        @media(max-width: 500px){ .detail-grid { grid-template-columns: 1fr; } }
        .detail-item label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 0.25rem; }
        .detail-item span { font-size: 0.95rem; color: var(--text); font-weight: 500; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo-area">🌿 EcoTrack</div>
        <div class="nav-menu">
            <button class="nav-item active" onclick="window.location.reload()">
                <span>📊</span> Management Panel
            </button>
            <a class="nav-item" href="vehicle_rental.php">
                <span>👤</span> Client View
            </a>
        </div>
        <div class="user-area">
            <div class="user-name">Administrator</div>
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">System Controller</div>
            <a href="logout.php" class="logout-btn">Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Bookings Dashboard</h1>
                <p class="page-subtitle">Track configurations, verify security file portfolios, and manage active operations ledger</p>
            </div>
            <div style="font-weight: 500; color: var(--text-muted);"><?php echo date('F d, Y'); ?></div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card"><div class="kpi-label">Total Logs</div><div class="kpi-value"><?php echo $total; ?></div></div>
            <div class="kpi-card"><div class="kpi-label">Confirmed</div><div class="kpi-value" style="color:var(--primary-dark);"><?php echo $confirmed; ?></div></div>
            <div class="kpi-card"><div class="kpi-label">Reserved</div><div class="kpi-value" style="color:#d97706;"><?php echo $reserved; ?></div></div>
            <div class="kpi-card"><div class="kpi-label">Cancelled</div><div class="kpi-value" style="color:#ef4444;"><?php echo $cancelled; ?></div></div>
        </div>

        <div class="panel">
            <h2>Driver's License Verifications</h2>
            <?php if (empty($pending_licenses)): ?>
                <p class="small-txt">No registration documentation portfolios are currently pending server verification loops.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>User Context</th>
                                <th>License Image File</th>
                                <th>Verification Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_licenses as $u): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo safe($u['username']); ?></strong>
                                        <div class="small-txt"><?php echo safe($u['name'] ?? ''); ?> • <?php echo safe($u['email']); ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($u['license_filename'])): ?>
                                            <a href="uploads/licenses/<?php echo safe($u['license_filename']); ?>" target="_blank">
                                                <img src="uploads/licenses/<?php echo safe($u['license_filename']); ?>" style="max-width:120px; max-height:80px; border-radius:0.375rem; border:1px solid var(--border); object-fit:cover;">
                                            </a>
                                        <?php else: ?>
                                            <span class="small-txt">Empty payload references</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($u['license_verified'])): ?>
                                            <span class="badge badge-confirmed">Verified Signature</span>
                                        <?php else: ?>
                                            <span class="badge badge-reserved">Awaiting Review</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (empty($u['license_verified'])): ?>
                                            <form method="post" style="display:inline">
                                                <input type="hidden" name="action" value="verify_license">
                                                <input type="hidden" name="user_id" value="<?php echo safe($u['id']); ?>">
                                                <button class="btn btn-primary" style="padding:0.4rem 0.8rem; font-size:0.8rem;" type="submit">Verify</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" style="display:inline" onsubmit="return confirm('Purge and discard this user documentation metadata file?');">
                                            <input type="hidden" name="action" value="reject_license">
                                            <input type="hidden" name="user_id" value="<?php echo safe($u['id']); ?>">
                                            <button class="btn btn-danger" style="padding:0.4rem 0.8rem; font-size:0.8rem;" type="submit">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h2>Operations Lease Ledger</h2>
            <div class="search-bar">
                <input type="text" id="searchBox" placeholder="Filter parameters by vehicle name, customer index, or profile identifier email..." oninput="filterTable()">
            </div>
            
            <div style="overflow-x: auto;">
                <table id="bookingsTable">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>User ID</th>
                            <th>Vehicle Matrix</th>
                            <th>Allocated Driver</th>
                            <th>Client Portfolio</th>
                            <th>Active Span</th>
                            <th>Financial Scope</th>
                            <th>Status Badge</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_bookings)): ?>
                            <tr><td colspan="9" style="text-align:center; color:var(--text-muted);">No transactional allocation history logs discovered inside this entity cache.</td></tr>
                        <?php else: ?>
                            <?php foreach ($all_bookings as $b): ?>
                                <tr>
                                    <td><strong>#<?php echo safe($b['booking_id']); ?></strong></td>
                                    <td class="small-txt"><?php echo safe($b['username'] ?? 'guest'); ?></td>
                                    <td><?php echo safe($b['vehicle_name'] ?? 'Fleet Unit ID: '.$b['vehicle_id']); ?></td>
                                    <td>
                                        <?php if (!empty($b['driver_image'])): ?>
                                            <img src="assets/images/<?php echo safe($b['driver_image']); ?>" alt="" class="driver-thumb">
                                        <?php endif; ?>
                                        <span style="font-weight:500;"><?php echo safe($b['driver_name'] ?? 'Self-Drive (None)'); ?></span>
                                    </td>
                                    <td>
                                        <div><?php echo safe($b['customer_name']); ?></div>
                                        <div class="small-txt"><?php echo safe($b['email']); ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo safe($b['start_date']); ?> → <?php echo safe($b['end_date']); ?></div>
                                        <div class="small-txt"><?php echo safe($b['days']); ?> allocation layout days</div>
                                    </td>
                                    <td>
                                        <span style="font-weight:700; color:var(--text);">₱<?php echo number_format($b['total_cost']); ?></span>
                                        <div class="small-txt">Rating: <?php echo safe($b['satisfaction_rating'] ?? 'Unrated'); ?> ★</div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $b['status'] === 'confirmed' ? 'badge-confirmed' : ($b['status'] === 'reserved' ? 'badge-reserved' : 'badge-cancelled'); ?>">
                                            <?php echo safe($b['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:0.5rem;">
                                            <button class="btn btn-secondary" style="padding:0.4rem 0.8rem; font-size:0.8rem;" onclick="showDetails(<?php echo htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8'); ?>)">View</button>
                                            <form method="post" onsubmit="return confirm('Purge this operational metadata row file from database registers?');" style="display:inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="booking_id" value="<?php echo safe($b['booking_id']); ?>">
                                                <button class="btn btn-danger" style="padding:0.4rem 0.8rem; font-size:0.8rem;" type="submit">Void</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="detailModal" class="detail-modal" onclick="hideModal(event)">
        <div class="detail-card" onclick="event.stopPropagation()">
            <h3 style="font-size:1.25rem; font-weight:800;" id="modalTitle">File Diagnostics Audit</h3>
            <div id="modalContent" class="detail-grid"></div>
            <div style="text-align:right; margin-top:2rem; border-top:1px solid var(--border); padding-top:1rem;">
                <button class="btn btn-secondary" onclick="hideModal()">Close Diagnostic</button>
            </div>
        </div>
    </div>

    <script>
        function filterTable() {
            const query = document.getElementById('searchBox').value.toLowerCase();
            document.querySelectorAll('#bookingsTable tbody tr').forEach(row => {
                if(row.cells.length > 1) {
                    const matched = row.textContent.toLowerCase().includes(query);
                    row.style.display = matched ? '' : 'none';
                }
            });
        }

        function showDetails(b) {
            document.getElementById('modalTitle').textContent = 'Audit Booking Ledger Reference #' + b.booking_id;
            const target = document.getElementById('modalContent');
            target.innerHTML = `
                <div class="detail-item"><label>Fleet Assignment Spec</label><span>${escapeHtml(b.vehicle_name ?? b.vehicle_id)}</span></div>
                <div class="detail-item"><label>Personnel Chauffeur</label><span>${escapeHtml(b.driver_name ?? 'Self-Drive (None)')}</span></div>
                <div class="detail-item"><label>Client Registered Identity</label><span>${escapeHtml(b.customer_name)}</span></div>
                <div class="detail-item"><label>Secure Ledger Email Address</label><span>${escapeHtml(b.email)}</span></div>
                <div class="detail-item"><label>Calendar Term parameters</label><span>${escapeHtml(b.start_date)} to ${escapeHtml(b.end_date)}</span></div>
                <div class="detail-item"><label>Total Days / Value Calculus</label><span>${escapeHtml(b.days)} days / ₱${Number(b.total_cost).toLocaleString()}</span></div>
                <div class="detail-item"><label>Operational Allocation Status</label><span>${escapeHtml(b.status).toUpperCase()}</span></div>
                <div class="detail-item"><label>Quality Metric Satisfaction Evaluation</label><span>${b.satisfaction_rating ? b.satisfaction_rating + ' / 5 Stars ⭐' : 'No metric submitted'}</span></div>
                <div class="detail-item" style="grid-column:1/-1; border-top:1px dashed var(--border); padding-top:0.5rem;"><label>System Receipt Timestamp</label><span style="font-size:0.85rem; color:var(--text-muted);">${escapeHtml(b.created_at)}</span></div>
            `;
            document.getElementById('detailModal').style.display = 'flex';
        }

        function hideModal(e) {
            if (!e || e.target === document.getElementById('detailModal')) {
                document.getElementById('detailModal').style.display = 'none';
            }
        }

        function escapeHtml(str){
            if (str === null || str === undefined) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }
    </script>
</body>
</html>