<section class="section">
  <div class="eyebrow">LANGKAH 1</div><h1>Keranjang belanja</h1>
  <div class="stepper"><div class="step active">1. Keranjang</div><div class="step">2. Alamat</div><div class="step">3. Pengiriman</div><div class="step">4. Pembayaran</div></div>
  <?php if(!$items): ?>
    <div class="empty card"><div style="font-size:38px">🛒</div><h2>Keranjang masih kosong</h2><p class="muted">Belum ada produk yang dipilih. Mulai dari katalog CORKCICLE.</p><a class="btn btn-primary" href="<?=route_url('products')?>">Lihat produk</a></div>
  <?php else: ?>
    <div class="cart-layout">
      <form method="post" action="<?=route_url('cart_update')?>" class="stack">
        <input type="hidden" name="_csrf" value="<?=h(csrf())?>">
        <?php foreach($items as $it): ?>
        <article class="card cart-item">
          <img src="<?=h($it['image_url'])?>" alt="" onerror="this.style.display='none';this.parentElement.classList.add('photo-missing')">
          <div class="cart-main"><b><?=h($it['product_name'])?></b><small><?=h($it['variant_name'])?> • <?=h($it['capacity'])?></small><span class="price"><?=rupiah($it['price'])?></span><div class="cart-controls"><div class="qty-row" style="grid-template-columns:38px 64px 38px"><button type="button" onclick="this.nextElementSibling.stepDown();this.nextElementSibling.dispatchEvent(new Event('change'))">−</button><input class="input" type="number" min="1" max="<?=h($it['stock'])?>" value="<?=h($it['quantity'])?>" name="qty[<?=h($it['id'])?>]"><button type="button" onclick="this.previousElementSibling.stepUp();this.previousElementSibling.dispatchEvent(new Event('change'))">+</button></div><button class="btn btn-danger btn-small cart-remove" type="submit" formaction="<?=route_url('cart_remove')?>" name="id" value="<?=h($it['id'])?>">Hapus</button></div></div>
          <strong><?=rupiah($it['price']*$it['quantity'])?></strong>
        </article>
        <?php endforeach; ?>
        <button class="btn btn-outline" type="submit">Simpan perubahan jumlah</button>
      </form>
      <aside class="card summary-card">
        <div class="eyebrow">RINGKASAN</div><h3 style="margin:6px 0 12px">Siap checkout?</h3>
        <div class="summary-row"><span>Subtotal</span><b><?=rupiah($subtotal)?></b></div>
        <div class="summary-row"><span>Pengiriman</span><span class="muted">Dipilih saat checkout</span></div>
        <div class="summary-row total"><span>Total barang</span><b><?=rupiah($subtotal)?></b></div>
        <p class="summary-note">Biaya pengiriman ditentukan setelah memilih kurir. Tidak ada biaya layanan pada demo ini.</p>
        <a class="btn btn-primary full" href="<?=route_url('checkout')?>">Lanjut ke checkout →</a>
        <a class="btn btn-ghost full" href="<?=route_url('products')?>" style="margin-top:8px">Tambah produk lain</a>
      </aside>
    </div>
  <?php endif; ?>
</section>
