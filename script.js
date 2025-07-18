// UrbanStitch Enhanced E-commerce System - FIXED USER INTERFACE VERSION
// This version fixes the user menu, cart, and wishlist button functionality
// Version: 2.1.2 - Critical UI Bug Fixes

console.log("%c🛒 UrbanStitch Enhanced System Loading (UI Fixed)...", "color: #00ff00; font-weight: bold; font-size: 16px;");

// ==========================================
// GLOBAL VARIABLES AND STATE MANAGEMENT
// ==========================================

// Global state for the application
const UrbanStitchState = {
  selectedSizes: new Map(), // productId -> sizeCode
  cartItems: new Map(),
  wishlistItems: new Set(),
  isLoading: false,
  currentUser: null,
  notifications: [],
  sidebarStates: {
    cart: false,
    wishlist: false,
    user: false,
  },
  debug: true // Enable debug mode
};

// Configuration constants
const CONFIG = {
  API_ENDPOINTS: {
    ADD_TO_CART: window.location.pathname,
    ADD_TO_WISHLIST: window.location.pathname,
    UPDATE_QUANTITY: window.location.pathname,
    REMOVE_FROM_CART: window.location.pathname,
    REMOVE_FROM_WISHLIST: window.location.pathname,
    GET_CART_COUNT: window.location.pathname + "?ajax=get_cart_count",
    GET_WISHLIST_COUNT: window.location.pathname + "?ajax=get_wishlist_count",
    GET_CART_TOTAL: window.location.pathname + "?ajax=get_cart_total",
    GET_PRODUCT_SIZES: window.location.pathname + "?ajax=get_product_sizes",
  },
  ANIMATION_DURATION: 300,
  NOTIFICATION_DURATION: 3000,
  DEBOUNCE_DELAY: 250,
  MAX_QUANTITY: 99,
  MIN_QUANTITY: 1,
  DEBUG: true
};

// ==========================================
// ENHANCED DEBUGGING AND LOGGING
// ==========================================

class DebugLogger {
  constructor() {
    this.enabled = CONFIG.DEBUG;
    this.logs = [];
  }

  log(level, message, data = null) {
    if (!this.enabled) return;

    const timestamp = new Date().toISOString();
    const logEntry = {
      timestamp,
      level,
      message,
      data
    };

    this.logs.push(logEntry);

    const color = this.getLogColor(level);
    const prefix = this.getLogPrefix(level);

    if (data) {
      console.log(`%c${prefix} ${message}`, `color: ${color}; font-weight: bold;`, data);
    } else {
      console.log(`%c${prefix} ${message}`, `color: ${color}; font-weight: bold;`);
    }

    // Keep only last 100 logs
    if (this.logs.length > 100) {
      this.logs = this.logs.slice(-100);
    }
  }

  getLogColor(level) {
    const colors = {
      error: "#ff4444",
      warn: "#ff6b35",
      info: "#007bff",
      success: "#00ff00",
      debug: "#6c757d"
    };
    return colors[level] || "#333";
  }

  getLogPrefix(level) {
    const prefixes = {
      error: "❌ ERROR:",
      warn: "⚠️ WARN:",
      info: "ℹ️ INFO:",
      success: "✅ SUCCESS:",
      debug: "🐛 DEBUG:"
    };
    return prefixes[level] || "📝 LOG:";
  }

  error(message, data) { this.log("error", message, data); }
  warn(message, data) { this.log("warn", message, data); }
  info(message, data) { this.log("info", message, data); }
  success(message, data) { this.log("success", message, data); }
  debug(message, data) { this.log("debug", message, data); }

  getLogs() {
    return this.logs;
  }

  clear() {
    this.logs = [];
    console.clear();
  }
}

// Create global logger
const logger = new DebugLogger();

// ==========================================
// ENHANCED ERROR HANDLING
// ==========================================

class ErrorHandler {
  constructor() {
    this.errors = [];
    this.init();
  }

  init() {
    // Global error handler
    window.addEventListener('error', (event) => {
      this.handleError({
        type: 'javascript_error',
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno,
        stack: event.error?.stack,
        timestamp: Date.now()
      });
    });

    // Unhandled promise rejection handler
    window.addEventListener('unhandledrejection', (event) => {
      this.handleError({
        type: 'promise_rejection',
        message: event.reason?.message || 'Unhandled promise rejection',
        stack: event.reason?.stack,
        timestamp: Date.now()
      });
    });
  }

  handleError(error) {
    this.errors.push(error);
    logger.error(`${error.type}: ${error.message}`, error);

    // Show user-friendly notification for critical errors
    if (error.type === 'cart_error' || error.type === 'api_error') {
      notificationManager.show(
        error.userMessage || 'An error occurred. Please try again.',
        'error'
      );
    }

    // Keep only last 50 errors
    if (this.errors.length > 50) {
      this.errors = this.errors.slice(-50);
    }
  }

  getErrors() {
    return this.errors;
  }

  clear() {
    this.errors = [];
  }
}

// Create global error handler
const errorHandler = new ErrorHandler();

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

// Debounce function for performance optimization
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

// Throttle function for scroll events
function throttle(func, limit) {
  let inThrottle;
  return function () {
    const args = arguments;
    
    if (!inThrottle) {
      func.apply(this, args);
      inThrottle = true;
      setTimeout(() => (inThrottle = false), limit);
    }
  };
}

// Format currency
function formatCurrency(amount, currency = "USD") {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: currency,
  }).format(amount);
}

