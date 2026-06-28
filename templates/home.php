<section class="home">
    <h2><?= $this->__('welcome') ?></h2>

    <?php if (!empty($featured)): ?>
        <div class="featured-story">
            <h3><?= $this->__('featured_story') ?></h3>
            <article>
                <h4><a href="/story/<?= (int) $featured['sid'] ?>"><?= $this->e($featured['title']) ?></a></h4>
                <p class="byline"><?= $this->__('by') ?> <?= $this->e($featured['penname'] ?? $this->__('anonymous')) ?></p>
                <p><?= $this->e($featured['summary']) ?></p>
            </article>
        </div>
    <?php endif; ?>

    <h3><?= $this->__('latest_stories') ?></h3>
    <?php if (!empty($latest)): ?>
        <ul class="story-list">
            <?php foreach ($latest as $story): ?>
                <li>
                    <a href="/story/<?= (int) $story['sid'] ?>"><?= $this->e($story['title']) ?></a>
                    <span class="byline"><?= $this->__('by') ?> <?= $this->e($story['penname'] ?? $this->__('anonymous')) ?></span>
                    <span class="meta"><?= (int) $story['wordcount'] ?> <?= $this->__('words') ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p><?= $this->__('no_stories') ?></p>
    <?php endif; ?>

    <?php if (!empty($news)): ?>
        <div class="news">
            <h3><?= $this->__('news') ?></h3>
            <?php foreach ($news as $item): ?>
                <article>
                    <h4><?= $this->e($item['title']) ?></h4>
                    <p><?= nl2br($this->e($item['content'])) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
