<div class="auth-shell">
  <div class="auth-card">
    <div class="auth-brand"><span class="logo-mark"></span><span>NEXORA</span></div>
    <div class="eyebrow">DAFTAR</div>
    <h1>Buat akun baru</h1>
    <p class="muted">Akun dibutuhkan agar keranjang, pesanan, pembayaran, dan invoice tersimpan.</p>
    <form method="post" class="form">
      <input type="hidden" name="_csrf" value="<?=h(csrf())?>">
      <label>Nama lengkap<input class="input" name="name" placeholder="Nama Anda" required></label>
      <label>Email<input class="input" type="email" name="email" placeholder="nama@email.com" required></label>
      <label>Password<input class="input" type="password" name="password" minlength="6" placeholder="Minimal 6 karakter" required></label>
      <button class="btn btn-primary full">Buat akun</button>
    </form>
    <p class="muted center">Sudah punya akun? <a class="text-link" href="<?=route_url('login')?>">Masuk</a></p>
  </div>
</div>