// Generate unique ID
function generateId() {
  return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

// Validate email
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

// Sanitize HTML
function sanitizeHTML(str) {
  const temp = document.createElement("div");
  temp.textContent = str;
  return temp.innerHTML;
}

// ==========================================
// ENHANCED API HELPER FUNCTIONS
// ==========================================

// Enhanced fetch wrapper with error handling and retries
async function apiRequest(url, options = {}, retries = 3) {
  const defaultOptions = {
    method: "GET",
    credentials: "same-origin",
  };

  const finalOptions = { ...defaultOptions, ...options };

  logger.debug(`API Request: ${finalOptions.method} ${url}`, finalOptions);

  for (let i = 0; i < retries; i++) {
    try {
      const response = await fetch(url, finalOptions);

      logger.debug(`API Response: ${response.status} ${response.statusText}`, {
        url,
        status: response.status,
        headers: Object.fromEntries(response.headers.entries())
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const contentType = response.headers.get("content-type");
      if (contentType && contentType.includes("application/json")) {
        const data = await response.json();
        logger.debug("API Response Data:", data);
        return data;
      } else {
        const text = await response.text();
        logger.debug("API Response Text:", text);
        
        // Try to parse as JSON if it looks like JSON
        if (text.trim().startsWith('{') || text.trim().startsWith('[')) {
          try {
            return JSON.parse(text);
          } catch (e) {
            return text;
          }
        }
        
        return text;
      }
    } catch (error) {
      logger.error(`API request failed (attempt ${i + 1}):`, error);

      if (i === retries - 1) {
        errorHandler.handleError({
          type: 'api_error',
          message: `Failed to ${finalOptions.method} ${url}: ${error.message}`,
          userMessage: 'Network error. Please check your connection and try again.',
          error,
          timestamp: Date.now()
        });
        throw error;
      }

      // Wait before retrying
      await new Promise((resolve) => setTimeout(resolve, 1000 * (i + 1)));
    }
  }
}

// Form data helper
function createFormData(data) {
  const formData = new FormData();
  Object.keys(data).forEach((key) => {
    if (data[key] !== null && data[key] !== undefined) {
      formData.append(key, data[key]);
    }
  });
  return formData;
}

// ==========================================
// ENHANCED NOTIFICATION SYSTEM
// ==========================================

class NotificationManager {
  constructor() {
    this.notifications = [];
    this.container = null;
    this.init();
  }

  init() {
    // Create notification container if it doesn't exist
    this.container = document.getElementById("notification-container");
    if (!this.container) {
      this.container = document.createElement("div");
      this.container.id = "notification-container";
      this.container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        pointer-events: none;
        max-width: 400px;
      `;
      document.body.appendChild(this.container);
    }
  }

  show(message, type = "success", duration = CONFIG.NOTIFICATION_DURATION) {
    logger.info(`Showing notification: ${type} - ${message}`);
    
    const notification = this.createNotification(message, type, duration);
    this.notifications.push(notification);
    this.container.appendChild(notification.element);

    // Animate in
    requestAnimationFrame(() => {
      notification.element.style.transform = "translateX(0)";
      notification.element.style.opacity = "1";
    });

    // Auto remove
    setTimeout(() => {
      this.remove(notification.id);
    }, duration);

    return notification.id;
  }

  createNotification(message, type, duration) {
    const id = generateId();
    const element = document.createElement("div");

    const config = this.getTypeConfig(type);

    element.style.cssText = `
      background: ${config.background};
      color: ${config.color};
      padding: 16px 20px;
      border-radius: 8px;
      margin-bottom: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      transform: translateX(400px);
      opacity: 0;
      transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      pointer-events: auto;
      cursor: pointer;
      position: relative;
      overflow: hidden;
      max-width: 100%;
      word-wrap: break-word;
    `;

    element.innerHTML = `
      <div style="display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-${config.icon}" style="font-size: 18px; flex-shrink: 0;"></i>
        <div style="flex: 1;">
          <div style="font-weight: 600; margin-bottom: 2px;">${config.title}</div>
          <div style="font-size: 14px; opacity: 0.9;">${sanitizeHTML(message)}</div>
        </div>
        <button onclick="notificationManager.remove('${id}')" style="
          background: none; 
          border: none; 
          color: inherit; 
          cursor: pointer; 
          padding: 4px; 
          border-radius: 4px;
          opacity: 0.7;
          transition: opacity 0.2s;
        " onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div style="
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        background: rgba(255,255,255,0.3);
        animation: notificationProgress ${duration}ms linear;
      "></div>
    `;

    // Add click to dismiss
    element.addEventListener("click", () => {
      this.remove(id);
    });

    return { id, element, type, message };
  }

  getTypeConfig(type) {
    const configs = {
      success: {
        background: "#00ff00",
        color: "#1a1a1a",
        icon: "check-circle",
        title: "Success",
      },
      error: {
        background: "#ff4444",
        color: "white",
        icon: "exclamation-circle",
        title: "Error",
      },
      warning: {
        background: "#ff6b35",
        color: "white",
        icon: "exclamation-triangle",
        title: "Warning",
      },
      info: {
        background: "#3b82f6",
        color: "white",
        icon: "info-circle",
        title: "Info",
      },
    };
    return configs[type] || configs.info;
  }

  remove(id) {
    const notification = this.notifications.find((n) => n.id === id);
    if (notification) {
      notification.element.style.transform = "translateX(400px)";
      notification.element.style.opacity = "0";

      setTimeout(() => {
        if (notification.element.parentNode) {
          notification.element.parentNode.removeChild(notification.element);
        }
        this.notifications = this.notifications.filter((n) => n.id !== id);
      }, CONFIG.ANIMATION_DURATION);
    }
  }

  clear() {
    this.notifications.forEach((notification) => {
      this.remove(notification.id);
    });
  }
}

// Create global notification manager
const notificationManager = new NotificationManager();

// ==========================================
// FIXED SIZE SELECTION SYSTEM
// ==========================================

class SizeSelector {
  constructor() {
    this.selectedSizes = new Map();
    this.sizeData = new Map();
    this.init();
  }

  init() {
    this.bindEvents();
    this.loadSizeData();
  }

  bindEvents() {
    // Use event delegation for better performance
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("size-option-uniqlo")) {
        e.preventDefault();
        e.stopPropagation();
        this.handleSizeSelection(e.target);
      }
    });
  }

  async loadSizeData() {
    const productCards = document.querySelectorAll("[data-product-id]");
    logger.info(`Loading size data for ${productCards.length} products`);

    for (const card of productCards) {
      const productId = parseInt(card.dataset.productId);
      if (productId && card.dataset.hasSizes === "true") {
        try {
          const response = await apiRequest(`${CONFIG.API_ENDPOINTS.GET_PRODUCT_SIZES}&product_id=${productId}`);
          if (response.sizes) {
            this.sizeData.set(productId, response.sizes);
            logger.debug(`Loaded ${response.sizes.length} sizes for product ${productId}`);
          }
        } catch (error) {
          logger.error(`Failed to load sizes for product ${productId}:`, error);
        }
      }
    }
  }

  handleSizeSelection(sizeElement) {
    const productId = parseInt(sizeElement.dataset.productId);
    const sizeCode = sizeElement.dataset.sizeCode;
    const sizeName = sizeElement.dataset.sizeName;
    const stock = parseInt(sizeElement.dataset.stock);

    logger.debug(`Size selection: Product ${productId}, Size ${sizeCode}, Stock ${stock}`);

    // Validate selection
    if (stock <= 0) {
      notificationManager.show("This size is out of stock", "warning");
      return;
    }

    const productCard = sizeElement.closest("[data-product-id]");
    if (!productCard) {
      logger.error("Product card not found");
      return;
    }

    // Clear previous selections for this product
    this.clearProductSizeSelection(productCard);

    // Set new selection
    this.setSelectedSize(productId, {
      code: sizeCode,
      name: sizeName,
      stock: stock,
      element: sizeElement,
    });

    // Update UI
    this.updateSizeSelectionUI(sizeElement, productCard);

    // Enable add to cart button
    this.enableAddToCartButton(productCard);

    logger.success(`Size ${sizeCode} selected for product ${productId}`);
  }

  clearProductSizeSelection(productCard) {
    const sizeOptions = productCard.querySelectorAll(".size-option-uniqlo");
    sizeOptions.forEach((option) => {
      option.classList.remove("selected");
      this.resetSizeOptionStyle(option);
    });
  }

  setSelectedSize(productId, sizeData) {
    this.selectedSizes.set(productId, sizeData);
    UrbanStitchState.selectedSizes.set(productId, sizeData.code);
  }

  updateSizeSelectionUI(selectedElement, productCard) {
    // Style selected element
    selectedElement.classList.add("selected");
    this.applySizeSelectedStyle(selectedElement);

    // Update size display
    const sizeDisplay = productCard.querySelector(".selected-size-display");
    if (sizeDisplay) {
      sizeDisplay.textContent = `Selected: ${selectedElement.dataset.sizeName}`;
      sizeDisplay.style.color = "#00cc00";
      sizeDisplay.style.fontWeight = "600";
    }

    // Hide warnings
    const warning = productCard.querySelector(".size-required-warning");
    if (warning) {
      warning.style.display = "none";
    }

    // Remove error styling
    productCard.classList.remove("size-required");
    const sizeSelector = productCard.querySelector(".size-selector-uniqlo");
    if (sizeSelector) {
      sizeSelector.style.borderColor = "#e0e0e0";
      sizeSelector.style.background = "#fafafa";
    }
  }

  applySizeSelectedStyle(element) {
    element.style.cssText += `
      border-color: #00ff00 !important;
      background: #f0fff0 !important;
      color: #00cc00 !important;
      font-weight: 700 !important;
      transform: scale(1.05) !important;
      box-shadow: 0 4px 16px rgba(0, 255, 0, 0.3) !important;
    `;
  }

  resetSizeOptionStyle(element) {
    element.style.cssText = element.style.cssText.replace(
      /border-color: #00ff00 !important;|background: #f0fff0 !important;|color: #00cc00 !important;|font-weight: 700 !important;|transform: scale\(1\.05\) !important;|box-shadow: 0 4px 16px rgba\(0, 255, 0, 0\.3\) !important;/g,
      "",
    );
  }

  enableAddToCartButton(productCard) {
    const addToCartBtn = productCard.querySelector(".add-to-cart-btn-uniqlo");
    if (addToCartBtn) {
      addToCartBtn.disabled = false;
      addToCartBtn.style.opacity = "1";
      addToCartBtn.style.cursor = "pointer";
      addToCartBtn.classList.remove("size-required");
    }
  }

  getSelectedSize(productId) {
    return this.selectedSizes.get(productId);
  }

  // FIXED: More flexible size validation
  validateSizeSelection(productId, productCard) {
    const hasSizes = productCard.dataset.hasSizes === "true";
    
    logger.debug(`Size validation for product ${productId}: hasSizes=${hasSizes}`);

    // If product doesn't require sizes, always valid
    if (!hasSizes) {
      logger.debug(`Product ${productId} doesn't require size selection`);
      return { valid: true };
    }

    // Check if there are actually size options visible
    const sizeOptions = productCard.querySelectorAll(".size-option-uniqlo");
    if (sizeOptions.length === 0) {
      logger.debug(`Product ${productId} has no size options available`);
      return { valid: true };
    }

    const selectedSize = this.getSelectedSize(productId);

    if (!selectedSize) {
    // Check if accessories or single size - auto-allow these
    const isAccessories = window.location.href.includes('category=accessories');
    const hasOnlyOneSize = sizeOptions.length === 1;
    
    if (isAccessories || hasOnlyOneSize) {
        logger.debug(`✅ Auto-allowing product ${productId}`);
        return { valid: true };
    }
    
    // Show warning only for multi-size non-accessories
    this.showSizeRequiredWarning(productCard);
    logger.warn(`⚠️ Product ${productId} requires size selection`);
    return {
        valid: false,
        message: "Please select a size before adding to cart",
    };
}

    if (selectedSize.stock <= 0) {
      return {
        valid: false,
        message: "Selected size is out of stock",
      };
    }

    return { valid: true, size: selectedSize };
  }

  showSizeRequiredWarning(productCard) {
    const warning = productCard.querySelector(".size-required-warning");
    if (warning) {
      warning.style.display = "block";
      warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please select a size before adding to cart';
    }

    // Highlight size selector
    const sizeSelector = productCard.querySelector(".size-selector-uniqlo");
    if (sizeSelector) {
      sizeSelector.style.borderColor = "#ff6b35";
      sizeSelector.style.background = "#fff3e0";
      sizeSelector.classList.add("required");

      // Remove highlight after 3 seconds
      setTimeout(() => {
        sizeSelector.style.borderColor = "#e0e0e0";
        sizeSelector.style.background = "#fafafa";
        sizeSelector.classList.remove("required");
      }, 3000);
    }
  }

  resetProductSelection(productId) {
    const productCard = document.querySelector(`[data-product-id="${productId}"]`);
    if (productCard) {
      this.clearProductSizeSelection(productCard);
      this.selectedSizes.delete(productId);
      UrbanStitchState.selectedSizes.delete(productId);

      // Reset size display
      const sizeDisplay = productCard.querySelector(".selected-size-display");
      if (sizeDisplay) {
        sizeDisplay.textContent = "";
        sizeDisplay.style.color = "#666";
        sizeDisplay.style.fontWeight = "normal";
      }

      // Hide warnings
      const warning = productCard.querySelector(".size-required-warning");
      if (warning) {
        warning.style.display = "none";
      }
    }
  }
}

// Create global size selector
const sizeSelector = new SizeSelector();

// ==========================================
// FIXED CART MANAGEMENT SYSTEM
// ==========================================

class CartManager {
  constructor() {
    this.items = new Map();
    this.isLoading = false;
    this.init();
  }

  init() {
    this.bindEvents();
    this.loadCartData();
  }

  bindEvents() {
    // FIXED: Enhanced event delegation for add to cart buttons
    document.addEventListener("click", (e) => {
      // Check for direct click on add to cart button or its children
      const addToCartBtn = e.target.closest(".add-to-cart-btn-uniqlo");
      
      if (addToCartBtn) {
        e.preventDefault();
        e.stopPropagation();

        const productId = parseInt(addToCartBtn.dataset.productId);
        
        logger.debug("Add to cart button clicked", { 
          productId, 
          button: addToCartBtn,
          target: e.target,
          hasDataset: !!addToCartBtn.dataset.productId
        });
        
        if (productId && productId > 0) {
          this.addToCart(productId, addToCartBtn);
        } else {
          logger.error("Invalid product ID", { productId, button: addToCartBtn });
          notificationManager.show("Invalid product selection", "error");
        }
      }
    });

    // Quantity update buttons
    document.addEventListener("click", (e) => {
      if (e.target.closest(".quantity-btn")) {
        e.preventDefault();
        e.stopPropagation();
        this.handleQuantityUpdate(e.target);
      }
    });

    // Remove buttons
    document.addEventListener("click", (e) => {
      if (e.target.closest('[onclick*="removeFromCartWithSize"]')) {
        e.preventDefault();
        e.stopPropagation();
        // Extract parameters from onclick attribute
        const onclick = e.target.closest('[onclick*="removeFromCartWithSize"]').getAttribute("onclick");
        const matches = onclick.match(/removeFromCartWithSize\((\d+),\s*'([^']*)'\)/);
        if (matches) {
          const productId = parseInt(matches[1]);
          const selectedSize = matches[2];
          this.removeFromCart(productId, selectedSize);
        }
      }
    });
  }

  async loadCartData() {
    try {
      const response = await apiRequest(CONFIG.API_ENDPOINTS.GET_CART_COUNT);
      if (response && response.count !== undefined) {
        this.updateCartBadge(response.count);
      }
    } catch (error) {
      logger.error("Failed to load cart data:", error);
    }
  }

  // FIXED: Enhanced addToCart method with better validation and error handling
  async addToCart(productId, buttonElement = null) {
    if (this.isLoading) {
      logger.warn("Cart operation already in progress");
      return;
    }

    logger.info(`🛒 Adding product ${productId} to cart`);

    // Validate inputs
    if (!productId || productId <= 0) {
      notificationManager.show("Invalid product ID", "error");
      return;
    }

    // Find product card - try multiple selectors
    let productCard = document.querySelector(`[data-product-id="${productId}"]`);
    if (!productCard && buttonElement) {
      productCard = buttonElement.closest('[data-product-id]');
    }
    
    if (!productCard) {
      logger.error("Product card not found", { productId, buttonElement });
      notificationManager.show("Product not found", "error");
      return;
    }

    logger.debug("Product card found", { productId, hasSizes: productCard.dataset.hasSizes });

    // FIXED: More robust size validation
    const sizeValidation = this.validateSizeRequirement(productId, productCard);
    if (!sizeValidation.valid) {
      notificationManager.show(sizeValidation.message, "warning");
      return;
    }

    // Show loading state
    this.setButtonLoading(buttonElement, true);
    this.isLoading = true;

    try {
      const requestData = {
        action: "add_to_cart",
        product_id: productId,
        quantity: 1,
      };

      // Add size if selected and validated
      if (sizeValidation.size) {
        requestData.selected_size = sizeValidation.size.code;
        logger.debug(`Adding with size: ${sizeValidation.size.code}`);
      } else {
        logger.debug("Adding without size requirement");
      }

      logger.debug("Sending add to cart request", requestData);

      const response = await apiRequest(CONFIG.API_ENDPOINTS.ADD_TO_CART, {
        method: "POST",
        body: createFormData(requestData),
      });

      logger.debug("Add to cart response", response);

      // Handle different response formats
      let parsedResponse = response;
      if (typeof response === 'string') {
        try {
          parsedResponse = JSON.parse(response);
        } catch (e) {
          logger.error("Failed to parse response as JSON", response);
          throw new Error("Invalid response format");
        }
      }

      if (parsedResponse && parsedResponse.success) {
        // Success feedback
        this.setButtonSuccess(buttonElement);
        notificationManager.show(parsedResponse.message || "Product added to cart!", "success");

        // Update cart count
        await this.updateCartCount();

        // Reset size selection if size was required
        if (sizeValidation.size) {
          sizeSelector.resetProductSelection(productId);
        }

        logger.success(`✅ Product ${productId} added to cart successfully`);
      } else {
        // Handle failure
        if (parsedResponse && parsedResponse.redirect) {
          logger.info("Redirecting to login");
          window.location.href = parsedResponse.redirect;
        } else {
          const errorMessage = (parsedResponse && parsedResponse.message) || "Failed to add to cart";
          notificationManager.show(errorMessage, "error");
          logger.error("Add to cart failed", parsedResponse);
        }
      }
    } catch (error) {
      logger.error("Add to cart error:", error);
      notificationManager.show("Network error occurred. Please try again.", "error");
      
      errorHandler.handleError({
        type: 'cart_error',
        message: `Failed to add product ${productId} to cart: ${error.message}`,
        userMessage: 'Failed to add product to cart. Please try again.',
        error,
        timestamp: Date.now()
      });
    } finally {
      this.isLoading = false;
      this.setButtonLoading(buttonElement, false);
    }
  }

  // FIXED: Enhanced size requirement validation
  validateSizeRequirement(productId, productCard) {
    const hasSizes = productCard.dataset.hasSizes === "true";
    
    logger.debug(`🔍 Validating size requirement for product ${productId}: hasSizes=${hasSizes}`);

    // If product doesn't require sizes, always valid
    if (!hasSizes) {
      logger.debug(`✅ Product ${productId} doesn't require size selection`);
      return { valid: true };
    }

    // Check if there are actually size options available
    const sizeSelector = productCard.querySelector(".size-selector-uniqlo");
    const sizeOptions = productCard.querySelectorAll(".size-option-uniqlo");
    
    if (!sizeSelector || sizeOptions.length === 0) {
      logger.debug(`✅ Product ${productId} has no size options available, treating as no-size product`);
      return { valid: true };
    }

    // Check if a size has been selected
    const selectedSize = this.getSelectedSizeForProduct(productId);

    if (!selectedSize) {
      this.showSizeRequiredWarning(productCard);
      logger.warn(`⚠️ Product ${productId} requires size selection but none selected`);
      return {
        valid: false,
        message: "Please select a size before adding to cart",
      };
    }

    if (selectedSize.stock <= 0) {
      logger.warn(`⚠️ Selected size for product ${productId} is out of stock`);
      return {
        valid: false,
        message: "Selected size is out of stock",
      };
    }

    logger.debug(`✅ Size validation passed for product ${productId} with size ${selectedSize.code}`);
    return { valid: true, size: selectedSize };
  }

  getSelectedSizeForProduct(productId) {
    return sizeSelector.getSelectedSize(productId);
  }

  showSizeRequiredWarning(productCard) {
    const warning = productCard.querySelector(".size-required-warning");
    if (warning) {
      warning.style.display = "block";
      warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please select a size before adding to cart';
    }

    // Highlight size selector
    const sizeSelector = productCard.querySelector(".size-selector-uniqlo");
    if (sizeSelector) {
      sizeSelector.style.borderColor = "#ff6b35";
      sizeSelector.style.background = "#fff3e0";
      sizeSelector.classList.add("required");

      // Remove highlight after 3 seconds
      setTimeout(() => {
        sizeSelector.style.borderColor = "#e0e0e0";
        sizeSelector.style.background = "#fafafa";
        sizeSelector.classList.remove("required");
      }, 3000);
    }
  }

  async removeFromCart(productId, selectedSize = "") {
    logger.info(`Removing product ${productId} from cart, size: ${selectedSize}`);

    try {
      const requestData = {
        action: "remove_from_cart",
        product_id: productId,
      };

      if (selectedSize) {
        requestData.selected_size = selectedSize;
      }

      const response = await apiRequest(CONFIG.API_ENDPOINTS.REMOVE_FROM_CART, {
        method: "POST",
        body: createFormData(requestData),
      });

      if (response.success) {
        notificationManager.show("Item removed from cart", "success");
        this.updateCartCount();
        this.updateCartTotal();
        this.removeCartItemFromDOM(productId, selectedSize);
      } else {
        notificationManager.show("Failed to remove item", "error");
      }
    } catch (error) {
      logger.error("Remove from cart error:", error);
      notificationManager.show("Network error occurred", "error");
    }
  }

  async updateQuantity(cartItemId, newQuantity) {
    logger.info(`Updating cart item ${cartItemId} quantity to ${newQuantity}`);

    if (newQuantity < 0) {
      return;
    }

    try {
      const requestData = {
        action: "update_quantity",
        cart_item_id: cartItemId,
        quantity: newQuantity,
      };

      const response = await apiRequest(CONFIG.API_ENDPOINTS.UPDATE_QUANTITY, {
        method: "POST",
        body: createFormData(requestData),
      });

      if (response.success) {
        if (newQuantity <= 0) {
          // Item was removed
          const cartItem = document.querySelector(`[data-cart-item-id="${cartItemId}"]`);
          if (cartItem) {
            cartItem.remove();
            this.checkAndShowEmptyCart();
          }
        } else {
          // Update quantity display
          const quantityDisplay = document.querySelector(`[data-cart-item-id="${cartItemId}"] .quantity-display`);
          if (quantityDisplay) {
            quantityDisplay.textContent = newQuantity;
          }
        }

        this.updateCartCount();
        this.updateCartTotal();
        notificationManager.show(response.message, "success");
      } else {
        notificationManager.show(response.message || "Failed to update quantity", "error");
      }
    } catch (error) {
      logger.error("Update quantity error:", error);
      notificationManager.show("Network error occurred", "error");
    }
  }

  handleQuantityUpdate(element) {
    const cartItem = element.closest("[data-cart-item-id]");
    if (!cartItem) return;

    const cartItemId = parseInt(cartItem.dataset.cartItemId);
    const quantityDisplay = cartItem.querySelector(".quantity-display");
    const currentQuantity = parseInt(quantityDisplay.textContent);

    let newQuantity;
    if (element.querySelector(".fa-plus") || element.classList.contains("fa-plus")) {
      newQuantity = Math.min(currentQuantity + 1, CONFIG.MAX_QUANTITY);
    } else if (element.querySelector(".fa-minus") || element.classList.contains("fa-minus")) {
      newQuantity = Math.max(currentQuantity - 1, 0);
    } else {
      return;
    }

    this.updateQuantity(cartItemId, newQuantity);
  }

  setButtonLoading(button, isLoading) {
    if (!button) return;

    if (isLoading) {
      button.dataset.originalText = button.innerHTML;
      button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
      button.disabled = true;
      button.style.opacity = "0.7";
      button.style.pointerEvents = "none";
    } else {
      if (button.dataset.originalText) {
        button.innerHTML = button.dataset.originalText;
        delete button.dataset.originalText;
      }
      button.disabled = false;
      button.style.opacity = "1";
      button.style.pointerEvents = "auto";
    }
  }

  setButtonSuccess(button) {
    if (!button) return;

    const originalText = button.dataset.originalText || button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i> Added!';
    button.style.background = "#4CAF50";
    button.style.color = "white";

    setTimeout(() => {
      button.innerHTML = originalText;
      button.style.background = "";
      button.style.color = "";
      button.disabled = false;
      button.style.opacity = "1";
      button.style.pointerEvents = "auto";
    }, 2000);
  }

  async updateCartCount() {
    try {
      const response = await apiRequest(CONFIG.API_ENDPOINTS.GET_CART_COUNT);
      if (response && response.count !== undefined) {
        this.updateCartBadge(response.count);
      }
    } catch (error) {
      logger.error("Failed to update cart count:", error);
    }
  }

  async updateCartTotal() {
    try {
      const response = await apiRequest(CONFIG.API_ENDPOINTS.GET_CART_TOTAL);
      if (response && response.total !== undefined) {
        const totalElement = document.querySelector(".total-amount");
        if (totalElement) {
          totalElement.textContent = formatCurrency(response.total);
        }
      }
    } catch (error) {
      logger.error("Failed to update cart total:", error);
    }
  }

  updateCartBadge(count) {
    const cartCountElement = document.getElementById("cartCount");
    const cartItemCountElement = document.getElementById("cartItemCount");

    if (cartCountElement) {
      cartCountElement.textContent = count;
    }

    if (cartItemCountElement) {
      cartItemCountElement.textContent = count;
    }

    logger.debug(`Cart badge updated to ${count}`);
  }

  removeCartItemFromDOM(productId, selectedSize) {
    const cartItems = document.querySelectorAll(".sidebar-item");
    cartItems.forEach((item) => {
      const removeButton = item.querySelector('[onclick*="removeFromCartWithSize"]');
      if (removeButton) {
        const onclick = removeButton.getAttribute("onclick");
        if (onclick.includes(productId.toString()) && (selectedSize === "" || onclick.includes(selectedSize))) {
          item.remove();
        }
      }
    });

    this.checkAndShowEmptyCart();
  }

  checkAndShowEmptyCart() {
    const cartItems = document.querySelector("#cartItems");
    if (!cartItems) return;

    const remainingItems = cartItems.querySelectorAll(".sidebar-item");

    if (remainingItems.length === 0) {
      const footer = document.querySelector("#cartSidebar .sidebar-footer");
      if (footer) {
        footer.style.display = "none";
      }

      cartItems.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-shopping-cart"></i>
          </div>
          <h4>Your cart is empty</h4>
          <p>Discover amazing products and start your shopping journey</p>
          <a href="index.php" class="shop-link">
            <i class="fas fa-arrow-left"></i>
            Continue Shopping
          </a>
        </div>
      `;
    }
  }
}

// Create global cart manager
const cartManager = new CartManager();

// ==========================================
// ENHANCED WISHLIST MANAGEMENT SYSTEM
// ==========================================

class WishlistManager {
  constructor() {
    this.items = new Set();
    this.init();
  }

  init() {
    this.bindEvents();
    this.loadWishlistData();
  }

  bindEvents() {
    // Wishlist buttons
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("wishlist-btn-uniqlo") || e.target.closest(".wishlist-btn-uniqlo")) {
        e.preventDefault();
        e.stopPropagation();

        const button = e.target.classList.contains("wishlist-btn-uniqlo")
          ? e.target
          : e.target.closest(".wishlist-btn-uniqlo");

        const productId = parseInt(button.dataset.productId);
        this.addToWishlist(productId, button);
      }
    });

    // Remove from wishlist buttons
    document.addEventListener("click", (e) => {
      if (e.target.closest('[onclick*="removeFromWishlist"]')) {
        e.preventDefault();
        e.stopPropagation();

        const onclick = e.target.closest('[onclick*="removeFromWishlist"]').getAttribute("onclick");
        const matches = onclick.match(/removeFromWishlist\((\d+)\)/);
        if (matches) {
          const productId = parseInt(matches[1]);
          this.removeFromWishlist(productId);
        }
      }
    });
  }

  async loadWishlistData() {
    try {
      const response = await apiRequest(CONFIG.API_ENDPOINTS.GET_WISHLIST_COUNT);
      if (response && response.count !== undefined) {
        this.updateWishlistBadge(response.count);
      }
    } catch (error) {
      logger.error("Failed to load wishlist data:", error);
    }
  }

  async addToWishlist(productId, buttonElement = null) {
    logger.info(`Adding product ${productId} to wishlist`);

    // Show loading state
    this.setWishlistButtonLoading(buttonElement, true);

    try {
      const response = await apiRequest(CONFIG.API_ENDPOINTS.ADD_TO_WISHLIST, {
        method: "POST",
        body: createFormData({
          action: "add_to_wishlist",
          product_id: productId,
        }),
      });

      if (response.success) {
        this.setWishlistButtonSuccess(buttonElement);
        notificationManager.show("Product added to wishlist!", "success");
        this.updateWishlistCount();
      } else {
        if (response.redirect) {
          window.location.href = response.redirect;
        } else {
          notificationManager.show(response.message || "Failed to add to wishlist", "error");
        }
      }
    } catch (error) {
      logger.error("Add to wishlist error:", error);
      notificationManager.show("Network error occurred", "error");
    } finally {
      this.setWishlistButtonLoading(buttonElement, false);
    }
  }

  async removeFromWishlist(productId) {
    logger.info(`Removing product ${productId} from wishlist`);

    try {
      const response = await apiRequest(CONFIG.API_ENDPOINTS.REMOVE_FROM_WISHLIST, {
        method: "POST",
        body: createFormData({
          action: "remove_from_wishlist",
          product_id: productId,
        }),
      });

      if (response.success) {
        notificationManager.show("Item removed from wishlist", "success");
        this.updateWishlistCount();
        this.removeWishlistItemFromDOM(productId);
      } else {
        notificationManager.show("Failed to remove item", "error");
      }
    } catch (error) {
      logger.error("Remove from wishlist error:", error);
      notificationManager.show("Network error occurred", "error");
    }
  }

  setWishlistButtonLoading(button, isLoading) {
    if (!button) return;

    if (isLoading) {
      button.dataset.originalHTML = button.innerHTML;
      button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
      button.disabled = true;
    } else {
      if (button.dataset.originalHTML) {
        button.innerHTML = button.dataset.originalHTML;
        delete button.dataset.originalHTML;
      }
      button.disabled = false;
    }
  }

  setWishlistButtonSuccess(button) {
    if (!button) return;

    button.innerHTML = '<i class="fas fa-heart" style="color: #ff6b35;"></i>';
    button.style.background = "#ff6b35";
    button.style.color = "white";
    button.style.transform = "scale(1.1)";

    setTimeout(() => {
      button.style.transform = "scale(1)";
    }, 200);
  }

  async updateWishlistCount() {
    try {
      const response = await apiRequest(CONFIG.API_ENDPOINTS.GET_WISHLIST_COUNT);
      if (response && response.count !== undefined) {
        this.updateWishlistBadge(response.count);
      }
    } catch (error) {
      logger.error("Failed to update wishlist count:", error);
    }
  }

  updateWishlistBadge(count) {
    const wishlistCountElement = document.getElementById("wishlistCount");
    const wishlistItemCountElement = document.getElementById("wishlistItemCount");

    if (wishlistCountElement) {
      wishlistCountElement.textContent = count;
    }

    if (wishlistItemCountElement) {
      wishlistItemCountElement.textContent = count;
    }
  }

  removeWishlistItemFromDOM(productId) {
    const wishlistItems = document.querySelectorAll("#wishlistSidebar .sidebar-item");
    wishlistItems.forEach((item) => {
      const removeButton = item.querySelector('[onclick*="removeFromWishlist"]');
      if (removeButton) {
        const onclick = removeButton.getAttribute("onclick");
        if (onclick.includes(productId.toString())) {
          item.remove();
        }
      }
    });

    this.checkAndShowEmptyWishlist();
  }

  checkAndShowEmptyWishlist() {
    const wishlistContainer = document.querySelector("#wishlistSidebar .items-container");
    if (!wishlistContainer) return;

    const remainingItems = wishlistContainer.querySelectorAll(".sidebar-item");

    if (remainingItems.length === 0) {
      wishlistContainer.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-heart"></i>
          </div>
          <h4>Your wishlist is empty</h4>
          <p>Save your favorite items for later and never miss out</p>
          <a href="index.php" class="shop-link">
            <i class="fas fa-heart"></i>
            Discover Products
          </a>
        </div>
      `;
    }
  }
}

// Create global wishlist manager
const wishlistManager = new WishlistManager();

// ==========================================
// FIXED SIDEBAR MANAGEMENT SYSTEM
// ==========================================

class SidebarManager {
  constructor() {
    this.activeSidebar = null;
    this.overlay = null;
    this.init();
  }

  init() {
    this.overlay = document.getElementById("overlay");
    this.createOverlayIfNeeded();
    // Delay binding to ensure DOM is ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.bindEvents());
    } else {
      this.bindEvents();
    }
  }

  createOverlayIfNeeded() {
    if (!this.overlay) {
      this.overlay = document.createElement("div");
      this.overlay.id = "overlay";
      this.overlay.className = "overlay";
      this.overlay.addEventListener("click", () => this.closeAll());
      document.body.appendChild(this.overlay);
    }
  }

  bindEvents() {
    logger.debug("🔧 Binding sidebar events...");

    // FIXED: Multiple selector approach for cart button
    const cartSelectors = ['.cart-btn', '.action-btn:has(.fa-shopping-bag)', 'button:has(.fa-shopping-bag)', '[class*="cart"]'];
    let cartBtn = null;
    
    for (const selector of cartSelectors) {
      cartBtn = document.querySelector(selector);
      if (cartBtn) {
        logger.debug(`✅ Cart button found with selector: ${selector}`);
        break;
      }
    }

    if (cartBtn) {
      cartBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        logger.debug("🛒 Cart button clicked");
        this.toggle("cart");
      });
    } else {
      logger.warn("⚠️ Cart button not found, checking all action buttons...");
      // Fallback: check all buttons with shopping icons
      document.querySelectorAll('button, .action-btn').forEach(btn => {
        if (btn.innerHTML.includes('fa-shopping-bag') || btn.innerHTML.includes('fa-shopping-cart')) {
          logger.debug("🛒 Found cart button via icon search");
          btn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            logger.debug("🛒 Cart button clicked (fallback)");
            this.toggle("cart");
          });
        }
      });
    }

    // FIXED: Multiple selector approach for wishlist button
    const wishlistSelectors = ['.wishlist-btn', '.action-btn:has(.fa-heart)', 'button:has(.fa-heart)', '[class*="wishlist"]'];
    let wishlistBtn = null;
    
    for (const selector of wishlistSelectors) {
      wishlistBtn = document.querySelector(selector);
      if (wishlistBtn) {
        logger.debug(`✅ Wishlist button found with selector: ${selector}`);
        break;
      }
    }

    if (wishlistBtn) {
      wishlistBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        logger.debug("❤️ Wishlist button clicked");
        this.toggle("wishlist");
      });
    } else {
      logger.warn("⚠️ Wishlist button not found, checking all action buttons...");
      // Fallback: check all buttons with heart icons
      document.querySelectorAll('button, .action-btn').forEach(btn => {
        if (btn.innerHTML.includes('fa-heart')) {
          logger.debug("❤️ Found wishlist button via icon search");
          btn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            logger.debug("❤️ Wishlist button clicked (fallback)");
            this.toggle("wishlist");
          });
        }
      });
    }

    // FIXED: Multiple selector approach for user menu button
    const userSelectors = ['.user-menu-btn', '.user-menu button', '.action-btn:has(.fa-user)', 'button:has(.fa-user)', '[class*="user"]'];
    let userMenuBtn = null;
    
    for (const selector of userSelectors) {
      userMenuBtn = document.querySelector(selector);
      if (userMenuBtn) {
        logger.debug(`✅ User menu button found with selector: ${selector}`);
        break;
      }
    }

    if (userMenuBtn) {
      userMenuBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        logger.debug("👤 User menu button clicked");
        this.toggleUserMenu();
      });
    } else {
      logger.warn("⚠️ User menu button not found, checking all action buttons...");
      // Fallback: check all buttons with user icons
      document.querySelectorAll('button, .action-btn').forEach(btn => {
        if (btn.innerHTML.includes('fa-user')) {
          logger.debug("👤 Found user button via icon search");
          btn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            logger.debug("👤 User button clicked (fallback)");
            this.toggleUserMenu();
          });
        }
      });
    }

    // Close buttons
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("close-btn") || e.target.closest(".close-btn")) {
        e.preventDefault();
        e.stopPropagation();
        this.closeAll();
      }
    });

    // Overlay click
    if (this.overlay) {
      this.overlay.addEventListener("click", () => this.closeAll());
    }

    // Escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        this.closeAll();
      }
    });

    // Click outside user menu
    document.addEventListener("click", (e) => {
      const userMenu = document.querySelector(".user-menu");
      const dropdown = document.getElementById("userDropdown");

      if (userMenu && dropdown && !userMenu.contains(e.target)) {
        dropdown.style.display = "none";
        UrbanStitchState.sidebarStates.user = false;
      }
    });

    logger.success("✅ Sidebar events bound successfully");
  }

  toggle(type) {
    logger.debug(`🔄 Toggling ${type} sidebar`);
    
    const sidebarId = type === "cart" ? "cartSidebar" : "wishlistSidebar";
    const sidebar = document.getElementById(sidebarId);

    if (!sidebar) {
      logger.error(`❌ Sidebar ${sidebarId} not found`);
      // Try to find sidebar by class name as fallback
      const fallbackSidebar = document.querySelector(`.${type}-sidebar, .enhanced-sidebar`);
      if (fallbackSidebar) {
        logger.debug(`✅ Found fallback sidebar for ${type}`);
        fallbackSidebar.id = sidebarId; // Set proper ID
        this.performToggle(type, fallbackSidebar);
      }
      return;
    }

    this.performToggle(type, sidebar);
  }

  performToggle(type, sidebar) {
    // Close other sidebars first
    this.closeOtherSidebars(type);

    const isActive = sidebar.classList.contains("active");

    if (isActive) {
      this.close(type);
    } else {
      this.open(type);
    }
  }

  open(type) {
    logger.debug(`📂 Opening ${type} sidebar`);
    
    const sidebarId = type === "cart" ? "cartSidebar" : "wishlistSidebar";
    const sidebar = document.getElementById(sidebarId);

    if (sidebar) {
      sidebar.classList.add("active");
      this.activeSidebar = type;
      UrbanStitchState.sidebarStates[type] = true;

      if (this.overlay) {
        this.overlay.classList.add("active");
      }

      document.body.style.overflow = "hidden";

      logger.success(`✅ ${type} sidebar opened`);
    }
  }

  close(type) {
    logger.debug(`📁 Closing ${type} sidebar`);
    
    const sidebarId = type === "cart" ? "cartSidebar" : "wishlistSidebar";
    const sidebar = document.getElementById(sidebarId);

    if (sidebar) {
      sidebar.classList.remove("active");
      UrbanStitchState.sidebarStates[type] = false;

      if (this.activeSidebar === type) {
        this.activeSidebar = null;
      }

      // Check if any sidebar is still open
      const hasActiveSidebar = Object.values(UrbanStitchState.sidebarStates).some((state) => state);

      if (!hasActiveSidebar) {
        if (this.overlay) {
          this.overlay.classList.remove("active");
        }
        document.body.style.overflow = "";
      }

      logger.success(`✅ ${type} sidebar closed`);
    }
  }

  closeOtherSidebars(exceptType) {
    const types = ["cart", "wishlist"];
    types.forEach((type) => {
      if (type !== exceptType && UrbanStitchState.sidebarStates[type]) {
        this.close(type);
      }
    });
  }

  closeAll() {
    logger.debug("🚪 Closing all sidebars");
    
    ["cart", "wishlist"].forEach((type) => {
      if (UrbanStitchState.sidebarStates[type]) {
        this.close(type);
      }
    });

    // Close user menu
    const dropdown = document.getElementById("userDropdown");
    if (dropdown) {
      dropdown.style.display = "none";
      UrbanStitchState.sidebarStates.user = false;
    }
  }

  toggleUserMenu() {
    logger.debug("👤 Toggling user menu");
    
    const dropdown = document.getElementById("userDropdown");
    if (dropdown) {
      const isVisible = dropdown.style.display === "block";
      dropdown.style.display = isVisible ? "none" : "block";
      UrbanStitchState.sidebarStates.user = !isVisible;
      logger.debug(`👤 User menu ${isVisible ? 'closed' : 'opened'}`);
    } else {
      logger.warn("⚠️ User dropdown not found");
      // Fallback: look for any dropdown
      const fallbackDropdown = document.querySelector(".user-dropdown, .dropdown-menu");
      if (fallbackDropdown) {
        logger.debug("✅ Found fallback user dropdown");
        fallbackDropdown.id = "userDropdown";
        const isVisible = fallbackDropdown.style.display === "block";
        fallbackDropdown.style.display = isVisible ? "none" : "block";
        UrbanStitchState.sidebarStates.user = !isVisible;
      }
    }
  }
}

