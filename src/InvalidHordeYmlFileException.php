<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile;

use RuntimeException;
use Throwable;

class InvalidHordeYmlFileException extends RuntimeException
{
    public function __construct(
        string $message = 'Invalid .horde.yml file',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
