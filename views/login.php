<div class="auth-shell">
  <div class="auth-card">
    <div class="auth-brand"><span class="logo-mark"></span><span>NEXORA</span></div>
    <div class="eyebrow">MASUK</div>
    <h1>Selamat datang kembali 👋</h1>
    <p class="muted">Masuk untuk menyimpan keranjang, checkout, melihat pesanan, tracking, dan invoice.</p>
    <form method="post" class="form">
      <input type="hidden" name="_csrf" value="<?=h(csrf())?>">
      <label>Email<input class="input" type="email" name="email" placeholder="nama@email.com" required autocomplete="email"></label>
      <label>Password<input class="input" type="password" name="password" placeholder="Masukkan password" required autocomplete="current-password"></label>
      <button class="btn btn-primary full">Masuk ke NEXORA</button>
    </form>
    <div class="demo-box"><b>Akun demo siap pakai</b><span>Customer: customer@nexora.com / customer123</span><span>Admin: admin@nexora.com / admin123</span></div>
    <p class="muted center">Belum punya akun? <a class="text-link" href="<?=route_url('register')?>">Daftar di sini</a></p>
  </div>
</div>
