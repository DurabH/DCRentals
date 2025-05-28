<?php
// provider/income.php
require_once '../db/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header('Location: ../authentication/logout.php');
    exit();
}

$error = '';
try {
    // Get current month and year
    $currentMonth = date('m');
    $currentYear = date('Y');

    // Count bookings and calculate revenue for provider's cars in the current month
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT b.id) AS booking_count, COALESCE(SUM(p.amount), 0) AS total_revenue
        FROM bookings b
        JOIN cars c ON b.car_id = c.id
        JOIN payments p ON b.id = p.booking_id
        WHERE c.provider_id = ?
        AND b.status IN ('confirmed', 'completed')
        AND YEAR(p.payment_date) = ?
        AND MONTH(p.payment_date) = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $currentYear, $currentMonth]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $booking_count = $result['booking_count'];
    $total_revenue = $result['total_revenue'];
    $tax_rate = 0.25;
    $tax_amount = $total_revenue * $tax_rate;
    $provider_income = $total_revenue * (1 - $tax_rate);
} catch (PDOException $e) {
    $error = "Error fetching income data: " . $e->getMessage();
    $booking_count = 0;
    $total_revenue = 0;
    $tax_amount = 0;
    $provider_income = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income - Car Rental</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
<nav class="provider-nav">
    <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="edit_car.php">Manage Cars</a></li>
        <li><a href="queries.php">Queries</a></li>
        <li><a href="income.php" class="active">Income</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="../authentication/logout.php">Logout</a></li>
    </ul>
</nav>

<div class="dashboard-container">
    <h2>Your Income (<?php echo date('F Y'); ?>)</h2>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    
    <div class="income-details">
        <p><strong>Bookings This Month:</strong> <?php echo htmlspecialchars($booking_count); ?> booking(s)</p>
        <p><strong>Total Revenue:</strong> $<?php echo number_format($total_revenue, 2); ?></p>
        <p><strong>Company Tax (25%):</strong> $<?php echo number_format($tax_amount, 2); ?> (sent to company)</p>
        <p><strong>Your Income (75%):</strong> $<?php echo number_format($provider_income, 2); ?></p>
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

.income-details {
    background: rgba(255, 255, 255, 0.8);
    padding: 20px;
    border-radius: 12px;
    margin-top: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.income-details p {
    margin: 15px 0;
    color: #1e3a8a;
    font-size: 18px;
    font-weight: 500;
    transition: transform 0.2s ease-in-out;
}

.income-details p:hover {
    transform: translateY(-2px);
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