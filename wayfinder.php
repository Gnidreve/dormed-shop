<?php
/**
 * Runner for wayfinder:generate that bypasses the Laravel 13 DevCommands CLI bug.
 *
 * Usage: php wayfinder.php
 *
 * This uses the Kernel API directly instead of the `php artisan` CLI,
 * avoiding the "DevCommands should be registered in application code" error.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

/** @var \Illuminate\Contracts\Console\Kernel $kernel */
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$exitCode = $kernel->call('wayfinder:generate', [
    '--with-form' => true,
]);

echo "wayfinder:generate exit code: {$exitCode}\n";
exit($exitCode);
