<?php
$config = [
    'db_file' => __DIR__ . '/data/nexora.sqlite',
];
@mkdir(dirname($config['db_file']), 0777, true);
@mkdir(__DIR__ . '/storage/invoices', 0777, true);

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO('sqlite:' . __DIR__ . '/data/nexora.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    init_db($pdo);
    return $pdo;
}

function init_db(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'customer',
        phone TEXT,
        created_at TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS addresses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        recipient_name TEXT NOT NULL,
        phone TEXT NOT NULL,
        address TEXT NOT NULL,
        city TEXT NOT NULL,
        province TEXT,
        postal_code TEXT,
        is_default INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        brand TEXT NOT NULL,
        description TEXT NOT NULL,
        capacity TEXT NOT NULL,
        material TEXT NOT NULL,
        image_url TEXT NOT NULL,
        price INTEGER NOT NULL,
        active INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS product_variants (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        color TEXT NOT NULL,
        sku TEXT NOT NULL UNIQUE,
        stock INTEGER NOT NULL DEFAULT 0,
        image_url TEXT NOT NULL,
        active INTEGER NOT NULL DEFAULT 1,
        FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS carts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL UNIQUE,
        created_at TEXT NOT NULL,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS cart_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        cart_id INTEGER NOT NULL,
        variant_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL,
        UNIQUE(cart_id, variant_id),
        FOREIGN KEY(cart_id) REFERENCES carts(id) ON DELETE CASCADE,
        FOREIGN KEY(variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        order_code TEXT NOT NULL UNIQUE,
        status TEXT NOT NULL,
        payment_status TEXT NOT NULL,
        subtotal INTEGER NOT NULL,
        shipping_cost INTEGER NOT NULL,
        service_fee INTEGER NOT NULL DEFAULT 0,
        total INTEGER NOT NULL,
        address_json TEXT NOT NULL,
        shipping_method TEXT NOT NULL,
        shipping_estimate TEXT,
        shipping_status TEXT NOT NULL DEFAULT 'pending',
        tracking_number TEXT,
        payment_method TEXT NOT NULL,
        payment_code TEXT,
        payment_expires_at TEXT,
        created_at TEXT NOT NULL,
        paid_at TEXT,
        completed_at TEXT,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        variant_id INTEGER NOT NULL,
        product_name TEXT NOT NULL,
        variant_name TEXT NOT NULL,
        capacity TEXT,
        material TEXT,
        unit_price INTEGER NOT NULL,
        quantity INTEGER NOT NULL,
        image_url TEXT NOT NULL,
        FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL UNIQUE,
        method TEXT NOT NULL,
        payment_code TEXT,
        amount INTEGER NOT NULL,
        status TEXT NOT NULL,
        created_at TEXT NOT NULL,
        paid_at TEXT,
        expires_at TEXT,
        FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS quality_checks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        physical_condition TEXT,
        lid_condition TEXT,
        seal_condition TEXT,
        leak_test TEXT,
        finishing_condition TEXT,
        completeness TEXT,
        notes TEXT,
        result TEXT NOT NULL DEFAULT 'pending',
        checked_at TEXT,
        FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        message TEXT NOT NULL,
        type TEXT NOT NULL DEFAULT 'info',
        is_read INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL UNIQUE,
        invoice_number TEXT NOT NULL UNIQUE,
        invoice_path TEXT NOT NULL,
        generated_at TEXT NOT NULL,
        FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");

    $now = date('c');
    $hasUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() > 0;
    if ($hasUsers) {
        ensure_catalog($pdo);
        return;
    }

    $users = [
        ['NEXORA Admin','admin@nexora.com','admin123','admin','081234567890'],
        ['Rafiyandi','customer@nexora.com','customer123','customer','081234567890'],
    ];
    $u = $pdo->prepare("INSERT INTO users(name,email,password_hash,role,phone,created_at) VALUES(?,?,?,?,?,?)");
    foreach ($users as $row) {
        $u->execute([$row[0],$row[1],password_hash($row[2],PASSWORD_DEFAULT),$row[3],$row[4],$now]);
    }
    $customerId=(int)$pdo->query("SELECT id FROM users WHERE email='customer@nexora.com'")->fetchColumn();
    $pdo->prepare("INSERT INTO addresses(user_id,recipient_name,phone,address,city,province,postal_code,is_default,created_at) VALUES(?,?,?,?,?,?,?,?,?)")
        ->execute([$customerId,'Rafiyandi','081234567890','Jl. Melati No. 12','Jakarta Selatan','DKI Jakarta','12345',1,$now]);

    $products = [
        ['Classic Sport Canteen','classic-sport-canteen','CORKCICLE','Slim canteen with sport cap.','20 oz (591 mL)','Stainless Steel','https://corkcicle.com/cdn/shop/files/2020GLF-2.png?crop=center&height=1000&v=1785869707&width=1000',712520,'Ether Sky','CORK-001'],
        ['Classic Coffee Mug','classic-coffee-mug','CORKCICLE','Classic insulated coffee mug.','16 oz (473 mL)','Stainless Steel','https://corkcicle.com/cdn/shop/files/2516PWG-2.png?crop=center&height=1000&v=1785331226&width=1000',534390,'Western Gothic','CORK-002'],
        ['Cold Cup','cold-cup','CORKCICLE','Cold cup for daily hydration.','24 oz (710 mL)','Stainless Steel','https://corkcicle.com/cdn/shop/files/2224PPP-01.png?crop=center&height=1000&v=1773666527&width=1000',623455,'Cypress Night','CORK-003'],
        ['Classic Stemless','classic-stemless','CORKCICLE','Stemless insulated tumbler.','12 oz (355 mL)','Stainless Steel','https://corkcicle.com/cdn/shop/files/2312GES-2.png?crop=center&height=1000&v=1785877368&width=1000',534390,'Lavender Fog','CORK-004'],
        ['Classic Tumbler','classic-tumbler-powder-puff','CORKCICLE','Classic triple-insulated tumbler.','16 oz (473 mL)','Stainless Steel','https://corkcicle.com/cdn/shop/files/2116GPF-02.png?crop=center&height=1000&v=1772722773&width=1000',534390,'Powder Puff','CORK-005'],
        ['Classic Canteen','classic-canteen','CORKCICLE','Classic canteen for everyday carry.','16 oz (473 mL)','Stainless Steel','https://corkcicle.com/cdn/shop/files/2016GW.png?crop=center&height=1000&v=1753797618&width=1000',534390,'Gloss White','CORK-006'],
        ['Cold Cup XL','cold-cup-xl','CORKCICLE','Oversized cold cup for long days.','30 oz (887 mL)','Stainless Steel','https://corkcicle.com/cdn/shop/products/2230CLT-1.png?crop=center&height=1000&v=1768941040&width=1000',712520,'Latte','CORK-007'],
    ];
    $p = $pdo->prepare("INSERT INTO products(name,slug,brand,description,capacity,material,image_url,price,active,created_at)
                       VALUES(?,?,?,?,?,?,?,?,1,?)");
    $v = $pdo->prepare("INSERT INTO product_variants(product_id,name,color,sku,stock,image_url,active)
                        VALUES(?,?,?,?,?, ?,1)");
    foreach ($products as $i => $row) {
        $p->execute([$row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$now]);
        $pid = (int)$pdo->lastInsertId();
        $v->execute([$pid,$row[8],$row[8],$row[9],15,$row[6]]);
    }

    // Tambahkan katalog revisi tanpa menghapus data lama.
    ensure_catalog($pdo);
}


/**
 * Menambahkan katalog baru secara idempotent.
 * Tidak menghapus/reset data lama, order, cart, maupun invoice.
 */
function ensure_catalog(PDO $pdo): void {
    $now = date('c');
    $catalog = [
        [
            'name' => 'Sierra Sport Canteen Terracotta',
            'slug' => 'sierra-sport-canteen-terracotta-20oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Terracotta, ukuran 20oz. Botol stainless steel berinsulasi tiga lapis dengan kemampuan menjaga minuman dingin hingga 25 jam dan panas hingga 12 jam menurut CORKCICLE.',
            'capacity' => '20 oz (591 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2020RTC-2.png?crop=center&height=500&v=1785442897&width=500',
            'price' => 799000,
            'color' => 'Terracotta',
            'sku' => 'CORK-SC-TERR-20',
        ],
        [
            'name' => 'Sierra Sport Canteen Harbor',
            'slug' => 'sierra-sport-canteen-harbor-20oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Harbor, ukuran 20oz. Botol stainless steel berinsulasi tiga lapis; CORKCICLE menyebut performa hingga 25 jam dingin dan 12 jam panas.',
            'capacity' => '20 oz (591 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2020RHB-2.png?crop=center&height=500&v=1785442344&width=500',
            'price' => 799000,
            'color' => 'Harbor',
            'sku' => 'CORK-SC-HARB-20',
        ],
        [
            'name' => 'Sierra Sport Canteen Brick',
            'slug' => 'sierra-sport-canteen-brick-20oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Brick, ukuran 20oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '20 oz (591 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2020RB-1.png?crop=center&height=500&v=1772223855&width=500',
            'price' => 799000,
            'color' => 'Brick',
            'sku' => 'CORK-SC-BRICK-20',
        ],
        [
            'name' => 'Sierra Sport Canteen Brick',
            'slug' => 'sierra-sport-canteen-brick-32oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Brick, ukuran 32oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '32 oz (946 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2032RB-2.png?crop=center&height=500&v=1769022877&width=500',
            'price' => 849000,
            'color' => 'Brick',
            'sku' => 'CORK-SC-BRICK-32',
        ],
        [
            'name' => 'Sierra Sport Canteen Desert',
            'slug' => 'sierra-sport-canteen-desert-20oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Desert, ukuran 20oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '20 oz (591 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2020MDS-01.png?crop=center&height=500&v=1769545948&width=500',
            'price' => 799000,
            'color' => 'Desert',
            'sku' => 'CORK-SC-DESERT-20',
        ],
        [
            'name' => 'Sierra Sport Canteen Desert',
            'slug' => 'sierra-sport-canteen-desert-32oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Desert, ukuran 32oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '32 oz (946 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2032MDS-01.png?crop=center&height=500&v=1769545948&width=500',
            'price' => 849000,
            'color' => 'Desert',
            'sku' => 'CORK-SC-DESERT-32',
        ],
        [
            'name' => 'Sierra Sport Canteen Cacao',
            'slug' => 'sierra-sport-canteen-cacao-20oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Cacao, ukuran 20oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '20 oz (591 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2020RC-2.png?crop=center&height=500&v=1772223855&width=500',
            'price' => 799000,
            'color' => 'Cacao',
            'sku' => 'CORK-SC-CACAO-20',
        ],
        [
            'name' => 'Sierra Sport Canteen Cacao',
            'slug' => 'sierra-sport-canteen-cacao-32oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Cacao, ukuran 32oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '32 oz (946 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2032RC-2.png?crop=center&height=500&v=1769027337&width=500',
            'price' => 849000,
            'color' => 'Cacao',
            'sku' => 'CORK-SC-CACAO-32',
        ],
        [
            'name' => 'Sierra Sport Canteen Desert Rose',
            'slug' => 'sierra-sport-canteen-desert-rose-20oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Desert Rose, ukuran 20oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '20 oz (591 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2020RDR-01.png?crop=center&height=500&v=1769545948&width=500',
            'price' => 799000,
            'color' => 'Desert Rose',
            'sku' => 'CORK-SC-DROSE-20',
        ],
        [
            'name' => 'Sierra Sport Canteen Olive',
            'slug' => 'sierra-sport-canteen-olive-20oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Olive, ukuran 20oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '20 oz (591 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2020RO-01.png?crop=center&height=500&v=1769545948&width=500',
            'price' => 799000,
            'color' => 'Olive',
            'sku' => 'CORK-SC-OLIVE-20',
        ],
        [
            'name' => 'Sierra Sport Canteen Olive',
            'slug' => 'sierra-sport-canteen-olive-32oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Olive, ukuran 32oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '32 oz (946 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2032RO-01_c2e1271c-c11a-4c32-802f-8384db9a85fd.jpg?crop=center&height=500&v=1769545948&width=500',
            'price' => 849000,
            'color' => 'Olive',
            'sku' => 'CORK-SC-OLIVE-32',
        ],
        [
            'name' => 'Sierra Sport Canteen Mauve',
            'slug' => 'sierra-sport-canteen-mauve-20oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Mauve, ukuran 20oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '20 oz (591 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2020MM-01.png?crop=center&height=500&v=1769545948&width=500',
            'price' => 799000,
            'color' => 'Mauve',
            'sku' => 'CORK-SC-MAUVE-20',
        ],
        [
            'name' => 'Sierra Sport Canteen North Shore',
            'slug' => 'sierra-sport-canteen-north-shore-20oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna North Shore, ukuran 20oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '20 oz (591 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2020MNS-03.png?crop=center&height=500&v=1769545948&width=500',
            'price' => 799000,
            'color' => 'North Shore',
            'sku' => 'CORK-SC-NSHORE-20',
        ],
        [
            'name' => 'Sierra Sport Canteen North Shore',
            'slug' => 'sierra-sport-canteen-north-shore-32oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna North Shore, ukuran 32oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '32 oz (946 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2032MNS-01.png?crop=center&height=500&v=1769545948&width=500',
            'price' => 849000,
            'color' => 'North Shore',
            'sku' => 'CORK-SC-NSHORE-32',
        ],
        [
            'name' => 'Sierra Sport Canteen Flint',
            'slug' => 'sierra-sport-canteen-flint-20oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Flint, ukuran 20oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '20 oz (591 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2020MF-01.png?crop=center&height=500&v=1769545948&width=500',
            'price' => 799000,
            'color' => 'Flint',
            'sku' => 'CORK-SC-FLINT-20',
        ],
        [
            'name' => 'Sierra Sport Canteen Flint',
            'slug' => 'sierra-sport-canteen-flint-32oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Flint, ukuran 32oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '32 oz (946 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2032MF-01.png?crop=center&height=500&v=1769545948&width=500',
            'price' => 849000,
            'color' => 'Flint',
            'sku' => 'CORK-SC-FLINT-32',
        ],
        [
            'name' => 'Sierra Sport Canteen Sage Mist',
            'slug' => 'sierra-sport-canteen-sage-mist-20oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Sage Mist, ukuran 20oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '20 oz (591 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2020MSM-3.png?crop=center&height=500&v=1769545948&width=500',
            'price' => 799000,
            'color' => 'Sage Mist',
            'sku' => 'CORK-SC-SAGEMIST-20',
        ],
        [
            'name' => 'Sierra Sport Canteen Thyme',
            'slug' => 'sierra-sport-canteen-thyme-20oz',
            'brand' => 'CORKCICLE',
            'description' => 'Sierra Sport Canteen warna Thyme, ukuran 20oz. Botol stainless steel berinsulasi tiga lapis dengan performa hingga 25 jam dingin dan 12 jam panas menurut CORKCICLE.',
            'capacity' => '20 oz (591 mL)',
            'material' => '18/8 Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/2020MT-01.png?crop=center&height=500&v=1769545948&width=500',
            'price' => 799000,
            'color' => 'Thyme',
            'sku' => 'CORK-SC-THYME-20',
        ],
        [
            'name' => 'Rifle Paper Co. Stemless Cherries',
            'slug' => 'rifle-paper-co-stemless-cherries-12oz',
            'brand' => 'CORKCICLE × Rifle Paper Co.',
            'description' => 'Kolaborasi CORKCICLE × Rifle Paper Co. dengan motif Cherries, ukuran 12oz. CORKCICLE mencantumkan kemampuan menjaga minuman dingin hingga 9 jam dan panas hingga 3 jam untuk lini stemless insulated.',
            'capacity' => '12 oz (355 mL)',
            'material' => 'Stainless Steel',
            'image_url' => 'https://riflepaperco.com/cdn/shop/files/RP2312GCCH-01.jpg?v=1770857571',
            'price' => 599000,
            'color' => 'Cherries',
            'sku' => 'CORK-RP-STEM-CHERRIES-12',
        ],
        [
            'name' => 'Rifle Paper Co. Sport Canteen Cherries',
            'slug' => 'rifle-paper-co-sport-canteen-cherries-20oz',
            'brand' => 'CORKCICLE × Rifle Paper Co.',
            'description' => 'Kolaborasi CORKCICLE × Rifle Paper Co. motif Cherries, ukuran 20oz. CORKCICLE mencantumkan hingga 25 jam dingin dan 12 jam panas, dengan sisi flat grip dan penutup leak-proof.',
            'capacity' => '20 oz (591 mL)',
            'material' => 'Stainless Steel',
            'image_url' => 'https://corkcicle.com/cdn/shop/files/RP2020GCCH_hero.png?crop=center&height=2048&v=1771451099&width=2048',
            'price' => 899000,
            'color' => 'Cherries',
            'sku' => 'CORK-RP-CANTEEN-CHERRIES-20',
        ],
    ];

    $findProduct = $pdo->prepare('SELECT id FROM products WHERE slug=? LIMIT 1');
    $insertProduct = $pdo->prepare('INSERT INTO products(name,slug,brand,description,capacity,material,image_url,price,active,created_at) VALUES(?,?,?,?,?,?,?,?,1,?)');
    $findVariant = $pdo->prepare('SELECT id FROM product_variants WHERE sku=? LIMIT 1');
    $insertVariant = $pdo->prepare('INSERT INTO product_variants(product_id,name,color,sku,stock,image_url,active) VALUES(?,?,?,?,?, ?,1)');

    foreach ($catalog as $row) {
        $findProduct->execute([$row['slug']]);
        $productId = $findProduct->fetchColumn();
        if (!$productId) {
            $insertProduct->execute([
                $row['name'], $row['slug'], $row['brand'], $row['description'],
                $row['capacity'], $row['material'], $row['image_url'], $row['price'], $now
            ]);
            $productId = (int)$pdo->lastInsertId();
        } else {
            $productId = (int)$productId;
        }

        $findVariant->execute([$row['sku']]);
        if (!$findVariant->fetchColumn()) {
            $variantName = $row['color'] . ' • ' . str_replace([' (591 mL)',' (946 mL)'], '', $row['capacity']);
            $insertVariant->execute([$productId, $variantName, $row['color'], $row['sku'], 20, $row['image_url']]);
        }
    }
}
