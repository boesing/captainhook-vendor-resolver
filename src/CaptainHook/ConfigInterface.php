<?php

declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\CaptainHook;

use Boesing\CaptainhookVendorResolver\Hook\ActionInterface;
use Boesing\CaptainhookVendorResolver\Hook\HookInterface;

interface ConfigInterface
{
    public function exists(string $hook): bool;

    public function store(): bool;

    public function remove(HookInterface $hook, ActionInterface $action): void;

    public function add(HookInterface $hook): void;

    public function get(string $hook): HookInterface;
}
