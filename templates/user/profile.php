<section class="user-profile">
    <h1><?= $this->e($author['penname']) ?></h1>
    <?php if ($author['realname']): ?>
        <p><?= $this->e($author['realname']) ?></p>
    <?php endif; ?>
    <?php if ($author['bio']): ?>
        <div class="bio"><p><?= nl2br($this->e($author['bio'])) ?></p></div>
    <?php endif; ?>

    <p class="meta">
        <?= (int) $author['story_count'] ?> <?= $this->__('stories') ?>
        &mdash; <?= (int) $author['review_count'] ?> <?= $this->__('reviews') ?>
    </p>

    <h2><?= $this->__('stories') ?></h2>
    <?php if (empty($stories)): ?>
        <p>No stories.</p>
    <?php else: ?>
        <ul class="story-list">
            <?php foreach ($stories as $story): ?>
                <li><a href="/story/<?= (int) $story['sid'] ?>"><?= $this->e($story['title']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (!empty($series)): ?>
        <h2><?= $this->__('series') ?></h2>
        <ul>
            <?php foreach ($series as $s): ?>
                <li><a href="/series/<?= (int) $s['seriesid'] ?>"><?= $this->e($s['title']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
