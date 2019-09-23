<?php


require __DIR__ . '/../vendor/autoload.php';

use Amp\Loop;
use Amp\Process\Process;
use Amp\Promise;
use function Amp\{
    Promise\wait,
    Promise\all,
    call,
};
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
        $this->workspace = new Workspace(__DIR__ . '/../Workspace');
    }

    public function run(): int
    {
        // remove everything from our workspace
        $this->workspace->reset();

        // wait for the promise of the run-scripts method
        $exitCodes = wait ( $this->runScripts());

        // if all exit codes were 0 (success) return 0. 
        // > 1 indicates failure.
        return array_sum($exitCodes);
    }

    private function runScripts(): Promise
    {
        $promises = [];
        
        foreach ($this->scripts as $repoUrl => $scripts) {

            // create a co-routine for this job (running CI for a repo)
            $promises[] = call(function () use ($repoUrl, $scripts) {

                $repoPath = $this->workspace->path(basename($repoUrl));

                // delegate the yielding of promises to a private generator method
                $exitCode = yield from $this->runCommand(sprintf(
                    'git clone %s %s',
                    $repoUrl,
                    $repoPath
                ), $this->workspace->path('/'));

                // we have now checked the repository out, run all the scripts
                foreach ($scripts as $script) {
                    $exitCode += yield from $this->runCommand($script, $repoPath);
                }

                // the exit code is the resolved value of this co-routine
                return $exitCode;
            });

        }

        // return a promise that will succeed when all the promises succeed
        return all($promises);
    }

    private function runCommand(string $command, $cwd): Generator
    {
        // start a new process and get the PID
        $process = new Process($command, $cwd);
        $pid     = yield $process->start();

        // use a promise directly to render stdout
        $process->getStdout()->read()->onResolve(function ($failure, $chunk) {
            echo $chunk;
        });

        // use a promise directly to render stderr
        $process->getStderr()->read()->onResolve(function ($failure, $chunk) {
            echo $chunk;
        });

        // promise from join will be resolved with the exit code of the process
        $exitCode = yield $process->join();

        return $exitCode;
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