// Create global sidebar manager
const sidebarManager = new SidebarManager();

// ==========================================
// LEGACY FUNCTIONS FOR BACKWARD COMPATIBILITY
// ==========================================

// Legacy function names for backward compatibility
function selectSizeUniqlo(sizeElement, productId) {
  return sizeSelector.handleSizeSelection(sizeElement);
}

function handleCartAddUniqlo(productId) {
  return cartManager.addToCart(productId);
}

function handleWishlistAddUniqlo(productId) {
  return wishlistManager.addToWishlist(productId);
}

function updateCartQuantity(cartItemId, newQuantity) {
  return cartManager.updateQuantity(cartItemId, newQuantity);
}

function removeFromCartWithSize(productId, selectedSize) {
  return cartManager.removeFromCart(productId, selectedSize);
}

function removeFromWishlist(productId) {
  return wishlistManager.removeFromWishlist(productId);
}

function toggleCart() {
  return sidebarManager.toggle("cart");
}

function toggleWishlist() {
  return sidebarManager.toggle("wishlist");
}

function closeSidebars() {
  return sidebarManager.closeAll();
}

function toggleUserMenu() {
  return sidebarManager.toggleUserMenu();
}

function showNotification(message, type = "success") {
  return notificationManager.show(message, type);
}

// Legacy aliases
function handleCartAdd(productId) {
  return handleCartAddUniqlo(productId);
}

