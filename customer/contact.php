<?php
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Contact Us - DigitalDine';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email address is required';
    }

    if (empty($subject)) {
        $errors[] = 'Subject is required';
    }

    if (empty($message)) {
        $errors[] = 'Message is required';
    }

    if (empty($errors)) {
        // In a real application, you would send an email here
        // For now, we'll just show a success message
        $success = 'Thank you for your message! We will get back to you within 24 hours.';

        // You could also store contact messages in a database table
        // For this demo, we'll just show the success message
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<?php
$hero_title = 'Contact Us';
$hero_subtitle = "We'd love to hear from you. Send us a message and we'll respond as soon as possible.";
$hero_image = '/restaurant_project/assets/images/hero-bg.jpg';
$hero_cta_text = 'View Location';
$hero_cta_href = 'contact.php#find-us';
include __DIR__ . '/../includes/hero.php';
?>

<!-- Contact Content -->
<section class="section animate-on-scroll">
    <div class="container">
        <div class="contact-container">
            <!-- Contact Form -->
            <div class="contact-form">
                <h3>Send us a Message</h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Your Name *</label>
                                <input type="text" id="name" name="name" required
                                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <input type="text" id="subject" name="subject" required
                                value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" rows="6" required
                                placeholder="Tell us how we can help you..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Send Message</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Contact Information -->
            <div class="contact-info">
                <h3>Get in Touch with US</h3>

                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <h4>Address</h4>
                        <p>78 Food Street<br>City, Islamabad<br>Pakistan</p>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <div>
                        <h4>Phone</h4>
                        <p>03039393833</p>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h4>Email</h4>
                        <p>info@digitaldine.com</p>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <h4>Opening Hours</h4>
                        <p>Mon-Thu: 11:00 AM - 10:00 PM<br>
                            Fri-Sat: 11:00 AM - 11:00 PM<br>
                            Sunday: 12:00 PM - 9:00 PM</p>
                    </div>
                </div>

                <div class="social-links" style="margin-top: 2rem;">
                    <h4>Follow Us</h4>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section id="find-us" class="section animate-on-scroll" style="background: #f8f9fa;">
    <div class="container">
        <h3 style="text-align: center; margin-bottom: 1rem;">Find Us</h3>
        <p style="text-align: center; color: #666; margin-bottom: 2rem;">Located in the heart of Islamabad Food Street</p>
        <div style="border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3319.745!2d73.0479!3d33.6844!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x38dfbf7b8b8b8b8b%3A0x8b8b8b8b8b8b8b8b!2sF-7%20Sector%2C%20Islamabad%2C%20Pakistan!5e0!3m2!1sen!2s!4v1703123456789!5m2!1sen!2s"
                width="100%"
                height="400"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
        <div style="text-align: center; margin-top: 2rem;">
            <p style="color: #666; margin-bottom: 1rem;">
                <i class="fas fa-map-marker-alt" style="color: #667eea; margin-right: 0.5rem;"></i>
                <strong>DigitalDine Restaurant</strong><br>
                78 Food Street, F-7 Sector<br>
                Islamabad, Pakistan
            </p>
            <a href="https://maps.google.com/?q=Islamabad+Food+Street" target="_blank" class="btn" style="background: #667eea;">
                <i class="fas fa-directions"></i> Get Directions
            </a>
        </div>
    </div>
</section>

<style>
    .contact-container {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 3rem;
    }

    .contact-form {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .contact-info {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .info-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 2rem;
    }

    .info-item i {
        font-size: 1.5rem;
        color: #667eea;
        margin-right: 1rem;
        margin-top: 0.2rem;
    }

    .info-item h4 {
        margin-bottom: 0.5rem;
        color: #333;
    }

    .info-item p {
        color: #666;
        line-height: 1.6;
    }

    .social-links h4 {
        margin-bottom: 1rem;
        color: #333;
    }

    .social-links a {
        color: #667eea;
        font-size: 1.5rem;
        margin-right: 1rem;
        transition: color 0.3s;
    }

    .social-links a:hover {
        color: #5a67d8;
    }

    @media (max-width: 768px) {
        .contact-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>