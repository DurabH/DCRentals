<?php
// admin/view_cars.php
require_once '../db/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../authentication/login.php');
    exit();
}

// Modified query to include duration and total amount
$stmt = $pdo->query("
    SELECT 
        cars.*, 
        users.name AS provider_name,
        (
            SELECT COUNT(*) 
            FROM bookings 
            WHERE car_id = cars.id AND status IN ('pending', 'confirmed')
        ) AS booking_count,
        (
            SELECT SUM(DATEDIFF(return_date, booking_date) + 1)
            FROM bookings 
            WHERE car_id = cars.id AND status IN ('pending', 'confirmed')
        ) AS total_duration,
        (
            SELECT SUM((DATEDIFF(return_date, booking_date) + 1) * cars.price_per_day)
            FROM bookings 
            WHERE car_id = cars.id AND status IN ('pending', 'confirmed')
        ) AS total_amount
    FROM cars 
    JOIN users ON cars.provider_id = users.id
");
$cars = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Cars - Car Rental</title>
    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
</head>
<body>
<nav class="admin-nav">
    <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="view_users.php">View Users</a></li>
        <li><a href="view_cars.php" class="active">View Cars</a></li>
        <li><a href="bookings.php">Bookings</a></li>
        <li><a href="query.php">Queries</a></li>
        <li><a href="revenue.php">Revenue</a></li>
        <li><a href="../authentication/logout.php">Logout</a></li>
    </ul>
</nav>

<div class="dashboard-container">
    <h2>All Cars</h2>
    <table class="styled-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Provider</th>
                <th>Car Name</th>
                <th>Price/Day</th>
                <th>Seats</th>
                <th>Availability</th>
                <th>Booking Status</th>
                <th>Duration (Days)</th>
                <th>Total Amount</th>
                <th>Images</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cars as $car): ?>
            <tr>
                <td><?php echo htmlspecialchars($car['id']); ?></td>
                <td><?php echo htmlspecialchars($car['provider_name']); ?></td>
                <td><?php echo htmlspecialchars($car['car_name']); ?></td>
                <td>$<?php echo htmlspecialchars($car['price_per_day']); ?></td>
                <td><?php echo htmlspecialchars($car['seats']); ?></td>
                <td><?php echo htmlspecialchars($car['availability']); ?></td>
                <td><?php echo $car['booking_count'] > 0 ? 'Booked' : 'Available'; ?></td>
                <td><?php echo $car['total_duration'] ? htmlspecialchars($car['total_duration']) : '0'; ?></td>
                <td>$<?php echo $car['total_amount'] ? number_format($car['total_amount'], 2) : '0.00'; ?></td>
                <td>
                    <?php
                    // Handle multiple images
                    $images = isset($car['images']) ? json_decode($car['images'], true) : ($car['image'] ? [$car['image']] : ['placeholder.jpg']);
                    ?>
                    <div class="image-gallery">
                        <?php foreach ($images as $index => $image): ?>
                            <a href="../assets/images/<?php echo htmlspecialchars($image); ?>" class="gallery-link" data-lightbox="car-<?php echo $car['id']; ?>">
                                <img src="../assets/images/<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($car['car_name'] . ' image ' . ($index + 1)); ?>" class="gallery-image">
                            </a>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include('../hf/footer.php'); ?>

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
    max-width: 1200px;
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
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.styled-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: #ffffff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.styled-table th, .styled-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
    color: #1e3a8a;
}

.styled-table th {
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    color: #ffffff;
    font-weight: 600;
}

.styled-table tr {
    transition: background-color 0.3s ease-in-out, transform 0.2s ease-in-out;
}

.styled-table tr:hover {
    background-color: #f1f5f9;
    transform: translateY(-2px);
}

.image-gallery {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.gallery-image {
    max-height: 60px;
    width: auto;
    border-radius: 6px;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
}

.gallery-image:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}
</style>
</body>
</html>