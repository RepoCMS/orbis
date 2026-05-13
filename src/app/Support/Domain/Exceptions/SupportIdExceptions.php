<?php

declare(strict_types=1);

namespace App\Support\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

class TicketIdException extends DomainException {}
class TicketAssignmentIdException extends DomainException {}
class JourneyIdException extends DomainException {}
class TicketFindingIdException extends DomainException {}
class TicketSparePartIdException extends DomainException {}
class TicketPendingSparePartIdException extends DomainException {}
class TicketEvidenceIdException extends DomainException {}
class TicketPauseIdException extends DomainException {}
class TicketRescheduleIdException extends DomainException {}
