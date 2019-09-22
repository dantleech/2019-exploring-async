<?php

require __DIR__ . '/../vendor/autoload.php';

use Amp\Loop;
use Amp\Process\Process;
use Amp\Promise;
use Phpactor\TestUtils\Workspace;

class Runner
{
    /**
     * @var array
     */
    private $scripts;

    public function __construct(array $scripts)
    {
        $this->scripts = $scripts;
    }

    public function run()
    {
        $workspace = new Workspace(__DIR__ . '/../Workspace');
        $workspace->reset();

        foreach ($this->scripts as $repoUrl => $scripts) {

            \Amp\call(function () use ($repoUrl, $scripts, $workspace) {
                $repoPath = $workspace->path(basename($repoUrl));
                $exitCode = yield from $this->runCommand(sprintf('git clone %s %s', $repoUrl, $repoPath), getcwd());
                foreach ($scripts as $script) {
                    $exitCode = yield from $this->runCommand($script, $repoPath);
                }
            });

        }
    }

    private function runCommand(string $command, $cwd): Generator
    {
        $process = new Process($command, $cwd);
        $pid = yield $process->start();
        return yield $process->join();
    }
}

$runner = new Runner([
    'git@github.com:dantleech/fink' => [
        'composer install',
        './vendor/bin/phpunit',
    ],
    'git@github.com:phpactor/container' => [
        'composer install',
        './vendor/bin/phpunit',
    ],
]);
$runner->run();

Loop::run();
