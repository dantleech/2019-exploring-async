<?php

use Phpactor\TestUtils\Workspace;

require __DIR__ . '/../vendor/autoload.php';

$repos = [
    'fink' => 'git@github.com:dantleech/fink',
    'container' => 'git@github.com:phpbench/container',
    'symfony' => 'git@github.com:symfony/yaml',
];

$commands = [
    'composer install',
    'vendor/bin/phpunit',
];

$workspace = new Workspace(__DIR__ . '/../Workspace');
$workspace->reset();

foreach ($repos as $repoUrl) {
    $repoPath = $workspace->path(basename($repoUrl));
    exec(sprintf('git clone %s %s', $repoUrl, $repoPath));
    chdir($repoPath);
    foreach ($commands as $command) {
        exec($command);
    }
}
