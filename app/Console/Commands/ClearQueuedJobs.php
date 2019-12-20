<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ClearQueuedJobs extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all queued jobs';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $redis_host = env('REDIS_HOST', '127.0.0.1');
        $redis_port = env('REDIS_PORT', 6379);
        // Stop Supervisor
        $process = Process::fromShellCommandline('service supervisor start');
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        echo $process->getOutput();

        // Remove jobs from queue
        $process = Process::fromShellCommandline('redis-cli -h $redis_host -p $redis_port --scan --pattern queue_update:* | xargs redis-cli -h $redis_host -p $redis_port del');
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        echo $process->getOutput();

        $process = Process::fromShellCommandline('redis-cli -h $redis_host -p $redis_port --scan --pattern queues:* | xargs redis-cli  -h $redis_host -p $redis_port del');
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        echo $process->getOutput();

        // Start Supervisor
        $process = Process::fromShellCommandline('sudo service supervisor stop');
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        echo $process->getOutput();
    }
}
