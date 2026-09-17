<?php require_once 'config.php'; $pageTitle='Shop the farm'; $category=trim($_GET['category']??''); $search=trim($_GET['q']??''); $products=[]; $categories=[]; try{$categories=db()->query('SELECT * FROM categories ORDER BY name')->fetchAll(); $sql='SELECT p.*, c.name category FROM products p JOIN categories c ON c.id=p.category_id WHERE 1'; $params=[]; if($category){$sql.=' AND c.name=?';$params[]=$category;} if($search){$sql.=' AND (p.name LIKE ? OR p.description LIKE ?)';$params[]="%$search%";$params[]="%$search%";} $sql.=' ORDER BY p.featured DESC,p.created_at DESC';$stmt=db()->prepare($sql);$stmt->execute($params);$products=$stmt->fetchAll();}catch(Throwable $e){} include 'includes/header.php'; ?>
<style>
  .search-shell{position:relative;max-width:760px;width:100%}
  .search-bar{display:flex;align-items:stretch;background:#fff;border:1.5px solid var(--line);border-radius:12px;box-shadow:0 2px 10px rgba(32,61,39,.06);overflow:hidden;transition:box-shadow .2s ease,border-color .2s ease}
  .search-bar:focus-within{border-color:var(--green);box-shadow:0 6px 22px rgba(46,125,50,.14)}
  .search-cat{position:relative;display:flex;align-items:center;border-right:1px solid var(--line)}
  .search-cat select{appearance:none;-webkit-appearance:none;background:transparent;border:0;padding:0 34px 0 18px;font:600 .88rem 'DM Sans';color:var(--ink);height:100%;cursor:pointer}
  .search-cat select:focus{outline:0}
  .search-cat i{position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:.7rem;color:var(--muted);pointer-events:none}
  .search-input-wrap{flex:1;display:flex;align-items:center;padding:0 16px;gap:10px;min-width:0}
  .search-input-wrap i.fa-magnifying-glass{color:var(--muted);font-size:.9rem}
  .search-input-wrap input{flex:1;border:0;background:transparent;font:500 .92rem 'DM Sans';padding:14px 0;color:var(--ink);min-width:0}
  .search-input-wrap input:focus{outline:0}
  .search-input-wrap input::placeholder{color:#a7b0a3}
  .search-submit{background:var(--green);color:#fff;border:0;padding:0 26px;font:700 .88rem 'DM Sans';display:flex;align-items:center;gap:8px;cursor:pointer;transition:background .2s ease;white-space:nowrap}
  .search-submit:hover{background:var(--green-dark)}
  .suggest-panel{position:absolute;top:calc(100% + 8px);left:0;right:0;background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 16px 40px rgba(32,61,39,.14);padding:16px;opacity:0;visibility:hidden;transform:translateY(-6px);transition:opacity .18s ease,transform .18s ease,visibility .18s ease;z-index:50}
  .search-shell.active .suggest-panel{opacity:1;visibility:visible;transform:translateY(0)}
  .suggest-heading{font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px}
  .suggest-chips{display:flex;flex-wrap:wrap;gap:8px}
  .suggest-chip{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;background:var(--paper);border:1px solid var(--line);font-size:.82rem;font-weight:500;color:var(--ink);cursor:pointer;transition:.15s ease}
  .suggest-chip:hover{background:#eaf2e9;border-color:var(--green);color:var(--green-dark)}
  .suggest-chip i{font-size:.72rem;color:var(--muted)}
  .suggest-divider{height:1px;background:var(--line);margin:14px 0}
  .suggest-categories{display:flex;flex-direction:column;gap:2px}
  .suggest-cat-link{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;font-size:.85rem;font-weight:500;color:var(--ink);cursor:pointer;transition:background .15s ease;background:transparent;border:0;width:100%;text-align:left}
  .suggest-cat-link:hover{background:var(--paper)}
  .suggest-cat-link i{width:16px;color:var(--gold)}
  @media(max-width:600px){.search-cat select{padding:0 30px 0 14px;font-size:.8rem}.search-submit span{display:none}.search-submit{padding:0 18px}}
</style>
<section class="page-hero"><div class="container"><div class="eyebrow">The farm stand</div><h1>Good things,<br>grown close.</h1><p>Fresh eggs, healthy starts and the essentials that make caring for your flock feel simple.</p></div></section>
<section class="section"><div class="container shop-layout"><aside class="filters"><h3>Browse</h3><a class="<?= !$category?'active':'' ?>" href="shop.php">All products</a><?php foreach($categories as $item): ?><a class="<?= $category===$item['name']?'active':'' ?>" href="shop.php?category=<?= urlencode($item['name']) ?>"><?= e($item['name']) ?></a><?php endforeach; ?><hr><p>Every order is packed with care and prepared for local delivery.</p></aside><div><div class="shop-toolbar"><strong><?= count($products) ?> products</strong><form method="get" action="shop.php" class="shop-search-form"><div class="search-shell" id="searchShell"><div class="search-bar"><div class="search-cat"><select id="catSelect" name="category"><option value="">All Categories</option><?php foreach($categories as $item): ?><option value="<?= e($item['name']) ?>" <?= $category===$item['name']?'selected':'' ?>><?= e($item['name']) ?></option><?php endforeach; ?></select><i class="fa-solid fa-chevron-down"></i></div><div class="search-input-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" id="searchInput" name="q" value="<?= e($search) ?>" placeholder="Search products, e.g. 'day-old chicks'" autocomplete="off"></div><button class="search-submit" type="submit"><i class="fa-solid fa-magnifying-glass"></i><span>Search</span></button></div><div class="suggest-panel" id="suggestPanel"><div class="suggest-heading">Trending Searches</div><div class="suggest-chips"><span class="suggest-chip" data-value="Layers mash 10kg"><i class="fa-solid fa-arrow-trend-up"></i>Layers mash 10kg</span><span class="suggest-chip" data-value="Kienyeji day-old chicks"><i class="fa-solid fa-arrow-trend-up"></i>Kienyeji day-old chicks</span><span class="suggest-chip" data-value="Kienyeji fertile eggs"><i class="fa-solid fa-arrow-trend-up"></i>Kienyeji fertile eggs</span><span class="suggest-chip" data-value="Farm crate"><i class="fa-solid fa-arrow-trend-up"></i>Farm crate</span><span class="suggest-chip" data-value="Growers mash"><i class="fa-solid fa-arrow-trend-up"></i>Growers mash</span><span class="suggest-chip" data-value="Chick mash"><i class="fa-solid fa-arrow-trend-up"></i>Chick mash</span></div><div class="suggest-divider"></div><div class="suggest-heading">Quick Links</div><div class="suggest-categories"><button type="submit" name="q" value="Fresh Eggs" class="suggest-cat-link"><i class="fa-solid fa-egg"></i>Fresh Eggs</button><button type="submit" name="q" value="Day-old Chicks" class="suggest-cat-link"><i class="fa-solid fa-dove"></i>Day-old Chicks</button><button type="submit" name="q" value="Feed" class="suggest-cat-link"><i class="fa-solid fa-wheat-awn"></i>Feed &amp; Supplements</button></div></div></div></form></div><div class="product-grid"><?php foreach($products as $product): ?><article class="product-card"><a href="product.php?id=<?= (int)$product['id'] ?>"><div class="product-image <?= str_contains(strtolower($product['category']), 'egg')?'eggs':(str_contains(strtolower($product['category']),'feed')?'feed':'chicks') ?>"><img src="<?= e($product['image'] ?: 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=700&q=80') ?>" alt="<?= e($product['name']) ?>" width="700" height="500" loading="lazy"><span class="badge"><?= e(stock_message((int)$product['stock'])) ?></span></div></a><div class="product-info"><div class="rating">★★★★★ <span>(<?= e((string)$product['rating']) ?>)</span></div><h3><?= e($product['name']) ?></h3><p><?= e($product['description']) ?></p><div class="product-meta"><span class="price"><?= money($product['price']) ?></span><?php if($product['stock']>0): ?><button class="btn btn-outline" data-add-cart="<?= (int)$product['id'] ?>" data-csrf="<?= e(csrf_token()) ?>">Add to cart</button><?php else: ?><button class="btn btn-outline" disabled>Currently unavailable</button><?php endif; ?></div></div></article><?php endforeach; ?></div><?php if(!$products): ?><div class="empty-state"><div class="empty-icon">⌕</div><h3>No products found</h3><p>Try another search or browse the full farm collection.</p><a class="btn btn-primary" href="shop.php">View all products</a></div><?php endif; ?></div></div></section>
<script>
  const shell = document.getElementById('searchShell');
  const input = document.getElementById('searchInput');
  if (shell && input) {
    input.addEventListener('focus', () => shell.classList.add('active'));
    document.addEventListener('click', (event) => {
      if (!shell.contains(event.target)) shell.classList.remove('active');
    });
    document.querySelectorAll('.suggest-chip').forEach((chip) => {
      chip.addEventListener('click', () => {
        input.value = chip.dataset.value || chip.textContent.trim();
        input.closest('form')?.submit();
      });
    });
  }
</script>
<?php include 'includes/footer.php'; ?>
