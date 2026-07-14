// Main JavaScript file for restaurant website

document.addEventListener("DOMContentLoaded", function () {
  // Mobile menu toggle
  const hamburger = document.querySelector(".hamburger");
  const navMenu = document.querySelector(".nav-menu");

  if (hamburger && navMenu) {
    // mobile toggle + aria
    hamburger.addEventListener("click", function () {
      const expanded = this.getAttribute("aria-expanded") === "true";
      this.setAttribute("aria-expanded", expanded ? "false" : "true");
      this.classList.toggle("open");
      navMenu.classList.toggle("active");
    });

    // close mobile menu when a link is clicked
    navMenu.querySelectorAll("a").forEach((a) => {
      a.addEventListener("click", () => {
        if (navMenu.classList.contains("active")) {
          navMenu.classList.remove("active");
          hamburger.classList.remove("open");
          hamburger.setAttribute("aria-expanded", "false");
        }
      });
    });
  }

  // Cart functionality
  let cart = JSON.parse(localStorage.getItem("cart")) || [];
  updateCartCount();
  // ensure drawer reflects current cart on load
  try {
    renderCartDrawer();
  } catch (e) {
    // ignore
  }

  document.querySelectorAll(".open-track-order-modal").forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      openTrackOrderModal();
    });
  });

  const trackForm = document.getElementById("track-order-form");
  if (trackForm) {
    trackForm.addEventListener("submit", submitTrackOrder);
    // also attach click listener to the button to ensure handler runs
    const trackBtn = trackForm.querySelector("button");
    if (trackBtn) trackBtn.addEventListener("click", submitTrackOrder);
  }

  // Add to cart functionality - delay to ensure DOM is ready
  setTimeout(() => {
    const addToCartButtons = document.querySelectorAll(".add-to-cart-btn");
    console.log("Found add-to-cart buttons:", addToCartButtons.length);
    addToCartButtons.forEach((button, index) => {
      if (button.hasAttribute("onclick")) {
        return;
      }
      button.addEventListener("click", function () {
        console.log("Add to cart button clicked!", this);
        const itemId = this.getAttribute("data-id");
        const itemName = this.getAttribute("data-name");
        const itemPrice = parseFloat(this.getAttribute("data-price"));
        const discountAttr = this.getAttribute("data-discount");
        const itemDiscount = parseFloat(discountAttr) || 0;
        const itemImage = this.getAttribute("data-image");

        console.log(`Button ${index} clicked:`, {
          itemId,
          itemName,
          itemPrice,
          discountAttr,
          itemDiscount,
          itemImage,
          allAttributes: Array.from(this.attributes).map(
            (attr) => `${attr.name}=${attr.value}`,
          ),
        });

        const sizesAttr = this.getAttribute("data-sizes");
        const sizes = sizesAttr
          ? sizesAttr
              .split(",")
              .map((s) => s.trim())
              .filter(Boolean)
          : [];
        addToCart(itemId, itemName, itemPrice, itemImage, itemDiscount, {
          sizes: sizes,
          sourceButton: this,
        });
      });
    });
  }, 100);

  // Form validation
  const forms = document.querySelectorAll("form");
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      if (!validateForm(this)) {
        e.preventDefault();
      }
    });
  });

  // Smooth scrolling for named anchor links
  const anchorLinks = Array.from(
    document.querySelectorAll('a[href^="#"]'),
  ).filter((link) => link.getAttribute("href") !== "#");
  anchorLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      const href = this.getAttribute("href");
      if (!href || href === "#") return;
      const target = document.querySelector(href);
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    });
  });

  // Initialize cart page if on cart page (only if not using inline cart JS)
  // Note: cart.php has its own cart display logic, so we skip this here
  // if (document.querySelector(".cart-container")) {
  //   displayCart();
  // }

  // Navbar shrink on scroll
  const navbar = document.querySelector(".navbar");
  const SCROLL_THRESHOLD = 40;
  function onScroll() {
    if (!navbar) return;
    if (window.scrollY > SCROLL_THRESHOLD)
      navbar.classList.add("navbar--scrolled");
    else navbar.classList.remove("navbar--scrolled");
  }
  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  // Reservation date validation
  const dateInput = document.getElementById("reservation-date");
  if (dateInput) {
    const today = new Date().toISOString().split("T")[0];
    dateInput.setAttribute("min", today);
  }

  // Initialize animations after DOM is ready
  initAnimations();

  // Initialize banner slider
  initBannerSlider();
});

// Cart functions
let CART_AUTO_CLOSE_MS = 8000;
let cartAutoCloseTimer = null;

function addToCart(id, name, price, image, discount = 0, opts = {}) {
  // opts: { sizes: [], sourceButton: Element }
  let cart = JSON.parse(localStorage.getItem("cart")) || [];
  const existingItem = cart.find(
    (item) => item.id === id && (!opts.size || item.size === opts.size),
  );

  if (existingItem) {
    existingItem.quantity += 1;
    existingItem.name = name;
    existingItem.price = price;
    existingItem.discount = discount;
    existingItem.image = image;
  } else {
    const newItem = {
      id: id,
      name: name,
      price: price,
      discount: discount,
      image: image,
      quantity: 1,
    };
    if (opts && opts.size) newItem.size = opts.size;
    cart.push(newItem);
  }

  localStorage.setItem("cart", JSON.stringify(cart));
  updateCartCount();

  // Prepare recently added item info for a richer drawer UI
  const recentlyAddedItem = {
    id,
    name,
    price,
    image,
    discount,
    sizes: opts.sizes || [],
  };

  try {
    // make the recentlyAdded available to the renderer for a richer UI
    renderCartDrawer.recentlyAdded = recentlyAddedItem;
    renderCartDrawer();
    // ensure drawer is visible; set display and then open
    const drawer = document.getElementById("cart-drawer");
    if (drawer) drawer.style.display = "flex";
    openCartDrawer();
    // sometimes reflow is needed on some browsers; call again shortly
    setTimeout(openCartDrawer, 60);
    // animate cart icon
    triggerCartIconAnimation();
    // start auto-close timer
    startCartAutoClose();
  } catch (e) {
    console.error("Cart drawer error", e);
  }
}

// Cart drawer controls
function openCartDrawer() {
  const drawer = document.getElementById("cart-drawer");
  const backdrop = document.getElementById("cart-drawer-backdrop");
  if (!drawer) return;
  drawer.classList.add("open");
  drawer.setAttribute("aria-hidden", "false");
  drawer.style.display = "flex";
  drawer.style.transform = "translateX(0)";
  if (backdrop) backdrop.classList.add("open");
  document.body.classList.add("cart-open");
}

function closeCartDrawer() {
  const drawer = document.getElementById("cart-drawer");
  const backdrop = document.getElementById("cart-drawer-backdrop");
  if (!drawer) return;
  drawer.classList.remove("open");
  drawer.setAttribute("aria-hidden", "true");
  drawer.style.transform = "translateX(100%)";
  if (backdrop) backdrop.classList.remove("open");
  document.body.classList.remove("cart-open");
}

