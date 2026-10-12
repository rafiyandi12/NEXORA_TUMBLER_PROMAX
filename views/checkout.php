<section class="section">
  <div class="eyebrow">LANGKAH 2–4</div><h1>Checkout</h1>
  <div class="stepper"><div class="step done">1. Keranjang</div><div class="step active">2. Alamat</div><div class="step active">3. Pengiriman</div><div class="step active">4. Pembayaran</div></div>
  <div class="notice">✅ Periksa kembali data sebelum menekan <b>Konfirmasi & buat pesanan</b>. Setelah itu Anda akan melihat kode pembayaran dan batas waktunya.</div>
  <form method="post" action="<?=route_url('place_order')?>" class="checkout-layout" style="margin-top:14px">
    <input type="hidden" name="_csrf" value="<?=h(csrf())?>">
    <div class="stack">
      <div class="card">
        <div class="section-title"><h3>📍 Alamat pengiriman</h3><a class="text-link" href="<?=route_url('profile')?>">Kelola alamat</a></div>
        <?php if(!empty($addresses)): ?>
          <div class="choice-list">
          <?php foreach($addresses as $idx=>$a): ?>
            <label class="choice"><input type="radio" name="address_id" value="<?=h($a['id'])?>" data-name="<?=h($a['recipient_name'])?>" data-phone="<?=h($a['phone'])?>" data-address="<?=h($a['address'])?>" data-city="<?=h($a['city'])?>" <?=$idx===0?'checked':''?>>
              <span class="choice-main"><span><b><?=h($a['recipient_name'])?></b> · <?=h($a['phone'])?><small><?=h($a['address'])?>, <?=h($a['city'])?><?=!empty($a['province'])?', '.h($a['province']):''?> <?=h($a['postal_code']??'')?></small></span><?php if($a['is_default']): ?><span class="address-badge">Alamat utama</span><?php endif; ?></span>
            </label>
          <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="helper">Belum ada alamat tersimpan. Isi data di bawah agar pesanan tetap bisa dilanjutkan.</div>
        <?php endif; ?>
        <div class="mini-divider"></div>
        <div class="field"><span>Nama penerima</span><input class="input" name="name" value="<?=h($defaultAddress['recipient_name']??auth()['name'])?>" required></div>
        <div class="field"><span>No. HP</span><input class="input" name="phone" value="<?=h($defaultAddress['phone']??'081234567890')?>" required></div>
        <div class="field"><span>Alamat lengkap</span><input class="input" name="address" value="<?=h($defaultAddress['address']??'Jl. Melati No. 12')?>" required></div>
        <div class="field"><span>Kota / Kabupaten</span><input class="input" name="city" value="<?=h($defaultAddress['city']??'Jakarta Selatan')?>" required></div>
      </div>

      <div class="card">
        <div class="section-title"><h3>🚚 Pilih pengiriman</h3><span class="muted">Biaya dan estimasi ditampilkan di sini</span></div>
        <div class="choice-list">
          <label class="choice"><input type="radio" name="shipping_method" value="JNE Reguler" checked><span class="choice-main"><span><b>JNE Reguler</b><small>Estimasi 2–3 hari</small></span><span class="choice-price">Rp 18.000</span></span></label>
          <label class="choice"><input type="radio" name="shipping_method" value="J&T Reguler"><span class="choice-main"><span><b>J&T Reguler</b><small>Estimasi 2–4 hari</small></span><span class="choice-price">Rp 20.000</span></span></label>
          <label class="choice"><input type="radio" name="shipping_method" value="SiCepat Reguler"><span class="choice-main"><span><b>SiCepat Reguler</b><small>Estimasi 2–3 hari</small></span><span class="choice-price">Rp 22.000</span></span></label>
        </div>
      </div>

      <div class="card">
        <div class="section-title"><h3>💳 Pilih pembayaran</h3><span class="muted">Kode diverifikasi setelah Anda memasukkannya</span></div>
        <div class="choice-list">
          <?php foreach(['BCA Virtual Account'=>'Kode pembayaran + batas waktu','BNI Virtual Account'=>'Kode pembayaran + batas waktu','QRIS'=>'Simulasi pembayaran','Gopay'=>'Simulasi pembayaran'] as $i=>$desc): ?>
          <label class="choice"><input type="radio" name="payment_method" value="<?=h($i)?>" <?=$i==='BCA Virtual Account'?'checked':''?>><span class="choice-main"><span><b><?=h($i)?></b><small><?=h($desc)?></small></span><span>›</span></span></label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <aside class="card summary-card">
      <div class="eyebrow">RINGKASAN PESANAN</div>
      <div class="stack" style="margin-top:10px">
        <?php foreach($items as $it): ?><div class="checkout-item"><img src="<?=h($it['image_url'])?>" alt="" onerror="this.style.display='none';this.parentElement.classList.add('photo-missing')"><span><b><?=h($it['product_name'])?></b><small><?=h($it['variant_name'])?> • <?=h($it['quantity'])?>x</small></span><strong><?=rupiah($it['price']*$it['quantity'])?></strong></div><?php endforeach; ?>
      </div>
      <div class="mini-divider"></div>
      <div class="summary-row"><span>Subtotal</span><b><?=rupiah($subtotal)?></b></div>
      <div class="summary-row"><span>Pengiriman</span><span>Mulai Rp 18.000</span></div>
      <div class="summary-row"><span>Biaya layanan</span><span>Rp 0</span></div>
      <div class="summary-row total"><span>Belum termasuk kurir</span><b><?=rupiah($subtotal)?></b></div>
      <label class="choice" style="margin-top:10px"><input type="checkbox" name="agree" required><span><b>Data sudah benar</b><small>Saya memahami pembayaran pada demo ini bersifat simulasi.</small></span></label>
      <button class="btn btn-primary full" style="margin-top:10px">Konfirmasi & buat pesanan</button>
      <a class="btn btn-ghost full" href="<?=route_url('cart')?>" style="margin-top:8px">← Kembali ke keranjang</a>
    </aside>
  </form>
</section>

<script>document.addEventListener('DOMContentLoaded',()=>{const radios=[...document.querySelectorAll('input[name=address_id]')];const n=document.querySelector('input[name=name]'),p=document.querySelector('input[name=phone]'),a=document.querySelector('input[name=address]'),c=document.querySelector('input[name=city]');const sync=()=>{const r=radios.find(x=>x.checked);if(!r)return; if(r.dataset.name)n.value=r.dataset.name; if(r.dataset.phone)p.value=r.dataset.phone; if(r.dataset.address)a.value=r.dataset.address; if(r.dataset.city)c.value=r.dataset.city;}; radios.forEach(r=>r.addEventListener('change',sync)); sync();});</script>
