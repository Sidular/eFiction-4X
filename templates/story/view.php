<article class="story-view">
    <header>
        <h1><?= $this->e($story['title']) ?></h1>
        <p class="meta">
            <?= $this->__('by') ?> <a href="/user/<?= (int) $story['uid'] ?>"><?= $this->e($story['penname']) ?></a>
            &mdash; <?= $this->__('rating') ?>: <?= (int) $story['rating'] ?>/10
            &mdash; <?= $story['completed'] ? $this->__('completed') : $this->__('incomplete') ?>
            &mdash; <?= (int) $story['wordcount'] ?> <?= $this->__('words') ?>
        </p>
    </header>

    <?php if ($story['summary']): ?>
        <div class="summary">
            <p><?= nl2br($this->e($story['summary'])) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($story['storynotes']): ?>
        <div class="notes">
            <strong>Story Notes:</strong>
            <p><?= nl2br($this->e($story['storynotes'])) ?></p>
        </div>
    <?php endif; ?>

    <section class="chapters">
        <h2><?= $this->__('chapters') ?></h2>
        <?php if (empty($chapters)): ?>
            <p>No chapters available.</p>
        <?php else: ?>
            <ol>
                <?php foreach ($chapters as $ch): ?>
                    <li>
                        <a href="/story/<?= (int) $story['sid'] ?>/chapter/<?= (int) $ch['chapid'] ?>">
                            <?= $this->e($ch['title'] ?: 'Chapter ' . ((int) $ch['inorder'] + 1)) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>

    <section class="reviews">
        <h2><?= $this->__('reviews') ?></h2>
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
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($this->config()->get('site.reviewsallowed', true)): ?>
            <h3>Add a review</h3>
            <form method="post" action="/reviews/add">
                <input type="hidden" name="sid" value="<?= (int) $story['sid'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $this->csrf() ?>">
                <?php if (!$this->auth()->check()): ?>
                    <label>Your name
                        <input type="text" name="reviewer" required>
                    </label>
                    <label>Email
                        <input type="email" name="email" required>
                    </label>
                <?php endif; ?>
                <label>Review
                    <textarea name="review" rows="5" required></textarea>
                </label>
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
