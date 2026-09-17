</main>
<style>
  .site-footer{background:var(--green-dark);color:#fff;padding:72px 0 0}
  .footer-grid{display:grid;grid-template-columns:1.6fr 1fr 1fr 1.3fr;gap:48px;padding-bottom:52px}
  .footer-brand{display:flex;align-items:center;gap:12px;margin-bottom:18px}
  .footer-brand img{
    width:52px;height:52px;object-fit:contain;display:block;flex-shrink:0;
    border-radius:12px;background:rgba(255,255,255,.08);padding:4px;
  }
  .footer-brand .name{font-family:Manrope,sans-serif;line-height:1.15}
  .footer-brand .name strong{display:block;font-size:1.02rem;font-weight:800}
  .footer-brand .name span{display:block;font-size:.72rem;color:#a9c2ab;letter-spacing:.04em;margin-top:2px}

  .footer-col p.tagline{color:#c3d6c2;font-size:.88rem;line-height:1.65;max-width:280px;margin:0 0 20px}
  .social-row{display:flex;gap:10px}
  .social-row a{
    width:36px;height:36px;border-radius:50%;border:1px solid rgba(255,255,255,.18);
    display:flex;align-items:center;justify-content:center;font-size:.85rem;color:#dce9db;
    transition:background .2s ease,border-color .2s ease,color .2s ease;
  }
  .social-row a:hover{background:var(--green);border-color:var(--green);color:#fff}

  .footer-col h4{
    font-family:Manrope,sans-serif;font-size:.82rem;font-weight:700;text-transform:uppercase;
    letter-spacing:.06em;color:#fff;margin:0 0 18px;
  }
  .footer-col ul{list-style:none;margin:0;padding:0}
  .footer-col ul li{margin-bottom:12px}
  .footer-col ul li a{
    display:inline-block;font-size:.88rem;color:#c3d6c2;transition:color .18s ease,transform .18s ease;
  }
  .footer-col ul li a:hover{color:var(--gold);transform:translateX(2px)}

  .footer-col p.desc{color:#c3d6c2;font-size:.85rem;line-height:1.6;margin:0 0 16px}
  .newsletter-row{display:flex;background:#fff;border-radius:9px;padding:4px;gap:4px;margin-bottom:16px}
  .newsletter-row input{
    flex:1;border:0;background:transparent;padding:10px 12px;font:500 .86rem 'DM Sans';
    color:#233;min-width:0;
  }
  .newsletter-row input:focus{outline:0}
  .newsletter-row button{
    background:var(--green);color:#fff;border:0;border-radius:6px;padding:0 18px;
    font:700 .85rem 'DM Sans';cursor:pointer;transition:background .2s ease;white-space:nowrap;
  }
  .newsletter-row button:hover{background:#256428}

  .locations{display:flex;flex-wrap:wrap;gap:6px;font-size:.78rem;color:#93ab92}
  .locations span{display:flex;align-items:center;gap:5px}
  .locations i{font-size:.68rem;color:var(--gold)}

  .footer-bottom{border-top:1px solid rgba(255,255,255,.12);padding:20px 0}
  .footer-bottom .container{
    display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;
    font-size:.78rem;color:#93ab92;
  }
  .footer-bottom-links{display:flex;gap:22px}
  .footer-bottom-links a{color:#93ab92;transition:color .18s ease}
  .footer-bottom-links a:hover{color:#fff}

  @media(max-width:900px){
    .footer-grid{grid-template-columns:1fr 1fr;row-gap:36px}
  }
  @media(max-width:560px){
    .footer-grid{grid-template-columns:1fr}
    .footer-bottom .container{flex-direction:column;align-items:flex-start}
  }
</style>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-col">
        <div class="footer-brand">
          <img src="golden-egg.jpg" alt="Golden Eggs &amp; Chicks Farm logo" width="52" height="52" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex'">
          <div class="name"><strong>Golden Eggs &amp; Chicks</strong><span>FARM</span></div>
        </div>
        <p class="tagline">Good food starts at the farm. We raise healthy birds and deliver honest freshness to your table.</p>
        <div class="social-row">
          <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
        </div>
      </div>

      <div class="footer-col">
        <h4>Explore</h4>
        <ul>
          <li><a href="shop.php">Shop All</a></li>
          <li><a href="about.php">Our Story</a></li>
          <li><a href="contact.php">Contact Us</a></li>
          <li><a href="account.php">My Account</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Shop by Category</h4>
        <ul>
          <li><a href="shop.php?category=Fresh+Eggs">Fresh Eggs</a></li>
          <li><a href="shop.php?category=Day-old+Chicks">Day-old Chicks</a></li>
          <li><a href="shop.php?category=Feed">Poultry Feed</a></li>
          <li><a href="shop.php?category=Poultry+Equipment">Equipment</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Stay in the Loop</h4>
        <p class="desc">Seasonal offers and farm notes, once a month.</p>
        <form class="newsletter-row" action="newsletter.php" method="post">
          <input type="email" name="email" placeholder="Your email address" required>
          <button type="submit">Join</button>
        </form>
        <div class="locations">
          <span><i class="fa-solid fa-location-dot"></i>Nairobi</span>
          <span><i class="fa-solid fa-location-dot"></i>Kiambu</span>
          <span><i class="fa-solid fa-location-dot"></i>Limuru</span>
        </div>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="container">
      <p>&copy; <?= date('Y') ?> Golden Eggs &amp; Chicks Farm. All rights reserved.</p>
      <div class="footer-bottom-links">
        <a href="#">Privacy</a>
        <a href="#">Terms</a>
        <a href="#">Made for good food</a>
      </div>
    </div>
  </div>
</footer>
<button class="back-top" aria-label="Back to top">↑</button>
</body>
</html>
