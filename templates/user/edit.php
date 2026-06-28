<section class="user-edit">
    <h1><?= $this->__('edit') ?> <?= $this->__('profile') ?></h1>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= $this->e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" action="/user/edit">
        <input type="hidden" name="csrf_token" value="<?= $this->csrf() ?>">
        <label><?= $this->__('realname') ?>
            <input type="text" name="realname" value="<?= $this->e($author['realname'] ?? '') ?>">
        </label>
        <label><?= $this->__('email') ?>
            <input type="email" name="email" value="<?= $this->e($author['email'] ?? '') ?>" required>
        </label>
        <label><?= $this->__('age') ?>
            <input type="number" name="age" value="<?= (int) ($author['age'] ?? 0) ?>">
        </label>
        <label><?= $this->__('location') ?>
            <input type="text" name="location" value="<?= $this->e($author['location'] ?? '') ?>">
        </label>
        <label><?= $this->__('website') ?>
            <input type="url" name="website" value="<?= $this->e($author['website'] ?? '') ?>">
        </label>
        <label><?= $this->__('bio') ?>
            <textarea name="bio" rows="6"><?= $this->e($author['bio'] ?? '') ?></textarea>
        </label>
        <button type="submit"><?= $this->__('save') ?></button>
    </form>
</section>
