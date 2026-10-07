<?php

$envFile = dirname(__DIR__) . "/.env";

$config = parse_ini_file($envFile);

$appEnv = $config['APP_ENV'] ?? 'unknown';

echo "<h1>PHP CI/CD Demo</h1>";
echo __DIR__ . ' <br>';
echo "<p>Environment: " . htmlspecialchars($appEnv) .  "</p>";
