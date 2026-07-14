const MINI_CART_KEY = "cart";

function formatPrice(value) {
  return "Rs " + Number(value).toLocaleString();
}

function getCart() {
  return JSON.parse(localStorage.getItem(MINI_CART_KEY)) || [];
}

function saveCart(cart) {
  localStorage.setItem(MINI_CART_KEY, JSON.stringify(cart));
}

function updateCartCount() {
  const cart = getCart();
  const total = cart.reduce((sum, item) => sum + item.quantity, 0);
  const countElement = document.getElementById("cart-count");
  if (countElement) {
    countElement.textContent = total;
  }
}

function calculateSubtotal(cart) {
  return cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
}

function buildCartItem(item) {
  const imageUrl = item.image
    ? `/restaurant_project/assets/images/${item.image}`
    : "/restaurant_project/assets/images/placeholder.jpg";
  return `
    <div class="mini-cart-item">
      <img src="${imageUrl}" alt="${item.name}" />
      <div class="mini-cart-item-details">
        <div class="mini-cart-item-title">${item.name}</div>
        <div class="mini-cart-item-meta">Qty: ${item.quantity}</div>
        <div class="mini-cart-item-price">${formatPrice(item.price)}</div>
      </div>
      <div class="mini-cart-item-total">${formatPrice(item.price * item.quantity)}</div>
    </div>
  `;
}

function renderMiniCart() {
  const itemsContainer = document.querySelector(".mini-cart-items");
  const subtotalElement = document.querySelector(".mini-cart-subtotal");
  const cart = getCart();

  if (!itemsContainer || !subtotalElement) {
    return;
  }

  if (cart.length === 0) {
    itemsContainer.innerHTML = `
      <div class="mini-cart-empty">
        <strong>Your cart is empty</strong>
        <p>Add items to see them here.</p>
      </div>
    `;
    subtotalElement.textContent = formatPrice(0);
    return;
  }

  itemsContainer.innerHTML = cart.map(buildCartItem).join("");
  subtotalElement.textContent = formatPrice(calculateSubtotal(cart));
}

function openMiniCart() {
  const drawer = document.getElementById("mini-cart-drawer");
  const overlay = document.getElementById("mini-cart-overlay");
  if (!drawer || !overlay) return;
  drawer.classList.add("open");
  overlay.classList.add("open");
  drawer.setAttribute("aria-hidden", "false");
  document.body.classList.add("mini-cart-open");
}

function closeMiniCart() {
  const drawer = document.getElementById("mini-cart-drawer");
  const overlay = document.getElementById("mini-cart-overlay");
  if (!drawer || !overlay) return;
  drawer.classList.remove("open");
  overlay.classList.remove("open");
  drawer.setAttribute("aria-hidden", "true");
  document.body.classList.remove("mini-cart-open");
}

function addToMiniCart(product) {
  const cart = getCart();
  const existing = cart.find((item) => String(item.id) === String(product.id));

  if (existing) {
    existing.quantity += 1;
  } else {
    cart.push({ ...product, quantity: 1 });
  }

  saveCart(cart);
  updateCartCount();
  renderMiniCart();
  openMiniCart();
}

function getProductFromButton(button) {
  if (!button) return null;

  const id = button.getAttribute("data-id");
  const name = button.getAttribute("data-name") || "Menu item";
  const price = parseFloat(button.getAttribute("data-price")) || 0;
  const discount = parseFloat(button.getAttribute("data-discount")) || 0;
  const image = button.getAttribute("data-image") || "";

  const finalPrice =
    discount > 0 ? Number((price * (100 - discount)) / 100).toFixed(2) : price;

  return {
    id,
    name,
    price: Number(finalPrice),
    image,
  };
}

function onAddToCartClick(event) {
  const button = event.target.closest(".mini-cart-add-btn");
  if (!button) return;

  event.preventDefault();
  event.stopImmediatePropagation();

  const product = getProductFromButton(button);
  if (!product || !product.id) return;

  addToMiniCart(product);
}

function initMiniCartDrawer() {
  const drawer = document.getElementById("mini-cart-drawer");
  const overlay = document.getElementById("mini-cart-overlay");
  const closeButton = document.querySelector(".mini-cart-close");
  const continueButton = document.querySelector(".mini-cart-secondary-btn");
  // Always update cart count so header cart indicator remains accurate
  updateCartCount();

  // Render mini cart only if elements exist on the page
  if (drawer && overlay) {
    renderMiniCart();

    if (closeButton) {
      closeButton.addEventListener("click", closeMiniCart);
    }

    if (continueButton) {
      continueButton.addEventListener("click", closeMiniCart);
    }

    overlay.addEventListener("click", closeMiniCart);

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        closeMiniCart();
      }
    });
  }

  // Ensure Add-to-Cart buttons work even if the drawer markup was removed
  document.addEventListener("click", onAddToCartClick, { capture: true });
}

window.addEventListener("load", initMiniCartDrawer);
