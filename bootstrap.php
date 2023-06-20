<?php

declare(strict_types=1);

foreach (
    [
        __DIR__ . '/vendor/autoload.php',
        __DIR__ . '/../../autoload.php',
    ] as $autoload
) {
    if (is_file($autoload)) {
        return require_once $autoload;
    }
}

fwrite(
    STDERR,
    'You need to set up the project dependencies using Composer:' . PHP_EOL . PHP_EOL .
    '    composer install' . PHP_EOL . PHP_EOL .
    'You can learn all about Composer on https://getcomposer.org/.' . PHP_EOL
);

exit(1);
