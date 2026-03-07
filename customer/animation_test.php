<?php
$page_title = 'Animation Test - DigitalDine';
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
    .test-section {
        padding: 4rem 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-align: center;
    }

    .test-card {
        background: white;
        color: #333;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        margin: 2rem 0;
        transition: all 0.4s ease;
    }

    .test-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    }
</style>

<section class="test-section animate-on-scroll">
    <div class="container">
        <h1>✨ Animation Test Page ✨</h1>
        <p>Testing all the smooth, professional animations we've added!</p>
    </div>
</section>

<section class="section animate-on-scroll">
    <div class="container">
        <h2>Scroll Animation Test</h2>
        <p>This section should animate in when you scroll to it.</p>

        <div class="menu-grid stagger-animation">
            <div class="menu-card test-card">
                <h3>Fade In Up</h3>
                <p>This card uses fadeInUp animation</p>
            </div>
            <div class="menu-card test-card">
                <h3>Scale In</h3>
                <p>This card uses scaleIn animation</p>
            </div>
            <div class="menu-card test-card">
                <h3>Bounce In</h3>
                <p>This card uses bounceIn animation</p>
            </div>
        </div>
    </div>
</section>

<section class="section animate-on-scroll">
    <div class="container">
        <h2>Button Interactions</h2>
        <p>Hover over these buttons to see the enhanced effects:</p>
        <br>
        <button class="btn pulse-gentle">Pulse Button</button>
        <button class="btn float">Float Button</button>
        <button class="btn">Regular Button</button>
    </div>
</section>

<section class="section animate-on-scroll">
    <div class="container">
        <h2>WhatsApp Button</h2>
        <p>The floating WhatsApp button should have a gentle floating animation.</p>
        <p>Scroll up and down to see the parallax effect on the hero section.</p>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>