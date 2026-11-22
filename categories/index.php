<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle='Categories'; $activePage='categories/index.php'; $basePath='../';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf(); $action=$_POST['action']??'';
 try {
  if($action==='save') {
   $id=(int)($_POST['category_id']??0); $name=trim((string)($_POST['category_name']??'')); $desc=trim((string)($_POST['description']??''));
   if($name==='') throw new RuntimeException('Category name is required.');
   if($id){$s=$pdo->prepare('UPDATE categories SET category_name=?, description=? WHERE category_id=?');$s->execute([$name,$desc?:null,$id]);$msg='Category updated.';}
   else{$s=$pdo->prepare('INSERT INTO categories(category_name,description) VALUES(?,?)');$s->execute([$name,$desc?:null]);$msg='Category added.';}
   flash('success',$msg);
  } elseif($action==='delete') { $s=$pdo->prepare('DELETE FROM categories WHERE category_id=?');$s->execute([(int)$_POST['category_id']]);flash('success','Category deleted.'); }
 } catch(Throwable $e){flash('error','Could not save category: '.$e->getMessage());}
 redirect('index.php');
}
$edit=null;if(isset($_GET['edit'])){$s=$pdo->prepare('SELECT * FROM categories WHERE category_id=?');$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
$rows=$pdo->query('SELECT c.*, COUNT(p.product_id) product_count FROM categories c LEFT JOIN products p ON p.category_id=c.category_id GROUP BY c.category_id ORDER BY c.category_name')->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><div><p class="eyebrow">PRODUCT ORGANISATION</p><h1>Categories</h1><p class="muted">Group grocery products into easy-to-manage categories.</p></div></section>
<div class="two-column"><section class="panel"><h2><?= $edit?'Edit category':'Add category' ?></h2><form method="post" class="form-grid"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="category_id" value="<?= e($edit['category_id']??'') ?>"><label class="full">Category name<input name="category_name" maxlength="60" required value="<?= e($edit['category_name']??'') ?>" placeholder="e.g. Grains"></label><label class="full">Description<input name="description" maxlength="150" value="<?= e($edit['description']??'') ?>" placeholder="Optional description"></label><div class="form-actions full"><button class="btn primary"><?= $edit?'Update category':'Save category' ?></button><?php if($edit): ?><a class="btn secondary" href="index.php">Cancel</a><?php endif; ?></div></form></section>
<section class="panel"><div class="panel-heading"><h2>Category list</h2><span class="count-label"><?= count($rows) ?> total</span></div><div class="table-wrap"><table><thead><tr><th>Category</th><th>Products</th><th>Description</th><th></th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><strong><?= e($r['category_name']) ?></strong></td><td><?= (int)$r['product_count'] ?></td><td><?= e($r['description']) ?></td><td class="actions"><a href="?edit=<?= (int)$r['category_id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this category? It cannot be deleted while products use it.');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="category_id" value="<?= (int)$r['category_id'] ?>"><button class="link-danger">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div></section></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
