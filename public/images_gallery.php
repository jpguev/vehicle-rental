<?php
session_start();

$authenticated = $_SESSION['authenticated'] ?? false;
$current_user = $_SESSION['user'] ?? null;

if (!$authenticated) {
    header('Location: login.php');
    exit;
}

// All images used in the system
$images = [
    [
        'name' => 'The-New-Civic-Back-View-removebg-preview.png',
        'category' => 'Vehicles',
        'description' => 'Honda Civic 2023 - Sedan'
    ],
    [
        'name' => 'corolla-removebg-preview.png',
        'category' => 'Vehicles',
        'description' => 'Toyota Corolla 2023 - Sedan'
    ],
    [
        'name' => 'download__4_-removebg-preview.png',
        'category' => 'Vehicles',
        'description' => 'Hyundai Tucson 2023 - SUV'
    ],
    [
        'name' => 'trim-2.5-S.png',
        'category' => 'Vehicles',
        'description' => 'Mazda CX-5 2023 - SUV'
    ],
    [
        'name' => 'click.png',
        'category' => 'Vehicles',
        'description' => 'Honda Click 125 - Scooter'
    ],
    [
        'name' => 'nmax-removebg-preview (1).png',
        'category' => 'Vehicles',
        'description' => 'Yamaha NMax 155 - Scooter'
    ],
    [
        'name' => 'Macho-Black.jpg',
        'category' => 'Vehicles',
        'description' => 'Honda CB150 Street - Motorcycle'
    ],
    [
        'name' => 'missubibi-removebg-preview.png',
        'category' => 'Vehicles',
        'description' => 'Missubibi Mirage 2023 - Hatchback'
    ],
    [
        'name' => 'ford.jpg',
        'category' => 'Vehicles',
        'description' => 'Ford Transit 2022 - Van'
    ],
    [
        'name' => 'crv.jpg',
        'category' => 'Vehicles',
        'description' => 'Honda CR-V 2024 - SUV'
    ],
    [
        'name' => 'Isuzu-d-max-1-2024.png',
        'category' => 'Vehicles',
        'description' => 'Isuzu D-Max 2023 - Pickup'
    ],
    [
        'name' => 'new.jpg',
        'category' => 'Vehicles',
        'description' => 'Kawasaki Ninja 400 - Sport Bike'
    ],
    [
        'name' => 'swift.png',
        'category' => 'Vehicles',
        'description' => 'Suzuki Swift 2023 - Hatchback'
    ],
    [
        'name' => 'hiace.jpg',
        'category' => 'Vehicles',
        'description' => 'Toyota Hiace 2021 - Van'
    ],
    [
        'name' => '2023-dodge-charger (1).png',
        'category' => 'Vehicles',
        'description' => 'Dodge Charger 2023 - Sports Car'
    ],
    [
        'name' => 'ferrari.webp',
        'category' => 'Vehicles',
        'description' => 'Ferrari SF90 - Sports Car'
    ],
    [
        'name' => 'porsche.png',
        'category' => 'Vehicles',
        'description' => 'Porsche 911 Carrera 2023 - Sports Car'
    ],
    [
        'name' => 'lambo-removebg-preview.png',
        'category' => 'Vehicles',
        'description' => 'Lamborghini Huracan EVO - Sports Car'
    ],
    [
        'name' => 'creator.jpg',
        'category' => 'Drivers',
        'description' => 'John Paolo Aala - Driver'
    ],
    [
        'name' => 'deirick.png',
        'category' => 'Drivers',
        'description' => 'Deirck Lopez - Driver'
    ],
    [
        'name' => 'erich.jpg',
        'category' => 'Drivers',
        'description' => 'Lance Jerich Macaspac - Driver'
    ],
    [
        'name' => 'ian.jpg',
        'category' => 'Drivers',
        'description' => 'Ian Cyrus Nicomedez - Driver'
    ],
];

