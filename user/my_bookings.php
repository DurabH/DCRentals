<?php
// user/my_bookings.php
require_once '../db/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../authentication/login.php');
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return_booking_id'])) {
    $booking_id = $_POST['return_booking_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = hash('sha256', trim($_POST['password']));

    try {
        // Verify user credentials
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND name = ? AND email = ? AND password = ?");
        $stmt->execute([$_SESSION['user_id'], $name, $email, $password]);
        $user = $stmt->fetch();

        if ($user) {
            // Fetch booking details for amount
            $stmt = $pdo->prepare("
                SELECT 
                    (DATEDIFF(bookings.return_date, bookings.booking_date) + 1) * cars.price_per_day AS total_amount
                FROM bookings 
                JOIN cars ON bookings.car_id = cars.id 
                WHERE bookings.id = ? AND bookings.user_id = ?
            ");
            $stmt->execute([$booking_id, $_SESSION['user_id']]);
            $booking = $stmt->fetch();

            if ($booking) {
                // Update booking status to completed
                $stmt = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ? AND user_id = ?");
                $stmt->execute([$booking_id, $_SESSION['user_id']]);

                // Log payment in payments table
                $stmt = $pdo->prepare("INSERT INTO payments (booking_id, user_id, amount) VALUES (?, ?, ?)");
                $stmt->execute([$booking_id, $_SESSION['user_id'], $booking['total_amount']]);

                $success = "Payment successful! Booking marked as completed.";
                // Redirect to dashboard after a short delay to show success message
                header('Refresh: 2; URL=dashboard.php');
            } else {
                $error = "Booking not found or unauthorized.";
            }
        } else {
            $error = "Invalid credentials. Please try again.";
        }
    } catch (PDOException $e) {
        $error = "Error processing payment: " . $e->getMessage();
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            bookings.*, 
            cars.car_name,
            cars.price_per_day,
            (DATEDIFF(bookings.return_date, bookings.booking_date) + 1) AS duration,
            ((DATEDIFF(bookings.return_date, bookings.booking_date) + 1) * cars.price_per_day) AS total_amount
        FROM bookings 
        JOIN cars ON bookings.car_id = cars.id 
        WHERE bookings.user_id = ?
        ORDER BY bookings.id ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
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
    <title>My Bookings - Car Rental</title>
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
    <h2>My Bookings</h2>
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
                <td><?php echo htmlspecialchars($booking['car_name']); ?></td>
                <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                <td><?php echo htmlspecialchars($booking['return_date']); ?></td>
                <td><?php echo htmlspecialchars($booking['status']); ?></td>
                <td>$<?php echo number_format($booking['price_per_day'], 2); ?></td>
                <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                <td>
                    <?php if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                        <button class="btn-primary return-car-btn" data-booking-id="<?php echo $booking['id']; ?>">Return Car</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Complete Payment</h3>
        <form id="paymentForm" action="" method="POST">
            <input type="hidden" name="return_booking_id" id="return_booking_id">
            <input type="text" name="name" placeholder="Your Name" required class="form-input">
            <input type="email" name="email" placeholder="Your Email" required class="form-input">
            <input type="password" name="password" placeholder="Your Password" required class="form-input">
            <button type="submit" class="btn-primary">Submit Payment</button>
            <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

<?php include('../hf/footer.php'); ?>

<script>
function openModal(bookingId) {
    document.getElementById('return_booking_id').value = bookingId;
    document.getElementById('paymentModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('paymentModal').style.display = 'none';
    document.getElementById('paymentForm').reset();
}

document.querySelectorAll('.return-car-btn').forEach(button => {
    button.addEventListener('click', function() {
        const bookingId = this.getAttribute('data-booking-id');
        openModal(bookingId);
    });
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('paymentModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

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

.btn-primary {
    padding: 10px 15px;
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

.btn-secondary {
    padding: 10px 15px;
    background: linear-gradient(135deg, #6b7280, #9ca3af);
    color: #ffffff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(107, 114, 128, 0.4);
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

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
}

.modal-content {
    background: rgba(255, 255, 255, 0.95);
    margin: 15% auto;
    padding: 30px;
    border-radius: 12px;
    max-width: 400px;
    text-align: center;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(10px);
}

.form-input {
    padding: 12px;
    margin: 10px 0;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 16px;
    background: #f9fafb;
    width: calc(100% - 24px);
    transition: border-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 8px rgba(59, 130, 246, 0.3);
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