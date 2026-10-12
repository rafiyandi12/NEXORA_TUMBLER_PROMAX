<section class="hero">
  <div class="hero-copy">
    <div class="eyebrow">CORKCICLE • NEXORA</div>
    <h1>Belanja tumbler tanpa bikin bingung.</h1>
    <p>Pilih produk, atur jumlah, cek alamat, pilih kurir, bayar, lalu pantau pesanan. Alurnya dibuat sederhana seperti marketplace agar mudah diikuti, termasuk untuk pengguna yang jarang belanja online.</p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="<?=route_url('products')?>">🛍️ Mulai belanja</a>
      <?php if(auth()): ?><a class="btn btn-outline" href="<?=route_url('orders')?>">Lihat pesanan saya</a><?php else: ?><a class="btn btn-outline" href="<?=route_url('register')?>">Buat akun</a><?php endif; ?>
    </div>
    <div class="chip-row" style="margin-top:20px"><span class="chip active">CORKCICLE</span><span class="chip">Foto produk asli</span><span class="chip">Checkout jelas</span><span class="chip">Invoice PDF</span></div>
  </div>
  <?php $heroImage = $featured[0]['image_url'] ?? ''; $heroName = $featured[0]['name'] ?? 'CORKCICLE'; ?>
  <div class="hero-art"><img class="hero-product-photo" src="<?=h($heroImage)?>" alt="<?=h($heroName)?>" loading="eager" onerror="this.style.display='none';this.parentElement.classList.add('photo-missing')"><div class="hero-label"><b>Foto produk asli</b><span><?=h($heroName)?> • proses belanja sederhana</span></div></div>
</section>

<section class="section">
  <div class="section-head"><div><div class="eyebrow">PILIH SESUAI KEBUTUHAN</div><h2>Produk CORKCICLE</h2><div class="muted">Katalog diperluas dengan produk CORKCICLE dan foto produk dari sumber brand.</div></div><a class="text-link" href="<?=route_url('products')?>">Lihat semua →</a></div>
  <div class="grid products-grid">
  <?php foreach($featured as $p): ?>
    <article class="product-card">
      <a href="<?=route_url('product',['id'=>$p['id']])?>">
        <div class="photo"><span class="product-badge">CORKCICLE</span><span class="product-badge green" style="left:auto;right:10px">Ready</span><img src="<?=h($p['image_url'])?>" alt="<?=h($p['name'])?>" onerror="this.style.display='none';this.parentElement.classList.add('photo-missing')"></div>
        <div class="product-info"><div class="product-name"><?=h($p['name'])?></div><div class="meta"><?=h($p['capacity'])?> • <?=h($p['material'])?></div><div class="price"><?=rupiah($p['price'])?></div><div class="product-bottom"><span class="stock">Stok siap dipilih</span><span class="mini-btn">Lihat detail</span></div></div>
      </a>
    </article>
  <?php endforeach; ?>
  </div>
</section>

<section class="section">
  <div class="section-head"><div><div class="eyebrow">CARA BELANJA</div><h2>3 langkah yang mudah diikuti</h2></div></div>
  <div class="feature-grid">
    <div class="card feature-card"><div><div class="eyebrow">01</div><h3>Pilih produk</h3><p class="muted">Lihat foto, ukuran, material, variant, harga, dan stok sebelum menambah ke keranjang.</p></div><a class="text-link" href="<?=route_url('products')?>">Cari produk →</a></div>
    <div class="card feature-card"><div><div class="eyebrow">02</div><h3>Checkout</h3><p class="muted">Masukkan alamat, pilih kurir, lalu pilih metode pembayaran pada satu halaman.</p></div><a class="text-link" href="<?=route_url(auth()?'cart':'login')?>">Mulai checkout →</a></div>
    <div class="card feature-card"><div><div class="eyebrow">03</div><h3>Pantau pesanan</h3><p class="muted">Setelah pembayaran berhasil, pesanan tersimpan dan dapat dilihat kembali bersama invoice.</p></div><a class="text-link" href="<?=route_url(auth()?'orders':'login')?>">Cek pesanan →</a></div>
  </div>
</section>
