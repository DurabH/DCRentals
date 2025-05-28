<?php
// provider/edit_car.php
require_once '../db/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header('Location: ../authentication/login.php');
    exit();
}

// Fetch provider's name
try {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? AND role = 'provider'");
    $stmt->execute([$_SESSION['user_id']]);
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);
    $provider_name = $provider ? htmlspecialchars($provider['name']) : 'Unknown Provider';
} catch (PDOException $e) {
    $provider_name = 'Error fetching name';
}

$success = '';
$car = null;
$action = 'add';

if (isset($_GET['id'])) {
    $action = 'edit';
    $car_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ? AND provider_id = ?");
    $stmt->execute([$car_id, $_SESSION['user_id']]);
    $car = $stmt->fetch();
    if (!$car) {
        header('Location: dashboard.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $car_name = trim($_POST['car_name']);
    $price_per_day = trim($_POST['price_per_day']);
    $seats = trim($_POST['seats']);
    $availability = trim($_POST['availability']);
    $images = $car && $car['images'] ? json_decode($car['images'], true) : [];

    // Handle multiple image uploads
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $target_dir = "../assets/images/";
        $new_images = [];
        foreach ($_FILES['images']['name'] as $key => $name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $image = basename($name);
                $target_file = $target_dir . $image;
                if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $target_file)) {
                    $new_images[] = $image;
                }
            }
        }
        // Merge new images with existing ones (for edit) or use new ones (for add)
        $images = $action === 'edit' ? array_merge($images, $new_images) : $new_images;
    }

    $images_json = !empty($images) ? json_encode($images) : null;

    try {
        if ($action === 'edit') {
            $stmt = $pdo->prepare("UPDATE cars SET car_name = ?, price_per_day = ?, seats = ?, availability = ?, images = ? WHERE id = ?");
            $stmt->execute([$car_name, $price_per_day, $seats, $availability, $images_json, $car_id]);
            $success = "Car updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO cars (provider_id, car_name, price_per_day, seats, availability, images) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $car_name, $price_per_day, $seats, $availability, $images_json]);
            $success = "Car added successfully!";
        }
        header('Location: dashboard.php');
        exit();
    } catch (PDOException $e) {
        $success = "Database error: " . $e->getMessage();
    }
}

if (isset($_GET['delete_id'])) {
    try {
        $delete_id = $_GET['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ? AND provider_id = ?");
        $stmt->execute([$delete_id, $_SESSION['user_id']]);
        $pdo->query("ALTER TABLE cars AUTO_INCREMENT = 1");
        header('Location: dashboard.php');
        exit();
    } catch (PDOException $e) {
        $success = "Error deleting car: " . $e->getMessage();
    }
}

try {
    $stmt = $pdo->prepare("SELECT cars.*, 
                           (SELECT COUNT(*) FROM bookings WHERE car_id = cars.id AND status IN ('pending', 'confirmed')) AS booking_count
                           FROM cars WHERE provider_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $success = "Error fetching cars: " . $e->getMessage();
    $cars = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cars - Car Rental</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
<nav class="provider-nav">
    <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="edit_car.php" class="active">Manage Cars</a></li>
        <li><a href="queries.php">Queries</a></li>
        <li><a href="income.php">Income</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="../authentication/logout.php">Logout</a></li>
    </ul>
</nav>

<div class="dashboard-container">
    <h2><?php echo $action === 'edit' ? 'Edit Car: ' . htmlspecialchars($car['car_name']) : 'Add New Car'; ?></h2>
    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <form action="" method="POST" enctype="multipart/form-data" class="car-form">
        <input type="text" name="car_name" value="<?php echo $car ? htmlspecialchars($car['car_name']) : ''; ?>" placeholder="Car Name" required class="form-input">
        <input type="number" name="price_per_day" value="<?php echo $car ? htmlspecialchars($car['price_per_day']) : ''; ?>" placeholder="Price per Day" required class="form-input">
        <input type="number" name="seats" value="<?php echo $car ? htmlspecialchars($car['seats']) : ''; ?>" placeholder="Seats" required class="form-input">
        <select name="availability" required class="form-input">
            <option value="available" <?php if ($car && $car['availability'] == 'available') echo 'selected'; ?>>Available</option>
            <option value="unavailable" <?php if ($car && $car['availability'] == 'unavailable') echo 'selected'; ?>>Unavailable</option>
        </select>
        <label for="images" class="form-label">Upload Images (multiple allowed):</label>
        <input type="file" name="images[]" id="images" accept="image/*" multiple class="form-input">
        <?php if ($car && $car['images']): ?>
            <p>Existing Images:</p>
            <div class="image-gallery">
                <?php foreach (json_decode($car['images'], true) as $image): ?>
                    <img src="../assets/images/<?php echo htmlspecialchars($image); ?>" alt="Car Image" class="gallery-image">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <button type="submit" class="btn-primary"><?php echo $action === 'edit' ? 'Update Car' : 'Add Car'; ?></button>
    </form>

    <h3>Your Cars</h3>
    <?php if (empty($cars)): ?>
        <p>No cars found.</p>
    <?php else: ?>
    <div class="car-grid">
        <?php foreach ($cars as $car): ?>
        <div class="car-card">
            <?php
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
            <p>Provider: <?php echo $provider_name; ?>, Status: <?php echo $car['booking_count'] > 0 ? 'Booked' : 'Available'; ?></p>
            <a href="edit_car.php?id=<?php echo $car['id']; ?>" class="btn-primary">Edit</a>
            <a href="edit_car.php?delete_id=<?php echo $car['id']; ?>" class="btn-danger" onclick="return confirm('Are you sure to delete this car?');">Delete</a>
        </div>
        <?php endforeach; ?>
    </div>
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

.car-form {
    max-width: 500px;
    margin: 0 auto 30px;
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

.form-label {
    color: #1e3a8a;
    margin: 10px 0 5px;
    font-size: 16px;
    font-weight: 500;
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

.btn-danger {
    display: inline-block;
    padding: 10px 20px;
    background: linear-gradient(135deg, #e74c3c, #f97316);
    color: #ffffff;
    text-decoration: none;
    6px;
    font-size: 16px;
    font-weight: 500;
    margin-top: 10px;
    border-radius: 4px;
    transition: transform 0.2s ease-in-out;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
}

.car-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin: auto;

}

.car-card {
    background: #ffffff;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0,0.1);
    transition: transform 0.3s ease-in-out;
}

.car-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
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