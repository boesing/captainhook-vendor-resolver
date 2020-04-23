<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Exception;

use Composer\DependencyResolver\Operation\OperationInterface;
use Throwable;

final class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function fromUnsupportedOperation(OperationInterface $operation): self
    {
        return new self(sprintf(
            'Unsupported operation "%s" provided.',
            $operation->getOperationType()
        ));
    }
}
