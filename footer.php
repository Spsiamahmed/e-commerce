<?php // includes/footer.php ?>

<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="<?= SITE_URL ?>" class="logo">
          <span class="logo-icon">◈</span>
          <span class="logo-text"><?= SITE_NAME ?></span>
        </a>
        <p>Your destination for premium products. Quality you can trust, prices you'll love.</p>
        <div class="social-links">
          <a href="#" aria-label="Facebook">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
          </a>
          <a href="#" aria-label="Twitter">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
          </a>
          <a href="#" aria-label="Instagram">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
          </a>
        </div>
      </div>

      <div class="footer-links">
        <h4>Shop</h4>
        <ul>
          <li><a href="<?= SITE_URL ?>/pages/shop.php">All Products</a></li>
          <li><a href="<?= SITE_URL ?>/pages/shop.php?filter=featured">Featured</a></li>
          <li><a href="<?= SITE_URL ?>/pages/shop.php?filter=sale">Sale Items</a></li>
          <li><a href="<?= SITE_URL ?>/pages/shop.php?filter=new">New Arrivals</a></li>
        </ul>
      </div>

      <div class="footer-links">
        <h4>Support</h4>
        <ul>
          <li><a href="<?= SITE_URL ?>/pages/contact.php">Contact Us</a></li>
          <li><a href="#">FAQ</a></li>
          <li><a href="#">Shipping Policy</a></li>
          <li><a href="#">Returns & Exchanges</a></li>
          <li><a href="#">Track Order</a></li>
        </ul>
      </div>

      <div class="footer-newsletter">
        <h4>Stay Updated</h4>
        <p>Get the latest deals and new arrivals to your inbox.</p>
        <form class="newsletter-form" id="newsletterForm">
          <input type="email" placeholder="your@email.com" required>
          <button type="submit">Subscribe</button>
        </form>
        <div class="trust-badges">
          <div class="badge">🔒 Secure Checkout</div>
          <div class="badge">🚚 Fast Shipping</div>
          <div class="badge">↩️ Easy Returns</div>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
      <div class="footer-payments">
        <span>VISA</span><span>MC</span><span>AMEX</span><span>PayPal</span>
      </div>
    </div>
  </div>
</footer>

<script src="<?= SITE_URL ?>/js/app.js"></script>
</body>
</html>
