<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Exception;

use DomainException;

final class IncompatibleHookException extends DomainException implements ExceptionInterface
{

    private function __construct($message)
    {
        parent::__construct($message);
    }

    public static function fromNotMachingHookName(string $expected, string $provided): self
    {
        return new self(sprintf('Hook names does not match. Expected "%s", got "%s".', $expected, $provided));
    }
}
