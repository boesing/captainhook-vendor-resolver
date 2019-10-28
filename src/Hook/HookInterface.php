<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Hook;

interface HookInterface
{

    public function name(): string;

    public function add(ActionInterface $action): void;

    public function remove(ActionInterface $hook): void;

    /**
     * @return ActionInterface[]
     */
    public function actions(): array;

    public function merge(HookInterface $hook, bool $overwrite): void;

    public function data(): array;
}
