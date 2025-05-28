<?php
// provider/about.php
require_once '../db/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header('Location: ../authentication/login.php');
    exit();
}

$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $message]);
    $success = "Your message has been sent successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Car Rental</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
<nav class="provider-nav">
    <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="edit_car.php">Manage Cars</a></li>
        <li><a href="queries.php">Queries</a></li>
        <li><a href="income.php">Income</a></li>
        <li><a href="about.php" class="active">About</a></li>
        <li><a href="../authentication/logout.php">Logout</a></li>
    </ul>
</nav>

<div class="dashboard-container">
    <h2>About Us</h2>
    <div class="about-content">
        <p>Welcome to Car Rental System! We are dedicated to providing top-quality car rental services at affordable prices. Whether you need a compact car for city driving or a spacious SUV for a family trip, we have a wide range of vehicles to suit your needs.</p>
        <p>Our mission is to make car rental easy, fast, and reliable. With a user-friendly platform, you can browse, book, and manage your rentals with ease. We partner with trusted providers to ensure every car meets high safety and quality standards.</p>
        <h3>Our Values</h3>
        <ul>
            <li>Customer Satisfaction: Your comfort and convenience are our priorities.</li>
            <li>Transparency: Clear pricing with no hidden fees.</li>
            <li>Reliability: Well-maintained vehicles you can trust.</li>
        </ul>
        <h3>Contact Information</h3>
        <p>Email: <a href="mailto:haiderdurab21@gmail.com">support@dcrcarrental.com</a></p>
        <p>Phone: (+92) 309-5180478</p>
        <p>Address: Murid, Chakwal, Punjab, Pakistan</p>
    </div>

    <h3>Contact Us</h3>
    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <form action="" method="POST" class="contact-form">
        <input type="text" name="name" placeholder="Your Name" required class="form-input">
        <input type="email" name="email" placeholder="Your Email" required class="form-input">
        <textarea name="message" placeholder="Your Message" required class="form-input" rows="4"></textarea>
        <button type="submit" class="btn-primary">Send Message</button>
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
    max-width: 800px;
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

.about-content {
    margin-bottom: 30px;
}

.about-content p, .about-content ul {
    color: #1e3a8a;
    line-height: 1.8;
    font-size: 16px;
}

.about-content ul {
    list-style: disc;
    margin-left: 30px;
    text-align: left;
}

.about-content a {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease-in-out;
}

.about-content a:hover {
    color: #60a5fa;
    text-decoration: underline;
}

.contact-form {
    max-width: 500px;
    margin: 0 auto;
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

textarea.form-input {
    resize: vertical;
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