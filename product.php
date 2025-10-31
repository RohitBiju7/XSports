<?php
session_start();
require_once 'includes/db.php';

// Validate and fetch product id
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($productId <= 0) {
	http_response_code(400);
	die('Invalid product id.');
}

// Fetch product
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
	http_response_code(404);
	die('Product not found.');
}

include('includes/header.php');
?>

<main class="page-container" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
	<section class="product-detail" style="display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:start;background:white;padding:40px;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.1);">
		<?php
			$images = [];
			if (!empty($product['image_path'])) { $images[] = $product['image_path']; }
			if (!empty($product['image_path2'] ?? null)) { $images[] = $product['image_path2']; }
			if (!empty($product['image_path3'] ?? null)) { $images[] = $product['image_path3']; }
			if (!empty($product['image_path4'] ?? null)) { $images[] = $product['image_path4']; }
			if (empty($images)) { $images[] = 'images/hero1.jpg'; }
		?>
		<div class="product-images">
			<div id="mainImageWrapper" style="border:1px solid #eee;border-radius:8px;overflow:hidden;">
				<img id="mainImage" src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width:100%;height:auto;display:block;">
			</div>
			<?php if (count($images) > 1): ?>
			<div class="thumbs" style="margin-top:12px;display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
				<?php foreach ($images as $idx => $img): ?>
					<div style="border:1px solid #eee;border-radius:6px;overflow:hidden;cursor:pointer;<?php echo $idx===0 ? 'outline:2px solid #385060;' : '' ?>" onclick="setMainImage('<?php echo htmlspecialchars($img); ?>', this)">
						<img src="<?php echo htmlspecialchars($img); ?>" alt="thumb <?php echo $idx+1; ?>" style="width:100%;height:100px;object-fit:cover;display:block;">
					</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
		<div class="product-info" style="padding-left:20px;">
			<p class="brand-name" style="margin:0;color:#666;font-weight:600;font-size:14px;text-transform:uppercase;letter-spacing:1px;">
				<?php echo htmlspecialchars($product['brand']); ?>
			</p>
			<h1 class="product-name" style="margin-top:8px;margin-bottom:16px;font-size:32px;font-weight:700;color:#333;line-height:1.2;">
				<?php echo htmlspecialchars($product['name']); ?>
			</h1>
			<div class="price" style="font-size:28px;font-weight:700;margin-bottom:20px;color:#005eb8;">₹<?php echo number_format($product['price'], 2); ?></div>
			<?php if (!empty($product['description'])): ?>
				<div style="background:#f8f9fa;padding:20px;border-radius:8px;margin-bottom:24px;">
					<h3 style="margin:0 0 12px 0;font-size:18px;color:#333;">Product Description</h3>
					<p style="line-height:1.6;margin:0;color:#666;"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
				</div>
			<?php endif; ?>
			<div style="margin-bottom:24px;padding:16px;background:#f8f9fa;border-radius:8px;border-left:4px solid <?php echo (int)$product['quantity'] > 0 ? '#28a745' : '#dc3545'; ?>;">
				<p style="margin:0;font-weight:600;color:<?php echo (int)$product['quantity'] > 0 ? '#28a745' : '#dc3545'; ?>;">
					<?php if ((int)$product['quantity'] > 0): ?>
						<i class="fa-solid fa-check-circle" style="margin-right:8px;"></i>In Stock (<?php echo $product['quantity']; ?> available)
					<?php else: ?>
						<i class="fa-solid fa-times-circle" style="margin-right:8px;"></i>Out of Stock
					<?php endif; ?>
				</p>
			</div>
			<div class="product-actions" style="display:flex;flex-direction:column;gap:12px;margin-top:24px;">
				<?php if ((int)$product['quantity'] > 0): ?>
					<form method="post" action="buy_now.php" style="width:100%;">
						<input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
						<button type="submit" name="buy_now" class="btn-buy-now" style="background: #005eb8 !important; color: white !important; border: 2px solid #005eb8 !important; padding: 16px 24px !important; border-radius: 8px !important; cursor: pointer; font-weight: 600; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px; width: 100%; box-sizing: border-box; margin: 0 !important; transition: all 0.3s ease;">BUY NOW</button>
					</form>
					<form method="post" action="add_to_cart.php" style="width:100%;">
						<input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
						<button type="submit" name="add_to_cart" class="btn-add-cart" style="background: white !important; color: #005eb8 !important; border: 2px solid #005eb8 !important; padding: 16px 24px !important; border-radius: 8px !important; cursor: pointer; font-weight: 600; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px; width: 100%; box-sizing: border-box; margin: 0 !important; transition: all 0.3s ease;">ADD TO CART</button>
					</form>
				<?php else: ?>
					<button class="btn-buy-now" disabled style="background: #ccc !important; color: #666 !important; border: 2px solid #ccc !important; padding: 16px 24px !important; border-radius: 8px !important; cursor: not-allowed; font-weight: 600; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px; width: 100%; box-sizing: border-box; margin: 0 !important;">OUT OF STOCK</button>
				<?php endif; ?>
				<form method="post" action="add_to_wishlist.php" style="width:100%;">
					<input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
					<button type="submit" name="add_to_wishlist" class="btn-wishlist" style="background: #005eb8 !important; color: white !important; border: 2px solid #005eb8 !important; padding: 16px 24px !important; border-radius: 8px !important; cursor: pointer; font-weight: 600; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px; width: 100%; box-sizing: border-box; margin: 0 !important; transition: all 0.3s ease;">WISHLIST</button>
				</form>
			</div>
		</div>
	</section>
</main>

<script>
function setMainImage(src, thumbEl){
	var main = document.getElementById('mainImage');
	main.src = src;
	var thumbs = thumbEl.parentNode.querySelectorAll('div');
	thumbs.forEach(function(d){ d.style.outline = 'none'; });
	thumbEl.style.outline = '2px solid #385060';
}
</script>

<style>
@media (max-width: 768px) {
	.page-container {
		padding: 20px 15px !important;
	}
	
	.product-detail {
		grid-template-columns: 1fr !important;
		gap: 24px !important;
		padding: 20px !important;
	}
	
	.product-info {
		padding-left: 0 !important;
	}
	
	.product-name {
		font-size: 24px !important;
	}
	
	.price {
		font-size: 24px !important;
	}
}
</style>

<?php include('includes/footer.php'); ?>
