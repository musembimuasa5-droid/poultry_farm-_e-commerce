const readStoredObject = key => {
  try {
    const value = JSON.parse(localStorage.getItem(key) || '{}');
    return value && typeof value === 'object' && !Array.isArray(value) ? value : {};
  } catch {
    return {};
  }
};

const readStoredArray = key => {
  try {
    const value = JSON.parse(localStorage.getItem(key) || '[]');
    return Array.isArray(value) ? value : [];
  } catch {
    return [];
  }
};

const saveGuestCart = (productId, quantity) => {
  if (document.body.dataset.authenticated === '1') return;
  const cart = readStoredObject('guestCart');
  cart[productId] = (Number(cart[productId]) || 0) + quantity;
  localStorage.setItem('guestCart', JSON.stringify(cart));
};

const addProductToCart = async button => {
  const productId = button.dataset.addCart;
  const card = button.closest('.product-card, .product-detail-copy') || button.parentElement;
  const quantityInput = card.querySelector('.shop-quantity, .home-quantity');
  const maxQuantity = quantityInput ? Number.parseInt(quantityInput.max, 10) : Number.MAX_SAFE_INTEGER;
  const parsedQuantity = quantityInput ? Number.parseInt(quantityInput.value, 10) : 1;
  const quantity = Math.min(maxQuantity || 1, Math.max(1, Number.isFinite(parsedQuantity) ? parsedQuantity : 1));
  const feedback = card.querySelector('[data-cart-feedback], .shop-cart-feedback, .home-cart-feedback');
  const originalText = button.textContent.trim();
  if (quantityInput) quantityInput.value = quantity;
  button.disabled = true;
  button.setAttribute('aria-busy', 'true');

  try {
    const body = new URLSearchParams({
      action: 'add',
      product_id: productId,
      quantity: String(quantity),
      csrf: button.dataset.csrf || document.body.dataset.csrf || ''
    });
    const response = await fetch('cart.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body
    });
    const result = await response.json();
    if (!response.ok || !result.success) {
      throw new Error(result.message || 'Unable to update your cart. Please try again.');
    }

    button.textContent = 'Added ✓';
    if (feedback) feedback.textContent = result.message;
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) cartCount.textContent = result.count;
    saveGuestCart(productId, quantity);
    window.setTimeout(() => { button.textContent = originalText; }, 1400);
  } catch (error) {
    if (feedback) feedback.textContent = error.message || 'Unable to update your cart. Please try again.';
    button.textContent = 'Try again';
    window.setTimeout(() => { button.textContent = originalText; }, 1800);
  } finally {
    button.disabled = false;
    button.removeAttribute('aria-busy');
  }
};

