<section class="section">
  <div class="eyebrow">AKUN SAYA</div><h1>Profil & alamat</h1>
  <div class="profile-layout">
    <form class="card stack" method="post" action="<?=route_url('profile')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf())?>">
      <div class="section-title"><h3>👤 Data akun</h3></div>
      <label class="field"><span>Nama</span><input class="input" name="name" value="<?=h(auth()['name'])?>" required></label>
      <label class="field"><span>Email</span><input class="input" value="<?=h(auth()['email'])?>" disabled></label>
      <label class="field"><span>No. HP</span><input class="input" name="phone" value="<?=h(scalar('SELECT phone FROM users WHERE id=?',[auth()['id']])??'')?>"></label>
      <button class="btn btn-primary">Simpan perubahan</button>
      <a class="btn btn-outline" href="<?=route_url('logout')?>">Keluar dari akun</a>
    </form>

    <div class="stack">
      <div class="card">
        <div class="section-title"><h3>📍 Alamat tersimpan</h3></div>
        <?php foreach($addresses as $a): ?>
          <div class="card address-card" style="padding:13px;margin-bottom:9px;background:#10161c"><div><b><?=h($a['recipient_name'])?></b> · <?=h($a['phone'])?> <?php if($a['is_default']): ?><span class="address-badge">• UTAMA</span><?php endif; ?></div><div class="muted"><?=h($a['address'])?>, <?=h($a['city'])?><?=!empty($a['province'])?', '.h($a['province']):''?> <?=h($a['postal_code']??'')?></div></div>
        <?php endforeach; ?>
        <?php if(!$addresses): ?><div class="empty"><div style="font-size:28px">📍</div><p class="muted">Belum ada alamat tersimpan.</p></div><?php endif; ?>
      </div>
      <form class="card stack" method="post" action="<?=route_url('address_add')?>">
        <input type="hidden" name="_csrf" value="<?=h(csrf())?>">
        <div class="section-title"><h3>➕ Tambah alamat</h3></div>
        <label class="field"><span>Nama penerima</span><input class="input" name="recipient_name" required></label>
        <label class="field"><span>No. HP</span><input class="input" name="phone" required></label>
        <label class="field"><span>Alamat lengkap</span><input class="input" name="address" required></label>
        <label class="field"><span>Kota / Kabupaten</span><input class="input" name="city" required></label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px"><label class="field"><span>Provinsi</span><input class="input" name="province"></label><label class="field"><span>Kode pos</span><input class="input" name="postal_code"></label></div>
        <label class="choice"><input type="checkbox" name="is_default"><span><b>Jadikan alamat utama</b><small>Alamat ini otomatis dipilih saat checkout berikutnya.</small></span></label>
        <button class="btn btn-primary">Simpan alamat</button>
      </form>
    </div>
  </div>
</section>