function renderCartDrawer() {
  const drawerItems = document.querySelector(".cart-items-drawer");
  const subtotalEl = document.querySelector(".drawer-subtotal-amount");
  if (!drawerItems) return;
  const cart = JSON.parse(localStorage.getItem("cart")) || [];
  drawerItems.innerHTML = "";
  let subtotal = 0;

  // If a recentlyAddedItem was provided via arguments (function overloading), show a larger product card
  const recentlyAddedItem = renderCartDrawer.recentlyAdded || null;
  // Clear the stored recentlyAdded flag after rendering once
  renderCartDrawer.recentlyAdded = null;

  if (cart.length === 0) {
    drawerItems.innerHTML = "<p>Your cart is empty.</p>";
    if (subtotalEl) subtotalEl.textContent = "Rs0";
    return;
  }

  if (recentlyAddedItem) {
    // Show a larger product card for the recently added item
    const card = document.createElement("div");
    card.className = "drawer-product-card";
    card.innerHTML = `
      <img src="/restaurant_project/assets/images/${recentlyAddedItem.image || "placeholder.jpg"}" alt="${recentlyAddedItem.name}">
      <div class="drawer-product-info">
        <h4>${recentlyAddedItem.name}</h4>
        <div class="drawer-price">Rs ${Number(recentlyAddedItem.price).toLocaleString()}</div>
        <div class="drawer-sizes"></div>
        <div class="drawer-qty">
          <button class="btn small" data-action="qty-decrease">-</button>
          <span class="drawer-qty-count">1</span>
          <button class="btn small" data-action="qty-increase">+</button>
        </div>
        <div class="drawer-note">
          <label>Order Note</label>
          <textarea placeholder="Add special instructions (optional)"></textarea>
        </div>
      </div>
    `;
    drawerItems.appendChild(card);

    // Populate sizes if available
    const sizesContainer = card.querySelector(".drawer-sizes");
    if (recentlyAddedItem.sizes && recentlyAddedItem.sizes.length) {
      sizesContainer.innerHTML =
        '<label>Size</label><div class="drawer-size-options"></div>';
      const opts = card.querySelector(".drawer-size-options");
      recentlyAddedItem.sizes.forEach((s, i) => {
        const btn = document.createElement("button");
        btn.className = "btn small drawer-size-btn";
        btn.type = "button";
        btn.textContent = s;
        if (i === 0) btn.classList.add("active");
        btn.addEventListener("click", function () {
          card
            .querySelectorAll(".drawer-size-btn")
            .forEach((b) => b.classList.remove("active"));
          this.classList.add("active");
          // update selected size on the item in cart
          setRecentlyAddedItemSize(recentlyAddedItem.id, s);
        });
        opts.appendChild(btn);
      });
    }

    // Quantity buttons (affect cart)
    const qtyCount = card.querySelector(".drawer-qty-count");
    const qtyInc = card.querySelector('[data-action="qty-increase"]');
    const qtyDec = card.querySelector('[data-action="qty-decrease"]');
    const noteBox = card.querySelector("textarea");

    function updateQtyDisplay(q) {
      qtyCount.textContent = q;
      // sync to cart: find the most recently added matching id
      let c = JSON.parse(localStorage.getItem("cart")) || [];
      const idx = c.findIndex(
        (it) => String(it.id) === String(recentlyAddedItem.id),
      );
      if (idx !== -1) {
        c[idx].quantity = q;
        localStorage.setItem("cart", JSON.stringify(c));
        updateCartCount();
        // update subtotal display
        updateDrawerSubtotal();
      }
    }

    qtyInc.addEventListener("click", function () {
      const q = parseInt(qtyCount.textContent || "1", 10) + 1;
      updateQtyDisplay(q);
    });
    qtyDec.addEventListener("click", function () {
      const q = Math.max(1, parseInt(qtyCount.textContent || "1", 10) - 1);
      updateQtyDisplay(q);
    });

    // order note
    noteBox.addEventListener("input", function () {
      let c = JSON.parse(localStorage.getItem("cart")) || [];
      const idx = c.findIndex(
        (it) => String(it.id) === String(recentlyAddedItem.id),
      );
      if (idx !== -1) {
        c[idx].note = this.value;
        localStorage.setItem("cart", JSON.stringify(c));
      }
    });

    // initialize quantity from cart if present
    const cartItem = cart.find(
      (it) => String(it.id) === String(recentlyAddedItem.id),
    );
    if (cartItem) {
      qtyCount.textContent = cartItem.quantity;
      if (noteBox && cartItem.note) noteBox.value = cartItem.note;
    }
  }

  // Render compact list after or below product card
  cart.forEach((item) => {
    const itemTotal = item.price * item.quantity;
    subtotal += itemTotal;
    const div = document.createElement("div");
    div.className = "cart-item";
    div.innerHTML = `
      <img src="/restaurant_project/assets/images/${item.image || "placeholder.jpg"}" alt="${item.name}" style="width:60px;height:60px;object-fit:cover;border-radius:6px;">
      <div style="flex:1">
        <strong style="display:block">${item.name}${item.size ? " (" + item.size + ")" : ""}</strong>
        <small>Qty: ${item.quantity}</small>
      </div>
      <div style="min-width:70px;text-align:right">Rs ${Number(item.price).toLocaleString()}</div>
    `;
    drawerItems.appendChild(div);
  });

  if (subtotalEl)
    subtotalEl.textContent = "Rs " + Number(subtotal).toLocaleString();
}

function setRecentlyAddedItemSize(itemId, size) {
  let c = JSON.parse(localStorage.getItem("cart")) || [];
  const idx = c.findIndex((it) => String(it.id) === String(itemId));
  if (idx !== -1) {
    c[idx].size = size;
    localStorage.setItem("cart", JSON.stringify(c));
    updateCartCount();
    renderCartDrawer();
  }
}

function updateDrawerSubtotal() {
  const subtotalEl = document.querySelector(".drawer-subtotal-amount");
  const cart = JSON.parse(localStorage.getItem("cart")) || [];
  const subtotal = cart.reduce((s, it) => s + it.price * it.quantity, 0);
  if (subtotalEl)
    subtotalEl.textContent = "Rs " + Number(subtotal).toLocaleString();
}

function triggerCartIconAnimation() {
  try {
    const icon =
      document.querySelector("header .fa-shopping-cart") ||
      document.querySelector(".fa-shopping-cart");
    if (!icon) return;
    icon.classList.remove("cart-icon-bounce");
    void icon.offsetWidth;
    icon.classList.add("cart-icon-bounce");
    icon.addEventListener("animationend", function handler() {
      icon.classList.remove("cart-icon-bounce");
      icon.removeEventListener("animationend", handler);
    });
  } catch (e) {
    // ignore
  }
}

function startCartAutoClose() {
  clearCartAutoClose();
  cartAutoCloseTimer = setTimeout(() => {
    closeCartDrawer();
  }, CART_AUTO_CLOSE_MS);
}

function clearCartAutoClose() {
  if (cartAutoCloseTimer) {
    clearTimeout(cartAutoCloseTimer);
    cartAutoCloseTimer = null;
  }
}

function initCartDrawerAutoClose() {
  const drawer = document.getElementById("cart-drawer");
  if (!drawer) return;
  drawer.addEventListener("mouseenter", function () {
    clearCartAutoClose();
  });
  drawer.addEventListener("focusin", function () {
    clearCartAutoClose();
  });
  drawer.addEventListener("mouseleave", function () {
    if (drawer.classList.contains("open")) startCartAutoClose();
  });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initCartDrawerAutoClose);
} else {
  initCartDrawerAutoClose();
}

// Close drawer when clicking outside or pressing Escape
document.addEventListener("click", function (e) {
  const drawer = document.getElementById("cart-drawer");
  if (!drawer) return;
  if (!drawer.classList.contains("open")) return;
  const isInside = e.target.closest && e.target.closest("#cart-drawer");
  const isAddBtn = e.target.closest && e.target.closest(".add-to-cart-btn");
  if (!isInside && !isAddBtn) {
    closeCartDrawer();
  }
});

document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    const drawer = document.getElementById("cart-drawer");
    if (drawer && drawer.classList.contains("open")) closeCartDrawer();
  }
});

// Ensure drawer is rendered initially (in case cart already had items)
try {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", renderCartDrawer);
  } else {
    renderCartDrawer();
  }
} catch (e) {
  // ignore
}

function updateCartCount() {
  const cart = JSON.parse(localStorage.getItem("cart")) || [];
  const cartCount = cart.reduce((total, item) => total + item.quantity, 0);
  const cartCountElement = document.getElementById("cart-count");
  if (cartCountElement) {
    cartCountElement.textContent = cartCount;
  }
}