function handleWishlistAdd(productId) {
  return handleWishlistAddUniqlo(productId);
}

function addToCart(productId) {
  return handleCartAddUniqlo(productId);
}

function addToWishlist(productId) {
  return handleWishlistAddUniqlo(productId);
}

// ==========================================
// ENHANCED INITIALIZATION AND MAIN ENTRY POINT
// ==========================================

class UrbanStitchApp {
  constructor() {
    this.initialized = false;
    this.version = "2.1.2";
    this.init();
  }

  async init() {
    if (this.initialized) return;

    logger.info(`🛒 UrbanStitch Enhanced System v${this.version} - Initializing...`);

    try {
      // Wait for DOM to be ready
      if (document.readyState === "loading") {
        await new Promise((resolve) => {
          document.addEventListener("DOMContentLoaded", resolve);
        });
      }

      // Initialize all systems
      await this.initializeSystems();

      // Setup global event listeners
      this.setupGlobalEvents();

      // Mark as initialized
      this.initialized = true;

      logger.success("✅ All systems initialized successfully!");

      // Run diagnostics
      this.runDiagnostics();

    } catch (error) {
      logger.error("Failed to initialize UrbanStitch app:", error);
      errorHandler.handleError({
        type: 'initialization_error',
        message: error.message,
        stack: error.stack,
        timestamp: Date.now()
      });
    }
  }

