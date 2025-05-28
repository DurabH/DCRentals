<?php
// admin/bookings.php
require_once '../db/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../authentication/login.php');
    exit();
}

$success = '';
$error = '';

if (isset($_POST['delete_booking_id'])) {
    $booking_id = $_POST['delete_booking_id'];
    try {
        // Check booking status
        $stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            $error = "Booking not found.";
        } elseif ($booking['status'] === 'pending') {
            $error = "User will not like this and this will also degrade your company's profile as fraudulent company.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
            // Note: AUTO_INCREMENT reset may not work as expected if IDs exist
            $pdo->query("ALTER TABLE bookings AUTO_INCREMENT = 1");
            $success = "Booking deleted successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error deleting booking: " . $e->getMessage();
    }
}

try {
    $stmt = $pdo->query("
        SELECT 
            bookings.*, 
            users.name AS user_name, 
            cars.car_name,
            cars.price_per_day,
            (DATEDIFF(bookings.return_date, bookings.booking_date) + 1) AS duration,
            ((DATEDIFF(bookings.return_date, bookings.booking_date) + 1) * cars.price_per_day) AS total_amount
        FROM bookings 
        JOIN users ON bookings.user_id = users.id 
        JOIN cars ON bookings.car_id = cars.id 
        ORDER BY bookings.id ASC
    ");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching bookings: " . $e->getMessage();
    $bookings = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Car Rental</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
<nav class="admin-nav">
    <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="view_users.php">View Users</a></li>
        <li><a href="view_cars.php">View Cars</a></li>
        <li><a href="bookings.php" class="active">Bookings</a></li>
        <li><a href="query.php">Queries</a></li>
        <li><a href="revenue.php">Revenue</a></li>
        <li><a href="../authentication/logout.php">Logout</a></li>
    </ul>
</nav>

<div class="dashboard-container">
    <h2>Manage Bookings</h2>
    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if (empty($bookings)): ?>
        <p>No bookings found.</p>
    <?php else: ?>
    <table class="styled-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Car</th>
                <th>Booking Date</th>
                <th>Return Date</th>
                <th>Status</th>
                <th>Price/Day</th>
                <th>Total Amount</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $booking): ?>
            <tr>
                <td><?php echo htmlspecialchars($booking['id']); ?></td>
                <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                <td><?php echo htmlspecialchars($booking['car_name']); ?></td>
                <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                <td><?php echo htmlspecialchars($booking['return_date']); ?></td>
                <td><?php echo htmlspecialchars($booking['status']); ?></td>
                <td>$<?php echo number_format($booking['price_per_day'], 2); ?></td>
                <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                <td>
                    <form action="" method="POST" class="delete-form">
                        <input type="hidden" name="delete_booking_id" value="<?php echo $booking['id']; ?>">
                        <button type="submit" class="btn-danger" onclick="return confirm('Are you sure you want to delete this booking?');">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
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
    max-width: 1000px;
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

.delete-form {
    display: inline;
}

.btn-danger {
    padding: 10px 15px;
    background: linear-gradient(135deg, #e74c3c, #f97316);
    color: #ffffff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
}

.success {
    color: #2ecc71;
    text-align: center;
    font-size: 18px;
    margin-bottom: 20px;
    background: rgba(46, 204, 113, 0.1);
    padding: 10px;
    border-radius: 6px;
}

.error {
    color: #e74c3c;
    text-align: center;
    font-size: 18px;
    margin-bottom: 20px;
    background: rgba(231, 76, 60, 0.1);
    padding: 10px;
    border-radius: 6px;
}
</style>
</body>
</html>