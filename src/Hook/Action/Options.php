<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Hook\Action;

use ArrayObject;

final class Options extends ArrayObject
{

    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }
}
