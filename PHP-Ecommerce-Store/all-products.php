<?php
$pageTitle = 'All Products | My Store';
include 'includes/header.php';

$products = [
    'Wireless Mouse' => [
        'price' => 250,
        'img'   => 'images/1.png',
        'desc'  => 'A smooth wireless mouse with ergonomic design.',
    ],
    'Mechanical Keyboard' => [
        'price' => 950,
        'img'   => 'images/2.png',
        'desc'  => 'RGB backlit mechanical keyboard with blue switches.',
    ],
    'Bluetooth Headphones' => [
        'price' => 620,
        'img'   => 'images/3.png',
        'desc'  => 'Noise-cancelling over-ear headphones.',
    ],
    '4K Monitor' => [
        'price' => 6500,
        'img'   => 'images/4.png',
        'desc'  => '27 inch 4K UHD monitor with vivid colors.',
    ],
    'Webcam HD' => [
        'price' => 480,
        'img'   => 'images/5.png',
        'desc'  => '1080p HD webcam with built-in microphone.',
    ],
    'USB-C Hub' => [
        'price' => 350,
        'img'   => 'images/6.png',
        'desc'  => '7-in-1 USB-C hub with HDMI and card reader.',
    ],
];
?>

<div class="container py-5">
    <h2 class="text-center mb-4">All Products</h2>
    <div class="row">
        <?php foreach ($products as $product => $values): ?>
            <div class="col-md-4 mb-4">
                <div class="card product-card h-100">
                    <img src="<?php echo htmlspecialchars($values['img']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product); ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($product); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($values['desc']); ?></p>
                        <p class="card-text font-weight-bold mt-auto"><?php echo htmlspecialchars($values['price']); ?> EGP</p>
                        <button type="button" class="btn btn-primary" onclick="addToCart('<?php echo htmlspecialchars($product, ENT_QUOTES); ?>')">Add to Cart</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div aria-live="polite" aria-atomic="true" style="position: fixed; top: 20px; right: 20px; z-index: 1050;">
    <div id="cartToast" class="toast" data-delay="2000" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="mr-auto">My Store</strong>
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">&times;</button>
        </div>
        <div class="toast-body" id="cartToastBody"></div>
    </div>
</div>

<script>
function addToCart(productName) {
    document.getElementById('cartToastBody').textContent = productName + ' added to cart!';
    $('#cartToast').toast('show');
}
</script>

<?php include 'includes/footer.php'; ?>
