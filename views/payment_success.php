<section class="section">
  <div class="success-screen">
    <div class="success-icon">✓</div>
    <div class="eyebrow">TRANSAKSI BERHASIL</div>
    <h1>Pembayaran berhasil!</h1>
    <p class="muted">Pesanan Anda sudah tercatat. Simpan nomor pesanan ini untuk memudahkan pengecekan.</p>
    <div class="success-card">
      <div class="success-grid">
        <div><span>Nomor pesanan</span><b><?=h($order['order_code'])?></b></div>
        <div><span>Metode pembayaran</span><b><?=h($order['payment_method'])?></b></div>
        <div><span>Waktu bayar</span><b><?=h(date('d/m/Y H:i',strtotime($order['paid_at']??$order['created_at'])))?></b></div>
        <div><span>Total</span><b><?=rupiah($order['total'])?></b></div>
      </div>
    </div>
    <div class="success-actions">
      <a class="btn btn-outline" href="<?=route_url('order',['id'=>$order['id']])?>">Lihat detail pesanan</a>
      <a class="btn btn-primary" href="<?=route_url('orders')?>">Pesanan saya</a>
    </div>
    <div class="helper" style="margin-top:12px">📄 Invoice PDF sudah dibuat dan dapat diunduh dari halaman detail pesanan.</div>
  </div>
</section>
