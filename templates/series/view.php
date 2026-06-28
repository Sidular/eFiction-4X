<article class="series-view">
    <h1><?= $this->e($series['title']) ?></h1>
    <p class="meta"><?= $this->__('by') ?> <a href="/user/<?= (int) $series['uid'] ?>"><?= $this->e($series['penname']) ?></a></p>

    <?php if ($series['summary']): ?>
        <div class="summary"><p><?= nl2br($this->e($series['summary'])) ?></p></div>
    <?php endif; ?>

    <h2><?= $this->__('stories') ?></h2>
    <?php if (empty($series['stories'])): ?>
        <p>No stories in this series.</p>
    <?php else: ?>
        <ol>
            <?php foreach ($series['stories'] as $story): ?>
                <li><a href="/story/<?= (int) $story['sid'] ?>"><?= $this->e($story['title']) ?></a> <?= $this->__('by') ?> <?= $this->e($story['penname']) ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</article>
