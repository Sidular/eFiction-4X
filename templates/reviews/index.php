<section class="reviews-index">
    <h1><?= $this->__('reviews') ?></h1>
    <?php if (empty($reviews)): ?>
        <p>No reviews yet.</p>
    <?php else: ?>
        <?php foreach ($reviews as $rev): ?>
            <div class="review">
                <p class="reviewer">
                    <?= $this->e($rev['reviewer'] ?? $rev['author_penname'] ?? $this->__('anonymous')) ?>
                    <span class="date"><?= $this->e($rev['date']) ?></span>
                </p>
                <p><?= nl2br($this->e($rev['review'])) ?></p>
                <?php if (!empty($rev['story_title'])): ?>
                    <p><a href="/story/<?= (int) $rev['sid'] ?>"><?= $this->e($rev['story_title']) ?></a></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
