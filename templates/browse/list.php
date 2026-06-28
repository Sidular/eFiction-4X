<section class="browse-list">
    <h1><?= $this->e(ucfirst($type)) ?></h1>
    <?php if (empty($items)): ?>
        <p>Nothing to list yet.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($items as $item): ?>
                <li>
                    <a href="/search?<?= $this->e($type) ?>=<?= (int) ($item['classid'] ?? $item['catid'] ?? 0) ?>">
                        <?= $this->e($item['name'] ?? $item['category'] ?? 'Untitled') ?>
                    </a>
                    <?php if (!empty($item['description'])): ?>
                        <p><?= $this->e($item['description']) ?></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
