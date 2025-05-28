<?php
// admin/dashboard.php
require_once '../db/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../authentication/login.php');
    exit();
}

$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Query to get the most booked cars (without image)
$stmt_cars = $pdo->prepare("
    SELECT c.id, c.car_name AS name, c.price_per_day, COUNT(b.id) AS booking_count
    FROM cars c
    LEFT JOIN bookings b ON c.id = b.car_id
    GROUP BY c.id, c.car_name, c.price_per_day
    ORDER BY booking_count DESC
    LIMIT 3
");
$stmt_cars->execute();
$featured_cars = $stmt_cars->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Car Rental</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
<nav class="admin-nav">
    <ul>
        <li><a href="dashboard.php" class="active">Home</a></li>
        <li><a href="view_users.php">View Users</a></li>
        <li><a href="view_cars.php">View Cars</a></li>
        <li><a href="bookings.php">Bookings</a></li>
        <li><a href="query.php">Queries</a></li>
        <li><a href="revenue.php">Revenue</a></li>
        <li><a href="../authentication/logout.php">Logout</a></li>
    </ul>
</nav>

<div class="dashboard-container">
    <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?></h2>
    <p>Manage users, cars, bookings, and queries from the navigation bar above.</p>

    <!-- Slideshow Section (Original) -->
    <div class="slideshow-container">
        <div class="mySlides fade">
            <img src="../assets/images/lc300.jpeg" alt="Car 1">
        </div>
        <div class="mySlides fade">
            <img src="../assets/images/reb.jpeg" alt="Car 2">
        </div>
        <div class="mySlides fade">
            <img src="../assets/images/wag.jpeg" alt="Car 3">
        </div>
    </div>
    <p class="promo-text">
        <strong>Rent A Car with DCR</strong><br>
        Experience luxury and convenience with our chauffeur-driven car rental service. 
        Whether you're traveling for business or pleasure, sit back and relax as our professional chauffeurs take the wheel.
    </p>

    <!-- Why Use DCR Section -->
    <div class="why-dcr">
        <h3>Why Use DCR?</h3>
        <p>We provide the best deals in Pakistan.</p>
        <div class="features">
            <div class="feature-item">
                <h4>More for Less</h4>
                <p>We offer bookings with exceptional discounted deals across the country</p>
            </div>
            <div class="feature-item">
                <h4>Lowest Fares</h4>
                <p>We provide affordable tickets to save up to 20%</p>
            </div>
            <div class="feature-item">
                <h4>Discover</h4>
                <p>We make travelling easy across Pakistan by providing easy bookings</p>
            </div>
        </div>
    </div>

    <!-- Featured Cars Section -->
    <div class="featured-cars">
        <h3>Our Most Featured Cars</h3>
        <ul>
            <?php foreach ($featured_cars as $car): ?>
                <li>
                    <?php echo htmlspecialchars($car['name']); ?> 
                    (Price per Day: $<?php echo number_format($car['price_per_day'], 2); ?>, 
                    Bookings: <?php echo $car['booking_count']; ?>)
                </li>
            <?php endforeach; ?>
        </ul>
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

.admin-nav {
    background: linear-gradient(to right, #111827, #1f2937);
    padding: 20px 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.admin-nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    gap: 30px;
}

.admin-nav li {
    margin: 0;
}

.admin-nav a {
    color: #e2e8f0;
    text-decoration: none;
    font-size: 18px;
    font-weight: 500;
    padding: 10px 15px;
    border-radius: 6px;
    transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease;
}

.admin-nav a:hover, .admin-nav a.active {
    background-color: #3b82f6;
    color: #ffffff;
    transform: translateY(-2px);
}

.dashboard-container {
    max-width: 900px;
    margin: 60px auto;
    padding: 30px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    text-align: center;
    backdrop-filter: blur(10px);
}

h2 {
    color: #1e3a8a;
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

h3 {
    color: #1e3a8a;
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 15px;
}

p {
    font-size: 16px;
    color: #4b5563;
    line-height: 1.6;
}

.slideshow-container {
    max-width: 100%;
    position: relative;
    margin: auto;
    border-radius: 8px;
    overflow: hidden;
}

.mySlides {
    display: none;
}

.mySlides img {
    width: 100%;
    max-height: 250px;
    object-fit: contain;
    border-radius: 8px;
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
    transition: transform 0.5s ease;
}

.mySlides img:hover {
    transform: scale(1.05);
}

.fade {
    animation: fade 1.5s ease-in-out;
}

@keyframes fade {
    from { opacity: 0.5; }
    to { opacity: 1; }
}

.promo-text {
    font-size: 20px;
    color: #1e3a8a;
    margin: 20px 0;
    font-weight: 500;
    background: linear-gradient(90deg,rgb(46, 63, 121),rgb(27, 10, 122));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    padding: 10px;
}

.why-dcr {
    margin-top: 50px;
    padding: 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
}

.features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.feature-item {
    padding: 20px;
    background: linear-gradient(135deg, #ffffff, #f1f5f9);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: transform 0.4s ease-in-out, box-shadow 0.4s ease-in-out, background 0.4s ease-in-out;
}

.feature-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    color: #ffffff;
}

.feature-item h4 {
    color: #1e3a8a;
    font-size: 20px;
    margin-bottom: 10px;
    transition: color 0.4s ease-in-out;
}

.feature-item:hover h4 {
    color: #ffffff;
}

.feature-item p {
    color: #4b5563;
    transition: color 0.4s ease-in-out;
}

.feature-item:hover p {
    color: #e2e8f0;
}

.featured-cars {
    margin-top: 50px;
    padding: 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
}

.featured-cars ul {
    list-style: none;
    padding: 0;
}

.featured-cars li {
    font-size: 18px;
    color: #1e3a8a;
    margin: 15px 0;
    padding: 15px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.featured-cars li:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
}
</style>

<script>
let slideIndex = 0;
showSlides();

function showSlides() {
    let slides = document.getElementsByClassName("mySlides");
    for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";  
    }
    slideIndex++;
    if (slideIndex > slides.length) {slideIndex = 1}    
    slides[slideIndex-1].style.display = "block";  
    setTimeout(showSlides, 3000);
}
</script>
</body>
</html>