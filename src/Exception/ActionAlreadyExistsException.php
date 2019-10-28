<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Exception;

use RuntimeException;

final class ActionAlreadyExistsException extends RuntimeException implements ExceptionInterface
{

    private function __construct(string $action)
    {
        parent::__construct(sprintf('Action %s already exists.', $action));
    }

    public static function create(string $action): self
    {
        return new self($action);
    }
}
