<?php
session_start();
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: selection.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoTrack | Sustainable Vehicle Rentals</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #047857;
            --bg: #f8fafc;
            --text: #0f172a;
            --text-muted: #64748b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg); color: var(--text); line-height: 1.6; }
        
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 5%; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: fixed; width: 100%; top: 0; z-index: 100; }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .nav-links a { text-decoration: none; color: var(--text); font-weight: 600; margin-left: 2rem; transition: color 0.3s; }
        .nav-links a:hover { color: var(--primary); }
        .btn-primary { background: var(--primary); color: white; padding: 0.75rem 1.5rem; border-radius: 99px; font-weight: 600; text-decoration: none; transition: background 0.3s; }
        .btn-primary:hover { background: var(--primary-dark); color: white; }
        
        .hero { display: flex; align-items: center; justify-content: space-between; min-height: 100vh; padding: 6rem 5% 2rem; background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); }
        .hero-content { flex: 1; max-width: 600px; }
        .hero-title { font-size: 3.5rem; font-weight: 800; line-height: 1.1; margin-bottom: 1.5rem; color: #064e3b; }
        .hero-subtitle { font-size: 1.25rem; color: var(--text-muted); margin-bottom: 2rem; }
        .hero-image { flex: 1; display: flex; justify-content: flex-end; }
        .hero-image img { max-width: 100%; height: auto; filter: drop-shadow(0 25px 25px rgba(0,0,0,0.15)); }
        
        .features { padding: 5rem 5%; background: white; }
        .section-title { text-align: center; font-size: 2.5rem; margin-bottom: 3rem; color: #064e3b; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
        .feature-card { padding: 2rem; border-radius: 1rem; background: var(--bg); text-align: center; transition: transform 0.3s; border: 1px solid #e2e8f0; }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(16, 185, 129, 0.1); border-color: var(--primary); }
        .feature-icon { font-size: 3rem; margin-bottom: 1rem; }
        .feature-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; }
        
        @media (max-width: 768px) {
            .hero { flex-direction: column; text-align: center; padding-top: 8rem; }
            .hero-image { margin-top: 3rem; justify-content: center; }
            .hero-title { font-size: 2.5rem; }
            .nav-links { display: none; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo">🌿 EcoTrack</a>
        <div class="nav-links">
            <a href="login.php">Log In</a>
            <a href="signup.php" class="btn-primary">Sign Up</a>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Sustainable Travel, Delivered.</h1>
            <p class="hero-subtitle">Experience the future of mobility. Rent premium, eco-friendly vehicles for self-drive or relax with our professional eco-drivers.</p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="signup.php" class="btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">Get Started Now</a>
                <a href="login.php" class="btn-primary" style="background: white; color: var(--primary); border: 2px solid var(--primary);">View Fleet</a>
            </div>
        </div>
        <div class="hero-image">
            <div style="font-size: 12rem;">🚘🌱</div>
        </div>
    </header>

    <section class="features">
        <h2 class="section-title">Why Choose EcoTrack?</h2>
        <div class="grid">
            <div class="feature-card">
                <div class="feature-icon">🔋</div>
                <h3 class="feature-title">Eco-Friendly Fleet</h3>
                <p class="text-muted">Reduce your carbon footprint with our selection of modern, efficient, and well-maintained vehicles.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🧑‍✈️</div>
                <h3 class="feature-title">Flexible Modes</h3>
                <p class="text-muted">Choose to take the wheel yourself or sit back and relax with our professional, highly-rated drivers.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3 class="feature-title">Instant Booking</h3>
                <p class="text-muted">Our streamlined platform makes reserving your next ride quick, easy, and completely hassle-free.</p>
            </div>
        </div>
    </section>
</body>
</html>