function openTrackOrderModal() {
  const modal = document.getElementById("order-track-modal");
  const input = document.getElementById("track-order-id");
  const result = document.getElementById("track-order-result");
  if (!modal) return;

  if (input) {
    if (window.lastOrderId) {
      input.value = window.lastOrderId;
    } else {
      input.value = "";
    }
  }

  if (result) {
    result.innerHTML = "";
  }

  modal.style.display = "flex";
  modal.classList.add("show");
  document.body.classList.add("modal-open");
  if (input) {
    setTimeout(() => input.focus(), 50);
  }
}

window.openTrackOrderModal = openTrackOrderModal;

function closeTrackOrderModal() {
  const modal = document.getElementById("order-track-modal");
  if (!modal) return;
  modal.style.display = "none";
  modal.classList.remove("show");
  document.body.classList.remove("modal-open");
}

window.closeTrackOrderModal = closeTrackOrderModal;

async function submitTrackOrder(event) {
  try {
    console.log("submitTrackOrder called", event);
    if (event && event.preventDefault) event.preventDefault();

    const input = document.getElementById("track-order-id");
    const result = document.getElementById("track-order-result");
    const button =
      event && event.target
        ? event.target.tagName === "BUTTON"
          ? event.target
          : document.querySelector("#track-order-form button")
        : document.querySelector("#track-order-form button");
    if (!input || !result) {
      console.error("Track order elements not found");
      return;
    }

    const orderId = input.value.trim().replace(/^#/, "");
    if (!orderId) {
      result.innerHTML =
        '<div class="alert alert-error">Please enter an order ID.</div>';
      return;
    }

    // UI: disable button and show loading
    if (button) {
      button.disabled = true;
      button.dataset.origText = button.innerHTML;
      button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
    }

    result.innerHTML =
      '<div class="alert alert-info">Looking up your order...</div>';

    const resp = await fetch("/restaurant_project/customer/track_order.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: "order_id=" + encodeURIComponent(orderId),
    });

    const text = await resp.text();
    // raw response received
    let data = null;
    try {
      data = JSON.parse(text);
    } catch (e) {
      console.error(
        "Unable to parse JSON from track_order.php response:",
        e,
        text,
      );
      result.innerHTML =
        '<div class="alert alert-error">Unexpected server response. Please try again later.</div>';
      // no debug banner
      return;
    }

    if (data && data.success) {
      const order = data.order;
      const items = order.items || [];
      const itemRows = items
        .map(
          (item) =>
            `<li><strong>${item.name}</strong> × ${item.quantity} — Rs ${Number(item.price).toLocaleString()}</li>`,
        )
        .join("");

      result.innerHTML = `
        <div class="alert alert-success">
          <h4>Order Found</h4>
          <div class="order-details">
            <p><strong>Order ID:</strong> #${String(order.id).padStart(4, "0")}</p>
            ${order.table_number ? `<p><strong>Table:</strong> ${order.table_number}</p>` : ""}
            <p><strong>Status:</strong> ${order.status}</p>
            <p><strong>Total:</strong> Rs ${Number(order.total_price).toLocaleString()}</p>
            <p><strong>Placed:</strong> ${order.created_at}</p>
          </div>
        </div>
      `;

      if (itemRows) {
        result.innerHTML += `
          <div class="track-order-items">
            <h4>Items</h4>
            <ul>${itemRows}</ul>
          </div>
        `;
      }
      if (result)
        result.scrollIntoView({ behavior: "smooth", block: "nearest" });
    } else {
      result.innerHTML = `<div class="alert alert-error">${data && data.message ? data.message : "No order found with that ID."}</div>`;
      // no additional debug output
      if (result)
        result.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
  } catch (err) {
    console.error("submitTrackOrder error", err);
    const result = document.getElementById("track-order-result");
    if (result) {
      result.innerHTML =
        '<div class="alert alert-error">An error occurred. Please try again.</div>';
      result.scrollIntoView({ behavior: "smooth", block: "nearest" });
      // no additional debug output
    }
  } finally {
    // restore button
    try {
      const btn = document.querySelector("#track-order-form button");
      if (btn) {
        btn.disabled = false;
        if (btn.dataset && btn.dataset.origText)
          btn.innerHTML = btn.dataset.origText;
      }
    } catch (e) {
      // ignore
    }
  }
}

// Expose function globally for inline fallbacks
window.submitTrackOrder = submitTrackOrder;

document.addEventListener("click", function (event) {
  const modal = document.getElementById("order-track-modal");
  if (modal && event.target === modal) {
    closeTrackOrderModal();
  }
});

document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
    closeTrackOrderModal();
  }
});

function displayCart() {
  const cart = JSON.parse(localStorage.getItem("cart")) || [];
  const cartContainer = document.querySelector(".cart-items");
  const cartTotal = document.querySelector(".cart-total");
  const cartTax = document.querySelector(".cart-tax");
  const cartFinalTotal = document.querySelector(".cart-final-total");

  if (!cartContainer) return;

  cartContainer.innerHTML = "";
  let subtotal = 0;

  if (cart.length === 0) {
    cartContainer.innerHTML =
      '<p class="empty-cart">Your cart is empty. <a href="menu.php">Browse our menu</a> to add items.</p>';
    if (cartTotal) cartTotal.textContent = "$0.00";
    if (cartTax) cartTax.textContent = "$0.00";
    if (cartFinalTotal) cartFinalTotal.textContent = "$0.00";
    return;
  }

  cart.forEach((item, index) => {
    const itemTotal = item.price * item.quantity;
    subtotal += itemTotal;

    const cartItem = document.createElement("div");
    cartItem.className = "cart-item";
    cartItem.innerHTML = `
            <img src="../assets/images/${item.image || "placeholder.jpg"}" alt="${item.name}" onerror="this.src='../assets/images/placeholder.jpg'">
            <div class="cart-item-details">
                <h4>${item.name}</h4>
                <p>$${item.price.toFixed(2)} each</p>
                <div class="cart-item-quantity">
                    <button class="quantity-btn" onclick="updateQuantity(${index}, -1)">-</button>
                    <span>${item.quantity}</span>
                    <button class="quantity-btn" onclick="updateQuantity(${index}, 1)">+</button>
                </div>
            </div>
            <div class="cart-item-price">$${itemTotal.toFixed(2)}</div>
            <button class="remove-btn" onclick="removeFromCart(${index})">Remove</button>
        `;
    cartContainer.appendChild(cartItem);
  });

  const tax = subtotal * 0.08;
  const total = subtotal + tax;

  if (cartTotal) cartTotal.textContent = `$${subtotal.toFixed(2)}`;
  if (cartTax) cartTax.textContent = `$${tax.toFixed(2)}`;
  if (cartFinalTotal) cartFinalTotal.textContent = `$${total.toFixed(2)}`;
}

function updateQuantity(index, change) {
  let cart = JSON.parse(localStorage.getItem("cart")) || [];
  if (cart[index]) {
    cart[index].quantity += change;
    if (cart[index].quantity <= 0) {
      cart.splice(index, 1);
    }
    localStorage.setItem("cart", JSON.stringify(cart));
    displayCart();
    updateCartCount();
  }
}

function removeFromCart(index) {
  let cart = JSON.parse(localStorage.getItem("cart")) || [];
  cart.splice(index, 1);
  localStorage.setItem("cart", JSON.stringify(cart));
  displayCart();
  updateCartCount();
  showNotification("Item removed from cart!", "warning");
}

function clearCart() {
  localStorage.removeItem("cart");
  displayCart();
  updateCartCount();
  showNotification("Cart cleared!", "warning");
}

// Form validation
function validateForm(form) {
  let isValid = true;
  const inputs = form.querySelectorAll("input, textarea, select");

  inputs.forEach((input) => {
    // Remove previous error messages
    const existingError = input.parentNode.querySelector(".error-message");
    if (existingError) {
      existingError.remove();
    }
    input.classList.remove("error");

    // Check required fields
    if (input.hasAttribute("required") && !input.value.trim()) {
      showError(input, "This field is required");
      isValid = false;
    }

    // Email validation
    if (input.type === "email" && input.value.trim()) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(input.value.trim())) {
        showError(input, "Please enter a valid email address");
        isValid = false;
      }
    }

    // Phone validation
    if (input.type === "tel" && input.value.trim()) {
      const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
      const cleanPhone = input.value.replace(/[\s\-\(\)\.]/g, "");
      if (!phoneRegex.test(cleanPhone) || cleanPhone.length < 10) {
        showError(
          input,
          "Please enter a valid phone number (at least 10 digits)",
        );
        isValid = false;
      }
    }

    // Date validation for reservations
    if (input.type === "date" && input.value) {
      const selectedDate = new Date(input.value);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      if (selectedDate < today) {
        showError(input, "Please select a future date");
        isValid = false;
      }
    }
  });

  return isValid;
}

