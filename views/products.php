<section class="section">
  <div class="section-head"><div><div class="eyebrow">CATALOG</div><h1>Pilih produk</h1><div class="muted">Temukan produk CORKCICLE berdasarkan nama, ukuran, atau warna.</div></div><div class="chip active"><?=$total?> produk</div></div>
  <form class="filters" method="get">
    <input type="hidden" name="r" value="products">
    <input class="input" name="q" value="<?=h($q)?>" placeholder="🔎 Cari misalnya: Classic, 16 oz, Lavender...">
    <select class="input" name="sort" aria-label="Urutkan">
      <option value="latest" <?=$sort==='latest'?'selected':''?>>Terbaru</option>
      <option value="price_asc" <?=$sort==='price_asc'?'selected':''?>>Harga termurah</option>
      <option value="price_desc" <?=$sort==='price_desc'?'selected':''?>>Harga termahal</option>
      <option value="name" <?=$sort==='name'?'selected':''?>>Nama A–Z</option>
    </select>
    <button class="btn btn-primary" type="submit">Terapkan</button>
  </form>
  <div class="section-head" style="margin-bottom:10px"><div class="muted">Menampilkan <?=count($products)?> dari <?=$total?> produk</div></div>
  <div class="grid products-grid">
  <?php foreach($products as $p): ?>
    <article class="product-card">
      <a href="<?=route_url('product',['id'=>$p['id']])?>">
        <div class="photo"><span class="product-badge">FOTO PRODUK ASLI</span><img src="<?=h($p['image_url'])?>" alt="<?=h($p['name'])?>" loading="lazy" onerror="this.style.display='none';this.parentElement.classList.add('photo-missing')"></div>
        <div class="product-info"><div class="product-name"><?=h($p['name'])?></div><div class="meta"><?=h($p['capacity'])?> • <?=h($p['variant_name'])?></div><div class="meta"><?=h($p['brand'])?> • <?=h($p['material'])?></div><div class="price"><?=rupiah($p['price'])?></div><div class="product-bottom"><span class="stock">Stok <?=h($p['stock'])?></span><span class="mini-btn primary">Pilih</span></div></div>
      </a>
    </article>
  <?php endforeach; ?>
  </div>
  <?php if(!$products): ?><div class="empty card"><h3>Produk tidak ditemukan</h3><p class="muted">Coba kata kunci yang lebih sederhana.</p><a class="btn btn-outline" href="<?=route_url('products')?>">Reset pencarian</a></div><?php endif; ?>
  <?php if($pages>1): ?><div class="pagination"><?php for($i=1;$i<=$pages;$i++): ?><a class="page <?=($i===$pageNum?'active':'')?>" href="<?=route_url('products',['q'=>$q,'sort'=>$sort,'page'=>$i])?>"><?=$i?></a><?php endfor; ?></div><?php endif; ?>
</section>
