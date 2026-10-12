<?php
session_start();
require __DIR__ . '/app.php';

$route = $_GET['r'] ?? 'home';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function page(string $view, array $data = []): void {
    extract($data);
    $GLOBALS['_page_view'] = $view;
    require __DIR__ . '/views/layout.php';
}

function route_url(string $route = 'home', array $params = []): string {
    $base = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    $q = ['r' => $route] + $params;
    return $base . '?' . http_build_query($q);
}

function go(string $route, array $params = []): never {
    header('Location: ' . route_url($route, $params));
    exit;
}

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function rupiah($n): string {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

function csrf(): string {
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '')) {
        http_response_code(419);
        exit('CSRF token tidak valid.');
    }
}

function flash(string $type, ?string $message = null): ?string {
    if ($message !== null) {
        $_SESSION['flash'][$type] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $value;
}

function auth(): ?array { return $_SESSION['user'] ?? null; }

function need_auth(): void {
    if (!auth()) go('login');
}

function need_admin(): void {
    need_auth();
    if ((auth()['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('403 - Admin only');
    }
}

function cart_count(): int {
    if (!auth()) return 0;
    $s = db()->prepare("SELECT COALESCE(SUM(ci.quantity),0)
        FROM cart_items ci
        JOIN carts c ON c.id=ci.cart_id
        WHERE c.user_id=?");
    $s->execute([auth()['id']]);
    return (int)$s->fetchColumn();
}

function scalar(string $sql, array $params=[]): mixed {
    $s=db()->prepare($sql); $s->execute($params); return $s->fetchColumn();
}

function status_label(string $status): string {
    return [
        'waiting_payment'=>'Menunggu pembayaran',
        'paid'=>'Pembayaran berhasil',
        'processing'=>'Sedang diproses',
        'quality_check'=>'Pemeriksaan barang',
        'packed'=>'Sedang dikemas',
        'shipped'=>'Sedang dikirim',
        'completed'=>'Pesanan selesai',
        'cancelled'=>'Dibatalkan',
        'expired'=>'Pembayaran kadaluarsa',
    ][$status] ?? ucfirst(str_replace('_',' ',$status));
}

function release_order_stock(int $orderId): void {
    $pdo=db();
    $s=$pdo->prepare("SELECT payment_status FROM orders WHERE id=?"); $s->execute([$orderId]);
    $paymentStatus=$s->fetchColumn();
    if ($paymentStatus !== 'pending') return;
    $s=$pdo->prepare("SELECT variant_id,quantity FROM order_items WHERE order_id=?"); $s->execute([$orderId]);
    $up=$pdo->prepare("UPDATE product_variants SET stock=stock+? WHERE id=?");
    foreach($s->fetchAll() as $it){ $up->execute([(int)$it['quantity'],(int)$it['variant_id']]); }
}

function notify(int $userId,string $title,string $message,string $type='info'): void {
    $s=db()->prepare("INSERT INTO notifications(user_id,title,message,type,is_read,created_at) VALUES(?,?,?,?,0,?)");
    $s->execute([$userId,$title,$message,$type,date('c')]);
}

function ensure_cart(): int {
    $s = db()->prepare("SELECT id FROM carts WHERE user_id=?");
    $s->execute([auth()['id']]);
    $id = $s->fetchColumn();
    if ($id) return (int)$id;
    $s = db()->prepare("INSERT INTO carts(user_id,created_at) VALUES(?,?)");
    $s->execute([auth()['id'], date('c')]);
    return (int)db()->lastInsertId();
}

function product(int $id): ?array {
    $s = db()->prepare("SELECT * FROM products WHERE id=? AND active=1");
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

function variant(int $id): ?array {
    $s = db()->prepare("SELECT v.*, p.name product_name,p.slug,p.price,p.capacity,p.material,
                               p.description,p.image_url product_image,p.brand
                        FROM product_variants v
                        JOIN products p ON p.id=v.product_id
                        WHERE v.id=? AND v.active=1 AND p.active=1");
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

function invoice_for(int $orderId): ?array {
    $s = db()->prepare("SELECT * FROM invoices WHERE order_id=?");
    $s->execute([$orderId]);
    return $s->fetch() ?: null;
}

function make_code(string $prefix): string {
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function create_invoice(int $orderId): void {
    $s = db()->prepare("SELECT o.*, u.name customer_name,u.email FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?");
    $s->execute([$orderId]);
    $order = $s->fetch();
    if (!$order) return;

    $s = db()->prepare("SELECT * FROM order_items WHERE order_id=?");
    $s->execute([$orderId]);
    $items = $s->fetchAll();

    $existing = invoice_for($orderId);
    $number = $existing['invoice_number'] ?? make_code('NXR-INV');
    $filename = $number . '.pdf';
    $filepath = __DIR__ . '/storage/invoices/' . $filename;

    if (!is_file($filepath)) {
        $lines = [
            'NEXORA TUMBLER - INVOICE',
            'Invoice: ' . $number,
            'Order: ' . $order['order_code'],
            'Date: ' . date('d M Y H:i', strtotime($order['created_at'])),
            'Customer: ' . $order['customer_name'],
            'Email: ' . $order['email'],
            '',
        ];
        foreach ($items as $it) {
            $lines[] = $it['product_name'] . ' / ' . $it['variant_name'];
            $lines[] = $it['quantity'] . ' x ' . rupiah($it['unit_price']) . ' = ' .
                       rupiah($it['quantity'] * $it['unit_price']);
        }
        $lines[] = '';
        $lines[] = 'Subtotal: ' . rupiah($order['subtotal']);
        $lines[] = 'Shipping: ' . rupiah($order['shipping_cost']);
        $lines[] = 'TOTAL: ' . rupiah($order['total']);
        $lines[] = 'Payment: ' . $order['payment_method'] . ' / ' . strtoupper($order['payment_status']);
        $lines[] = 'Status: ' . $order['status'];
        $lines[] = 'Thank you for shopping at NEXORA.';
        write_simple_pdf($filepath, $lines);
    }

    if ($existing) {
        $u = db()->prepare("UPDATE invoices SET invoice_path=?, generated_at=? WHERE order_id=?");
        $u->execute([$filename, date('c'), $orderId]);
    } else {
        $i = db()->prepare("INSERT INTO invoices(order_id,invoice_number,invoice_path,generated_at) VALUES(?,?,?,?)");
        $i->execute([$orderId, $number, $filename, date('c')]);
    }
}

function write_simple_pdf(string $file, array $lines): void {
    $content = "BT\n/F1 11 Tf\n50 780 Td\n";
    foreach ($lines as $idx => $line) {
        $line = preg_replace('/[^\x20-\x7E]/', '?', $line);
        $line = str_replace(['\\','(',')'], ['\\\\','\\(','\\)'], $line);
        if ($idx > 0) $content .= "0 -16 Td\n";
        $content .= '(' . $line . ") Tj\n";
    }
    $content .= "ET";

    $objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
        '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream",
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    ];
    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $i => $obj) {
        $n = $i + 1;
        $offsets[$n] = strlen($pdf);
        $pdf .= $n . " 0 obj\n" . $obj . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects)+1) . "\n0000000000 65535 f \n";
    for ($i=1; $i<=count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects)+1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
    file_put_contents($file, $pdf);
}

/* Public pages */
if ($route === 'home') {
    $featured = db()->query("SELECT * FROM products WHERE active=1 ORDER BY id LIMIT 7")->fetchAll();
    page('home', compact('featured'));
    exit;
}

if ($route === 'login') {
    if ($method === 'POST') {
        check_csrf();
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $s = db()->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $s->execute([$email]);
        $u = $s->fetch();
        if (!$u || !password_verify($password, $u['password_hash'])) {
            flash('error', 'Email atau password salah.');
            go('login');
        }
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$u['id'],
            'name' => $u['name'],
            'email' => $u['email'],
            'role' => $u['role'],
        ];
        go($u['role'] === 'admin' ? 'admin' : 'dashboard');
    }
    page('login');
    exit;
}

if ($route === 'register') {
    if ($method === 'POST') {
        check_csrf();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            flash('error', 'Nama, email valid, dan password minimal 6 karakter wajib diisi.');
            go('register');
        }
        try {
            $s = db()->prepare("INSERT INTO users(name,email,password_hash,role,created_at) VALUES(?,?,?,?,?)");
            $s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),'customer',date('c')]);
            flash('success', 'Akun berhasil dibuat. Silakan login.');
            go('login');
        } catch (Throwable) {
            flash('error', 'Email sudah terdaftar.');
            go('register');
        }
    }
    page('register');
    exit;
}

