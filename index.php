<?php

declare(strict_types=1);

$app = require_once __DIR__ . '/bootstrap.php';

if ($app instanceof \eFiction\App) {
    $app->run();
}