function showError(input, message) {
  input.classList.add("error");
  const errorDiv = document.createElement("div");
  errorDiv.className = "error-message";
  errorDiv.textContent = message;
  errorDiv.style.color = "#e53e3e";
  errorDiv.style.fontSize = "0.8rem";
  errorDiv.style.marginTop = "5px";
  input.parentNode.appendChild(errorDiv);
}

// Notification system
function showNotification(message, type = "info") {
  const notification = document.createElement("div");
  notification.className = `alert alert-${type}`;
  notification.textContent = message;
  notification.style.position = "fixed";
  notification.style.top = "100px";
  notification.style.right = "20px";
  notification.style.zIndex = "9999";
  notification.style.maxWidth = "300px";

  document.body.appendChild(notification);

  setTimeout(() => {
    notification.remove();
  }, 3000);
}

// Global function for onclick backup
function addToCartFromButton(button) {
  console.log("Add to cart button clicked via onclick!", button);
  const itemId = button.getAttribute("data-id");
  const itemName = button.getAttribute("data-name");
  const itemPrice = parseFloat(button.getAttribute("data-price"));
  const discountAttr = button.getAttribute("data-discount");
  const itemDiscount = parseFloat(discountAttr) || 0;
  const itemImage = button.getAttribute("data-image");

  const sizesAttr = button.getAttribute("data-sizes");
  const sizes = sizesAttr
    ? sizesAttr
        .split(",")
        .map((s) => s.trim())
        .filter(Boolean)
    : [];
  addToCart(itemId, itemName, itemPrice, itemImage, itemDiscount, {
    sizes: sizes,
    sourceButton: button,
  });
}

function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Add loading states (currently unused but available for future use)
function showLoading(button) {
  button.disabled = true;
  button.innerHTML = '&lt;div class="spinner"&gt;&lt;/div&gt; Loading...';
}

function hideLoading(button, originalText) {
  button.disabled = false;
  button.innerHTML = originalText;
}
// ===== PROFESSIONAL ANIMATIONS & SCROLL EFFECTS =====

// Scroll-triggered animations
function initScrollAnimations() {
  const observerOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px",
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("animate");

        // Add stagger animation to children if parent has stagger-animation class
        if (entry.target.classList.contains("stagger-animation")) {
          const children = entry.target.children;
          Array.from(children).forEach((child, index) => {
            setTimeout(() => {
              child.style.opacity = "1";
              child.style.transform = "translateY(0)";
            }, index * 100);
          });
        }
      }
    });
  }, observerOptions);

  // Observe all elements with animate-on-scroll class
  document.querySelectorAll(".animate-on-scroll").forEach((el) => {
    observer.observe(el);
  });

  // Observe all elements with stagger-animation class
  document.querySelectorAll(".stagger-animation").forEach((el) => {
    observer.observe(el);
  });
}

// Enhanced hover effects
function initHoverEffects() {
  // Add magnetic effect to buttons
  document.querySelectorAll(".btn").forEach((btn) => {
    btn.addEventListener("mousemove", (e) => {
      const rect = btn.getBoundingClientRect();
      const x = e.clientX - rect.left - rect.width / 2;
      const y = e.clientY - rect.top - rect.height / 2;

      btn.style.transform = `translate(${x * 0.1}px, ${y * 0.1}px)`;
    });

    btn.addEventListener("mouseleave", () => {
      btn.style.transform = "translate(0, 0)";
    });
  });

  // Enhanced card hover effects
  document.querySelectorAll(".menu-card").forEach((card) => {
    card.addEventListener("mouseenter", () => {
      card.style.transform = "translateY(-15px) rotate(1deg)";
    });

    card.addEventListener("mouseleave", () => {
      card.style.transform = "translateY(0) rotate(0deg)";
    });
  });
}

// Smooth scroll for anchor links
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    });
  });
}

// Page load animations with delay
function initPageLoadAnimations() {
  // Note: animate-on-scroll classes are already added in HTML
  // Add floating animation to WhatsApp button
  const whatsappBtn = document.querySelector(".whatsapp-float");
  if (whatsappBtn) {
    whatsappBtn.classList.add("float");
  }

  // Add gentle pulse to hero button
  const heroBtn = document.querySelector(".hero .btn");
  if (heroBtn) {
    heroBtn.classList.add("pulse-gentle");
  }
}

// Typing effect for hero text (optional enhancement)
function initTypingEffect() {
  const heroText = document.querySelector(".hero-content h1");
  if (heroText && heroText.textContent.includes("DigitalDine")) {
    const text = heroText.textContent;
    heroText.textContent = "";
    let i = 0;

    const typeWriter = () => {
      if (i < text.length) {
        heroText.textContent += text.charAt(i);
        i++;
        setTimeout(typeWriter, 100);
      }
    };

    setTimeout(typeWriter, 500);
  }
}

// Parallax effect for hero section (subtle)
function initParallaxEffect() {
  window.addEventListener("scroll", () => {
    const scrolled = window.pageYOffset;
    const hero = document.querySelector(".hero");

    if (hero) {
      hero.style.backgroundPositionY = -(scrolled * 0.5) + "px";
    }
  });
}

// Initialize all animations when DOM is loaded
function initAnimations() {
  initScrollAnimations();
  initHoverEffects();
  initSmoothScroll();
  initPageLoadAnimations();
  initParallaxEffect();

  // Optional: Enable typing effect (comment out if not wanted)
  // initTypingEffect();
}

// Call animation initialization
// initAnimations(); // Moved to DOMContentLoaded

// Add loading animation to page transitions
window.addEventListener("beforeunload", () => {
  document.body.style.opacity = "0.5";
});

window.addEventListener("load", () => {
  document.body.style.opacity = "1";
  document.body.style.transition = "opacity 0.3s ease";
});

// ===== BANNER SLIDER FUNCTIONALITY =====

let currentBannerIndex = 0;
let bannerInterval;

function initBannerSlider() {
  const banners = document.querySelectorAll(".banner-slide");
  const indicators = document.querySelectorAll(".indicator");

  if (banners.length === 0) return;

  // Show first banner
  showBanner(0);

  // Auto-rotate banners every 5 seconds
  bannerInterval = setInterval(() => {
    nextBanner();
  }, 5000);

  // Pause auto-rotation on hover
  const bannerSection = document.querySelector(".banner-slider-section");
  if (bannerSection) {
    bannerSection.addEventListener("mouseenter", () => {
      clearInterval(bannerInterval);
    });

    bannerSection.addEventListener("mouseleave", () => {
      bannerInterval = setInterval(() => {
        nextBanner();
      }, 5000);
    });
  }

  // Keyboard navigation
  document.addEventListener("keydown", (e) => {
    if (e.key === "ArrowLeft") {
      prevBanner();
    } else if (e.key === "ArrowRight") {
      nextBanner();
    }
  });
}

function showBanner(index) {
  const banners = document.querySelectorAll(".banner-slide");
  const indicators = document.querySelectorAll(".indicator");

  if (banners.length === 0) return;

  // Hide all banners
  banners.forEach((banner) => banner.classList.remove("active"));
  indicators.forEach((indicator) => indicator.classList.remove("active"));

  // Show selected banner
  banners[index].classList.add("active");
  if (indicators[index]) {
    indicators[index].classList.add("active");
  }

  currentBannerIndex = index;
}

