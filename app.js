// js/app.js — ShopNest Main JS

document.addEventListener('DOMContentLoaded', () => {

  // ——— Mobile Menu ———
  const menuBtn = document.getElementById('mobileMenuBtn');
  const nav     = document.getElementById('mainNav');
  if (menuBtn && nav) {
    menuBtn.addEventListener('click', () => {
      nav.classList.toggle('open');
      menuBtn.classList.toggle('active');
    });
  }

  // ——— Sticky Header Shadow ———
  const header = document.getElementById('header');
  if (header) {
    window.addEventListener('scroll', () => {
      header.style.boxShadow = window.scrollY > 40
        ? '0 4px 24px rgba(15,22,41,0.12)'
        : '0 1px 8px rgba(15,22,41,0.06)';
    }, { passive: true });
  }

  // ——— Auto-dismiss flash ———
  const flash = document.getElementById('flashMsg');
  if (flash) {
    setTimeout(() => flash.remove(), 4000);
  }

  // ——— Add to Cart (AJAX) ———
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.add-to-cart');
    if (!btn) return;
    e.preventDefault();

    const productId = btn.dataset.id;
    if (!productId) return;

    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = '...';

    try {
      const res = await fetch('/ecommerce/pages/ajax-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_to_cart&product_id=${productId}&qty=1`
      });
      const data = await res.json();

      if (data.success) {
        // Update cart badge
        const badge = document.querySelector('.cart-badge');
        if (badge) {
          badge.textContent = data.cart_count;
          badge.style.display = '';
        } else {
          const cartBtn = document.querySelector('.cart-btn');
          if (cartBtn) {
            const span = document.createElement('span');
            span.className = 'cart-badge';
            span.textContent = data.cart_count;
            cartBtn.appendChild(span);
          }
        }
        showToast(data.message, 'success');
        btn.textContent = '✓ Added!';
        setTimeout(() => { btn.textContent = originalText; btn.disabled = false; }, 2000);
      } else {
        showToast(data.message, 'error');
        btn.textContent = originalText;
        btn.disabled = false;
      }
    } catch {
      showToast('Something went wrong. Please try again.', 'error');
      btn.textContent = originalText;
      btn.disabled = false;
    }
  });

  // ——— Add to Wishlist (AJAX) ———
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.add-to-wishlist');
    if (!btn) return;
    e.preventDefault();

    const productId = btn.dataset.id;
    if (!productId) return;

    try {
      const res = await fetch('/ecommerce/pages/ajax-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_to_wishlist&product_id=${productId}`
      });
      const data = await res.json();

      if (data.redirect) {
        window.location.href = data.redirect;
        return;
      }
      showToast(data.message, data.success ? 'success' : 'error');
      if (data.wishlisted !== undefined) {
        btn.style.color = data.wishlisted ? '#ef4444' : '';
      }
    } catch {
      showToast('Something went wrong.', 'error');
    }
  });

  // ——— Newsletter Form ———
  const nlForm = document.getElementById('newsletterForm');
  if (nlForm) {
    nlForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const email = nlForm.querySelector('input').value;
      const btn   = nlForm.querySelector('button');
      btn.disabled = true;
      btn.textContent = '...';

      try {
        const res = await fetch('/ecommerce/pages/newsletter.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `email=${encodeURIComponent(email)}`
        });
        const data = await res.json();
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) { nlForm.reset(); }
      } catch {
        showToast('Please try again later.', 'error');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Subscribe';
      }
    });
  }

  // ——— Countdown Timer ———
  const cdEl = document.getElementById('countdown');
  if (cdEl) {
    // 8 hours 34 min from now
    let end = new Date().getTime() + (8 * 3600 + 34 * 60 + 22) * 1000;
    const tick = () => {
      const diff = end - new Date().getTime();
      if (diff <= 0) { cdEl.innerHTML = '<div class="countdown-item"><div class="countdown-num">00</div><div class="countdown-label">Ended</div></div>'; return; }
      const h = Math.floor(diff / 3600000);
      const m = Math.floor((diff % 3600000) / 60000);
      const s = Math.floor((diff % 60000) / 1000);
      document.getElementById('cd-h').textContent = String(h).padStart(2,'0');
      document.getElementById('cd-m').textContent = String(m).padStart(2,'0');
      document.getElementById('cd-s').textContent = String(s).padStart(2,'0');
    };
    tick();
    setInterval(tick, 1000);
  }

  // ——— Product image zoom ———
  const mainImg = document.getElementById('mainImg');
  if (mainImg) {
    mainImg.addEventListener('mouseenter', () => { mainImg.style.transform = 'scale(1.04)'; mainImg.style.transition = 'transform .4s'; });
    mainImg.addEventListener('mouseleave', () => { mainImg.style.transform = ''; });
  }

});

// ——— Toast Notification ———
function showToast(message, type = 'success') {
  const existing = document.querySelector('.toast-notification');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.className = `flash-message flash-${type} toast-notification`;
  toast.style.cssText = 'position:fixed;top:80px;right:24px;z-index:9999;';
  toast.innerHTML = message + '<button onclick="this.parentElement.remove()" style="margin-left:12px;font-size:18px;opacity:0.6;">×</button>';
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3500);
}