function safe($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Gallery | Vehicle Rental</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        header h1 {
            color: #667eea;
            font-size: 2em;
        }

        header p {
            color: #666;
            flex: 1;
            min-width: 200px;
        }

        .user-bar {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .user-bar span {
            color: #333;
            font-size: 1em;
            font-weight: 600;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
        }

        .nav-buttons a,
        .nav-buttons button {
            background: #667eea;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .nav-buttons a:hover,
        .nav-buttons button:hover {
            background: #764ba2;
        }

        .logout-button {
            background: #dc3545;
        }

        .logout-button:hover {
            background: #c82333;
        }

        .category-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .category-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.8em;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .image-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .image-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }

        .image-container {
            width: 100%;
            height: 200px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .image-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .image-info {
            padding: 15px;
        }

        .image-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            word-break: break-word;
            font-size: 0.95em;
        }

        .image-description {
            color: #666;
            font-size: 0.9em;
            line-height: 1.4;
        }

        .stats {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.95em;
            opacity: 0.9;
        }

        .search-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .search-container input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
        }

        .search-container input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                align-items: flex-start;
            }

            .image-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }

            .image-container {
                height: 150px;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h1>🖼️ Image Gallery</h1>
                <p>All vehicle and driver images in our rental system</p>
            </div>
            <div class="user-bar">
                <span>Welcome, <?php echo safe($current_user['name'] ?? $current_user['username'] ?? 'Guest'); ?></span>
                <div class="nav-buttons">
                    <a href="vehicle_rental.php">Back to System</a>
                    <a href="logout.php" class="logout-button">Log out</a>
                </div>
            </div>
        </header>

        <div class="stats">
            <div class="stat-item">
                <div class="stat-number"><?php echo count($images); ?></div>
                <div class="stat-label">Total Images</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count(array_filter($images, fn($img) => $img['category'] === 'Vehicles')); ?></div>
                <div class="stat-label">Vehicle Images</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count(array_filter($images, fn($img) => $img['category'] === 'Drivers')); ?></div>
                <div class="stat-label">Driver Images</div>
            </div>
        </div>

        <div class="search-container">
            <input type="text" id="searchInput" placeholder="🔍 Search images by name or description..." onkeyup="filterImages()">
        </div>

        <?php
        $categories = array_unique(array_column($images, 'category'));
        foreach ($categories as $category):
            $category_images = array_filter($images, fn($img) => $img['category'] === $category);
        ?>

        <div class="category-section">
            <h2><?php echo safe($category); ?> (<?php echo count($category_images); ?>)</h2>

            <div class="image-grid" id="imageGrid<?php echo str_replace(' ', '', safe($category)); ?>">
                <?php foreach ($category_images as $image): ?>
                    <div class="image-card" data-search="<?php echo strtolower(safe($image['name'] . ' ' . $image['description'])); ?>">
                        <div class="image-container">
                            <?php if (file_exists($image['name'])): ?>
                                <img src="assets/images/<?php echo safe($image['name']); ?>" alt="<?php echo safe($image['name']); ?>">
                            <?php else: ?>
                                <div style="color: #999; text-align: center;">
                                    <div style="font-size: 3em; margin-bottom: 10px;">📷</div>
                                    <div>Image not found</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="image-info">
                            <div class="image-name"><?php echo safe($image['name']); ?></div>
                            <div class="image-description"><?php echo safe($image['description']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php endforeach; ?>

    </div>

    <script>
        function filterImages() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.image-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const searchText = card.getAttribute('data-search');
                if (searchText.includes(searchInput)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (visibleCount === 0) {
                const firstGrid = document.querySelector('.image-grid');
                if (firstGrid && !document.getElementById('emptyState')) {
                    const emptyState = document.createElement('div');
                    emptyState.id = 'emptyState';
                    emptyState.className = 'empty-state';
                    emptyState.innerHTML = '<div class="empty-state-icon">📷</div><div>No images found matching your search.</div>';
                    firstGrid.parentElement.appendChild(emptyState);
                }
            } else {
                const emptyState = document.getElementById('emptyState');
                if (emptyState) {
                    emptyState.remove();
                }
            }
        }
    </script>
</body>
</html>
