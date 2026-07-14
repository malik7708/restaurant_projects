    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>DigitalDine</h3>
                    <p>Delicious food delivered to your doorstep. Experience the best dining experience with our carefully crafted dishes.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="/restaurant_project/customer/index.php">Home</a></li>
                        <li><a href="/restaurant_project/customer/menu.php">Menu</a></li>
                        <li><a href="/restaurant_project/customer/about.php">About Us</a></li>
                        <li><a href="/restaurant_project/customer/contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> 78 Food Street, F-7 Sector<br>
                            Islamabad, Pakistan</li>
                        <li><i class="fas fa-phone"></i> 03039393833</li>
                        <li><i class="fas fa-envelope"></i> info@digitaldine.com</li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Opening Hours</h4>
                    <ul>
                        <li>Mon - Thu: 11:00 AM - 10:00 PM</li>
                        <li>Fri - Sat: 11:00 AM - 11:00 PM</li>
                        <li>Sunday: 12:00 PM - 9:00 PM</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 DigitalDine. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Order Tracking Modal -->
    <div id="order-track-modal" class="modal track-order-modal">
        <div class="modal-content track-order-modal-content">
            <div class="modal-header">
                <h3>Track Your Order</h3>
                <button type="button" class="modal-close" onclick="closeTrackOrderModal()" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="track-order-form" action="javascript:void(0);">
                    <label for="track-order-id">Order ID</label>
                    <input type="text" id="track-order-id" name="order_id" placeholder="Enter your order ID" required>
                    <button type="button" class="btn" onclick="submitTrackOrder(event)">Check Status</button>
                </form>
                <div id="track-order-result" class="track-order-result"></div>
            </div>
        </div>
    </div>

    <style>
        body.modal-open {
            overflow: hidden;
        }

        .modal.track-order-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal.track-order-modal.show {
            display: flex;
        }

        .track-order-modal-content {
            width: min(550px, 100%);
            background: #ffffff;
            border-radius: 22px;
            padding: 1.75rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
            max-height: 90vh;
            overflow-y: auto;
        }

        .track-order-modal .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .track-order-modal .modal-close {
            background: transparent;
            border: none;
            color: #333;
            font-size: 1.75rem;
            line-height: 1;
            cursor: pointer;
        }

        .track-order-modal form {
            display: grid;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .track-order-modal input {
            width: 100%;
            padding: 0.95rem 1rem;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 1rem;
        }

        .track-order-result {
            margin-top: 0.75rem;
        }

        .track-order-result .order-details {
            display: grid;
            gap: 0.75rem;
            padding: 1rem 0;
        }

        .track-order-result .order-details p {
            margin: 0;
        }

        .track-order-items {
            margin-top: 1rem;
            border-top: 1px solid #eee;
            padding-top: 1rem;
        }

        .track-order-items li {
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }
    </style>

    <script>
        window.openTrackOrderModal = function() {
            var modal = document.getElementById('order-track-modal');
            var input = document.getElementById('track-order-id');
            var result = document.getElementById('track-order-result');
            if (!modal) return;
            modal.style.display = 'flex';
            document.body.classList.add('modal-open');
            if (result) result.innerHTML = '';
            if (input) {
                input.value = '';
                setTimeout(function() {
                    input.focus();
                }, 50);
            }
        };

        window.closeTrackOrderModal = function() {
            var modal = document.getElementById('order-track-modal');
            if (!modal) return;
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        };
    </script>

    <!-- WhatsApp Floating Button -->
    <a href="https://wa.me/923039393833?text=Hi%20DigitalDine!%20I%20would%20like%20to%20place%20an%20order."
        class="whatsapp-float"
        target="_blank"
        title="Chat with us on WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>

    <script src="/restaurant_project/assets/js/script.js"></script>
    </body>

    </html>