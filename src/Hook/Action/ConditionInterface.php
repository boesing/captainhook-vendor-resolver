<?php

declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Hook\Action;

interface ConditionInterface
{
    public function exec(): string;

    public function arguments(): array;

    public function data(): array;

    public function equals(self $condition): bool;
}