function nextBanner() {
  const banners = document.querySelectorAll(".banner-slide");
  if (banners.length === 0) return;

  const nextIndex = (currentBannerIndex + 1) % banners.length;
  showBanner(nextIndex);
}

function prevBanner() {
  const banners = document.querySelectorAll(".banner-slide");
  if (banners.length === 0) return;

  const prevIndex = (currentBannerIndex - 1 + banners.length) % banners.length;
  showBanner(prevIndex);
}

function goToBanner(index) {
  showBanner(index);
}

// ==========================================
// PROFESSIONAL QR DINING EXPERIENCE FEATURES
// ==========================================

class ProDiningExperience {
  constructor() {
    this.init();
  }

  init() {
    this.bindEvents();
    this.initializeAnimations();
    this.setupIntersectionObserver();
    this.initializeFloatingElements();
    this.setupPhoneInteractions();
    this.initializeScrollEffects();
  }

  bindEvents() {
    // CTA Button interactions
    const ctaPrimary = document.querySelector(".cta-primary");
    if (ctaPrimary) {
      ctaPrimary.addEventListener("mouseenter", this.handleCTAHover.bind(this));
      ctaPrimary.addEventListener("mouseleave", this.handleCTALeave.bind(this));
      ctaPrimary.addEventListener("click", this.handleCTAClick.bind(this));
    }

    // Watch Demo button
    const ctaSecondary = document.querySelector(".cta-secondary");
    if (ctaSecondary) {
      ctaSecondary.addEventListener("click", this.handleDemoClick.bind(this));
    }

    // Phone interaction
    const phoneDevice = document.querySelector(".phone-device");
    if (phoneDevice) {
      phoneDevice.addEventListener("click", this.handlePhoneClick.bind(this));
    }

    // Action cards interaction
    const actionCards = document.querySelectorAll(".action-card");
    actionCards.forEach((card) => {
      card.addEventListener("click", this.handleActionCardClick.bind(this));
    });
  }

  initializeAnimations() {
    // Stagger animation for feature highlights
    const highlights = document.querySelectorAll(".highlight-item");
    highlights.forEach((item, index) => {
      item.style.animationDelay = `${index * 0.2}s`;
      item.classList.add("animate-in");
    });

    // Stagger trust indicators
    const trustItems = document.querySelectorAll(".trust-item");
    trustItems.forEach((item, index) => {
      item.style.animationDelay = `${index * 0.15}s`;
      item.classList.add("animate-in");
    });
  }

  setupIntersectionObserver() {
    const observerOptions = {
      threshold: 0.1,
      rootMargin: "0px 0px -50px 0px",
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("in-view");
        }
      });
    }, observerOptions);

    // Observe all animatable elements
    const animatedElements = document.querySelectorAll(
      ".hero-left, .hero-right, .premium-badge, .hero-headline, .feature-highlights, .trust-indicators",
    );
    animatedElements.forEach((el) => observer.observe(el));
  }

  initializeFloatingElements() {
    // Animate floating shapes
    const shapes = document.querySelectorAll(".floating-shape");
    shapes.forEach((shape, index) => {
      shape.style.animationDelay = `${index * 0.5}s`;
      shape.style.animationDuration = `${3 + index * 0.5}s`;
    });

    // Add mouse movement parallax
    document.addEventListener("mousemove", (e) => {
      const shapes = document.querySelectorAll(".floating-shape");
      const mouseX = e.clientX / window.innerWidth;
      const mouseY = e.clientY / window.innerHeight;

      shapes.forEach((shape, index) => {
        const speed = (index + 1) * 0.5;
        const x = (mouseX - 0.5) * speed;
        const y = (mouseY - 0.5) * speed;
        shape.style.transform = `translate(${x}px, ${y}px)`;
      });
    });
  }

  setupPhoneInteractions() {
    // Phone screen interactions
    const phoneScreen = document.querySelector(".phone-screen");
    if (phoneScreen) {
      phoneScreen.addEventListener("click", (e) => {
        e.stopPropagation();
        this.showPhoneInteraction();
      });
    }

    // Navigation items
    const navItems = document.querySelectorAll(".nav-item");
    navItems.forEach((item) => {
      item.addEventListener("click", (e) => {
        e.stopPropagation();
        this.handleNavClick(e.currentTarget);
      });
    });
  }

  initializeScrollEffects() {
    let ticking = false;

    const updateScrollEffects = () => {
      const scrolled = window.pageYOffset;
      const heroSection = document.querySelector(".pro-dining-hero");

      if (heroSection) {
        const heroRect = heroSection.getBoundingClientRect();
        const heroTop = heroRect.top;
        const heroHeight = heroRect.height;

        // Parallax effect for background elements
        if (heroTop < window.innerHeight && heroTop > -heroHeight) {
          const progress =
            Math.abs(heroTop) / (window.innerHeight + heroHeight);
          const bgElements = document.querySelector(".hero-bg-elements");
          if (bgElements) {
            bgElements.style.transform = `translateY(${progress * 50}px)`;
          }
        }
      }

      ticking = false;
    };

    window.addEventListener("scroll", () => {
      if (!ticking) {
        requestAnimationFrame(updateScrollEffects);
        ticking = true;
      }
    });
  }

  handleCTAHover(e) {
    const button = e.currentTarget;
    const arrow = button.querySelector(".cta-arrow");
    const glow = button.querySelector(".cta-glow");

    if (arrow) {
      arrow.style.transform = "translateX(5px)";
    }
    if (glow) {
      glow.style.opacity = "1";
      glow.style.transform = "scale(1.1)";
    }

    // Add ripple effect
    this.createRippleEffect(e, button);
  }

  handleCTALeave(e) {
    const button = e.currentTarget;
    const arrow = button.querySelector(".cta-arrow");
    const glow = button.querySelector(".cta-glow");

    if (arrow) {
      arrow.style.transform = "translateX(0)";
    }
    if (glow) {
      glow.style.opacity = "0";
      glow.style.transform = "scale(1)";
    }
  }

  handleCTAClick(e) {
    e.preventDefault();

    // Add click animation
    const button = e.currentTarget;
    button.classList.add("clicked");

    // Create multiple ripples
    for (let i = 0; i < 3; i++) {
      setTimeout(() => {
        this.createRippleEffect(e, button, true);
      }, i * 100);
    }

    // Navigate after animation
    setTimeout(() => {
      window.location.href = "menu.php";
    }, 600);
  }

  handleDemoClick(e) {
    e.preventDefault();

    const phoneDevice = document.querySelector(".phone-device");
    if (phoneDevice) {
      phoneDevice.classList.add("demo-mode");
      this.showDemoAnimation();
    }
  }

  handlePhoneClick(e) {
    const phoneDevice = e.currentTarget;
    phoneDevice.classList.add("interacted");

    // Add vibration effect
    phoneDevice.style.animation = "vibrate 0.3s ease-in-out";

    setTimeout(() => {
      phoneDevice.style.animation = "";
      phoneDevice.classList.remove("interacted");
    }, 300);
  }

  handleActionCardClick(e) {
    const card = e.currentTarget;
    const isPrimary = card.classList.contains("primary");

    // Add click feedback
    card.classList.add("clicked");

    setTimeout(() => {
      card.classList.remove("clicked");
    }, 300);

    // Show different interactions based on card type
    if (isPrimary) {
      this.showMenuInteraction();
    } else {
      this.showWaiterCall();
    }
  }

  handleNavClick(navItem) {
    // Remove active class from all nav items
    document.querySelectorAll(".nav-item").forEach((item) => {
      item.classList.remove("active");
    });

    // Add active class to clicked item
    navItem.classList.add("active");

    // Update screen content based on navigation
    const navText = navItem.querySelector("span").textContent;
    this.updatePhoneScreen(navText);
  }

  createRippleEffect(e, element, isLarge = false) {
    const ripple = document.createElement("div");
    const rect = element.getBoundingClientRect();
    const size = isLarge
      ? Math.max(rect.width, rect.height) * 2
      : Math.max(rect.width, rect.height);
    const x = e.clientX - rect.left - size / 2;
    const y = e.clientY - rect.top - size / 2;

    ripple.style.width = ripple.style.height = size + "px";
    ripple.style.left = x + "px";
    ripple.style.top = y + "px";
    ripple.className = isLarge ? "ripple-large" : "ripple";

    element.appendChild(ripple);

    setTimeout(() => {
      ripple.remove();
    }, 600);
  }

  showPhoneInteraction() {
    const screenContent = document.querySelector(".screen-content");
    if (screenContent) {
      screenContent.classList.add("interacting");

      setTimeout(() => {
        screenContent.classList.remove("interacting");
      }, 1000);
    }
  }

  showDemoAnimation() {
    const phoneDevice = document.querySelector(".phone-device");
    const actionCards = document.querySelectorAll(".action-card");

    // Animate phone
    phoneDevice.classList.add("demo-active");

    // Animate cards sequentially
    actionCards.forEach((card, index) => {
      setTimeout(() => {
        card.classList.add("demo-highlight");
        setTimeout(() => {
          card.classList.remove("demo-highlight");
        }, 1000);
      }, index * 500);
    });

    setTimeout(() => {
      phoneDevice.classList.remove("demo-active");
    }, 3000);
  }

  showMenuInteraction() {
    const appContent = document.querySelector(".app-content");
    if (appContent) {
      appContent.innerHTML = `
        <div class="menu-screen animate-in">
          <div class="menu-header">
            <h3>Our Menu</h3>
            <div class="menu-categories">
              <span class="category active">All</span>
              <span class="category">Starters</span>
              <span class="category">Main Course</span>
              <span class="category">Desserts</span>
            </div>
          </div>
          <div class="menu-items">
            <div class="menu-item">
              <div class="item-image">
                <i class="fas fa-utensils"></i>
              </div>
              <div class="item-details">
                <h4>Grilled Salmon</h4>
                <p>Fresh Atlantic salmon with herbs</p>
                <span class="price">$24.99</span>
              </div>
              <button class="add-btn">+</button>
            </div>
          </div>
        </div>
      `;
    }
  }

  showWaiterCall() {
    const appContent = document.querySelector(".app-content");
    if (appContent) {
      appContent.innerHTML = `
        <div class="waiter-screen animate-in">
          <div class="call-header">
            <i class="fas fa-bell ringing"></i>
            <h3>Calling Waiter...</h3>
          </div>
          <div class="call-status">
            <div class="status-indicator">
              <div class="pulse-ring"></div>
              <div class="pulse-ring pulse-delay"></div>
              <i class="fas fa-user-tie"></i>
            </div>
            <p>A waiter will be with you shortly</p>
          </div>
          <button class="cancel-call">Cancel Call</button>
        </div>
      `;

      // Add cancel functionality
      const cancelBtn = document.querySelector(".cancel-call");
      if (cancelBtn) {
        cancelBtn.addEventListener("click", () => {
          this.resetPhoneScreen();
        });
      }
    }
  }

  updatePhoneScreen(navText) {
    const appContent = document.querySelector(".app-content");

    switch (navText) {
      case "Home":
        this.resetPhoneScreen();
        break;
      case "Menu":
        this.showMenuInteraction();
        break;
      case "Cart":
        appContent.innerHTML = `
          <div class="cart-screen animate-in">
            <h3>Your Cart</h3>
            <div class="empty-cart">
              <i class="fas fa-shopping-cart"></i>
              <p>Your cart is empty</p>
            </div>
          </div>
        `;
        break;
      case "Profile":
        appContent.innerHTML = `
          <div class="profile-screen animate-in">
            <div class="profile-header">
              <div class="avatar">
                <i class="fas fa-user"></i>
              </div>
              <h3>John Doe</h3>
            </div>
            <div class="profile-options">
              <div class="option">
                <i class="fas fa-history"></i>
                <span>Order History</span>
              </div>
              <div class="option">
                <i class="fas fa-heart"></i>
                <span>Favorites</span>
              </div>
              <div class="option">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
              </div>
            </div>
          </div>
        `;
        break;
    }
  }

  resetPhoneScreen() {
    const appContent = document.querySelector(".app-content");
    if (appContent) {
      appContent.innerHTML = `
        <div class="welcome-screen">
          <div class="qr-success">
            <div class="success-icon">
              <i class="fas fa-check-circle"></i>
            </div>
            <h3>QR Code Scanned!</h3>
            <p>Ready to start your dining experience</p>
          </div>
          <div class="action-cards">
            <div class="action-card primary">
              <div class="card-icon">
                <i class="fas fa-list"></i>
              </div>
              <div class="card-content">
                <h4>Browse Menu</h4>
                <p>Explore our delicious dishes</p>
              </div>
              <div class="card-arrow">
                <i class="fas fa-chevron-right"></i>
              </div>
            </div>
            <div class="action-card secondary">
              <div class="card-icon">
                <i class="fas fa-bell"></i>
              </div>
              <div class="card-content">
                <h4>Call Waiter</h4>
                <p>Need assistance? We're here</p>
              </div>
              <div class="card-arrow">
                <i class="fas fa-chevron-right"></i>
              </div>
            </div>
          </div>
        </div>
      `;

      // Re-bind action card events
      const actionCards = document.querySelectorAll(".action-card");
      actionCards.forEach((card) => {
        card.addEventListener("click", this.handleActionCardClick.bind(this));
      });
    }
  }
}