document.addEventListener('DOMContentLoaded', () => {
  const menuToggle = document.querySelector('.menu-toggle');
  const mobileMenu = document.querySelector('.mobile-menu');
  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener('click', () => {
      const open = mobileMenu.classList.toggle('open');
      menuToggle.classList.toggle('is-open', open);
      menuToggle.setAttribute('aria-expanded', String(open));
    });
  }

  const accountTrigger = document.querySelector('.account-trigger');
  if (accountTrigger) {
    accountTrigger.addEventListener('click', () => {
      const menu = accountTrigger.closest('.account-menu');
      const open = menu.classList.toggle('is-open');
      accountTrigger.setAttribute('aria-expanded', String(open));
    });
  }

  const backToTop = document.querySelector('.back-top');
  if (backToTop) {
    window.addEventListener('scroll', () => {
      backToTop.style.display = window.scrollY > 450 ? 'block' : 'none';
    }, { passive: true });
    backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  const announcementMessages = [...document.querySelectorAll('.announcement-message')];
  if (announcementMessages.length > 1) {
    let currentMessage = 0;
    window.setInterval(() => {
      announcementMessages[currentMessage].classList.remove('is-visible');
      currentMessage = (currentMessage + 1) % announcementMessages.length;
      announcementMessages[currentMessage].classList.add('is-visible');
    }, 4500);
  }

  document.querySelectorAll('[data-add-cart]').forEach(button => {
    button.addEventListener('click', () => addProductToCart(button));
  });

  document.querySelectorAll('[data-quantity-control]').forEach(control => {
    const input = control.querySelector('input[type="number"]');
    control.querySelectorAll('[data-quantity-change]').forEach(button => {
      button.addEventListener('click', () => {
        const minimum = Number.parseInt(input.min, 10) || 1;
        const maximum = Number.parseInt(input.max, 10) || Number.MAX_SAFE_INTEGER;
        const current = Number.parseInt(input.value, 10) || minimum;
        input.value = Math.min(maximum, Math.max(minimum, current + Number(button.dataset.quantityChange)));
      });
    });
    input.addEventListener('change', () => {
      const minimum = Number.parseInt(input.min, 10) || 1;
      const maximum = Number.parseInt(input.max, 10) || Number.MAX_SAFE_INTEGER;
      const current = Number.parseInt(input.value, 10) || minimum;
      input.value = Math.min(maximum, Math.max(minimum, current));
    });
  });

  document.querySelectorAll('[data-auto-submit]').forEach(select => {
    select.addEventListener('change', () => select.form.requestSubmit());
  });

  const shopGrid = document.querySelector('[data-shop-grid]');
  if (shopGrid) {
    document.querySelectorAll('[data-shop-view]').forEach(button => {
      button.addEventListener('click', () => {
        const view = button.dataset.shopView;
        shopGrid.dataset.view = view;
        document.querySelectorAll('[data-shop-view]').forEach(option => {
          const active = option === button;
          option.classList.toggle('is-active', active);
          option.setAttribute('aria-pressed', String(active));
        });
      });
    });
  }

  const siteHeader = document.querySelector('.site-header');
  if (siteHeader) {
    window.addEventListener('scroll', () => {
      siteHeader.classList.toggle('scrolled', window.scrollY > 40);
    }, { passive: true });
  }

  const searchInput = document.querySelector('[data-live-search]');
  const searchResults = document.querySelector('[data-search-results]');
  let searchTimer;
  if (searchInput && searchResults) {
    const openShopSearch = () => {
      const query = searchInput.value.trim();
      if (query) window.location.href = `shop.php?q=${encodeURIComponent(query)}`;
    };
    searchInput.addEventListener('keydown', event => {
      if (event.key === 'Enter') {
        event.preventDefault();
        openShopSearch();
      }
    });
    document.querySelector('.search-submit')?.addEventListener('click', openShopSearch);
    searchInput.addEventListener('input', () => {
      window.clearTimeout(searchTimer);
      const query = searchInput.value.trim();
      if (query.length < 2) {
        searchResults.classList.remove('open');
        searchResults.replaceChildren();
        return;
      }
      searchResults.textContent = 'Searching products...';
      searchResults.classList.add('open');
      searchTimer = window.setTimeout(async () => {
        try {
          const response = await fetch(`search.php?q=${encodeURIComponent(query)}`);
          if (!response.ok) throw new Error('Search is temporarily unavailable.');
          const products = await response.json();
          searchResults.replaceChildren();
          if (!products.length) {
            searchResults.textContent = 'No products found.';
          } else {
            products.forEach(product => {
              const link = document.createElement('a');
              link.href = `product.php?id=${Number(product.id)}`;
              const name = document.createElement('span');
              name.textContent = product.name;
              const price = document.createElement('strong');
              price.textContent = `KSh ${Number(product.price).toLocaleString()}`;
              link.append(name, price);
              searchResults.append(link);
            });
          }
        } catch (error) {
          searchResults.textContent = error.message;
        }
      }, 300);
    });
  }

  document.querySelectorAll('.shop-product-card img, .home-product-card img, .gallery-main img, .gallery-thumbs img, .related-section .product-card img').forEach(image => {
    const fallbackImage = () => {
      if (image.dataset.fallbackApplied) return;
      image.dataset.fallbackApplied = 'true';
      image.src = 'assets/images/product-placeholder.svg';
    };
    image.addEventListener('error', fallbackImage);
    if (image.complete && image.naturalWidth === 0) fallbackImage();
  });

  const productId = new URLSearchParams(window.location.search).get('id');
  if (productId) {
    const viewed = readStoredArray('recentProducts').filter(id => String(id) !== productId);
    viewed.unshift(productId);
    localStorage.setItem('recentProducts', JSON.stringify(viewed.slice(0, 6)));
  }

  const recentStrip = document.querySelector('[data-recent-products]');
  const recentIds = readStoredArray('recentProducts');
  if (recentStrip && recentIds.length) {
    fetch(`recent.php?ids=${recentIds.map(Number).filter(Number.isInteger).join(',')}`)
      .then(response => {
        if (!response.ok) throw new Error('Recently viewed products are unavailable.');
        return response.json();
      })
      .then(products => {
        recentStrip.replaceChildren();
        products.forEach(product => {
          const link = document.createElement('a');
          link.className = 'recent-product';
          link.href = `product.php?id=${Number(product.id)}`;
          const image = document.createElement('img');
          image.alt = '';
          image.width = 100;
          image.height = 75;
          image.loading = 'lazy';
          image.addEventListener('error', () => {
            if (!image.dataset.fallbackApplied) {
              image.dataset.fallbackApplied = 'true';
              image.src = 'assets/images/product-placeholder.svg';
            }
          });
          image.src = product.image || 'assets/images/product-placeholder.svg';
          const name = document.createElement('span');
          name.textContent = product.name;
          link.append(image, name);
          recentStrip.append(link);
        });
      })
      .catch(() => {});
  }

  const guestCart = localStorage.getItem('guestCart');
  if (guestCart) {
    fetch('cart.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: new URLSearchParams({
        action: 'sync',
        cart: guestCart,
        csrf: document.body.dataset.csrf || ''
      })
    }).then(async response => {
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.message || 'Unable to sync your cart.');
      localStorage.removeItem('guestCart');
      const cartCount = document.querySelector('.cart-count');
      if (cartCount) cartCount.textContent = result.count;
      if (window.location.pathname.endsWith('/cart.php') && !sessionStorage.getItem('cartHydrated')) {
        sessionStorage.setItem('cartHydrated', '1');
        window.location.reload();
      }
    }).catch(error => {
      console.error('Guest cart could not be synchronized:', error);
      const feedback = document.querySelector('[data-cart-sync-feedback]');
      if (feedback) feedback.textContent = 'Your saved cart could not be synchronized. Please retry from the cart page.';
    });
  }
  if (!window.location.pathname.endsWith('/cart.php')) sessionStorage.removeItem('cartHydrated');
});
