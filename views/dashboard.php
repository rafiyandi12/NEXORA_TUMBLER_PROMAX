<?php $orderCount=(int)scalar('SELECT COUNT(*) FROM orders WHERE user_id=?',[auth()['id']]); $pending=(int)scalar("SELECT COUNT(*) FROM orders WHERE user_id=? AND payment_status='pending'",[auth()['id']]); $unread=(int)scalar('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0',[auth()['id']]); ?>
<section class="section">
  <div class="eyebrow">BERANDA AKUN</div><h1>Halo, <?=h(auth()['name'])?> 👋</h1><p class="muted">Kelola belanja dan pesanan kamu dari satu tempat.</p>
  <div class="trust-banner"><div><div class="eyebrow">CORKCICLE JASTIP</div><h3>Belanja lebih tenang</h3><p>Komitmen pada keaslian produk, penggunaan foto produk asli, dan pemantauan pesanan dari pembayaran sampai proses pengiriman.</p></div><div class="trust-points"><span>✓ Foto produk asli</span><span>✓ Pesanan tersimpan</span><span>✓ Invoice PDF</span></div></div>
  <div class="admin-grid" style="margin-top:18px">
    <a class="card" href="<?=route_url('cart')?>"><span class="muted">Keranjang</span><strong class="kpi"><?=cart_count()?></strong><span class="text-link">Lihat keranjang →</span></a>
    <a class="card" href="<?=route_url('orders')?>"><span class="muted">Pesanan</span><strong class="kpi"><?=$orderCount?></strong><span class="text-link">Lihat semua →</span></a>
    <a class="card" href="<?=route_url('orders')?>"><span class="muted">Menunggu bayar</span><strong class="kpi"><?=$pending?></strong><span class="text-link">Bayar sekarang →</span></a>
    <a class="card" href="<?=route_url('notifications')?>"><span class="muted">Notifikasi baru</span><strong class="kpi"><?=$unread?></strong><span class="text-link">Buka notifikasi →</span></a>
  </div>
  <div class="section split" style="margin-top:18px">
    <div class="card"><div class="eyebrow">BELANJA CEPAT</div><h3>Butuh tumbler?</h3><p class="muted">Cari produk, pilih variant, lalu gunakan tombol Beli sekarang untuk langsung ke checkout.</p><a class="btn btn-primary" href="<?=route_url('products')?>">Cari produk</a></div>
    <div class="card"><div class="eyebrow">AKUN</div><h3>Data Anda</h3><p class="muted"><?=h(auth()['email'])?></p><a class="btn btn-outline" href="<?=route_url('profile')?>">Profil & alamat</a></div>
  </div>
</section>
