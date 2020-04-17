<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Exception;

use InvalidArgumentException;

final class InvalidConfigurationException extends InvalidArgumentException implements ExceptionInterface
{

    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function fromInvalidPath(string $path): self
    {
        return new self(sprintf('Provided path `%s` is invalid.', $path));
    }
}