// Initialize professional dining experience when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new ProDiningExperience();
});

// Add CSS animations via JavaScript for dynamic effects
const styleSheet = document.createElement("style");
styleSheet.textContent = `
  @keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
  }

  @keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.7; }
  }

  @keyframes vibrate {
    0% { transform: translateX(0); }
    25% { transform: translateX(-2px); }
    50% { transform: translateX(2px); }
    75% { transform: translateX(-2px); }
    100% { transform: translateX(0); }
  }

  @keyframes ripple {
    0% { transform: scale(0); opacity: 1; }
    100% { transform: scale(4); opacity: 0; }
  }

  .floating-shape {
    animation: float 6s ease-in-out infinite;
  }

  .animate-in {
    opacity: 0;
    transform: translateY(30px);
    animation: slideInUp 0.8s ease-out forwards;
  }

  @keyframes slideInUp {
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .in-view {
    animation-play-state: running;
  }

  .cta-primary.clicked {
    animation: buttonPress 0.3s ease;
  }

  @keyframes buttonPress {
    0% { transform: scale(1); }
    50% { transform: scale(0.95); }
    100% { transform: scale(1); }
  }

  .ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.6);
    animation: ripple 0.6s linear;
    pointer-events: none;
  }

  .ripple-large {
    background: rgba(255, 255, 255, 0.3);
    animation: ripple 1s linear;
  }

  .phone-device.demo-active {
    animation: demoPulse 2s ease-in-out infinite;
  }

  @keyframes demoPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(102, 126, 234, 0); }
  }

  .action-card.clicked {
    animation: cardClick 0.3s ease;
  }

  @keyframes cardClick {
    0% { transform: scale(1); }
    50% { transform: scale(0.95); }
    100% { transform: scale(1); }
  }

  .action-card.demo-highlight {
    background: linear-gradient(135deg, #667eea, #764ba2);
    transform: scale(1.05);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
  }

  .ringing {
    animation: ring 1s ease-in-out infinite;
  }

  @keyframes ring {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(5deg); }
    75% { transform: rotate(-5deg); }
  }

  .pulse-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 2px solid #667eea;
    border-radius: 50%;
    animation: pulseRing 2s infinite;
  }

  .pulse-ring.pulse-delay {
    animation-delay: 1s;
  }

  @keyframes pulseRing {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
  }
`;
document.head.appendChild(styleSheet);

/* QR Scanner Functionality */
let isFlashOn = false;
let isCameraActive = true;
let videoStream = null;
let qrScanner = null;

