<section class="user-login">
    <h1><?= $this->__('login') ?></h1>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= $this->e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="/user/login">
        <input type="hidden" name="csrf_token" value="<?= $this->csrf() ?>">
        <label><?= $this->__('penname') ?>
            <input type="text" name="penname" required autofocus>
        </label>
        <label><?= $this->__('password') ?>
            <input type="password" name="password" required>
        </label>
        <button type="submit"><?= $this->__('login') ?></button>
    </form>
    <p><a href="/user/register"><?= $this->__('register') ?></a></p>
</section>
