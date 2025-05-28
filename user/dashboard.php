<?php
// user/dashboard.php
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
session_start();

// Debug session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    error_log("Session missing: user_id=" . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'unset') . 
              ", role=" . (isset($_SESSION['role']) ? $_SESSION['role'] : 'unset'), 3, 'C:\xampp\logs\session_errors.log');
}

require_once '../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../authentication/login.php');
    exit();
}

$success = '';
$error = '';

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $error = "User not found.";
        session_destroy();
        header('Location: ../authentication/login.php');
        exit();
    }
} catch (PDOException $e) {
    $error = "Error fetching user: " . $e->getMessage();
    error_log("User fetch error: " . $e->getMessage(), 3, 'C:\xampp\logs\db_errors.log');
}

// Fetch available cars
try {
    $stmt = $pdo->prepare("SELECT cars.*, 
                           (SELECT COUNT(*) FROM bookings WHERE car_id = cars.id AND status IN ('pending', 'confirmed')) AS booking_count
                           FROM cars WHERE availability = 'available'");
    $stmt->execute();
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching cars: " . $e->getMessage();
    $cars = [];
    error_log("Cars fetch error: " . $e->getMessage(), 3, 'C:\xampp\logs\db_errors.log');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Car Rental</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
<nav class="user-nav">
    <ul>
        <li><a href="dashboard.php" class="active">Home</a></li>
        <li><a href="my_bookings.php">My Bookings</a></li>
        <li><a href="queries.php">Queries</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="../authentication/logout.php">Logout</a></li>
    </ul>
</nav>

<div class="dashboard-container">
    <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
    <div class="welcome-message">
        <p>Discover the joy of hassle-free travel with DCR Car Rental System.</p>
        <p>Book your perfect ride today and experience luxury on the go!</p>
    </div>
    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <p>Browse and book cars below.</p>
    <h3>Available Cars</h3>
    <?php if (empty($cars)): ?>
        <p>No available cars found.</p>
    <?php else: ?>
    <div class="car-grid">
        <?php foreach ($cars as $car): ?>
        <div class="car-card">
            <?php
            // Handle multiple images
            $images = isset($car['images']) && json_decode($car['images'], true) ? json_decode($car['images'], true) : ($car['image'] ? [$car['image']] : ['placeholder.jpg']);
            ?>
            <div class="image-gallery">
                <?php foreach ($images as $index => $image): ?>
                    <a href="../assets/images/<?php echo htmlspecialchars($image); ?>" class="gallery-link" data-lightbox="car-<?php echo $car['id']; ?>">
                        <img src="../assets/images/<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($car['car_name'] . ' image ' . ($index + 1)); ?>" class="gallery-image">
                    </a>
                <?php endforeach; ?>
            </div>
            <h4><?php echo htmlspecialchars($car['car_name']); ?></h4>
            <p>Price: $<?php echo htmlspecialchars($car['price_per_day']); ?>/day</p>
            <p>Seats: <?php echo htmlspecialchars($car['seats']); ?></p>
            <p>Status: <?php echo $car['booking_count'] > 0 ? 'Booked' : 'Available'; ?></p>
            <?php if ($car['booking_count'] == 0): ?>
                <a href="book_car.php?car_id=<?php echo $car['id']; ?>" class="btn-primary">Book Now</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include('../hf/footer.php'); ?>

<!-- Lightbox2 Library -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>

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

.user-nav {
    background: linear-gradient(to right, #111827, #1f2937);
    padding: 20px 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.user-nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    gap: 30px;
}

.user-nav li {
    margin: 0;
}

.user-nav a {
    color: #e2e8f0;
    text-decoration: none;
    font-size: 18px;
    font-weight: 500;
    padding: 10px 15px;
    border-radius: 6px;
    transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease;
}

.user-nav a:hover, .user-nav a.active {
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

h2, h3 {
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

.car-card h4 {
    color: #1e3a8a;
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 10px;
}

.car-card p {
    color: #1e3a8a;
    font-size: 16px;
    font-weight: 500;
    line-height: 1.5;
    margin: 5px 0;
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
    max-height: 150px;
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

.query-form {
    max-width: 500px;
    margin: 20px auto;
    display: flex;
    flex-direction: column;
    gap: 15px;
    background: rgba(255, 255, 255, 0.8);
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.form-input {
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 16px;
    background: #f9fafb;
    transition: border-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 8px rgba(59, 130, 246, 0.3);
}

textarea.form-input {
    resize: vertical;
}

.success {
    color: #2ecc71;
    text-align: center;
    font-size: 16px;
    margin-bottom: 15px;
    background: rgba(46, 204, 113, 0.1);
    padding: 10px;
    border-radius: 6px;
}

.error {
    color: #e74c3c;
    text-align: center;
    font-size: 16px;
    margin-bottom: 15px;
    background: rgba(231, 76, 60, 0.1);
    padding: 10px;
    border-radius: 6px;
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