<?php

declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Config;

use Boesing\CaptainhookVendorResolver\Hook\ActionInterface;
use Boesing\CaptainhookVendorResolver\Hook\HookInterface;

interface ConfigInterface
{
    /**
     * @param ActionInterface[] $actions
     */
    public function markActionsSkipped(HookInterface $hook, array $actions): void;

    public function skipped(HookInterface $hook, ActionInterface $action): bool;

    public function store(): bool;

    public function remove(HookInterface $hook, ActionInterface $action): void;

    public function captainhookLocation(): string;

    public function unlink(): void;
}
