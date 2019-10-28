<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Hook;

use Boesing\CaptainhookVendorResolver\Hook\Action\ConditionInterface;
use Boesing\CaptainhookVendorResolver\Hook\Action\Options;

interface ActionInterface
{

    public function equals(ActionInterface $action): bool;

    public function action(): string;

    public function has(string $exec): bool;

    /**
     * @return ConditionInterface[]
     */
    public function conditions(): array;

    public function options(): Options;

    public function condition(string $exec): ConditionInterface;

    public function data(): array;
}