  async initializeSystems() {
    // All managers are already initialized above
    // This method can be used for additional setup

    // Setup additional event listeners
    this.setupProductCardEvents();
    this.setupFormValidation();
    this.setupAnimations();

    // Load user preferences
    this.loadUserPreferences();

    // Initialize tooltips and popovers
    this.initializeTooltips();

    // Setup enhanced debugging
    this.setupDebugging();

    // FIXED: Setup enhanced UI button detection
    this.setupEnhancedUIButtons();
  }

  setupEnhancedUIButtons() {
    logger.debug("🔧 Setting up enhanced UI button detection...");

    // Ensure all add to cart buttons have proper data attributes
    document.querySelectorAll('.add-to-cart-btn-uniqlo').forEach(button => {
      const productCard = button.closest('[data-product-id]');
      if (productCard && !button.dataset.productId) {
        button.dataset.productId = productCard.dataset.productId;
        logger.debug(`Fixed missing productId on cart button for product ${productCard.dataset.productId}`);
      }
    });

    // Log all UI button status
    const uiButtons = {
      cart: document.querySelectorAll('.add-to-cart-btn-uniqlo, .cart-btn, button:has(.fa-shopping-bag), .action-btn:has(.fa-shopping-bag)'),
      wishlist: document.querySelectorAll('.wishlist-btn-uniqlo, .wishlist-btn, button:has(.fa-heart), .action-btn:has(.fa-heart)'),
      user: document.querySelectorAll('.user-menu-btn, .user-menu button, button:has(.fa-user), .action-btn:has(.fa-user)')
    };

    Object.entries(uiButtons).forEach(([type, buttons]) => {
      logger.info(`🎯 Found ${buttons.length} ${type} buttons on page`);
      
      buttons.forEach((button, index) => {
        const hasProductId = button.dataset.productId;
        const hasEventListener = button.onclick || button.getAttribute('onclick');
        logger.debug(`${type} button ${index + 1}: productId=${hasProductId}, hasHandler=${!!hasEventListener}`);
      });
    });

    // FIXED: Add fallback event listeners for buttons that might not be caught
    this.setupFallbackEventListeners();
  }

