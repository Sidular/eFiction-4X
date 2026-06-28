<header class="site-header">
    <div class="container">
        <h1 class="site-title"><a href="/"><?= $this->__('site_name') ?></a></h1>
        <nav class="site-nav" aria-label="Main navigation">
            <a href="/"><?= $this->__('home') ?></a>
            <a href="/browse"><?= $this->__('browse') ?></a>
            <a href="/search"><?= $this->__('search') ?></a>
            <a href="/contact"><?= $this->__('contact') ?></a>
            <?php if ($this->auth()->check()): ?>
                <a href="/user/profile"><?= $this->e($this->auth()->user()['penname'] ?? '') ?></a>
                <?php if ($this->auth()->isAdmin()): ?>
                    <a href="/admin"><?= $this->__('admin') ?></a>
                <?php endif; ?>
                <a href="/user/logout"><?= $this->__('logout') ?></a>
            <?php else: ?>
                <a href="/user/login"><?= $this->__('login') ?></a>
                <a href="/user/register"><?= $this->__('register') ?></a>
            <?php endif; ?>
        </nav>
    </div>
</header>
