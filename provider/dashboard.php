<?php
// provider/dashboard.php
require_once '../db/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header('Location: ../authentication/login.php');
    exit();
}

$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT cars.*, 
                       (SELECT COUNT(*) FROM bookings WHERE car_id = cars.id AND status IN ('pending', 'confirmed')) AS booking_count
                       FROM cars WHERE provider_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cars = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Dashboard - Car Rental</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
<nav class="provider-nav">
    <ul>
        <li><a href="dashboard.php" class="active">Home</a></li>
        <li><a href="edit_car.php">Manage Cars</a></li>
        <li><a href="queries.php">Queries</a></li>
        <li><a href="income.php">Income</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="../authentication/logout.php">Logout</a></li>
    </ul>
</nav>

<div class="dashboard-container">
    <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?> (Provider)</h2>
    <div class="welcome-message">
        <p>Empower seamless travel with your premium vehicles at DCR Car Rental System.</p>
        <p>Manage your fleet and maximize earnings with ease today!</p>
    </div>
    <p>Manage your cars below.</p>
    <h3>Your Cars</h3>
    <div class="car-grid">
        <?php foreach ($cars as $car): ?>
        <div class="car-card">
            <?php
            // Decode images JSON or fallback to legacy image
            $images = $car['images'] ? json_decode($car['images'], true) : [];
            $primary_image = !empty($images) ? $images[0] : ($car['image'] ?: 'placeholder.jpg');
            ?>
            <div class="image-gallery">
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $image): ?>
                        <img src="../assets/images/<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($car['car_name']); ?>" class="gallery-image">
                    <?php endforeach; ?>
                <?php else: ?>
                    <img src="../assets/images/<?php echo htmlspecialchars($primary_image); ?>" alt="<?php echo htmlspecialchars($car['car_name']); ?>" class="gallery-image">
                <?php endif; ?>
            </div>
            <h4><?php echo htmlspecialchars($car['car_name']); ?></h4>
            <p>Price: $<?php echo htmlspecialchars($car['price_per_day']); ?>/day</p>
            <p>Seats: <?php echo htmlspecialchars($car['seats']); ?></p>
            <p>Availability: <?php echo htmlspecialchars($car['availability']); ?></p>
            <p>Status: <?php echo $car['booking_count'] > 0 ? 'Booked' : 'Available'; ?></p>
            <a href="edit_car.php?id=<?php echo $car['id']; ?>" class="btn-primary">Edit</a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include('../hf/footer.php'); ?>

<style>
body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #1e3a8a, #3b82f6);
    margin: 0;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    color: #f1f5f9;
}

.provider-nav {
    background: linear-gradient(to right, #111827, #1f2937);
    padding: 20px 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.provider-nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    gap: 30px;
}

.provider-nav li {
    margin: 0;
}

.provider-nav a {
    color: #e2e8f0;
    text-decoration: none;
    font-size: 18px;
    font-weight: 500;
    padding: 10px 15px;
    border-radius: 6px;
    transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease;
}

.provider-nav a:hover, .provider-nav a.active {
    background-color: #3b82f6;
    color: #ffffff;
    transform: translateY(-2px);
}

.dashboard-container {
    max-width: 1200px;
    margin: 60px auto;
    padding: 30px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    text-align: center;
    backdrop-filter: blur(10px);
}

h2, h3, h4 {
    color: #1e3a8a;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

h2 {
    font-size: 32px;
    margin-bottom: 20px;
}

h3 {
    font-size: 24px;
    margin-bottom: 15px;
}

h4 {
    font-size: 20px;
    margin-bottom: 10px;
}

p {
    color: #1e3a8a;
    font-size: 16px;
    margin-bottom: 20px;
}

.welcome-message {
    margin: 10px 0 20px;
}

.welcome-message p {
    color: #1e3a8a;
    font-size: 16px;
    font-weight: 400;
    line-height: 1.6;
    margin: 5px 0;
}

.car-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.car-card {
    background: #ffffff;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
}

.car-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.image-gallery {
    display: flex;
    overflow-x: auto;
    gap: 10px;
    margin-bottom: 15px;
    padding: 5px;
    scroll-snap-type: x mandatory;
}

.gallery-image {
    max-height: 100px;
    width: auto;
    border-radius: 6px;
    scroll-snap-align: start;
    object-fit: cover;
    transition: transform 0.3s ease-in-out;
}

.gallery-image:hover {
    transform: scale(1.1);
}

.btn-primary {
    display: inline-block;
    padding: 10px 20px;
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    color: #ffffff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 500;
    margin-top: 10px;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.site-footer {
    background: linear-gradient(to right, #111827, #1f2937);
    color: #e2e8f0;
    padding: 20px 0;
    text-align: center;
    margin-top: auto;
}

.footer-container {
    max-width: 1200px;
    margin: 0 auto;
}

.footer-container p {
    margin: 5px 0;
    font-size: 14px;
}

.footer-container a {
    color: #3b82f6;
    text-decoration: none;
    transition: color 0.3s ease-in-out;
}

.footer-container a:hover {
    color: #60a5fa;
    text-decoration: underline;
}
</style>
</body>
</html>