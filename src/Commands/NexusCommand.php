<?php

namespace Malikad778\LaravelNexus\Commands;

use Illuminate\Console\Command;

class NexusCommand extends Command
{
    public $signature = 'nexus';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
