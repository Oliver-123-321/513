                    <p class="product-category"><?= sanitize($product['category']); ?></p>
                    <h3><?= sanitize($product['name']); ?></h3>
                    <?php if (!empty($product['description'])): ?>
                        <p class="product-description"><?= sanitize(mb_substr($product['description'], 0, 80)); ?><?= mb_strlen($product['description']) > 80 ? '...' : ''; ?></p>
                    <?php endif; ?>
                    <div class="product-rating">