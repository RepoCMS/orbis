<?php

declare(strict_types=1);

namespace App\Support\Application\Command\StartOutwardJourney;

use App\Support\Domain\Enums\JourneyType;

final readonly class StartOutwardJourneyCommand
{
    public function __construct(
        public string      $ticketId,
        public JourneyType $journeyType = JourneyType::OUTWARD,
        public bool        $isPartOfRoute = false,
        public ?string     $notes = null,
    ) {}
}
