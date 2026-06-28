<section class="user-register">
    <h1><?= $this->__('register') ?></h1>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= $this->e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" action="/user/register">
        <input type="hidden" name="csrf_token" value="<?= $this->csrf() ?>">
        <label><?= $this->__('penname') ?>
            <input type="text" name="penname" value="<?= $this->e($data['penname'] ?? '') ?>" required>
        </label>
        <label><?= $this->__('email') ?>
            <input type="email" name="email" value="<?= $this->e($data['email'] ?? '') ?>" required>
        </label>
        <label><?= $this->__('password') ?>
            <input type="password" name="password" minlength="8" required>
        </label>
        <label><?= $this->__('confirm_password') ?>
            <input type="password" name="password_confirm" minlength="8" required>
        </label>
        <button type="submit"><?= $this->__('register') ?></button>
    </form>
</section>
