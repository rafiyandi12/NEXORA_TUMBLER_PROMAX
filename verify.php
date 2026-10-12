<?php
header('Content-Type: text/plain; charset=utf-8');
echo "NEXORA TUMBLER PRO V4 CHECK\n";
echo "===========================\n";
$mods=get_loaded_extensions();
echo 'PHP: '.PHP_VERSION."\n";
echo 'PDO: '.(extension_loaded('pdo')?'OK':'MISSING')."\n";
echo 'PDO SQLite: '.(extension_loaded('pdo_sqlite')?'OK':'MISSING')."\n";
echo 'SQLite3: '.(extension_loaded('sqlite3')?'OK':'MISSING')."\n";
$sourceFiles=['app.php','index.php','views/home.php','views/products.php','views/product.php','views/cart.php','views/checkout.php','views/order.php','views/admin_order.php'];
$placeholderFound=false;
foreach($sourceFiles as $f){
    $path=__DIR__.'/'.$f;
    if(!is_file($path)){echo "MISSING FILE: {$f}\n"; continue;}
    $code=file_get_contents($path);
    $out=[];
    exec('php -l '.escapeshellarg($path).' 2>&1',$out,$status);
    echo basename($path).': '.($status===0?'SYNTAX OK':'SYNTAX ERROR')."\n";
    if(str_contains($code,'tumbler-placeholder.svg')) $placeholderFound=true;
}
echo 'Placeholder tumbler references: '.($placeholderFound?'FOUND':'NONE')."\n";
if(!extension_loaded('pdo_sqlite')){echo "\nRuntime DB check skipped because pdo_sqlite is not enabled.\n";exit;}
require __DIR__ . '/app.php';
$db=db();
$productCount=(int)$db->query("SELECT COUNT(*) FROM products WHERE active=1")->fetchColumn();
$variantCount=(int)$db->query("SELECT COUNT(*) FROM product_variants WHERE active=1")->fetchColumn();
$realPhotoDomains=(int)$db->query("SELECT COUNT(*) FROM products WHERE active=1 AND (image_url LIKE 'https://corkcicle.com/%' OR image_url LIKE 'https://riflepaperco.com/%')")->fetchColumn();
$skuDup=(int)$db->query("SELECT COUNT(*) FROM (SELECT sku,COUNT(*) c FROM product_variants GROUP BY sku HAVING c>1)")->fetchColumn();
$requiredSlugs=[
 'sierra-sport-canteen-terracotta-20oz','sierra-sport-canteen-harbor-20oz','sierra-sport-canteen-brick-20oz','sierra-sport-canteen-brick-32oz',
 'sierra-sport-canteen-desert-20oz','sierra-sport-canteen-desert-32oz','sierra-sport-canteen-cacao-20oz','sierra-sport-canteen-cacao-32oz',
 'sierra-sport-canteen-desert-rose-20oz','sierra-sport-canteen-olive-20oz','sierra-sport-canteen-olive-32oz','sierra-sport-canteen-mauve-20oz',
 'sierra-sport-canteen-north-shore-20oz','sierra-sport-canteen-north-shore-32oz','sierra-sport-canteen-flint-20oz','sierra-sport-canteen-flint-32oz',
 'sierra-sport-canteen-sage-mist-20oz','sierra-sport-canteen-thyme-20oz','rifle-paper-co-stemless-cherries-12oz','rifle-paper-co-sport-canteen-cherries-20oz'
];
$missing=[];$q=$db->prepare('SELECT id FROM products WHERE slug=? AND active=1 LIMIT 1');
foreach($requiredSlugs as $slug){$q->execute([$slug]); if(!$q->fetchColumn()) $missing[]=$slug;}
echo 'Active products: '.$productCount."\n";
echo 'Active variants: '.$variantCount."\n";
echo 'Real-photo URL products: '.$realPhotoDomains."\n";
echo 'Duplicate SKUs: '.$skuDup."\n";
echo 'New 20-product catalog records: '.(count($requiredSlugs)-count($missing)).'/20'."\n";
if($missing) echo "Missing slugs: ".implode(', ',$missing)."\n";
echo 'Database file: data/nexora.sqlite\n';
