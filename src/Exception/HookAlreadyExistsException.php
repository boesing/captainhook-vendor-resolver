<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Exception;

use RuntimeException;

final class HookAlreadyExistsException extends RuntimeException implements ExceptionInterface
{

    private function __construct(string $hook)
    {
        parent::__construct(sprintf('Hook %s already exists.', $hook));
    }

    public static function create(string $name): self
    {
        return new self($name);
    }
}
