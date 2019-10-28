<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Hook\Action;

use ArrayObject;

final class Options extends ArrayObject
{

    public function equals(Options $options): bool
    {
        return $options->getArrayCopy() === $this->getArrayCopy();
    }
}
