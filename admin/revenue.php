<?php
// admin/revenue.php
require_once '../db/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../authentication/logout.php');
    exit();
}

$error = '';
try {
    // Get current month and year
    $currentMonth = date('m');
    $currentYear = date('Y');

    // Count total bookings and calculate total revenue
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT b.id) AS total_bookings, COALESCE(SUM(p.amount), 0) AS total_revenue
        FROM bookings b
        JOIN payments p ON b.id = p.booking_id
        WHERE b.status IN ('confirmed', 'completed')
        AND YEAR(p.payment_date) = ?
        AND MONTH(p.payment_date) = ?
    ");
    $stmt->execute([$currentYear, $currentMonth]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_bookings = $result['total_bookings'];
    $total_revenue = $result['total_revenue'];

    // Calculate total tax (25% per provider)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(p.amount), 0) AS provider_revenue
        FROM bookings b
        JOIN cars c ON b.car_id = c.id
        JOIN payments p ON b.id = p.booking_id
        WHERE b.status IN ('confirmed', 'completed')
        AND YEAR(p.payment_date) = ?
        AND MONTH(p.payment_date) = ?
        GROUP BY c.provider_id
    ");
    $stmt->execute([$currentYear, $currentMonth]);
    $provider_revenues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tax_rate = 0.25;
    $total_tax = 0;
    foreach ($provider_revenues as $revenue) {
        $total_tax += $revenue['provider_revenue'] * $tax_rate;
    }
    $providers_share = $total_revenue - $total_tax;
} catch (PDOException $e) {
    $error = "Error fetching revenue data: " . $e->getMessage();
    $total_bookings = 0;
    $total_revenue = 0;
    $total_tax = 0;
    $providers_share = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue - Car Rental</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
<nav class="admin-nav">
    <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="view_users.php">View Users</a></li>
        <li><a href="view_cars.php">View Cars</a></li>
        <li><a href="bookings.php">Bookings</a></li>
        <li><a href="query.php">Queries</a></li>
        <li><a href="revenue.php" class="active">Revenue</a></li>
        <li><a href="../authentication/logout.php">Logout</a></li>
    </ul>
</nav>

<div class="dashboard-container">
    <h2>Company Revenue (<?php echo date('F Y'); ?>)</h2>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    
    <div class="revenue-details">
        <p><strong>Total Bookings This Month:</strong> <?php echo htmlspecialchars($total_bookings); ?> booking(s)</p>
        <p><strong>Total Revenue:</strong> $<?php echo number_format($total_revenue, 2); ?></p>
        <p><strong>Company Wealth (25% Tax):</strong> $<?php echo number_format($total_tax, 2); ?></p>
        <p><strong>Providers' Share (75%):</strong> $<?php echo number_format($providers_share, 2); ?></p>
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

.revenue-details {
    background: rgba(255, 255, 255, 0.8);
    padding: 20px;
    border-radius: 12px;
    margin-top: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.revenue-details p {
    margin: 15px 0;
    color: #1e3a8a;
    font-size: 18px;
    font-weight: 500;
    transition: transform 0.2s ease-in-out;
}

.revenue-details p:hover {
    transform: translateY(-2px);
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