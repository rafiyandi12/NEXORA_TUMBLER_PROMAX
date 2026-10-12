<section class="section">
  <a class="text-link" href="<?=route_url('products')?>">← Kembali ke produk</a>
  <div class="product-detail">
    <div class="detail-photo"><img id="detailImage" src="<?=h($p['image_url'])?>" alt="<?=h($p['name'])?>" onerror="this.style.display='none';this.parentElement.classList.add('photo-missing')"></div>
    <div class="detail-copy">
      <div class="eyebrow"><?=h($p['brand'])?></div>
      <h1><?=h($p['name'])?></h1>
      <div class="price big"><?=rupiah($p['price'])?></div>
      <p class="muted"><?=h($p['description'])?></p>
      <div class="facts">
        <div><span>Ukuran</span><b><?=h($p['capacity'])?></b></div>
        <div><span>Material</span><b><?=h($p['material'])?></b></div>
        <div><span>Merek</span><b><?=h($p['brand'])?></b></div>
      </div>
      <?php
        $thermalCold = null; $thermalHot = null;
        if (stripos($p['name'], 'Sport Canteen') !== false) { $thermalCold = 'hingga 25 jam dingin'; $thermalHot = 'hingga 12 jam panas'; }
        elseif (stripos($p['name'], 'Stemless') !== false || stripos($p['name'], 'Tumbler') !== false) { $thermalCold = 'hingga 9 jam dingin'; $thermalHot = 'hingga 3 jam panas'; }
        elseif (stripos($p['name'], 'Coffee Mug') !== false) { $thermalHot = 'hingga 3 jam panas'; }
        elseif (stripos($p['name'], 'Cold Cup') !== false) { $thermalCold = 'hingga 12 jam dingin'; }
      ?>
      <?php if($thermalCold || $thermalHot): ?>
      <div class="performance-card">
        <div><span>Performa suhu</span><b><?=h($thermalCold ?: '—')?></b></div>
        <div><span>Minuman panas</span><b><?=h($thermalHot ?: '—')?></b></div>
      </div>
      <?php endif; ?>
      <?php if(auth()): ?>
      <form method="post" action="<?=route_url('cart_add')?>" class="purchase-card" id="purchaseForm">
        <input type="hidden" name="_csrf" value="<?=h(csrf())?>">
        <label class="field"><span>1. Pilih variant / warna</span><select class="input" name="variant_id" id="variantSelect"><?php foreach($variants as $v): ?><option value="<?=h($v['id'])?>" data-stock="<?=h($v['stock'])?>" data-image="<?=h($v['image_url'])?>"><?=h($v['name'])?> — stok <?=h($v['stock'])?></option><?php endforeach; ?></select></label>
        <label class="field"><span>2. Tentukan jumlah</span><div class="qty-row"><button type="button" id="minus">−</button><input class="input" type="number" name="quantity" min="1" max="<?=h($variants[0]['stock'] ?? 1)?>" value="1" id="qty"><button type="button" id="plus">+</button></div></label>
        <div class="action-row"><button class="btn btn-outline full" type="submit">🛒 Tambah ke keranjang</button><button class="btn btn-primary full" type="submit" formaction="<?=route_url('buy_now')?>">⚡ Beli sekarang</button></div>
        <div class="helper">💡 <span><b>Beli sekarang</b> langsung membawa pilihan ini ke checkout. <b>Tambah ke keranjang</b> cocok untuk membeli beberapa produk sekaligus.</span></div>
      </form>
      <?php else: ?>
        <div class="card"><h3>Satu langkah lagi</h3><p class="muted">Masuk atau daftar agar pilihan produk bisa disimpan ke keranjang dan diproses sampai pembayaran.</p><div class="action-row"><a class="btn btn-outline" href="<?=route_url('login')?>">Masuk</a><a class="btn btn-primary" href="<?=route_url('register')?>">Daftar</a></div></div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php if(auth()): ?>
<div class="sticky-buy"><button class="btn btn-outline" type="submit" form="purchaseForm">🛒 Keranjang</button><button class="btn btn-primary" type="submit" form="purchaseForm" formaction="<?=route_url('buy_now')?>">⚡ Beli sekarang</button></div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const s=document.getElementById('variantSelect'),q=document.getElementById('qty'),i=document.getElementById('detailImage'),minus=document.getElementById('minus'),plus=document.getElementById('plus');
 const sync=()=>{const o=s.options[s.selectedIndex];const stock=Number(o.dataset.stock||1);q.max=stock;if(Number(q.value)>stock)q.value=stock;if(o.dataset.image)i.src=o.dataset.image;};
 s?.addEventListener('change',sync);minus?.addEventListener('click',()=>{q.value=Math.max(1,Number(q.value)-1)});plus?.addEventListener('click',()=>{q.value=Math.min(Number(q.max||99),Number(q.value)+1)});sync();
});
</script>
<?php endif; ?>