async function startQRScan() {
  const phoneDevice = document.getElementById("phoneDevice");
  const scannerViewport = document.querySelector(".scanner-viewport");
  const scanQrBtn = document.getElementById("scan-qr-btn");

  if (!phoneDevice) {
    console.log("Phone device element not found");
    return;
  }

  // Update button state
  scanQrBtn.disabled = true;
  scanQrBtn.innerHTML =
    '<i class="fas fa-spinner fa-spin"></i> Accessing Camera...';

  try {
    // Request camera access
    const stream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: "environment",
        width: { ideal: 1280 },
        height: { ideal: 720 },
      },
    });

    videoStream = stream;

    // Create video element for camera feed
    const videoElement = document.createElement("video");
    videoElement.id = "camera-feed";
    videoElement.srcObject = stream;
    videoElement.autoplay = true;
    videoElement.playsinline = true;
    videoElement.style.cssText = `
      width: 100%;
      height: 100%;
      object-fit: cover;
      position: absolute;
      top: 0;
      left: 0;
      z-index: 1;
      border-radius: 12px;
    `;

    // Clear scanner viewport and add video
    scannerViewport.innerHTML = "";
    scannerViewport.appendChild(videoElement);

    // Add scan animation overlay
    const scanAnimation = document.createElement("div");
    scanAnimation.className = "scan-animation";
    scanAnimation.innerHTML = `
      <div class="scan-line"></div>
      <div class="scan-corners">
        <div class="corner top-left"></div>
        <div class="corner top-right"></div>
        <div class="corner bottom-left"></div>
        <div class="corner bottom-right"></div>
      </div>
    `;
    scanAnimation.style.cssText = `
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      z-index: 3;
    `;
    scannerViewport.appendChild(scanAnimation);

    // Update button state
    scanQrBtn.disabled = false;
    scanQrBtn.innerHTML = '<i class="fas fa-stop-circle"></i> Stop Scanning';
    scanQrBtn.onclick = stopQRScan;

    // Update scan buttons state
    const scanButtons = document.querySelectorAll(".scanner-footer .phone-btn");
    scanButtons.forEach((btn, index) => {
      btn.disabled = false;
      if (index === 1) {
        // Camera button
        btn.classList.add("active");
      }
    });

    // Load QR scanning library
    if (!window.Html5Qrcode) {
      const script = document.createElement("script");
      script.src =
        "https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.4/html5-qrcode.min.js";
      script.onload = () => {
        initializeQRScannerWithVideo(videoElement);
      };
      document.head.appendChild(script);
    } else {
      initializeQRScannerWithVideo(videoElement);
    }
  } catch (error) {
    console.error("Camera access error:", error);

    // Show error message in scanner
    scannerViewport.innerHTML = `
      <div style="
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        text-align: center;
        padding: 20px;
        border-radius: 12px;
      ">
        <i class="fas fa-camera-slash" style="font-size: 2.5rem; margin-bottom: 1rem; color: #ff3366;"></i>
        <h4 style="margin: 0 0 0.5rem 0;">Camera Access Denied</h4>
        <p style="margin: 0; font-size: 0.85rem; opacity: 0.8;">${error.name}: ${error.message}</p>
        <p style="margin: 1rem 0 0 0; font-size: 0.75rem; opacity: 0.6;">Please enable camera permissions in your browser settings</p>
      </div>
    `;

    scanQrBtn.disabled = false;
    scanQrBtn.innerHTML = '<i class="fas fa-camera"></i> Scan QR Code';
    scanQrBtn.onclick = startQRScan;
  }
}

function stopQRScan() {
  const scanQrBtn = document.getElementById("scan-qr-btn");
  const scannerViewport = document.querySelector(".scanner-viewport");

  // Stop video stream
  if (videoStream) {
    videoStream.getTracks().forEach((track) => track.stop());
    videoStream = null;
  }

  // Restore original scanner view
  scannerViewport.innerHTML = `
    <div class="scan-animation">
      <div class="scan-line"></div>
      <div class="scan-corners">
        <div class="corner top-left"></div>
        <div class="corner top-right"></div>
        <div class="corner bottom-left"></div>
        <div class="corner bottom-right"></div>
      </div>
    </div>
    <div class="qr-placeholder">
      <i class="fas fa-qrcode"></i>
    </div>
  `;

  // Reset button
  scanQrBtn.disabled = false;
  scanQrBtn.innerHTML = '<i class="fas fa-camera"></i> Scan QR Code';
  scanQrBtn.onclick = startQRScan;

  // Reset scan buttons
  const scanButtons = document.querySelectorAll(".scanner-footer .phone-btn");
  scanButtons.forEach((btn, index) => {
    btn.disabled = false;
    if (index === 1) {
      // Camera button
      btn.classList.add("active");
    } else {
      btn.classList.remove("active");
    }
  });

  // Reset flash
  isFlashOn = false;
  isCameraActive = true;
}

function initializeQRScannerWithVideo(videoElement) {
  try {
    // Load jsQR library for frame-based detection
    if (!window.jsQR) {
      const script = document.createElement("script");
      script.src = "https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js";
      script.onload = () => {
        startFrameBasedQRDetection(videoElement);
      };
      document.head.appendChild(script);
    } else {
      startFrameBasedQRDetection(videoElement);
    }
  } catch (error) {
    console.error("Error initializing QR scanner:", error);
  }
}

function startFrameBasedQRDetection(videoElement) {
  const canvas = document.createElement("canvas");
  const ctx = canvas.getContext("2d");
  let isScanning = true;
  let lastDetectedCode = null;
  let detectionCooldown = 0;
  let frameCount = 0;

  // Set canvas size
  function updateCanvasSize() {
    if (videoElement.videoWidth && videoElement.videoHeight) {
      canvas.width = videoElement.videoWidth;
      canvas.height = videoElement.videoHeight;
    }
  }

  // Wait for video to load
  if (videoElement.readyState < 2) {
    videoElement.onloadeddata = updateCanvasSize;
  } else {
    updateCanvasSize();
  }

  function captureFrame() {
    if (!isScanning || !videoStream) {
      window.qrScanningActive = false;
      return;
    }

    try {
      // Only process every other frame for performance
      frameCount++;
      if (frameCount % 2 === 0) {
        // Draw current video frame to canvas
        if (videoElement.readyState === videoElement.HAVE_ENOUGH_DATA) {
          ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);

          // Get image data
          const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

          // Try jsQR detection
          if (window.jsQR) {
            const code = window.jsQR(
              imageData.data,
              imageData.width,
              imageData.height,
            );
            if (code && code.data) {
              if (lastDetectedCode !== code.data && detectionCooldown === 0) {
                console.log("QR Code detected:", code.data);
                lastDetectedCode = code.data;
                detectionCooldown = 60; // 60 frames cooldown
                isScanning = false; // Stop scanning
                handleQRCodeDetected(code.data, null);
              }
            }
          }
        }
      }

      if (detectionCooldown > 0) {
        detectionCooldown--;
      }

      // Continue scanning
      if (isScanning) {
        requestAnimationFrame(captureFrame);
      }
    } catch (error) {
      console.error("Frame capture error:", error);
      if (isScanning) {
        requestAnimationFrame(captureFrame);
      }
    }
  }

  // Start capturing frames
  window.qrScanningActive = true;
  captureFrame();
}

function handleQRCodeDetected(decodedText, scanner) {
  const scannerViewport = document.querySelector(".scanner-viewport");

  // Stop the scanner
  if (scanner) {
    try {
      scanner.stop();
    } catch (e) {}
  }

  // Stop video stream
  if (videoStream) {
    videoStream.getTracks().forEach((track) => track.stop());
    videoStream = null;
  }

  // Show success animation in scanner
  scannerViewport.innerHTML = `
    <div style="
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
      color: white;
      text-align: center;
      animation: scaleIn 0.5s ease-out;
    ">
      <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
      <h4 style="margin: 0 0 0.5rem 0;">QR Code Scanned!</h4>
      <p style="margin: 0; font-size: 0.85rem; opacity: 0.9;">${decodedText}</p>
      <p style="margin: 1rem 0 0 0; font-size: 0.75rem; opacity: 0.7;">Processing...</p>
    </div>
  `;

  // Process the QR code
  processQRCode(decodedText);
}

