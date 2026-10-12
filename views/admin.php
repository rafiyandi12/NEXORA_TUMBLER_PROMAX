<section class="section">
  <div class="eyebrow">ADMIN</div><div class="section-head"><div><h1>Dashboard toko</h1><div class="muted">Pantau order, pembayaran, QC, dan produk dari satu halaman.</div></div><a class="btn btn-outline" href="<?=route_url('admin_report')?>">Lihat laporan</a></div>
  <div class="admin-grid">
    <div class="card"><span class="muted">Pelanggan</span><strong class="kpi"><?=h($stats['users'])?></strong></div>
    <div class="card"><span class="muted">Produk aktif</span><strong class="kpi"><?=h($stats['products'])?></strong></div>
    <div class="card"><span class="muted">Total order</span><strong class="kpi"><?=h($stats['orders'])?></strong></div>
    <div class="card"><span class="muted">Pendapatan dibayar</span><strong class="kpi"><?=rupiah($stats['revenue'])?></strong></div>
  </div>
  <div class="admin-actions"><a class="btn btn-primary" href="<?=route_url('admin_products')?>">📦 Produk & stok</a><a class="btn btn-outline" href="<?=route_url('admin_payments')?>">💳 Pembayaran</a><a class="btn btn-outline" href="<?=route_url('admin_report')?>">📊 Laporan</a></div>
  <div class="stack">
  <?php foreach($orders as $o): ?>
    <div class="card admin-order">
      <div class="section-title"><div><b><?=h($o['order_code'])?></b><div class="muted"><?=h($o['customer'])?> • <?=rupiah($o['total'])?></div></div><a class="text-link" href="<?=route_url('admin_order',['id'=>$o['id']])?>">Detail →</a></div>
      <div class="notice">Status: <b><?=h(status_label($o['status']))?></b> • pembayaran: <b><?=h($o['payment_status'])?></b></div>
      <form method="post" class="admin-form"><input type="hidden" name="_csrf" value="<?=h(csrf())?>"><input type="hidden" name="id" value="<?=$o['id']?>"><select class="input" name="status"><?php foreach(['waiting_payment','paid','processing','quality_check','packed','shipped','completed','cancelled'] as $st):?><option value="<?=$st?>" <?=$o['status']===$st?'selected':''?>><?=h(status_label($st))?></option><?php endforeach;?></select><input class="input" name="tracking_number" value="<?=h($o['tracking_number']??'')?>" placeholder="Nomor resi bila sudah dikirim"><button class="btn btn-primary" formaction="<?=route_url('admin_orders')?>">Simpan status</button></form>
      <details><summary>Isi Quality Check</summary><form method="post" action="<?=route_url('admin_qc')?>" class="stack" style="margin-top:10px"><input type="hidden" name="_csrf" value="<?=h(csrf())?>"><input type="hidden" name="order_id" value="<?=$o['id']?>"><div style="display:grid;grid-template-columns:1fr 1fr;gap:8px"><input class="input" name="physical_condition" placeholder="Kondisi fisik"><input class="input" name="lid_condition" placeholder="Tutup"><input class="input" name="seal_condition" placeholder="Seal"><input class="input" name="leak_test" placeholder="Tes kebocoran"><input class="input" name="finishing_condition" placeholder="Finishing"><input class="input" name="completeness" placeholder="Kelengkapan"></div><input class="input" name="notes" placeholder="Catatan QC"><select class="input" name="result"><option value="pending">Pending</option><option value="passed">Lulus</option><option value="failed">Gagal</option></select><button class="btn btn-outline btn-small">Simpan QC</button></form></details>
    </div>
  <?php endforeach; ?>
  </div>
</section>
