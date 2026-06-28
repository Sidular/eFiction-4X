<!DOCTYPE html>
<html lang="<?= $this->i18n()->language() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->e($title ?? $this->__('site_name')) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?= $this->partial('header') ?>

    <main id="content">
        <?= $this->section('content') ?>
    </main>

    <?= $this->partial('footer') ?>
</body>
</html>
