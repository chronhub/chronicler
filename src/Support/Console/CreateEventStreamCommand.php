<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Support\Console;

use Illuminate\Console\Command;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Chronicler\Support\Facade\Chronicle;

final class CreateEventStreamCommand extends Command
{
    protected $signature = 'chronicler:create-stream
                                {stream : stream name}
                                {--chronicler=default}';

    protected $description = 'Create first commit for one stream';

    public function handle(): void
    {
        $streamName = new StreamName($this->argument('stream'));

        $driver = $this->hasOption('chronicler') ? $this->option('chronicler') : 'default';

        $chronicler = Chronicle::create($driver);

        if ($chronicler->hasStream($streamName)) {
            $this->error("Stream $streamName already exists");

            return;
        }

        $chronicler->persistFirstCommit(new Stream($streamName));

        $this->info("Stream $streamName created");
    }
}
