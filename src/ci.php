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

    /**
     * @var Workspace
     */
    private $workspace;

    public function __construct(array $scripts)
    {
        $this->scripts = $scripts;
        $this->workspace = Workspace::create(__DIR__ . '/../Workspace');
    }

    public function run()
    {
        $this->workspace->reset();
        $promises = [];

        foreach ($this->scripts as $repoUrl => $commands) {

            $promises[] = \Amp\call(function () use ($repoUrl, $commands) {
                $exitCode = 0;
                $repoName = basename($repoUrl);
                $repoPat = $this->workspace->path($repoName);

                yield $this->runCommand(sprintf(
                    'git clone %s %s',
                    $repoUrl,
                    $repoPat
                ), '/tmp');

                foreach ($commands as $command) {
                    $exitCode += yield $this->runCommand($command, $repoPat);
                }

                return $exitCode;
            });
        }

        return $promises;
    }

    private function runCommand(string $command, string $path): Promise
    {
        return \Amp\call(function () use ($command, $path) {
            echo 'Running ' . $command . PHP_EOL;
            $process = new Process($command, $path);
            $pid = yield $process->start();

            $process->getStdout()->read()->onResolve(function ($failure, $value) {
                echo $value;
            });
            $process->getStderr()->read()->onResolve(function ($failure, $value) {
                echo $value;
            });

            return yield $process->join();
        });

    }
}

$runner = new Runner([
    'git@github.com:dantleech/fink' => [
        'composer install',
        './vendor/bin/phpunit'
    ],
    'git@github.com:phpbench/container' => [
        'composer install',
        './vendor/bin/phpunit'
    ]
]);;

$resuts = \Amp\Promise\wait(\Amp\Promise\all($runner->run()));
var_dump($resuts);

Loop::run();
