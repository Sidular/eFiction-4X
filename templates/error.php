<section class="error-page">
    <h2><?= $this->e($code ?? 'Error') ?></h2>
    <p><?= $this->e($message ?? $this->__('page_not_found')) ?></p>
    <p><a href="/">&larr; <?= $this->__('home') ?></a></p>
</section>
