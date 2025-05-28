<?php
// hf/footer.php
?>
<footer class="site-footer">
    <div class="footer-container">
        <p>© <?php echo date("Y"); ?> Car Rental System. All rights reserved.</p>
        <p>Contact: <a href="mailto:haiderdurab21@gmail.com">support@carrental.com</a> | Phone: (+92) 309-5180478</p>
    </div>
</footer>

<style>
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