function processQRCode(qrData) {
  console.log("Processing QR code:", qrData);

  // Extract table number from QR data
  // QR could be: table_number, URL with table param, or other format
  let tableNumber = null;

  // Check if it's a URL
  if (qrData.startsWith("http://") || qrData.startsWith("https://")) {
    // Extract table parameter from URL
    const url = new URL(qrData);
    tableNumber = url.searchParams.get("table");
  } else if (qrData.includes("table=")) {
    // Check if it contains "table=" parameter
    const urlParams = new URLSearchParams(qrData.split("?")[1]);
    tableNumber = urlParams.get("table");
  } else {
    // Otherwise, treat the entire data as table number
    tableNumber = qrData.trim();
  }

  // Validate table number (should be alphanumeric)
  if (!tableNumber || tableNumber.length === 0) {
    console.error("Invalid QR code: No table number found");
    alert("Invalid QR code. Please try again.");
    location.reload();
    return;
  }

  console.log("Extracted table number:", tableNumber);

  // Check if user has items in cart (came from checkout redirect)
  const cart = JSON.parse(localStorage.getItem("cart")) || [];
  const hasCartItems = cart.length > 0;

  // Show processing message
  const qrResult = document.getElementById("qr-result");
  if (qrResult) {
    qrResult.innerHTML = `<p style="color: #fff; font-size: 0.9rem;">✓ Table: ${tableNumber}</p><p style="color: #fff; font-size: 0.8rem;">${hasCartItems ? "Redirecting to checkout..." : "Redirecting to menu..."}</p>`;
    qrResult.style.display = "block";
  }

  // Redirect based on cart status
  const redirectUrl = hasCartItems
    ? `checkout.php?table=${encodeURIComponent(tableNumber)}`
    : `menu.php?table=${encodeURIComponent(tableNumber)}`;
  console.log("Redirecting to:", redirectUrl);

  setTimeout(() => {
    window.location.href = redirectUrl;
  }, 1500);
}

/* Phone Mockup Interactive Functions */
function toggleFlash() {
  const flashBtn = document.querySelectorAll(".phone-btn")[0];
  const scannerViewport = document.querySelector(".scanner-viewport");
  const videoElement = document.getElementById("camera-feed");

  if (!videoElement) {
    console.log("Camera not active");
    return;
  }

  isFlashOn = !isFlashOn;

  if (isFlashOn) {
    flashBtn.classList.add("active");
    flashBtn.style.background = "linear-gradient(135deg, #ffd700, #ffed4e)";
    flashBtn.style.color = "#333";
    flashBtn.style.boxShadow = "0 0 20px rgba(255, 215, 0, 0.6)";

    // Add brightness effect to video
    videoElement.style.filter = "brightness(1.4) contrast(1.1)";
    scannerViewport.style.background = "rgba(255, 255, 255, 0.1)";
  } else {
    flashBtn.classList.remove("active");
    flashBtn.style.background = "rgba(255, 255, 255, 0.2)";
    flashBtn.style.color = "white";
    flashBtn.style.boxShadow = "";

    // Remove brightness effect
    videoElement.style.filter = "brightness(1) contrast(1)";
    scannerViewport.style.background = "rgba(0, 0, 0, 0.1)";
  }
}

function toggleCamera() {
  const cameraBtn = document.querySelectorAll(".phone-btn")[1];
  const videoElement = document.getElementById("camera-feed");
  const scannerViewport = document.querySelector(".scanner-viewport");

  if (!videoElement) {
    console.log("Camera not active");
    return;
  }

  isCameraActive = !isCameraActive;

  if (!isCameraActive) {
    cameraBtn.classList.remove("active");
    videoElement.style.display = "none";

    // Show camera off message but keep scanning active in background
    const offMessage = document.getElementById("camera-off-message");
    if (!offMessage) {
      const newOffMessage = document.createElement("div");
      newOffMessage.id = "camera-off-message";
      newOffMessage.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 5;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
      `;
      newOffMessage.innerHTML = `
        <i class="fas fa-ban" style="font-size: 2.5rem; color: rgba(255, 100, 100, 0.7); margin-bottom: 1rem;"></i>
        <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.9rem; margin: 0; text-align: center;">Camera Display Off<br><span style="font-size: 0.75rem; opacity: 0.7;">Scanning continues...</span></p>
      `;
      scannerViewport.appendChild(newOffMessage);
    }
    scannerViewport.style.background =
      "linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)";
  } else {
    cameraBtn.classList.add("active");
    videoElement.style.display = "block";

    // Remove camera off message
    const offMessage = document.getElementById("camera-off-message");
    if (offMessage) {
      offMessage.remove();
    }

    // Restore normal background
    scannerViewport.style.background = "rgba(0, 0, 0, 0.1)";
  }
}

function toggleGallery() {
  const galleryBtn = document.querySelectorAll(".phone-btn")[2];
  const scannerViewport = document.querySelector(".scanner-viewport");
  const videoElement = document.getElementById("camera-feed");

  galleryBtn.classList.toggle("active");

  if (galleryBtn.classList.contains("active")) {
    // Hide video and show gallery
    if (videoElement) {
      videoElement.style.display = "none";
    }

    scannerViewport.innerHTML = `
      <div style="
        display: flex;
        flex-direction: column;
        height: 100%;
        background: linear-gradient(135deg, #00d4ff 0%, #0099ff 100%);
        color: white;
        padding: 0;
        overflow: hidden;
      ">
        <div style="
          background: rgba(0, 0, 0, 0.3);
          padding: 12px;
          text-align: center;
          border-bottom: 1px solid rgba(255, 255, 255, 0.1);
          z-index: 5;
        ">
          <h4 style="margin: 0; font-size: 0.9rem; font-weight: 600;">Gallery</h4>
          <p style="margin: 2px 0 0 0; font-size: 0.7rem; opacity: 0.85;">No scanned items yet</p>
        </div>
        <div style="
          flex: 1;
          display: grid;
          grid-template-columns: repeat(2, 1fr);
          gap: 8px;
          padding: 12px;
          overflow-y: auto;
        ">
          <div style="
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60px;
            border: 1px dashed rgba(255, 255, 255, 0.3);
          ">
            <i class="fas fa-image" style="font-size: 1.5rem; opacity: 0.5;"></i>
          </div>
          <div style="
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60px;
            border: 1px dashed rgba(255, 255, 255, 0.3);
          ">
            <i class="fas fa-image" style="font-size: 1.5rem; opacity: 0.5;"></i>
          </div>
          <div style="
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60px;
            border: 1px dashed rgba(255, 255, 255, 0.3);
          ">
            <i class="fas fa-image" style="font-size: 1.5rem; opacity: 0.5;"></i>
          </div>
          <div style="
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60px;
            border: 1px dashed rgba(255, 255, 255, 0.3);
          ">
            <i class="fas fa-image" style="font-size: 1.5rem; opacity: 0.5;"></i>
          </div>
        </div>
      </div>
    `;
  } else {
    // Show video again and restore QR scanning
    if (videoElement) {
      videoElement.style.display = "block";
    }

    // Keep the video element and restore scanning overlay
    const scanAnimation = document.createElement("div");
    scanAnimation.className = "scan-animation";
    scanAnimation.innerHTML = `
      <div class="scan-line"></div>
      <div class="scan-corners">
        <div class="corner top-left"></div>
        <div class="corner top-right"></div>
        <div class="corner bottom-left"></div>
        <div class="corner bottom-right"></div>
      </div>
    `;
    scanAnimation.style.cssText = `
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      z-index: 3;
    `;
    scannerViewport.innerHTML = "";
    scannerViewport.appendChild(videoElement);
    scannerViewport.appendChild(scanAnimation);

    // Ensure video is still streaming
    if (videoStream && !videoElement.srcObject) {
      videoElement.srcObject = videoStream;
    }
  }
}
