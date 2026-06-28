<section class="browse-index">
    <h1><?= $this->__('browse') ?></h1>
    <?php if (empty($categories)): ?>
        <p>No categories yet.</p>
    <?php else: ?>
        <ul class="category-tree">
            <?php foreach ($categories as $cat): ?>
                <li>
                    <a href="/browse?catid=<?= (int) $cat['catid'] ?>"><?= $this->e($cat['category']) ?></a>
                    <?php if (!empty($cat['children'])): ?>
                        <ul>
                            <?php foreach ($cat['children'] as $child): ?>
                                <li>
                                    <a href="/browse?catid=<?= (int) $child['catid'] ?>"><?= $this->e($child['category']) ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
