<?php

declare(strict_types=1);

namespace Rincler\Domain;

use Throwable;

class InvalidDomainException extends \Exception
{
    /**
     * @var int
     */
    private $idnErrorBitSet;

    public function __construct(int $idnErrorBitSet, string $message = '', int $code = 0, Throwable $previous = null)
    {
        $this->idnErrorBitSet = $idnErrorBitSet;

        parent::__construct($message, $code, $previous);
    }

    public function getIdnErrorBitSet(): int
    {
        return $this->idnErrorBitSet;
    }
}
