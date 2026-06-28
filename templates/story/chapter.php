<article class="story-chapter">
    <nav class="breadcrumb">
        <a href="/story/<?= (int) $story['sid'] ?>"><?= $this->e($story['title']) ?></a>
        &rsaquo; <?= $this->e($chapter['title'] ?: 'Chapter') ?>
    </nav>

    <h1><?= $this->e($chapter['title'] ?: 'Chapter') ?></h1>
    <p class="meta"><?= $this->__('by') ?> <a href="/user/<?= (int) $story['uid'] ?>"><?= $this->e($story['penname']) ?></a></p>

    <?php if ($chapter['notes']): ?>
        <div class="notes">
            <p><?= nl2br($this->e($chapter['notes'])) ?></p>
        </div>
    <?php endif; ?>

    <div class="chapter-text">
        <?= nl2br($this->e($text)) ?>
    </div>

    <?php if ($chapter['endnotes']): ?>
        <div class="endnotes">
            <p><?= nl2br($this->e($chapter['endnotes'])) ?></p>
        </div>
    <?php endif; ?>

    <section class="reviews">
        <h2><?= $this->__('reviews') ?></h2>
        <?php if (empty($reviews)): ?>
            <p>No reviews for this chapter yet.</p>
        <?php else: ?>
            <?php foreach ($reviews as $rev): ?>
                <div class="review">
                    <p class="reviewer"><?= $this->e($rev['reviewer'] ?? $rev['author_penname'] ?? $this->__('anonymous')) ?> <span class="date"><?= $this->e($rev['date']) ?></span></p>
                    <p><?= nl2br($this->e($rev['review'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($this->config()->get('site.reviewsallowed', true)): ?>
            <h3>Add a chapter review</h3>
            <form method="post" action="/reviews/add">
                <input type="hidden" name="sid" value="<?= (int) $story['sid'] ?>">
                <input type="hidden" name="chapid" value="<?= (int) $chapter['chapid'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $this->csrf() ?>">
                <?php if (!$this->auth()->check()): ?>
                    <label>Your name <input type="text" name="reviewer" required></label>
                    <label>Email <input type="email" name="email" required></label>
                <?php endif; ?>
                <label>Review <textarea name="review" rows="5" required></textarea></label>
                <label>Rating
                    <select name="rating">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <button type="submit">Submit</button>
            </form>
        <?php endif; ?>
    </section>
</article>
