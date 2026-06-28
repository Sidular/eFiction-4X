<section class="search-page">
    <h1><?= $this->__('search') ?></h1>
    <form method="post" action="/search">
        <input type="hidden" name="csrf_token" value="<?= $this->csrf() ?>">
        <label>Keywords
            <input type="text" name="q" value="<?= $this->e($query) ?>">
        </label>
        <label>Category
            <select name="catid">
                <option value="">Any</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['catid'] ?>" <?= ($filters['catid'] ?? 0) == $cat['catid'] ? 'selected' : '' ?>>
                        <?= $this->e($cat['category']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Rating
            <select name="rid">
                <option value="">Any</option>
                <?php foreach ($ratings as $r): ?>
                    <option value="<?= (int) $r['classid'] ?>" <?= ($filters['rid'] ?? 0) == $r['classid'] ? 'selected' : '' ?>>
                        <?= $this->e($r['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <input type="checkbox" name="completed" value="1" <?= !empty($filters['completed']) ? 'checked' : '' ?>> Completed only
        </label>
        <button type="submit">Search</button>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' || $query || $filters): ?>
        <h2>Results</h2>
        <?php if (empty($results)): ?>
            <p>No stories found.</p>
        <?php else: ?>
            <ul class="story-list">
                <?php foreach ($results as $story): ?>
                    <li>
                        <a href="/story/<?= (int) $story['sid'] ?>"><?= $this->e($story['title']) ?></a>
                        <?= $this->__('by') ?> <?= $this->e($story['penname']) ?>
                        <span class="meta"><?= (int) $story['wordcount'] ?> words</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</section>