  setupFallbackEventListeners() {
    logger.debug("🛡️ Setting up fallback event listeners...");

    // Fallback for any button with shopping cart icon
    document.addEventListener('click', (e) => {
      const target = e.target.closest('button, .action-btn, [role="button"]');
      if (!target) return;

      const innerHTML = target.innerHTML || '';
      const className = target.className || '';

      // Cart button fallback
      if ((innerHTML.includes('fa-shopping-bag') || innerHTML.includes('fa-shopping-cart') || 
           className.includes('cart')) && !target.classList.contains('add-to-cart-btn-uniqlo')) {
        
        logger.debug("🛒 Fallback cart button triggered");
        e.preventDefault();
        e.stopPropagation();
        sidebarManager.toggle('cart');
        return;
      }

      // Wishlist button fallback (only for sidebar, not product wishlist buttons)
      if ((innerHTML.includes('fa-heart') && className.includes('wishlist')) || 
          (className.includes('wishlist') && !className.includes('btn-uniqlo'))) {
        
        logger.debug("❤️ Fallback wishlist button triggered");
        e.preventDefault();
        e.stopPropagation();
        sidebarManager.toggle('wishlist');
        return;
      }

      // User menu button fallback
      if ((innerHTML.includes('fa-user') && (className.includes('user') || className.includes('menu'))) ||
          (className.includes('user-menu'))) {
        
        logger.debug("👤 Fallback user menu button triggered");
        e.preventDefault();
        e.stopPropagation();
        sidebarManager.toggleUserMenu();
        return;
      }
    });

    logger.success("✅ Fallback event listeners set up");
  }

  setupGlobalEvents() {
    // Handle page visibility changes
    document.addEventListener("visibilitychange", () => {
      if (document.hidden) {
        logger.debug("Page hidden");
      } else {
        logger.debug("Page visible");
      }
    });

    // Handle before unload
    window.addEventListener("beforeunload", () => {
      logger.debug("Page unloading");
    });

    // Handle resize events
    window.addEventListener(
      "resize",
      debounce(() => {
        this.handleResize();
      }, 250),
    );
  }

  setupProductCardEvents() {
    // Product card hover effects
    document.querySelectorAll(".product-card, .enhanced-product-card").forEach((card) => {
      card.addEventListener("mouseenter", () => {
        const productId = card.dataset.productId;
        if (productId) {
          logger.debug(`Product ${productId} viewed`);
        }
      });
    });
  }

  setupFormValidation() {
    // Add real-time form validation
    document.querySelectorAll('input[type="email"]').forEach((input) => {
      input.addEventListener("blur", (e) => {
        const email = e.target.value;
        if (email && !isValidEmail(email)) {
          e.target.setCustomValidity("Please enter a valid email address");
          e.target.reportValidity();
        } else {
          e.target.setCustomValidity("");
        }
      });
    });
  }

