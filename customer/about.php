<?php
$page_title = 'About Us - FoodieHub';
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<?php
$hero_title = 'About FoodieHub';
$hero_subtitle = 'Discover our story and what makes us special.';
$hero_image = '/restaurant_project/assets/images/about-bg.png';
$hero_cta_text = 'Learn More';
$hero_cta_href = 'about.php#our-story';
include __DIR__ . '/../includes/hero.php';
?>

<!-- Our Story -->
<section id="our-story" class="section animate-on-scroll">
	<div class="container">
		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center;">
			<div>
				<h2>Our Story</h2>
				<p>Founded in 2010, DigitalDine began as a small family-owned restaurant with a simple mission: to serve delicious, high-quality food made from the freshest ingredients. What started as a passion project has grown into one of the most beloved dining destinations in the city.</p>
				<p>Our journey began when our founder, Maria Rodriguez, decided to share her grandmother's secret recipes with the world. Drawing inspiration from traditional family meals and modern culinary techniques, we created a menu that celebrates both heritage and innovation.</p>
				<p>Today, we continue to uphold the values that made us successful: exceptional quality, warm hospitality, and a commitment to creating memorable dining experiences for every guest.</p>
			</div>
			<div>
				<img src="../assets/images/restaurant-interior.jpg" alt="DigitalDine Restaurant Interior" style="width: 100%; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);" onerror="this.src='../assets/images/hero-bg.jpg';">
			</div>
		</div>
	</div>
</section>
<!-- About Section -->
<section class="section about-section animate-on-scroll">
	<div class="container">
		<div class="about-wrapper">
			<!-- Left: Image -->
			<div class="about-image">
				<div class="image-wrapper">
					<img src="../assets/images/restaurant-interior.jpg" alt="DigitalDine Restaurant" onerror="this.src='../assets/images/hero-bg.jpg';">
					<div class="image-badge">
						<i class="fas fa-star"></i> Award Winning
					</div>
				</div>
			</div>
			<!-- Right: Text -->
			<div class="about-content">
				<h2>Welcome to FoodieHub</h2>
				<p>At FoodieHub, we believe that food is more than just sustenance – it's an experience. Our chefs craft each dish with passion and precision, using only the freshest ingredients sourced from local farms and suppliers.</p>
				<p>Whether you're joining us for a casual lunch, a romantic dinner, or a special celebration, we strive to create an atmosphere where you can relax, enjoy great company, and savor every bite.</p>
				<p>Thank you for being part of our story. We look forward to welcoming you to FoodieHub and sharing our love of food with you.</p>
			</div>
		</div>
	</div>
</section>

<!-- Our Values -->
<section class="section" style="background: #f8f9fa;">
	<div class="container">
		<h2 style="text-align: center;">Our Values</h2>
		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 3rem;">
			<div style="text-align: center;">
				<i class="fas fa-leaf" style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;"></i>
				<h3>Fresh &amp; Local</h3>
				<p>We source our ingredients from local farmers and suppliers to ensure the highest quality and freshness in every dish.</p>
			</div>
			<div style="text-align: center;">
				<i class="fas fa-heart" style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;"></i>
				<h3>Made with Love</h3>
				<p>Every dish is prepared with care and attention to detail by our passionate culinary team.</p>
			</div>
			<div style="text-align: center;">
				<i class="fas fa-users" style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;"></i>
				<h3>Community Focused</h3>
				<p>We're proud to be part of this community and actively support local initiatives and charities.</p>
			</div>
			<div style="text-align: center;">
				<i class="fas fa-star" style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;"></i>
				<h3>Excellence</h3>
				<p>We strive for excellence in everything we do, from food preparation to customer service.</p>
			</div>
		</div>
	</div>
</section>

<!-- Our Team -->
<section class="section">
	<div class="container">
		<h2 style="text-align: center;">Meet Our Team</h2>
		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 3rem;">
			<div style="text-align: center;">
				<img src="../assets/images/chef-murtaza.jpg" alt="Chef Murtaza" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1);" onerror="this.style.display='none';">
				<h3>Chef Murtaza</h3>
				<p style="color: #667eea; font-weight: 600;">Executive Chef &amp; Founder</p>
				<p>With over 20 years of culinary experience, Chef Murtaza brings traditional flavors and modern techniques to every dish.</p>
			</div>
			<div style="text-align: center;">
				<img src="../assets/images/manager-john.jpg" alt="John Smith" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1);" onerror="this.style.display='none';">
				<h3>Usman Malik</h3>
				<p style="color: #667eea; font-weight: 600;">Restaurant Manager</p>
				<p>Usman ensures every guest has an exceptional dining experience and oversees our daily operations.</p>
			</div>
			<div style="text-align: center;">
				<img src="../assets/images/sommelier-sarah.jpg" alt="Sarah Johnson" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1);" onerror="this.style.display='none';">
				<h3>Rehan Ahmed</h3>
				<p style="color: #667eea; font-weight: 600;">Sommelier</p>
				<p>Rehan curates our wine selection and helps guests find the perfect pairing for their meal.</p>
			</div>
		</div>
	</div>
</section>

<!-- Visit Us -->
<section class="section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
	<div class="container">
		<div style="text-align: center;">
			<h2>Visit Us Today</h2>
			<p>Experience the DigitalDine difference for yourself. We're located in the heart of the city.</p>
			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; margin-top: 3rem;">
				<div>
					<i class="fas fa-map-marker-alt" style="font-size: 2rem; margin-bottom: 1rem;"></i>
					<h4>Location</h4>
					<p>78 Food Street<br>F-7 Sector<br>Islamabad, Pakistan</p>
				</div>
				<div>
					<i class="fas fa-clock" style="font-size: 2rem; margin-bottom: 1rem;"></i>
					<h4>Hours</h4>
					<p>Mon-Thu: 11AM-10PM<br>Fri-Sat: 11AM-11PM<br>Sun: 12PM-9PM</p>
				</div>
				<div>
					<i class="fas fa-phone" style="font-size: 2rem; margin-bottom: 1rem;"></i>
					<h4>Contact</h4>
					<p>03039393833<br>info@digitaldine.com</p>
				</div>
			</div>
			<a href="reservation.php" class="btn" style="margin-top: 2rem;">Make a Reservation</a>
		</div>
	</div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>