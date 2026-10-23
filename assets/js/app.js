<<<<<<< HEAD
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

  document.querySelectorAll('[data-price-range]').forEach(range => {
    const minRange = range.querySelector('[data-price-min]');
    const maxRange = range.querySelector('[data-price-max]');
    const form = range.closest('form');
    const minInput = form?.querySelector('input[name="min_price"]:not([type="hidden"])');
    const maxInput = form?.querySelector('input[name="max_price"]:not([type="hidden"])');
    const syncPriceInputs = changed => {
      let minimum = Number(minRange.value);
      let maximum = Number(maxRange.value);
      if (minimum > maximum) {
        if (changed === minRange) maximum = minimum;
        else minimum = maximum;
      }
      minRange.value = String(minimum);
      maxRange.value = String(maximum);
      if (minInput) minInput.value = minimum > Number(minRange.min) ? String(minimum) : '';
      if (maxInput) maxInput.value = maximum < Number(maxRange.max) ? String(maximum) : '';
    };
    minRange.addEventListener('input', () => syncPriceInputs(minRange));
    maxRange.addEventListener('input', () => syncPriceInputs(maxRange));
  });

  document.querySelectorAll('[data-delivery-checker]').forEach(checker => {
    const form = checker.querySelector('.delivery-checker-form');
    const result = checker.querySelector('[data-delivery-result]');
    form.addEventListener('submit', async event => {
      event.preventDefault();
      const area = form.querySelector('[name="area"]').value;
      if (!area) {
        result.textContent = 'Select a delivery area to see your estimate.';
        return;
      }
      result.textContent = 'Checking local delivery...';
      const request = new URLSearchParams({
        csrf: document.body.dataset.csrf || '',
        area
      });
      if (checker.dataset.productId) {
        const productCard = checker.closest('.product-detail') || document;
        const quantityInput = productCard.querySelector('#product-quantity');
        request.set('product_id', checker.dataset.productId);
        request.set('quantity', String(Math.max(1, Number.parseInt(quantityInput?.value || '1', 10) || 1)));
      }
      try {
        const response = await fetch('delivery.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: request
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Delivery estimate unavailable.');
        result.textContent = `${data.message} ${data.quote.remaining > 0 ? `Add ${formatMoney(data.quote.remaining)} for free delivery.` : ''}`;
        document.querySelectorAll('[data-cart-delivery-fee]').forEach(node => {
          node.textContent = data.quote.fee ? formatMoney(data.quote.fee) : 'Free';
        });
        document.querySelectorAll('[data-cart-grand-total]').forEach(node => {
          node.textContent = formatMoney(data.subtotal + data.subtotal * 0.16 + data.quote.fee);
        });
      } catch (error) {
        result.textContent = error.message || 'Delivery estimate unavailable.';
      }
    });
  });

  const checkoutDeliveryArea = document.querySelector('[data-checkout-delivery-area]');
  if (checkoutDeliveryArea) {
    checkoutDeliveryArea.addEventListener('change', async () => {
      const feedback = document.querySelector('[data-checkout-delivery-feedback]');
      const feeNode = document.querySelector('[data-checkout-delivery-fee]');
      const totalNode = document.querySelector('[data-checkout-grand-total]');
      if (!checkoutDeliveryArea.value) return;
      feedback.textContent = 'Checking local delivery...';
      try {
        const response = await fetch('delivery.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            csrf: checkoutDeliveryArea.dataset.csrf || '',
            area: checkoutDeliveryArea.value
          })
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Delivery estimate unavailable.');
        feedback.textContent = data.message;
        feeNode.textContent = data.quote.fee ? formatMoney(data.quote.fee) : 'Free';
        totalNode.textContent = formatMoney(data.subtotal + data.subtotal * 0.16 + data.quote.fee);
      } catch (error) {
        feedback.textContent = error.message || 'Delivery estimate unavailable.';
      }
    });
    if (checkoutDeliveryArea.value) checkoutDeliveryArea.dispatchEvent(new Event('change'));
  }

  document.querySelectorAll('[data-batch-countdown]').forEach(time => {
    const releaseDate = new Date(`${time.dataset.batchCountdown}T00:00:00`);
    const days = Math.ceil((releaseDate.getTime() - new Date().setHours(0, 0, 0, 0)) / 86400000);
    const count = document.createElement('span');
    count.className = 'batch-countdown';
    count.textContent = days <= 0 ? 'Available today' : `${days} day${days === 1 ? '' : 's'} until release`;
    time.insertAdjacentElement('afterend', count);
  });

  document.querySelectorAll('[data-auto-submit]').forEach(select => {
    select.addEventListener('change', () => select.form.requestSubmit());
  });

  const formatMoney = amount => `KSh ${Number(amount).toLocaleString('en-KE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })}`;

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
=======
document.addEventListener('DOMContentLoaded', () => {
  const siteHeader = document.querySelector('.site-header');
  const announcementMessages = [...document.querySelectorAll('.announcement-message')];
  const mobileMenu = document.querySelector('.mobile-menu');
  const menuToggle = document.querySelector('.menu-toggle');
  const accountMenu = document.querySelector('.account-menu');
  const shopMenu = document.querySelector('.nav-shop');

  if (siteHeader) {
    const syncHeaderState = () => siteHeader.classList.toggle('scrolled', window.scrollY > 36);
    syncHeaderState();
    window.addEventListener('scroll', syncHeaderState, { passive: true });
  }

  if (announcementMessages.length > 1) {
    let activeIndex = 0;
    const showAnnouncement = () => {
      announcementMessages.forEach((message, index) => 
        message.classList.toggle('is-visible', index === activeIndex)
      );
    };
    showAnnouncement();
    setInterval(() => {
      activeIndex = (activeIndex + 1) % announcementMessages.length;
      showAnnouncement();
    }, 3000);
  }

  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener('click', () => {
      const isOpen = mobileMenu.classList.toggle('open');
      menuToggle.classList.toggle('is-open', isOpen);
      menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    document.addEventListener('click', (event) => {
      if (!menuToggle.contains(event.target) && !mobileMenu.contains(event.target)) {
        mobileMenu.classList.remove('open');
        menuToggle.classList.remove('is-open');
        menuToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  if (accountMenu) {
    const accountButton = accountMenu.querySelector('.account-trigger');
    if (accountButton) {
      accountButton.addEventListener('click', () => {
        accountMenu.classList.toggle('is-open');
      });
      document.addEventListener('click', (event) => {
        if (!accountMenu.contains(event.target)) {
          accountMenu.classList.remove('is-open');
        }
      });
    }
  }

  if (shopMenu) {
    const shopTrigger = shopMenu.querySelector('> a');
    if (shopTrigger) {
      shopTrigger.addEventListener('click', (event) => {
        if (window.innerWidth > 860) {
          event.preventDefault();
          const open = !shopMenu.classList.contains('is-open');
          shopMenu.classList.toggle('is-open', open);
          shopTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
      });
    }
    document.addEventListener('click', (event) => {
      if (!shopMenu.contains(event.target)) {
        shopMenu.classList.remove('is-open');
        if (shopTrigger) shopTrigger.setAttribute('aria-expanded', 'false');
      }
    });
  }

  const top = document.querySelector('.back-top');
  if (top) {
    window.addEventListener('scroll', () => {
      top.style.display = scrollY > 450 ? 'block' : 'none';
    });
    top.addEventListener('click', () => scrollTo({ top: 0, behavior: 'smooth' }));
  }

  document.querySelectorAll('[data-add-cart]').forEach(button => {
    button.addEventListener('click', async () => {
      const id = button.dataset.addCart;
      const response = await fetch('cart.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=add&product_id=${id}&csrf=${button.dataset.csrf}`
      });
      const data = await response.json();
      if (data.success) {
        button.textContent = 'Added ✓';
        setTimeout(() => { button.textContent = 'Add to cart'; }, 1400);
        const badge = document.querySelector('.cart-link .cart-count');
        if (badge) badge.textContent = data.count;

        const toast = document.createElement('div');
        toast.className = 'toast toast-success';
        toast.textContent = 'Added to cart';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 1400);
      }
    });
  });
});

document.querySelectorAll('.home-product-card img').forEach(image =>
  image.addEventListener('error', () => {
    image.src = 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=900&q=85';
  }, { once: true })
);

const searchInput = document.querySelector('[data-live-search]');
const searchResults = document.querySelector('[data-search-results]');
let searchTimer;
if (searchInput && searchResults) {
  searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const query = searchInput.value.trim();
    if (query.length < 2) {
      searchResults.classList.remove('open');
      searchResults.innerHTML = '';
      return;
    }
    searchResults.innerHTML = '<div class="skeleton" style="height:42px"></div><div class="skeleton" style="height:42px;margin-top:2px"></div>';
    searchResults.classList.add('open');
    searchTimer = setTimeout(async () => {
      const response = await fetch(`search.php?q=${encodeURIComponent(query)}`);
      const products = await response.json();
      searchResults.innerHTML = products.length
        ? products.map(product => `<a href="product.php?id=${product.id}"><span>${product.name}</span><strong>KSh ${Number(product.price).toLocaleString()}</strong></a>`).join('')
        : '<div style="padding:12px;font-size:.82rem">No products found.</div>';
    }, 250);
  });
  document.addEventListener('click', (event) => {
    if (!searchInput.parentElement?.contains(event.target)) {
      searchResults.classList.remove('open');
    }
  });
}

const shopSearch = document.querySelector('.shop-toolbar input[name="q"]');
const shopGrid = document.querySelector('.shop-toolbar')?.nextElementSibling;
if (shopSearch && shopGrid) {
  const shopForm = shopSearch.closest('form');
  const categorySelect = document.createElement('select');
  categorySelect.className = 'shop-category-select';
  categorySelect.setAttribute('aria-label', 'Choose a product category');
  categorySelect.innerHTML = '<option value="">All categories</option>' + ['Fresh Eggs', 'Fertilized Eggs', 'Day-old Chicks', 'Broilers', 'Layers', 'Feed', 'Poultry Equipment'].map(category => `<option value="${category}">${category}</option>`).join('');
  categorySelect.value = new URLSearchParams(window.location.search).get('category') || '';
  categorySelect.addEventListener('change', () => {
    const params = new URLSearchParams(window.location.search);
    if (categorySelect.value) params.set('category', categorySelect.value); else params.delete('category');
    params.delete('q');
    window.location.href = `shop.php?${params.toString()}`;
  });
  shopForm?.prepend(categorySelect);

  const shopDropdown = document.createElement('div');
  shopDropdown.className = 'shop-search-dropdown';
  shopSearch.parentElement.classList.add('shop-search-field');
  shopSearch.parentElement.appendChild(shopDropdown);

  let shopTimer;
  const filterShopCards = () => {
    const query = shopSearch.value.trim().toLowerCase();
    const cards = [...shopGrid.querySelectorAll('.product-card')];
    let visible = 0;
    cards.forEach(card => {
      const match = !query || card.textContent.toLowerCase().includes(query);
      card.hidden = !match;
      if (match) visible++;
    });
    const count = document.querySelector('.shop-toolbar strong');
    if (count) count.textContent = `${visible} products`;
  };

  shopSearch.addEventListener('input', () => {
    clearTimeout(shopTimer);
    const query = shopSearch.value.trim();
    if (query.length < 2) {
      shopDropdown.classList.remove('open');
      shopDropdown.innerHTML = '';
      filterShopCards();
      return;
    }
    shopDropdown.innerHTML = '<div class="skeleton" style="height:58px"></div><div class="skeleton" style="height:58px;margin-top:2px"></div>';
    shopDropdown.classList.add('open');
    shopTimer = setTimeout(async () => {
      filterShopCards();
      const response = await fetch(`search.php?q=${encodeURIComponent(query)}`);
      const products = await response.json();
      shopDropdown.innerHTML = products.length ? products.map(product => `<a class="shop-search-result" href="product.php?id=${product.id}"><img src="${product.image || 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=120&q=70'}" alt="" width="48" height="42"><span>${product.name}<small>KSh ${Number(product.price).toLocaleString()}</small></span></a>`).join('') : '<div class="shop-search-empty">No matching products found.</div>';
    }, 250);
  });

  document.addEventListener('click', (event) => {
    if (!shopSearch.parentElement.contains(event.target)) {
      shopDropdown.classList.remove('open');
    }
  });
}

document.querySelectorAll('[data-gallery-image]').forEach(button => button.addEventListener('click', () => {
  const image = document.querySelector('#main-product-image');
  if (image) image.src = button.dataset.galleryImage;
}));

const productId = new URLSearchParams(window.location.search).get('id');
if (productId) {
  const viewed = JSON.parse(localStorage.getItem('recentProducts') || '[]').filter(id => id !== productId);
  viewed.unshift(productId);
  localStorage.setItem('recentProducts', JSON.stringify(viewed.slice(0, 6)));
}

document.querySelectorAll('[data-add-cart]').forEach(button => button.addEventListener('click', () => {
  const localCart = JSON.parse(localStorage.getItem('guestCart') || '{}');
  const id = button.dataset.addCart;
  localCart[id] = (localCart[id] || 0) + 1;
  localStorage.setItem('guestCart', JSON.stringify(localCart));
}));

const recentStrip = document.querySelector('[data-recent-products]');
const recentIds = JSON.parse(localStorage.getItem('recentProducts') || '[]');
if (recentStrip && recentIds.length) {
  fetch(`recent.php?ids=${recentIds.join(',')}`).then(response => response.json()).then(products => {
    recentStrip.innerHTML = products.map(product => `<a class="recent-product" href="product.php?id=${product.id}"><img src="${product.image || 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=300&q=80'}" alt="${product.name}" width="100" height="75" loading="lazy"><span>${product.name}</span></a>`).join('');
  });
}
if (recentStrip && !recentIds.length) {
  const fallbackProducts = [...document.querySelectorAll('.home-product-card')].slice(0, 4).map(card => ({
    href: card.querySelector('a')?.href,
    name: card.querySelector('h3')?.textContent.trim(),
    price: card.querySelector('.price')?.textContent.trim(),
    image: card.querySelector('img')?.src
  }));
  recentStrip.innerHTML = fallbackProducts.map(product => `<a class="recent-product" href="${product.href}"><img src="${product.image}" alt="${product.name}" width="100" height="75" loading="lazy"><span>${product.name}<small>${product.price}</small><b>View product →</b></span></a>`).join('');
}

const guestCart = localStorage.getItem('guestCart');
if (guestCart) {
  fetch('cart.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: `action=sync&cart=${encodeURIComponent(guestCart)}&csrf=${encodeURIComponent(document.body.dataset.csrf)}` })
    .then(() => {
      localStorage.removeItem('guestCart');
      if (location.pathname.endsWith('/cart.php') && !sessionStorage.getItem('cartHydrated')) {
        sessionStorage.setItem('cartHydrated', '1');
        location.reload();
      }
    });
}
if (!location.pathname.endsWith('/cart.php')) sessionStorage.removeItem('cartHydrated');
>>>>>>> origin/main