  setupAnimations() {
    // Setup intersection observer for animations
    if ("IntersectionObserver" in window) {
      const animationObserver = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add("animate-in");
              animationObserver.unobserve(entry.target);
            }
          });
        },
        {
          threshold: 0.1,
          rootMargin: "50px",
        },
      );

      // Observe elements that should animate in
      document.querySelectorAll(".product-card, .category-card, .deal-content").forEach((el) => {
        animationObserver.observe(el);
      });
    }
  }

  loadUserPreferences() {
    try {
      const preferences = localStorage.getItem("urbanstitch_preferences");
      if (preferences) {
        const prefs = JSON.parse(preferences);
        this.applyUserPreferences(prefs);
      }
    } catch (error) {
      logger.error("Failed to load user preferences:", error);
    }
  }

  applyUserPreferences(preferences) {
    // Apply user preferences like theme, language, etc.
    if (preferences.theme) {
      document.body.classList.add(`theme-${preferences.theme}`);
    }

    if (preferences.reducedMotion) {
      document.body.classList.add("reduced-motion");
    }
  }

  initializeTooltips() {
    // Initialize tooltips for elements with title attributes
    document.querySelectorAll("[title]").forEach((element) => {
      element.addEventListener("mouseenter", (e) => {
        this.showTooltip(e.target, e.target.getAttribute("title"));
      });

      element.addEventListener("mouseleave", () => {
        this.hideTooltip();
      });
    });
  }

  setupDebugging() {
    // Add CSS for notification progress animation
    if (!document.getElementById('urbanstitch-styles')) {
      const style = document.createElement("style");
      style.id = 'urbanstitch-styles';
      style.textContent = `
        @keyframes notificationProgress {
          from { width: 100%; }
          to { width: 0%; }
        }
        
        .animate-in {
          animation: fadeInUp 0.6s ease-out forwards;
        }
        
        @keyframes fadeInUp {
          from {
            opacity: 0;
            transform: translateY(30px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
        
        .reduced-motion * {
          animation-duration: 0.01ms !important;
          animation-iteration-count: 1 !important;
          transition-duration: 0.01ms !important;
        }
      `;
      document.head.appendChild(style);
    }
  }

  showTooltip(element, text) {
    // Create and show tooltip
    const tooltip = document.createElement("div");
    tooltip.className = "custom-tooltip";
    tooltip.textContent = text;
    tooltip.style.cssText = `
      position: absolute;
      background: #333;
      color: white;
      padding: 8px 12px;
      border-radius: 4px;
      font-size: 12px;
      z-index: 10000;
      pointer-events: none;
      opacity: 0;
      transition: opacity 0.2s;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      white-space: nowrap;
      max-width: 200px;
      word-wrap: break-word;
      white-space: normal;
    `;

    document.body.appendChild(tooltip);

    // Position tooltip
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + "px";
    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + "px";

    // Show tooltip
    requestAnimationFrame(() => {
      tooltip.style.opacity = "1";
    });

    // Store reference for cleanup
    this.currentTooltip = tooltip;
  }

  hideTooltip() {
    if (this.currentTooltip) {
      this.currentTooltip.style.opacity = "0";
      setTimeout(() => {
        if (this.currentTooltip && this.currentTooltip.parentNode) {
          this.currentTooltip.parentNode.removeChild(this.currentTooltip);
        }
        this.currentTooltip = null;
      }, 200);
    }
  }

  handleResize() {
    // Handle responsive behavior
    const width = window.innerWidth;

    if (width < 768) {
      document.body.classList.add("mobile");
      document.body.classList.remove("desktop");
    } else {
      document.body.classList.add("desktop");
      document.body.classList.remove("mobile");
    }

    // Close sidebars on mobile orientation change
    if (width < 768) {
      sidebarManager.closeAll();
    }

    logger.debug(`Window resized: ${width}px`);
  }

  runDiagnostics() {
    logger.info("🔧 Running system diagnostics...");

    const diagnostics = {
      version: this.version,
      userAgent: navigator.userAgent,
      viewport: {
        width: window.innerWidth,
        height: window.innerHeight
      },
      url: window.location.href,
      timestamp: new Date().toISOString(),
      features: {
        localStorage: typeof Storage !== "undefined",
        fetch: typeof fetch !== "undefined",
        intersectionObserver: "IntersectionObserver" in window,
        webGL: !!window.WebGLRenderingContext
      },
      elements: {
        products: document.querySelectorAll('[data-product-id]').length,
        cartButtons: document.querySelectorAll('.add-to-cart-btn-uniqlo').length,
        wishlistButtons: document.querySelectorAll('.wishlist-btn-uniqlo').length,
        sizeSelectors: document.querySelectorAll('.size-option-uniqlo').length,
        productCards: document.querySelectorAll('.product-card, .enhanced-product-card').length,
        uiButtons: {
          cart: document.querySelectorAll('.cart-btn, button:has(.fa-shopping-bag), .action-btn:has(.fa-shopping-bag)').length,
          wishlist: document.querySelectorAll('.wishlist-btn, button:has(.fa-heart), .action-btn:has(.fa-heart)').length,
          user: document.querySelectorAll('.user-menu-btn, button:has(.fa-user), .action-btn:has(.fa-user)').length
        }
      },
      managers: {
        cartManager: !!cartManager,
        wishlistManager: !!wishlistManager,
        sizeSelector: !!sizeSelector,
        sidebarManager: !!sidebarManager,
        notificationManager: !!notificationManager
      },
      cart: {
        buttonValidation: this.validateCartButtons()
      },
      ui: {
        buttonValidation: this.validateUIButtons()
      }
    };

    logger.info("📊 System Diagnostics:", diagnostics);

    // Check for potential issues
    this.checkForIssues(diagnostics);

    return diagnostics;
  }

  validateCartButtons() {
    const buttons = document.querySelectorAll('.add-to-cart-btn-uniqlo');
    const validation = {
      total: buttons.length,
      withProductId: 0,
      withoutProductId: 0,
      issues: []
    };

    buttons.forEach((button, index) => {
      const productId = button.dataset.productId;
      const productCard = button.closest('[data-product-id]');
      
      if (productId) {
        validation.withProductId++;
      } else {
        validation.withoutProductId++;
        validation.issues.push(`Button ${index + 1} missing productId`);
      }

      if (!productCard) {
        validation.issues.push(`Button ${index + 1} not in product card`);
      }
    });

    return validation;
  }

  validateUIButtons() {
    const validation = {
      cart: {
        found: 0,
        working: 0,
        issues: []
      },
      wishlist: {
        found: 0,
        working: 0,
        issues: []
      },
      user: {
        found: 0,
        working: 0,
        issues: []
      }
    };

    // Check cart buttons
    const cartButtons = document.querySelectorAll('.cart-btn, button:has(.fa-shopping-bag), .action-btn:has(.fa-shopping-bag)');
    validation.cart.found = cartButtons.length;
    cartButtons.forEach((btn, index) => {
      if (btn.onclick || btn.getAttribute('onclick') || btn.addEventListener) {
        validation.cart.working++;
      } else {
        validation.cart.issues.push(`Cart button ${index + 1} has no event handler`);
      }
    });

    // Check wishlist buttons
    const wishlistButtons = document.querySelectorAll('.wishlist-btn, button:has(.fa-heart), .action-btn:has(.fa-heart)');
    validation.wishlist.found = wishlistButtons.length;
    wishlistButtons.forEach((btn, index) => {
      if (btn.onclick || btn.getAttribute('onclick') || btn.addEventListener) {
        validation.wishlist.working++;
      } else {
        validation.wishlist.issues.push(`Wishlist button ${index + 1} has no event handler`);
      }
    });

    // Check user buttons
    const userButtons = document.querySelectorAll('.user-menu-btn, button:has(.fa-user), .action-btn:has(.fa-user)');
    validation.user.found = userButtons.length;
    userButtons.forEach((btn, index) => {
      if (btn.onclick || btn.getAttribute('onclick') || btn.addEventListener) {
        validation.user.working++;
      } else {
        validation.user.issues.push(`User button ${index + 1} has no event handler`);
      }
    });

    return validation;
  }

  checkForIssues(diagnostics) {
    const issues = [];

    // Check for missing elements
    if (diagnostics.elements.products === 0) {
      issues.push("No products found on page");
    }

    if (diagnostics.elements.cartButtons === 0) {
      issues.push("No add to cart buttons found");
    }

    // Check UI button issues
    if (diagnostics.elements.uiButtons.cart === 0) {
      issues.push("No cart sidebar buttons found");
    }

    if (diagnostics.elements.uiButtons.user === 0) {
      issues.push("No user menu buttons found");
    }

    // Check cart button issues
    if (diagnostics.cart.buttonValidation.withoutProductId > 0) {
      issues.push(`${diagnostics.cart.buttonValidation.withoutProductId} cart buttons missing productId`);
    }

    // Check UI button functionality
    Object.entries(diagnostics.ui.buttonValidation).forEach(([type, validation]) => {
      if (validation.found > 0 && validation.working === 0) {
        issues.push(`${type} buttons found but none appear to have event handlers`);
      }
    });

    // Check for missing features
    if (!diagnostics.features.fetch) {
      issues.push("Fetch API not available");
    }

    if (!diagnostics.features.localStorage) {
      issues.push("localStorage not available");
    }

    // Check for manager initialization
    Object.entries(diagnostics.managers).forEach(([manager, initialized]) => {
      if (!initialized) {
        issues.push(`${manager} not initialized`);
      }
    });

    if (issues.length > 0) {
      logger.warn("⚠️ Issues detected:", issues);
    } else {
      logger.success("✅ No issues detected - all systems operational");
    }

    return issues;
  }

  // Public API methods
  getState() {
    return UrbanStitchState;
  }

  getVersion() {
    return this.version;
  }

  isInitialized() {
    return this.initialized;
  }

  getDiagnostics() {
    return this.runDiagnostics();
  }

  getLogs() {
    return logger.getLogs();
  }

  getErrors() {
    return errorHandler.getErrors();
  }

  clearLogs() {
    logger.clear();
    errorHandler.clear();
  }

  // FIXED: Debug helper for testing UI functionality
  testUIFunctionality() {
    logger.info("🧪 Testing UI functionality...");
    
    const testResults = {
      timestamp: new Date().toISOString(),
      tests: []
    };

    // Test 1: Check for UI buttons
    const uiButtons = {
      cart: document.querySelectorAll('.cart-btn, button:has(.fa-shopping-bag), .action-btn:has(.fa-shopping-bag)'),
      wishlist: document.querySelectorAll('.wishlist-btn, button:has(.fa-heart), .action-btn:has(.fa-heart)'),
      user: document.querySelectorAll('.user-menu-btn, button:has(.fa-user), .action-btn:has(.fa-user)')
    };

    Object.entries(uiButtons).forEach(([type, buttons]) => {
      testResults.tests.push({
        name: `${type} buttons found`,
        passed: buttons.length > 0,
        details: `Found ${buttons.length} ${type} buttons`
      });
    });

    // Test 2: Check sidebars exist
    const sidebars = {
      cart: document.getElementById('cartSidebar'),
      wishlist: document.getElementById('wishlistSidebar'),
      userDropdown: document.getElementById('userDropdown')
    };

    Object.entries(sidebars).forEach(([type, sidebar]) => {
      testResults.tests.push({
        name: `${type} sidebar/dropdown exists`,
        passed: !!sidebar,
        details: sidebar ? "Found" : "Not found"
      });
    });

    // Test 3: Test manager functionality
    testResults.tests.push({
      name: "Sidebar manager initialized",
      passed: !!sidebarManager && typeof sidebarManager.toggle === 'function',
      details: "SidebarManager instance available"
    });

    // Test 4: Test API endpoints
    testResults.tests.push({
      name: "API endpoints configured",
      passed: !!CONFIG.API_ENDPOINTS.ADD_TO_CART,
      details: `Configured endpoints: ${Object.keys(CONFIG.API_ENDPOINTS).length}`
    });

    const passedTests = testResults.tests.filter(test => test.passed).length;
    const totalTests = testResults.tests.length;

    logger.info(`🧪 UI Test Results: ${passedTests}/${totalTests} passed`, testResults);

    if (passedTests === totalTests) {
      logger.success("✅ All UI functionality tests passed!");
    } else {
      logger.warn("⚠️ Some UI functionality tests failed");
    }

    return testResults;
  }

  // FIXED: Manual UI test functions
  async testCartSidebar() {
    logger.info("🧪 Testing cart sidebar...");
    try {
      sidebarManager.toggle('cart');
      logger.success("✅ Cart sidebar test completed");
    } catch (error) {
      logger.error("❌ Cart sidebar test failed:", error);
    }
  }

  async testWishlistSidebar() {
    logger.info("🧪 Testing wishlist sidebar...");
    try {
      sidebarManager.toggle('wishlist');
      logger.success("✅ Wishlist sidebar test completed");
    } catch (error) {
      logger.error("❌ Wishlist sidebar test failed:", error);
    }
  }

  async testUserMenu() {
    logger.info("🧪 Testing user menu...");
    try {
      sidebarManager.toggleUserMenu();
      logger.success("✅ User menu test completed");
    } catch (error) {
      logger.error("❌ User menu test failed:", error);
    }
  }

  async testAddToCart(productId) {
    if (!productId) {
      const firstButton = document.querySelector('.add-to-cart-btn-uniqlo');
      if (firstButton) {
        productId = parseInt(firstButton.dataset.productId);
      }
    }

    if (!productId) {
      logger.error("No product ID provided and no cart button found");
      return;
    }

    logger.info(`🧪 Testing add to cart for product ${productId}`);

    try {
      await cartManager.addToCart(productId);
      logger.success(`✅ Test add to cart for product ${productId} completed`);
    } catch (error) {
      logger.error(`❌ Test add to cart for product ${productId} failed:`, error);
    }
  }
}

// ==========================================
// GLOBAL API EXPOSURE
// ==========================================

// Create and initialize the main app
const urbanStitchApp = new UrbanStitchApp();

// Expose global API
window.UrbanStitch = {
  // Core app
  app: urbanStitchApp,
  version: urbanStitchApp.version,

  // Managers
  cart: cartManager,
  wishlist: wishlistManager,
  sidebar: sidebarManager,
  size: sizeSelector,
  notifications: notificationManager,
  logger: logger,
  errorHandler: errorHandler,

  // Utility functions
  utils: {
    debounce,
    throttle,
    formatCurrency,
    generateId,
    isValidEmail,
    sanitizeHTML,
    apiRequest,
    createFormData
  },

  // State
  state: UrbanStitchState,

  // Legacy functions
  legacy: {
    selectSizeUniqlo,
    handleCartAddUniqlo,
    handleWishlistAddUniqlo,
    updateCartQuantity,
    removeFromCartWithSize,
    removeFromWishlist,
    toggleCart,
    toggleWishlist,
    closeSidebars,
    toggleUserMenu,
    showNotification,
  },

  // Debug functions
  debug: {
    getDiagnostics: () => urbanStitchApp.getDiagnostics(),
    getLogs: () => logger.getLogs(),
    getErrors: () => errorHandler.getErrors(),
    clearLogs: () => urbanStitchApp.clearLogs(),
    testCart: (productId) => urbanStitchApp.testAddToCart(productId),
    testCartSidebar: () => urbanStitchApp.testCartSidebar(),
    testWishlistSidebar: () => urbanStitchApp.testWishlistSidebar(),
    testUserMenu: () => urbanStitchApp.testUserMenu(),
    testUI: () => urbanStitchApp.testUIFunctionality(),
    runDiagnostics: () => urbanStitchApp.runDiagnostics()
  }
};

