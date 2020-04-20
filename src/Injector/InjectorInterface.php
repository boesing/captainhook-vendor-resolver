<?php

declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Injector;

use Boesing\CaptainhookVendorResolver\Exception\ActionsAlreadyExistsException;
use Boesing\CaptainhookVendorResolver\Hook\ActionInterface;
use Boesing\CaptainhookVendorResolver\Hook\HookInterface;

interface InjectorInterface
{
    /**
     * @throws ActionsAlreadyExistsException If one or more actions are already existing within that hook.
     */
    public function inject(HookInterface $hook, bool $update = false): void;

    public function remove(HookInterface $hook): void;

    /**
     * @param ActionInterface[] $actions
     */
    public function skipped(HookInterface $hook, array $actions): void;

    public function store(): bool;
}
