<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Hook;

interface HookInterface
{

    public function name(): string;

    public function add(ActionInterface $action): void;

    public function remove(ActionInterface $hook): void;

    /**
     * @param ActionInterface[] $actions
     */
    public function replace(array $actions): self;

    /**
     * @return ActionInterface[]
     */
    public function actions(): array;

    public function data(): array;
}
