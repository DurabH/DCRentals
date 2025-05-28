<?php
// user/book_car.php
require_once '../db/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../authentication/login.php');
    exit();
}

if (!isset($_GET['car_id'])) {
    header('Location: dashboard.php');
    exit();
}

$car_id = $_GET['car_id'];
$stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ? AND availability = 'available'");
$stmt->execute([$car_id]);
$car = $stmt->fetch();

if (!$car) {
    header('Location: dashboard.php');
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_date = trim($_POST['booking_date']);
    $return_date = trim($_POST['return_date']);
    
    // Validate dates
    $today = date('Y-m-d');
    if ($booking_date < $today) {
        $error = "Booking date cannot be in the past.";
    } elseif ($return_date <= $booking_date) {
        $error = "Return date must be after booking date.";
    } else {
        // Check if car is available for the selected dates
        $check = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE car_id = ? AND status IN ('pending', 'confirmed') 
                                AND ((? BETWEEN booking_date AND return_date) OR (? BETWEEN booking_date AND return_date) 
                                OR (booking_date BETWEEN ? AND ?))");
        $check->execute([$car_id, $booking_date, $return_date, $booking_date, $return_date]);
        $conflict = $check->fetchColumn();

        if ($conflict > 0) {
            $error = "This car is not available for the selected dates.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, car_id, booking_date, return_date, status) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $car_id, $booking_date, $return_date, 'pending']);
            $success = "Car booked successfully! Awaiting confirmation.";
            header('Location: my_bookings.php');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Car - Car Rental</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
<nav class="user-nav">
    <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="my_bookings.php" class="active">My Bookings</a></li>
        <li><a href="queries.php">Queries</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="../authentication/logout.php">Logout</a></li>
    </ul>
</nav>

<div class="dashboard-container">
    <h2>Book <?php echo htmlspecialchars($car['car_name']); ?></h2>
    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form action="" method="POST" class="booking-form">
        <label for="booking_date">Booking Date:</label>
        <input type="date" name="booking_date" required class="form-input" min="<?php echo date('Y-m-d'); ?>">
        <label for="return_date">Return Date:</label>
        <input type="date" name="return_date" required class="form-input" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
        <button type="submit" class="btn-primary">Confirm Booking</button>
    </form>
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
    max-width: 600px;
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

.booking-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
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

label {
    color: #1e3a8a;
    font-size: 16px;
    font-weight: 500;
    text-align: left;
    margin-bottom: 5px;
}

.btn-primary {
    padding: 12px;
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    color: #ffffff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
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