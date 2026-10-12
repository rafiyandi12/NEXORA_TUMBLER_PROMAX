<section class="section">
  <div class="eyebrow">AKTIVITAS AKUN</div><h1>Pesanan saya</h1>
  <p class="muted">Semua transaksi tersimpan di sini. Buka pesanan untuk pembayaran, invoice, dan tracking.</p>
  <div class="order-tabs"><a class="order-tab active" href="<?=route_url('orders')?>">Semua</a><span class="order-tab">Belum bayar</span><span class="order-tab">Diproses</span><span class="order-tab">Dikirim</span><span class="order-tab">Selesai</span></div>
  <div class="stack">
  <?php foreach($orders as $o):
    $statusClass=''; if(in_array($o['status'],['completed','paid'],true))$statusClass='green'; elseif(in_array($o['status'],['cancelled','expired'],true))$statusClass='red'; elseif($o['status']==='waiting_payment')$statusClass='orange';
    $statusLabel=function_exists('status_label')?status_label($o['status']):$o['status'];
  ?>
    <a class="card order-card" href="<?=route_url('order',['id'=>$o['id']])?>">
      <div class="order-head"><div><b><?=h($o['order_code'])?></b><small><?=h(date('d M Y • H:i',strtotime($o['created_at'])))?></small></div><span class="status <?=$statusClass?>"><?=h($statusLabel)?></span></div>
      <div class="order-product"><div style="width:64px;height:64px;border-radius:10px;background:#171d25;display:grid;place-items:center;font-size:26px">📦</div><div><b>Total <?=rupiah($o['total'])?></b><small><?=h($o['shipping_method'])?><?php if($o['tracking_number']): ?> • <?=h($o['tracking_number'])?><?php endif; ?></small></div></div>
      <div class="order-foot"><div><span class="muted">Status pembayaran:</span> <b><?=h($o['payment_status'])?></b></div><span class="text-link">Buka detail →</span></div>
    </a>
  <?php endforeach; ?>
  <?php if(!$orders): ?><div class="empty card"><div style="font-size:36px">📦</div><h3>Belum ada pesanan</h3><p class="muted">Pesanan yang berhasil dibuat akan muncul di sini.</p><a class="btn btn-primary" href="<?=route_url('products')?>">Belanja sekarang</a></div><?php endif; ?>
  </div>
</section>
