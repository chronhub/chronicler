<?php
declare(strict_types=1);

namespace Chronhub\Chronicler\Tests\Unit\Tracking\Subscribers;

use Chronhub\Chronicler\Support\Contracts\EventableChronicler;
use Chronhub\Chronicler\Tests\TestCase;
use Chronhub\Chronicler\Tests\TestCaseWithProphecy;
use Chronhub\Chronicler\Tracking\Subscribers\MarkCausationCommand;
use Chronhub\Chronicler\Tracking\TrackStream;
use Chronhub\Foundation\Tracker\TrackMessage;

final class CausationCommandTest extends TestCaseWithProphecy
{
    /**
     * @test
     */
    public function it_decorate_message_header_with_causation_id_and_type(): void
    {

    }
}
