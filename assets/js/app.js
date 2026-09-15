document.addEventListener('DOMContentLoaded',()=>{const toggle=document.querySelector('.menu-toggle'),nav=document.querySelector('.main-nav');if(toggle&&nav){toggle.addEventListener('click',()=>{const open=nav.classList.toggle('open');toggle.setAttribute('aria-expanded',open)})}const top=document.querySelector('.back-top');if(top){window.addEventListener('scroll',()=>{top.style.display=scrollY>450?'block':'none'});top.addEventListener('click',()=>scrollTo({top:0,behavior:'smooth'}))}document.querySelectorAll('[data-add-cart]').forEach(button=>button.addEventListener('click',async()=>{const id=button.dataset.addCart;const response=await fetch('cart.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body:`action=add&product_id=${id}&csrf=${button.dataset.csrf}`});const data=await response.json();if(data.success){button.textContent='Added ✓';setTimeout(()=>button.textContent='Add to cart',1400);const badge=document.querySelector('.cart-link span');if(badge)badge.textContent=data.count}}));});
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
  searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const query = searchInput.value.trim();
    if (query.length < 2) { searchResults.classList.remove('open'); searchResults.innerHTML = ''; return; }
    searchResults.innerHTML = '<div class="skeleton" style="height:42px"></div><div class="skeleton" style="height:42px;margin-top:2px"></div>';
    searchResults.classList.add('open');
    searchTimer = setTimeout(async () => {
      const response = await fetch(`search.php?q=${encodeURIComponent(query)}`);
      const products = await response.json();
      searchResults.innerHTML = products.length ? products.map(product => `<a href="product.php?id=${product.id}">${product.name}<strong style="float:right">KSh ${Number(product.price).toLocaleString()}</strong></a>`).join('') : '<div style="padding:12px;font-size:.82rem">No products found.</div>';
    }, 300);
  });
}
document.querySelectorAll('[data-gallery-image]').forEach(button => button.addEventListener('click', () => {
  const image = document.querySelector('#main-product-image');
  if (image) image.src = button.dataset.galleryImage;
}));
const productId = new URLSearchParams(window.location.search).get('id');
if (productId) {
  const viewed = JSON.parse(localStorage.getItem('recentProducts') || '[]').filter(id => id !== productId);
  viewed.unshift(productId); localStorage.setItem('recentProducts', JSON.stringify(viewed.slice(0, 6)));
}
document.querySelectorAll('[data-add-cart]').forEach(button => button.addEventListener('click', () => {
  const localCart = JSON.parse(localStorage.getItem('guestCart') || '{}');
  const id = button.dataset.addCart; localCart[id] = (localCart[id] || 0) + 1;
  localStorage.setItem('guestCart', JSON.stringify(localCart));
}));
const guestCart = localStorage.getItem('guestCart');
if (guestCart) {
  fetch('cart.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'}, body: `action=sync&cart=${encodeURIComponent(guestCart)}&csrf=${encodeURIComponent(document.body.dataset.csrf)}` })
    .then(() => localStorage.removeItem('guestCart'));
}