// ==========================================
// FINAL INITIALIZATION
// ==========================================

// Log successful load
logger.success("🎯 UrbanStitch Enhanced System Loaded Successfully!");
logger.info("🚀 Features: Size Selection, Cart Management, Wishlist, Enhanced UI");
logger.info("🔧 API Available: window.UrbanStitch");
logger.info("🐛 Debug Console: window.UrbanStitch.debug");
logger.info("🧪 Test UI: window.UrbanStitch.debug.testUI()");
logger.info("🛒 Test Cart: window.UrbanStitch.debug.testCartSidebar()");
logger.info("❤️ Test Wishlist: window.UrbanStitch.debug.testWishlistSidebar()");
logger.info("👤 Test User Menu: window.UrbanStitch.debug.testUserMenu()");

// Export for module systems
if (typeof module !== "undefined" && module.exports) {
  module.exports = window.UrbanStitch;
}

if (typeof define === "function" && define.amd) {
  define([], () => window.UrbanStitch);
}

// ==========================================
// ENHANCED DEBUGGING FOR UI ISSUES
// ==========================================

// Add a specific debug function for UI issues
window.debugUI = function() {
  console.log("%c🔧 UI DEBUG INFORMATION", "color: #ff6b35; font-weight: bold; font-size: 16px;");
  
  // 1. Check UI buttons
  const uiButtons = {
    cart: document.querySelectorAll('.cart-btn, button:has(.fa-shopping-bag), .action-btn:has(.fa-shopping-bag)'),
    wishlist: document.querySelectorAll('.wishlist-btn, button:has(.fa-heart), .action-btn:has(.fa-heart)'),
    user: document.querySelectorAll('.user-menu-btn, button:has(.fa-user), .action-btn:has(.fa-user)')
  };

  Object.entries(uiButtons).forEach(([type, buttons]) => {
    console.log(`📝 Found ${buttons.length} ${type} UI buttons`);
    
    buttons.forEach((button, index) => {
      const hasClick = button.onclick || button.getAttribute('onclick');
      const hasId = button.id;
      const classes = button.className;
      
      console.log(`   Button ${index + 1}: ID=${hasId}, Classes="${classes}", HasClick=${!!hasClick}`);
    });
  });
  
  // 2. Check sidebars
  const sidebars = {
    cart: document.getElementById('cartSidebar'),
    wishlist: document.getElementById('wishlistSidebar'),
    userDropdown: document.getElementById('userDropdown')
  };

  console.log(`📱 Sidebar Elements:`);
  Object.entries(sidebars).forEach(([type, element]) => {
    console.log(`   ${type}: ${element ? '✅ Found' : '❌ Not Found'}`);
    if (element) {
      console.log(`      Classes: "${element.className}"`);
      console.log(`      Visible: ${element.style.display !== 'none'}`);
    }
  });
  
  // 3. Test managers
  console.log(`⚙️ Manager Status:`);
  console.log(`   Cart Manager: ${!!cartManager ? '✅' : '❌'}`);
  console.log(`   Wishlist Manager: ${!!wishlistManager ? '✅' : '❌'}`);
  console.log(`   Sidebar Manager: ${!!sidebarManager ? '✅' : '❌'}`);
  
  // 4. Test click functionality
  console.log(`🎯 Testing Button Functionality:`);
  if (window.UrbanStitch && window.UrbanStitch.debug) {
    const testResults = window.UrbanStitch.debug.testUI();
    console.log("🧪 UI Test Results:", testResults);
  }
  
  console.log("%c✅ UI debug complete - check logs above", "color: #00ff00; font-weight: bold;");
};

// Quick test functions
window.testCartButton = () => sidebarManager.toggle('cart');
window.testWishlistButton = () => sidebarManager.toggle('wishlist');
window.testUserButton = () => sidebarManager.toggleUserMenu();

// ==========================================
// SEARCH FUNCTIONALITY (FROM INDEX.PHP)
// ==========================================

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchSuggestions = document.getElementById('searchSuggestions');
    const categorySuggestions = document.getElementById('categorySuggestions');
    const recentSearches = document.getElementById('recentSearches');
    
    // Only proceed if search elements exist
    if (!searchInput || !searchSuggestions) {
        return;
    }
    
    // Categories data (if available)
    let categories = [];
    if (typeof window.categories !== 'undefined') {
        categories = window.categories;
    }
    
    // Get recent searches from localStorage
    function getRecentSearches() {
        try {
            return JSON.parse(localStorage.getItem('urbanstitch_recent_searches')) || [];
        } catch (e) {
            return [];
        }
    }
    
    // Save search to recent searches
    function saveRecentSearch(term) {
        try {
            let recent = getRecentSearches();
            recent = recent.filter(item => item.toLowerCase() !== term.toLowerCase());
            recent.unshift(term);
            recent = recent.slice(0, 5);
            localStorage.setItem('urbanstitch_recent_searches', JSON.stringify(recent));
        } catch (e) {
            console.log('Could not save recent search');
        }
    }
    
    // Show search suggestions
    function showSuggestions(query) {
        if (!query || query.length < 2) {
            searchSuggestions.style.display = 'none';
            return;
        }
        
        // Filter categories
        const matchingCategories = categories.filter(cat => 
            cat.name.toLowerCase().includes(query.toLowerCase())
        );
        
        // Clear previous suggestions
        if (categorySuggestions) {
            categorySuggestions.innerHTML = '';
            
            // Add category suggestions
            matchingCategories.slice(0, 4).forEach(category => {
                const suggestionItem = document.createElement('div');
                suggestionItem.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; cursor: pointer; border-radius: 4px; transition: all 0.2s;" 
                         onmouseover="this.style.background='#f0f0f0'" 
                         onmouseout="this.style.background='transparent'"
                         onclick="selectCategory('${category.slug}')">
                        <div class="category-dot dot-${category.color.replace('text-', '')}" style="width: 8px; height: 8px; border-radius: 50%;"></div>
                        <span style="flex: 1; font-size: 14px;">${category.name}</span>
                        <span style="color: #666; font-size: 12px;">${category.product_count || 0} items</span>
                    </div>
                `;
                categorySuggestions.appendChild(suggestionItem);
            });
        }
        
        // Add recent searches
        if (recentSearches) {
            const recent = getRecentSearches();
            recentSearches.innerHTML = '';
            recent.slice(0, 3).forEach(term => {
                const recentItem = document.createElement('div');
                recentItem.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; cursor: pointer; border-radius: 4px; transition: all 0.2s;"
                         onmouseover="this.style.background='#f0f0f0'" 
                         onmouseout="this.style.background='transparent'"
                         onclick="selectSearch('${term}')">
                        <i class="fas fa-history" style="color: #666; font-size: 12px;"></i>
                        <span style="flex: 1; font-size: 14px;">${term}</span>
                    </div>
                `;
                recentSearches.appendChild(recentItem);
            });
        }
        
        searchSuggestions.style.display = 'block';
    }
    
    // Select category suggestion
    window.selectCategory = function(slug) {
        window.location.href = `index.php?category=${slug}`;
    }
    
    // Select search suggestion  
    window.selectSearch = function(term) {
        searchInput.value = term;
        searchInput.form.submit();
    }
    
    // Search input events
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            showSuggestions(this.value);
        });
        
        searchInput.addEventListener('focus', function() {
            if (this.value.length >= 2) {
                showSuggestions(this.value);
            }
        });
        
        searchInput.addEventListener('blur', function() {
            setTimeout(() => {
                searchSuggestions.style.display = 'none';
            }, 200);
        });
        
        // Save search when form is submitted
        if (searchInput.form) {
            searchInput.form.addEventListener('submit', function() {
                const searchTerm = searchInput.value.trim();
                if (searchTerm) {
                    saveRecentSearch(searchTerm);
                }
            });
        }
    }
});

// ==========================================
// END OF URBANSTITCH ENHANCED SYSTEM
// ==========================================

// Final initialization message
console.log("%c🎉 UrbanStitch Enhanced System v2.1.2 - FULLY LOADED!", "color: #00ff00; font-weight: bold; font-size: 18px; background: #1a1a1a; padding: 10px; border-radius: 5px;");
console.log("%c🛒 Cart, Wishlist, User Menu - All Fixed!", "color: #00ff00; font-weight: bold;");
console.log("%c🎯 Quick Tests Available:", "color: #ff6b35; font-weight: bold;");
console.log("  • debugUI() - Complete UI diagnostic");
console.log("  • testCartButton() - Test cart sidebar");
console.log("  • testWishlistButton() - Test wishlist sidebar"); 
console.log("  • testUserButton() - Test user menu");
console.log("  • window.UrbanStitch.debug.testUI() - Full functionality test");

// Auto-run quick diagnostic on load
setTimeout(() => {
    if (window.UrbanStitch && window.UrbanStitch.debug) {
        console.log("%c🔍 Running auto-diagnostic...", "color: #007bff; font-weight: bold;");
        const results = window.UrbanStitch.debug.getDiagnostics();
        
        // Quick summary
        const summary = {
            cartButtons: results.elements.uiButtons.cart,
            wishlistButtons: results.elements.uiButtons.wishlist,
            userButtons: results.elements.uiButtons.user,
            cartSidebar: !!document.getElementById('cartSidebar'),
            wishlistSidebar: !!document.getElementById('wishlistSidebar'),
            userDropdown: !!document.getElementById('userDropdown')
        };
        
        console.log("%c📊 Quick Summary:", "color: #28a745; font-weight: bold;", summary);
        
        if (summary.cartButtons === 0) {
            console.log("%c⚠️ WARNING: No cart buttons found!", "color: #ff6b35; font-weight: bold;");
        }
        
        if (!summary.cartSidebar) {
            console.log("%c⚠️ WARNING: Cart sidebar not found!", "color: #ff6b35; font-weight: bold;");
        }
        
        if (!summary.userDropdown) {
            console.log("%c⚠️ WARNING: User dropdown not found!", "color: #ff6b35; font-weight: bold;");
        }
    }
}, 1000);