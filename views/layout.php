<?php
$title = $title ?? 'NEXORA TUMBLER';
$view = $GLOBALS['_page_view'] ?? '404';
$route = $_GET['r'] ?? 'home';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title)?> — NEXORA</title>
<link rel="stylesheet" href="public/app.css">
</head>
<body>
<header class="topbar">
  <div class="container nav">
    <a class="brand" href="<?=route_url('home')?>"><span class="logo-mark"></span><span>NEXORA</span></a>
    <form class="nav-search" method="get">
      <input type="hidden" name="r" value="products">
      <input name="q" placeholder="Cari CORKCICLE, ukuran, atau warna..." value="<?=h($_GET['q'] ?? '')?>" aria-label="Cari produk">
      <button type="submit" aria-label="Cari">⌕</button>
    </form>
    <nav class="navlinks">
      <a href="<?=route_url('home')?>">Beranda</a>
      <a href="<?=route_url('products')?>">Produk</a>
      <a href="<?=route_url('services')?>">Bantuan</a>
      <a href="<?=route_url('faq')?>">FAQ</a>
    </nav>
    <div class="navactions">
      <?php if(auth()): ?>
        <a class="nav-pill" href="<?=route_url('notifications')?>">🔔</a>
        <a class="nav-pill cart-pill" href="<?=route_url('cart')?>">🛒 <span class="count"><?=cart_count()?></span></a>
        <a class="nav-pill" href="<?=route_url(auth()['role']==='admin'?'admin':'dashboard')?>"><?=h(auth()['name'])?></a>
      <?php else: ?>
        <a class="btn btn-small btn-outline" href="<?=route_url('login')?>">Masuk</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main class="container main">
<?php if($m=flash('success')): ?><div class="flash success">✅ <?=h($m)?></div><?php endif; ?>
<?php if($m=flash('error')): ?><div class="flash error">⚠️ <?=h($m)?></div><?php endif; ?>
<?php include __DIR__ . '/' . ($view ?? '404') . '.php'; ?>
</main>
<nav class="mobile-bottom">
  <a class="<?=in_array($route,['home','products','product'],true)?'active':''?>" href="<?=route_url('home')?>">⌂<span>Beranda</span></a>
  <a class="<?=in_array($route,['products','product'],true)?'active':''?>" href="<?=route_url('products')?>">▦<span>Produk</span></a>
  <a class="<?=in_array($route,['cart','checkout','payment'],true)?'active':''?>" href="<?=route_url('cart')?>">🛒<span>Keranjang</span></a>
  <a class="<?=in_array($route,['orders','order','tracking','notifications','dashboard'],true)?'active':''?>" href="<?=route_url(auth()?'orders':'login')?>">◉<span><?=auth()?'Pesanan':'Masuk'?></span></a>
</nav>
<footer class="footer"><div class="container footer-row"><div><strong>NEXORA</strong><div class="muted">Toko online CORKCICLE dengan foto produk asli dan alur belanja yang simpel dan jelas.</div></div><div class="muted">Pembayaran di demo ini bersifat simulasi.</div></div></footer>
</body>
</html>
