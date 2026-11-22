<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle='Products'; $activePage='products/index.php'; $basePath='../';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 try{
  $action=$_POST['action']??'';
  if($action==='save'){
   $id=(int)($_POST['product_id']??0);$name=trim((string)($_POST['product_name']??''));$cat=(int)($_POST['category_id']??0);$price=nonnegative_money($_POST['selling_price']??null,'Selling price');$reorder=nonnegative_money($_POST['reorder_level']??null,'Reorder level');$unit=trim((string)($_POST['unit']??''));
   $allowed=['kg','g','litre','ml','packet','piece','bottle','box','dozen'];
   if($name===''||$cat<1||!in_array($unit,$allowed,true))throw new RuntimeException('Enter a product name, category and a supported unit.');
   if($id){$old=inventory_row($pdo,$id,false);if((float)$old['quantity_available']>0 && $old['unit']!==$unit)throw new RuntimeException('Unit cannot be changed while stock is available.');$s=$pdo->prepare('UPDATE products SET product_name=?,category_id=?,selling_price=?,reorder_level=?,unit=? WHERE product_id=?');$s->execute([$name,$cat,$price,$reorder,$unit,$id]);flash('success','Product updated.');}
   else{
    $opening=isset($_POST['opening_quantity'])&&$_POST['opening_quantity']!==''?positive_decimal($_POST['opening_quantity'],'Opening quantity'):0.0;
    $openingCost=isset($_POST['opening_unit_cost'])&&$_POST['opening_unit_cost']!==''?nonnegative_money($_POST['opening_unit_cost'],'Opening unit cost'):0.0;
    if($opening>0 && $openingCost<=0)throw new RuntimeException('Enter an opening unit cost when opening quantity is provided.');
    $pdo->beginTransaction();$s=$pdo->prepare('INSERT INTO products(product_name,category_id,selling_price,reorder_level,unit) VALUES(?,?,?,?,?)');$s->execute([$name,$cat,$price,$reorder,$unit]);$newId=(int)$pdo->lastInsertId();$pdo->prepare('INSERT INTO inventory(product_id,quantity_available,average_cost) VALUES(?,0,0)')->execute([$newId]);
    if($opening>0){$pdo->prepare('UPDATE inventory SET quantity_available=?,average_cost=?,last_updated=NOW() WHERE product_id=?')->execute([$opening,$openingCost,$newId]);insert_movement($pdo,$newId,'OPENING',$opening,$openingCost,'Opening stock entry','OPENING-'.$newId);}
    $pdo->commit();flash('success','Product added'.($opening>0?' with opening stock.':'.'));
   }
  }elseif($action==='delete'){$s=$pdo->prepare('DELETE FROM products WHERE product_id=?');$s->execute([(int)$_POST['product_id']]);flash('success','Product deleted.');}
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash('error','Operation failed: '.$e->getMessage());}
 redirect('index.php');
}
$edit=null;if(isset($_GET['edit'])){$s=$pdo->prepare('SELECT p.*,i.quantity_available FROM products p LEFT JOIN inventory i ON i.product_id=p.product_id WHERE p.product_id=?');$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
$categories=category_options($pdo);$search=trim((string)($_GET['q']??''));
$sql='SELECT p.*,c.category_name,i.quantity_available,i.average_cost FROM products p JOIN categories c ON c.category_id=p.category_id JOIN inventory i ON i.product_id=p.product_id';$params=[];if($search!==''){$sql.=' WHERE p.product_name LIKE ? OR c.category_name LIKE ?';$params=['%'.$search.'%','%'.$search.'%'];}$sql.=' ORDER BY p.product_name';$st=$pdo->prepare($sql);$st->execute($params);$rows=$st->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><div><p class="eyebrow">CATALOGUE</p><h1>Products</h1><p class="muted">Maintain grocery products, selling prices, units and reorder levels.</p></div></section>
<div class="two-column"><section class="panel"><h2><?= $edit?'Edit product':'Add product' ?></h2><form method="post" class="form-grid"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="product_id" value="<?= e($edit['product_id']??'') ?>">
<label class="full">Product name<input name="product_name" maxlength="100" required value="<?= e($edit['product_name']??'') ?>" placeholder="e.g. Basmati Rice"></label>
<label>Category<select name="category_id" required><option value="">Select category</option><?php foreach($categories as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= (int)($edit['category_id']??0)===(int)$c['category_id']?'selected':'' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?></select></label>
<label>Unit<select name="unit" required><?php foreach(['kg','g','litre','ml','packet','piece','bottle','box','dozen'] as $u): ?><option value="<?= e($u) ?>" <?= ($edit['unit']??'kg')===$u?'selected':'' ?>><?= e($u) ?></option><?php endforeach; ?></select></label>
<label>Selling price (₹)<input type="number" name="selling_price" min="0" step="0.01" required value="<?= e($edit['selling_price']??'') ?>"></label>
<label>Reorder level<input type="number" name="reorder_level" min="0" step="0.001" required value="<?= e($edit['reorder_level']??'0') ?>"></label>
<?php if(!$edit): ?><div class="field-note full">Optional opening stock (leave blank if you want to record stock later).</div><label>Opening quantity<input type="number" name="opening_quantity" min="0.001" step="0.001" placeholder="e.g. 25.500"></label><label>Opening cost per unit (₹)<input type="number" name="opening_unit_cost" min="0.01" step="0.01" placeholder="e.g. 48.00"></label><?php endif; ?>
<div class="form-actions full"><button class="btn primary"><?= $edit?'Update product':'Add product' ?></button><?php if($edit): ?><a class="btn secondary" href="index.php">Cancel</a><?php endif; ?></div></form></section>
<section class="panel"><div class="panel-heading"><div><h2>Product list</h2><p class="muted">Search by product or category.</p></div><span class="count-label"><?= count($rows) ?> products</span></div><form class="search-row" method="get"><input name="q" value="<?= e($search) ?>" placeholder="Search products..."><button class="btn secondary">Search</button><?php if($search!==''): ?><a class="text-link" href="index.php">Clear</a><?php endif; ?></form><div class="table-wrap"><table><thead><tr><th>Product</th><th>Category</th><th>Stock</th><th>Price</th><th>Reorder</th><th></th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><strong><?= e($r['product_name']) ?></strong><small class="subline">Cost <?= money($r['average_cost']) ?></small></td><td><?= e($r['category_name']) ?></td><td><?= qty($r['quantity_available']) ?> <?= e($r['unit']) ?></td><td><?= money($r['selling_price']) ?></td><td><?= qty($r['reorder_level']) ?> <?= e($r['unit']) ?></td><td class="actions"><a href="?edit=<?= (int)$r['product_id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this product? Products with transaction history cannot be deleted.');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="product_id" value="<?= (int)$r['product_id'] ?>"><button class="link-danger">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div></section></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