if ($route === 'logout') {
    $_SESSION = [];
    session_regenerate_id(true);
    flash('success', 'Anda sudah logout.');
    go('home');
}

if ($route === 'products') {
    $q = trim($_GET['q'] ?? '');
    $sort = $_GET['sort'] ?? 'latest';
    $pageNum = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 9;

    $where = ['p.active=1'];
    $params = [];
    if ($q !== '') {
        $where[] = '(p.name LIKE ? OR p.capacity LIKE ? OR v.name LIKE ?)';
        $like = '%' . $q . '%';
        $params = [$like,$like,$like];
    }
    $whereSql = implode(' AND ', $where);

    $order = 'p.id DESC';
    if ($sort === 'price_asc') $order = 'p.price ASC';
    if ($sort === 'price_desc') $order = 'p.price DESC';
    if ($sort === 'name') $order = 'p.name ASC';

    $s = db()->prepare("SELECT COUNT(DISTINCT p.id)
                        FROM products p LEFT JOIN product_variants v ON v.product_id=p.id
                        WHERE $whereSql");
    $s->execute($params);
    $total = (int)$s->fetchColumn();
    $pages = max(1, (int)ceil($total/$perPage));
    $pageNum = min($pageNum, $pages);
    $offset = ($pageNum-1)*$perPage;

    $sql = "SELECT p.*, MIN(v.id) variant_id, MIN(v.name) variant_name, MIN(v.stock) stock
            FROM products p
            LEFT JOIN product_variants v ON v.product_id=p.id AND v.active=1
            WHERE $whereSql
            GROUP BY p.id
            ORDER BY $order
            LIMIT $perPage OFFSET $offset";
    $s = db()->prepare($sql);
    $s->execute($params);
    $products = $s->fetchAll();

    page('products', compact('products','q','sort','pageNum','pages','total'));
    exit;
}

if ($route === 'product') {
    $p = product((int)($_GET['id'] ?? 0));
    if (!$p) { http_response_code(404); page('404'); exit; }
    $s = db()->prepare("SELECT * FROM product_variants WHERE product_id=? AND active=1 ORDER BY id");
    $s->execute([$p['id']]);
    $variants = $s->fetchAll();
    page('product', compact('p','variants'));
    exit;
}

if ($route === 'cart') {
    need_auth();
    $cid = ensure_cart();
    $s = db()->prepare("SELECT ci.id,ci.quantity,v.id variant_id,v.name variant_name,v.stock,
                               p.name product_name,p.slug,p.price,p.image_url,p.capacity
                        FROM cart_items ci
                        JOIN carts c ON c.id=ci.cart_id
                        JOIN product_variants v ON v.id=ci.variant_id
                        JOIN products p ON p.id=v.product_id
                        WHERE ci.cart_id=? ORDER BY ci.id");
    $s->execute([$cid]);
    $items = $s->fetchAll();
    $subtotal = 0;
    foreach ($items as $it) $subtotal += $it['price']*$it['quantity'];
    page('cart', compact('items','subtotal'));
    exit;
}

if ($route === 'cart_add' && $method === 'POST') {
    need_auth(); check_csrf();
    $v = variant((int)($_POST['variant_id'] ?? 0));
    if (!$v) { flash('error','Variant tidak ditemukan.'); go('products'); }
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    $qty = min($qty, (int)$v['stock']);
    if ($qty < 1) { flash('error','Stok habis.'); go('product',['id'=>$v['product_id']]); }
    $cid = ensure_cart();
    $s = db()->prepare("SELECT id,quantity FROM cart_items WHERE cart_id=? AND variant_id=?");
    $s->execute([$cid,$v['id']]);
    $row = $s->fetch();
    if ($row) {
        $u = db()->prepare("UPDATE cart_items SET quantity=? WHERE id=?");
        $u->execute([min($v['stock'], $row['quantity']+$qty),$row['id']]);
    } else {
        $i = db()->prepare("INSERT INTO cart_items(cart_id,variant_id,quantity) VALUES(?,?,?)");
        $i->execute([$cid,$v['id'],$qty]);
    }
    flash('success','Produk ditambahkan ke keranjang.');
    go('cart');
}

if ($route === 'buy_now' && $method === 'POST') {
    need_auth(); check_csrf();
    $v=variant((int)($_POST['variant_id']??0));
    $qty=max(1,(int)($_POST['quantity']??1));
    if(!$v){flash('error','Produk tidak ditemukan.');go('products');}
    if($v['stock']<1){flash('error','Stok produk sedang habis.');go('product',['id'=>$v['product_id']]);}
    $qty=min($qty,(int)$v['stock']);
    $_SESSION['direct_checkout']=['variant_id'=>(int)$v['id'],'quantity'=>$qty];
    go('checkout');
}

if ($route === 'cart_update' && $method === 'POST') {
    need_auth(); check_csrf();
    $cid = ensure_cart();
    foreach (($_POST['qty'] ?? []) as $id => $qty) {
        $qty = max(0, (int)$qty);
        if ($qty === 0) {
            $s = db()->prepare("DELETE FROM cart_items WHERE id=? AND cart_id=?");
            $s->execute([$id,$cid]);
            continue;
        }
        $s = db()->prepare("SELECT v.stock FROM cart_items ci JOIN product_variants v ON v.id=ci.variant_id WHERE ci.id=? AND ci.cart_id=?");
        $s->execute([$id,$cid]);
        $stock = (int)$s->fetchColumn();
        $s = db()->prepare("UPDATE cart_items SET quantity=? WHERE id=? AND cart_id=?");
        $s->execute([min($qty,$stock),$id,$cid]);
    }
    flash('success','Keranjang diperbarui.');
    go('cart');
}

if ($route === 'cart_remove' && $method === 'POST') {
    need_auth(); check_csrf();
    $cid = ensure_cart();
    $s = db()->prepare("DELETE FROM cart_items WHERE id=? AND cart_id=?");
    $s->execute([(int)($_POST['id'] ?? 0),$cid]);
    go('cart');
}

if ($route === 'checkout') {
    need_auth();
    $direct=$_SESSION['direct_checkout']??null;
    $items=[];
    if($direct){
        $s=db()->prepare("SELECT v.id variant_id,v.name variant_name,v.stock,p.name product_name,p.price,p.image_url,p.capacity,p.material FROM product_variants v JOIN products p ON p.id=v.product_id WHERE v.id=? AND v.active=1 AND p.active=1");
        $s->execute([(int)$direct['variant_id']]); $v=$s->fetch();
        if(!$v){unset($_SESSION['direct_checkout']);flash('error','Produk tidak tersedia lagi.');go('products');}
        $v['quantity']=min((int)$direct['quantity'],(int)$v['stock']); $items=[$v];
    } else {
        $cid=ensure_cart();
        $s=db()->prepare("SELECT ci.*,v.id variant_id,v.name variant_name,v.stock,p.name product_name,p.price,p.image_url,p.capacity,p.material FROM cart_items ci JOIN carts c ON c.id=ci.cart_id JOIN product_variants v ON v.id=ci.variant_id JOIN products p ON p.id=v.product_id WHERE ci.cart_id=?");
        $s->execute([$cid]); $items=$s->fetchAll();
    }
    if(!$items){ flash('error','Belum ada produk untuk checkout.'); go('products'); }
    $subtotal=0; foreach($items as $it){ $subtotal += $it['price']*$it['quantity']; }
    $a=db()->prepare("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC,id DESC"); $a->execute([auth()['id']]); $addresses=$a->fetchAll();
    $defaultAddress=$addresses[0]??null;
    $totalPreview=$subtotal+18000;
    page('checkout',compact('items','subtotal','totalPreview','addresses','defaultAddress','direct'));
    exit;
}

if ($route === 'place_order' && $method === 'POST') {
    need_auth(); check_csrf();
    $name=trim($_POST['name']??''); $phone=trim($_POST['phone']??''); $address=trim($_POST['address']??''); $city=trim($_POST['city']??''); $addressId=(int)($_POST['address_id']??0);
    if($addressId){ $as=db()->prepare("SELECT recipient_name,phone,address,city FROM addresses WHERE id=? AND user_id=?"); $as->execute([$addressId,auth()['id']]); if($saved=$as->fetch()){ $name=$saved['recipient_name']; $phone=$saved['phone']; $address=$saved['address']; $city=$saved['city']; } }
    $shippingMethod=trim($_POST['shipping_method']??'JNE Reguler'); $paymentMethod=trim($_POST['payment_method']??'BCA Virtual Account');
    $allowedPay=['BCA Virtual Account','BNI Virtual Account','QRIS','Gopay'];
    if(!in_array($paymentMethod,$allowedPay,true)) $paymentMethod='BCA Virtual Account';
    if(!$name||!$phone||!$address||!$city){flash('error','Alamat belum lengkap.');go('checkout');}
    $shipMap=['JNE Reguler'=>[18000,'2-3 hari'],'J&T Reguler'=>[20000,'2-4 hari'],'SiCepat Reguler'=>[22000,'2-3 hari']];
    if(!isset($shipMap[$shippingMethod]))$shippingMethod='JNE Reguler'; [$shipping,$estimate]=$shipMap[$shippingMethod];
    $direct=$_SESSION['direct_checkout']??null; $cid=ensure_cart(); $items=[];
    if($direct){
        $s=db()->prepare("SELECT v.id variant_id,v.name variant_name,v.stock,p.name product_name,p.price,p.image_url,p.capacity,p.material FROM product_variants v JOIN products p ON p.id=v.product_id WHERE v.id=? AND v.active=1 AND p.active=1");
        $s->execute([(int)$direct['variant_id']]); $v=$s->fetch();
        if(!$v){unset($_SESSION['direct_checkout']);flash('error','Produk tidak tersedia.');go('products');}
        $v['quantity']=min((int)$direct['quantity'],(int)$v['stock']); $items=[$v];
    } else {
        $s=db()->prepare("SELECT ci.*,v.id variant_id,v.name variant_name,v.stock,p.name product_name,p.price,p.image_url,p.capacity,p.material FROM cart_items ci JOIN carts c ON c.id=ci.cart_id JOIN product_variants v ON v.id=ci.variant_id JOIN products p ON p.id=v.product_id WHERE ci.cart_id=?"); $s->execute([$cid]); $items=$s->fetchAll();
    }
    if(!$items){flash('error','Keranjang kosong.');go('products');}
    $subtotal=0; foreach($items as $it){if($it['quantity']>$it['stock']){flash('error','Stok berubah. Periksa keranjang.');go('cart');}$subtotal += $it['price']*$it['quantity'];}
    $service=0; $total=$subtotal+$shipping+$service; $orderCode=make_code('NXR'); $paymentCode='PAY-'.strtoupper(bin2hex(random_bytes(4))); $expires=date('c',time()+900);
    $addressJson=json_encode(compact('name','phone','address','city'),JSON_UNESCAPED_UNICODE);
    db()->beginTransaction();
    try{
        $s=db()->prepare("INSERT INTO orders(user_id,order_code,status,payment_status,subtotal,shipping_cost,service_fee,total,address_json,shipping_method,shipping_estimate,shipping_status,payment_method,payment_code,payment_expires_at,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $s->execute([auth()['id'],$orderCode,'waiting_payment','pending',$subtotal,$shipping,$service,$total,$addressJson,$shippingMethod,$estimate,'pending',$paymentMethod,$paymentCode,$expires,date('c')]);
        $orderId=(int)db()->lastInsertId();
        $oi=db()->prepare("INSERT INTO order_items(order_id,variant_id,product_name,variant_name,capacity,material,unit_price,quantity,image_url) VALUES(?,?,?,?,?,?,?,?,?)");
        $dec=db()->prepare("UPDATE product_variants SET stock=stock-? WHERE id=?");
        foreach($items as $it){$oi->execute([$orderId,$it['variant_id'],$it['product_name'],$it['variant_name'],$it['capacity'],$it['material'],$it['price'],$it['quantity'],$it['image_url']]);$dec->execute([$it['quantity'],$it['variant_id']]);}
        db()->prepare("INSERT INTO payments(order_id,method,payment_code,amount,status,created_at,expires_at) VALUES(?,?,?,?,?,?,?)")->execute([$orderId,$paymentMethod,$paymentCode,$total,'pending',date('c'),$expires]);
        db()->prepare("INSERT INTO quality_checks(order_id,result) VALUES(?,?)")->execute([$orderId,'pending']);
        if(!$direct) db()->prepare("DELETE FROM cart_items WHERE cart_id=?")->execute([$cid]);
        db()->commit(); unset($_SESSION['direct_checkout']);
        notify(auth()['id'],'Pesanan dibuat','Order '.$orderCode.' menunggu pembayaran. Kode pembayaran sudah tersedia.','payment');
        go('payment',['id'=>$orderId]);
    }catch(Throwable $e){db()->rollBack();flash('error','Checkout gagal. '.$e->getMessage());go('checkout');}
}

if ($route === 'payment') {
    need_auth();
    $s=db()->prepare("SELECT * FROM orders WHERE id=? AND user_id=?"); $s->execute([(int)($_GET['id']??0),auth()['id']]); $order=$s->fetch();
    if(!$order){http_response_code(404);page('404');exit;}
    if($order['payment_status']==='pending' && $order['payment_expires_at'] && strtotime($order['payment_expires_at'])<time()){
        db()->beginTransaction();
        try{ release_order_stock((int)$order['id']); db()->prepare("UPDATE orders SET payment_status='expired',status='cancelled' WHERE id=? AND payment_status='pending'")->execute([$order['id']]); db()->prepare("UPDATE payments SET status='expired' WHERE order_id=? AND status='pending'")->execute([$order['id']]); db()->commit(); }catch(Throwable $e){db()->rollBack();}
        notify(auth()['id'],'Pembayaran kadaluarsa','Waktu pembayaran '.$order['order_code'].' sudah habis. Stok dikembalikan ke katalog.','warning');
        $order= (function() use($order){$s=db()->prepare("SELECT * FROM orders WHERE id=?");$s->execute([$order['id']]);return $s->fetch();})();
    }
    page('payment',compact('order')); exit;
}

if ($route === 'verify_payment' && $method === 'POST') {
    need_auth(); check_csrf(); $id=(int)($_POST['order_id']??0); $code=trim($_POST['payment_code']??'');
    $s=db()->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");$s->execute([$id,auth()['id']]);$order=$s->fetch();
    if(!$order){http_response_code(404);exit('Order tidak ditemukan');}
    if($order['payment_status']==='paid'){go('payment_success',['id'=>$id]);}
    if(!$order['payment_code']||!hash_equals($order['payment_code'],$code)){flash('error','Kode pembayaran tidak cocok.');go('payment',['id'=>$id]);}
    if($order['payment_expires_at']&&strtotime($order['payment_expires_at'])<time()){db()->prepare("UPDATE orders SET payment_status='expired',status='cancelled' WHERE id=?")->execute([$id]);db()->prepare("UPDATE payments SET status='expired' WHERE order_id=?")->execute([$id]);flash('error','Pembayaran sudah kadaluarsa.');go('payment',['id'=>$id]);}
    db()->prepare("UPDATE orders SET payment_status='paid',status='paid',paid_at=? WHERE id=?")->execute([date('c'),$id]);
    db()->prepare("UPDATE payments SET status='paid',paid_at=? WHERE order_id=?")->execute([date('c'),$id]);
    create_invoice($id);
    notify(auth()['id'],'Pembayaran Berhasil','Pembayaran order '.$order['order_code'].' sudah terverifikasi.','success');
    notify(auth()['id'],'Pesanan Diproses','Pesanan '.$order['order_code'].' sedang diproses.','order');
    go('payment_success',['id'=>$id]);
}

if ($route === 'pay_fail' && $method === 'POST') {
    need_auth();check_csrf();$id=(int)$_POST['order_id'];$s=db()->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");$s->execute([$id,auth()['id']]);$order=$s->fetch();if(!$order)exit('Not found');
    db()->prepare("UPDATE orders SET payment_status='failed' WHERE id=?")->execute([$id]);db()->prepare("UPDATE payments SET status='failed' WHERE order_id=?")->execute([$id]);notify(auth()['id'],'Pembayaran gagal','Pembayaran simulasi untuk '.$order['order_code'].' gagal.','warning');go('order',['id'=>$id]);
}

if ($route === 'pay_expired' && $method === 'POST') {
    need_auth();check_csrf();$id=(int)$_POST['order_id'];
    $s=db()->prepare("SELECT * FROM orders WHERE id=? AND user_id=? AND payment_status='pending'");$s->execute([$id,auth()['id']]);$o=$s->fetch();
    if($o){db()->beginTransaction();try{release_order_stock($id);db()->prepare("UPDATE orders SET payment_status='expired',status='cancelled' WHERE id=? AND payment_status='pending'")->execute([$id]);db()->prepare("UPDATE payments SET status='expired' WHERE order_id=? AND status='pending'")->execute([$id]);db()->commit();}catch(Throwable $e){db()->rollBack();}}
    notify(auth()['id'],'Pembayaran kadaluarsa','Pembayaran simulasi kadaluarsa.','warning');go('order',['id'=>$id]);
}

if ($route === 'payment_success') {
    need_auth();$s=db()->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");$s->execute([(int)($_GET['id']??0),auth()['id']]);$order=$s->fetch();if(!$order){http_response_code(404);page('404');exit;}page('payment_success',compact('order'));exit;
}

if ($route === 'profile') {
    need_auth();
    if ($method === 'POST') {
        check_csrf();
        $name=trim($_POST['name']??''); $phone=trim($_POST['phone']??'');
        if($name===''){ flash('error','Nama wajib diisi.'); go('profile'); }
        db()->prepare("UPDATE users SET name=?,phone=? WHERE id=?")->execute([$name,$phone,auth()['id']]);
        $_SESSION['user']['name']=$name;
        flash('success','Profil berhasil diperbarui.'); go('profile');
    }
    if ($method === 'POST' && isset($_POST['delete_address'])) {}
    $a=db()->prepare("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC,id DESC"); $a->execute([auth()['id']]);
    $addresses=$a->fetchAll();
    page('profile',compact('addresses')); exit;
}

if ($route === 'address_add' && $method === 'POST') {
    need_auth(); check_csrf();
    $name=trim($_POST['recipient_name']??''); $phone=trim($_POST['phone']??''); $address=trim($_POST['address']??''); $city=trim($_POST['city']??''); $province=trim($_POST['province']??''); $postal=trim($_POST['postal_code']??'');
    if(!$name||!$phone||!$address||!$city){flash('error','Nama, nomor HP, alamat, dan kota wajib diisi.');go('profile');}
    $has=(int)scalar("SELECT COUNT(*) FROM addresses WHERE user_id=?",[auth()['id']]);
    if(!$has) $default=1; else $default=!empty($_POST['is_default'])?1:0;
    if($default) db()->prepare("UPDATE addresses SET is_default=0 WHERE user_id=?")->execute([auth()['id']]);
    db()->prepare("INSERT INTO addresses(user_id,recipient_name,phone,address,city,province,postal_code,is_default,created_at) VALUES(?,?,?,?,?,?,?,?,?)")
      ->execute([auth()['id'],$name,$phone,$address,$city,$province,$postal,$default,date('c')]);
    flash('success','Alamat berhasil disimpan.'); go('profile');
}

if ($route === 'notifications') {
    need_auth();$s=db()->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC");$s->execute([auth()['id']]);$notifications=$s->fetchAll();db()->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([auth()['id']]);page('notifications',compact('notifications'));exit;
}

if ($route === 'orders') {
    need_auth();
    $s = db()->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC");
    $s->execute([auth()['id']]);
    $orders = $s->fetchAll();
    page('orders', compact('orders'));
    exit;
}

if ($route === 'order') {
    need_auth();
    $s = db()->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
    $s->execute([(int)($_GET['id'] ?? 0),auth()['id']]);
    $order = $s->fetch();
    if (!$order) { http_response_code(404); page('404'); exit; }
    $s = db()->prepare("SELECT * FROM order_items WHERE order_id=?");
    $s->execute([$order['id']]);
    $items = $s->fetchAll();
    $invoice = invoice_for($order['id']);
    page('order', compact('order','items','invoice'));
    exit;
}

if ($route === 'tracking') {
    need_auth();$s=db()->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");$s->execute([(int)($_GET['id']??0),auth()['id']]);$order=$s->fetch();if(!$order){http_response_code(404);page('404');exit;}page('tracking',compact('order'));exit;
}

if ($route === 'invoice_download') {
    need_auth();
    $id = (int)($_GET['order_id'] ?? 0);
    $s = db()->prepare("SELECT o.*, i.invoice_path FROM orders o JOIN invoices i ON i.order_id=o.id WHERE o.id=? AND o.user_id=?");
    $s->execute([$id,auth()['id']]);
    $row = $s->fetch();
    if (!$row) { http_response_code(404); exit('Invoice tidak ditemukan'); }
    $file = __DIR__ . '/storage/invoices/' . basename($row['invoice_path']);
    if (!is_file($file)) { create_invoice($id); }
    $file = __DIR__ . '/storage/invoices/' . basename(invoice_for($id)['invoice_path']);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

if ($route === 'dashboard') {
    need_auth();
    page('dashboard');
    exit;
}

/* Admin */
if ($route === 'admin') {
    need_admin();
    $stats = [
        'users' => (int)db()->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
        'products' => (int)db()->query("SELECT COUNT(*) FROM products WHERE active=1")->fetchColumn(),
        'orders' => (int)db()->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'paid' => (int)db()->query("SELECT COUNT(*) FROM orders WHERE payment_status='paid'")->fetchColumn(),
        'revenue' => (int)db()->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid'")->fetchColumn(),
    ];
    $orders = db()->query("SELECT o.*,u.name customer FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC LIMIT 10")->fetchAll();
    page('admin', compact('stats','orders'));
    exit;
}

if ($route === 'admin_payments') {
    need_admin();
    $payments=db()->query("SELECT p.*,o.order_code,u.name customer FROM payments p JOIN orders o ON o.id=p.order_id JOIN users u ON u.id=o.user_id ORDER BY p.id DESC")->fetchAll();
    page('admin_payments',compact('payments')); exit;
}

if ($route === 'admin_report') {
    need_admin();
    $summary=db()->query("SELECT COUNT(*) orders, COALESCE(SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END),0) revenue, COALESCE(SUM(CASE WHEN payment_status='paid' THEN 1 ELSE 0 END),0) paid_orders FROM orders")->fetch();
    $statusRows=db()->query("SELECT status,COUNT(*) total FROM orders GROUP BY status ORDER BY total DESC")->fetchAll();
    $best=db()->query("SELECT product_name,COALESCE(SUM(quantity),0) qty FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.payment_status='paid' GROUP BY product_name ORDER BY qty DESC LIMIT 10")->fetchAll();
    page('admin_report',compact('summary','statusRows','best')); exit;
}

if ($route === 'admin_qc' && $method === 'POST') {
    need_admin(); check_csrf();
    $id=(int)($_POST['order_id']??0); $result=$_POST['result']??'pending';
    $allowed=['pending','passed','failed']; if(!in_array($result,$allowed,true))$result='pending';
    $s=db()->prepare("UPDATE quality_checks SET physical_condition=?,lid_condition=?,seal_condition=?,leak_test=?,finishing_condition=?,completeness=?,notes=?,result=?,checked_at=? WHERE order_id=?");
    $s->execute([trim($_POST['physical_condition']??''),trim($_POST['lid_condition']??''),trim($_POST['seal_condition']??''),trim($_POST['leak_test']??''),trim($_POST['finishing_condition']??''),trim($_POST['completeness']??''),trim($_POST['notes']??''),$result,date('c'),$id]);
    if($result==='passed') db()->prepare("UPDATE orders SET status='packed' WHERE id=? AND status='quality_check'")->execute([$id]);
    flash('success','Quality check tersimpan.'); go('admin');
}

if ($route === 'admin_orders' && $method === 'POST') {
    need_admin(); check_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'processing';
    $tracking = trim($_POST['tracking_number'] ?? '');
    $allowed = ['waiting_payment','paid','processing','quality_check','packed','shipped','completed','cancelled'];
    if (!in_array($status, $allowed, true)) $status='processing';
    db()->prepare("UPDATE orders SET status=?,tracking_number=?,completed_at=? WHERE id=?")
       ->execute([$status,$tracking,$status==='completed'?date('c'):null,$id]);
    flash('success','Order diperbarui.');
    go('admin');
}

if ($route === 'admin_order') {
    need_admin();
    $s = db()->prepare("SELECT o.*,u.name customer_name,u.email customer_email FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?");
    $s->execute([(int)($_GET['id'] ?? 0)]);
    $order = $s->fetch();
    if (!$order) { http_response_code(404); page('404'); exit; }
    $s = db()->prepare("SELECT * FROM order_items WHERE order_id=?");
    $s->execute([$order['id']]);
    $items = $s->fetchAll();
    $invoice = invoice_for($order['id']);
    page('admin_order', compact('order','items','invoice'));
    exit;
}

if ($route === 'admin_invoice') {
    need_admin();
    $id=(int)($_GET['order_id']??0);
    $s=db()->prepare("SELECT o.*,u.name customer_name,u.email customer_email FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?");$s->execute([$id]);$order=$s->fetch();
    if(!$order){http_response_code(404);exit('Order tidak ditemukan');}
    if(!invoice_for($id)){ if($order['payment_status']==='paid') create_invoice($id); }
    $invoice=invoice_for($id); $s=db()->prepare("SELECT * FROM order_items WHERE order_id=?");$s->execute([$id]);$items=$s->fetchAll();
    page('admin_order',compact('order','items','invoice')); exit;
}

if ($route === 'admin_invoice_download') {
    need_admin();
    $id=(int)($_GET['order_id']??0);
    $s=db()->prepare("SELECT id,payment_status FROM orders WHERE id=?");$s->execute([$id]);$o=$s->fetch();
    if(!$o || $o['payment_status']!=='paid'){http_response_code(404);exit('Invoice belum tersedia');}
    if(!invoice_for($id)) create_invoice($id);
    $inv=invoice_for($id); $file=__DIR__.'/storage/invoices/'.basename($inv['invoice_path']);
    if(!is_file($file)){http_response_code(404);exit('File invoice tidak ditemukan');}
    header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename="'.basename($file).'"'); header('Content-Length: '.filesize($file)); readfile($file); exit;
}

if ($route === 'admin_products') {
    need_admin();
    $products = db()->query("SELECT p.*, (SELECT COUNT(*) FROM product_variants v WHERE v.product_id=p.id) variant_count,
                             (SELECT COALESCE(SUM(v.stock),0) FROM product_variants v WHERE v.product_id=p.id) stock_total
                             FROM products p ORDER BY p.id")->fetchAll();
    page('admin_products', compact('products'));
    exit;
}

if ($route === 'services') {
    page('simple', ['heading'=>'Our services','copy'=>'Checkout, simulated payment, invoice, PDF storage, order status, dan admin order management sudah tersedia dalam package ini.']);
    exit;
}

if ($route === 'faq') {
    page('simple', ['heading'=>'FAQ','copy'=>'Payment yang digunakan adalah simulasi. Produk yang ditanam pada demo mengikuti 7 listing pada prototype NEXORA yang tersedia.']);
    exit;
}

http_response_code(404);
page('404');
