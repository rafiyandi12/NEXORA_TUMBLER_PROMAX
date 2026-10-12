<section class="section narrow payment-box">
  <div class="eyebrow">LANGKAH 5</div>
  <div class="card payment-box">
    <div class="section-title"><span class="status orange">MENUNGGU PEMBAYARAN</span><span class="muted"><?=h($order['order_code'])?></span></div>
    <h1>Bayar pesanan Anda</h1>
    <p class="muted">Pesanan sudah dibuat. Sekarang selesaikan pembayaran dengan kode di bawah. Di demo ini pembayaran dianggap berhasil hanya jika kode yang dimasukkan sama persis.</p>
    <div class="payment-total"><small>Total yang perlu dibayar</small><strong><?=rupiah($order['total'])?></strong></div>
    <div class="payment-code"><small>Kode pembayaran Anda</small><strong id="payCode"><?=h($order['payment_code'])?></strong><button type="button" class="btn btn-ghost btn-small" onclick="navigator.clipboard?.writeText(document.getElementById('payCode').textContent).then(()=>this.textContent='✓ Tersalin')">📋 Salin kode</button></div>
    <div id="countdown" class="countdown" data-exp="<?=h($order['payment_expires_at'])?>">15:00</div>
    <div class="helper">⏱️ Kode berlaku sampai waktu habis. Bila waktu habis, pesanan otomatis masuk status kadaluarsa pada demo ini.</div>
    <form method="post" action="<?=route_url('verify_payment')?>" class="form">
      <input type="hidden" name="_csrf" value="<?=h(csrf())?>"><input type="hidden" name="order_id" value="<?=$order['id']?>">
      <label>Masukkan kode pembayaran<input class="input" name="payment_code" placeholder="Contoh: PAY-AB12CD34" autocomplete="off" required></label>
      <button class="btn btn-primary full">✅ Saya sudah bayar — verifikasi</button>
    </form>
    <div class="payment-links"><a class="text-link" href="<?=route_url('order',['id'=>$order['id']])?>">Lihat detail order</a><a class="text-link" href="<?=route_url('orders')?>">Pesanan saya</a></div>
  </div>
</section>
<script>
const c=document.getElementById('countdown');
function tick(){const e=new Date(c.dataset.exp).getTime(),n=Date.now(),x=Math.max(0,Math.floor((e-n)/1000));c.textContent=String(Math.floor(x/60)).padStart(2,'0')+':'+String(x%60).padStart(2,'0');if(x===0){location.reload();}}tick();setInterval(tick,1000);
</script>
