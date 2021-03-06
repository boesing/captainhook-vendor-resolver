<?php

declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Hook;

use Boesing\CaptainhookVendorResolver\Exception\ActionAlreadyExistsException;
use OutOfBoundsException;
use Webmozart\Assert\Assert;

use function array_combine;
use function array_map;
use function array_values;

final class Hook implements HookInterface
{
    /** @var string */
    private $name;

    /** @var bool */
    private $enabled;

    /** @var ActionInterface[] */
    private $actions = [];

    /** @var bool */
    private $dirty = false;

    public function __construct(string $name, bool $enabled)
    {
        $this->name    = $name;
        $this->enabled = $enabled;
    }

    public static function fromDefinition(string $name, array $hook): self
    {
        $enabled  = $hook['enabled'] ?? true;
        $instance = new self($name, $enabled);

        $actions = array_map(function (array $action): ActionInterface {
            return Action::fromDefinition($action);
        }, $hook['actions'] ?? []);

        $instance->setActions($actions);

        return $instance;
    }

    private function setActions(array $actions): void
    {
        if (empty($actions)) {
            return;
        }

        Assert::allIsInstanceOf($actions, ActionInterface::class);
        $mapped = (array) array_combine(array_map(function (ActionInterface $action): string {
            return $action->action();
        }, $actions), array_values($actions));

        Assert::isMap($mapped);
        $this->actions = $mapped;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function remove(ActionInterface $action): void
    {
        if (! $this->has($action->action())) {
            return;
        }

        unset($this->actions[$action->action()]);
        $this->dirty = true;
    }

    private function has(string $action): bool
    {
        return ($this->actions[$action] ?? null) instanceof ActionInterface;
    }

    public function add(ActionInterface $action): void
    {
        if ($this->has($action->action())) {
            $stored = $this->get($action->action());
            if ($stored->equals($action)) {
                return;
            }

            throw ActionAlreadyExistsException::create($action->action());
        }

        $this->actions[$action->action()] = $action;
        $this->dirty                      = true;
    }

    public function data(): array
    {
        $actions = [];
        foreach ($this->actions() as $action) {
            $actions[] = $action->data();
        }

        return [
            'enabled' => $this->enabled,
            'actions' => $actions,
        ];
    }

    /**
     * @return ActionInterface[]
     */
    public function actions(): array
    {
        return $this->actions;
    }

    /**
     * @param ActionInterface[] $actions
     */
    public function replace(array $actions): HookInterface
    {
        Assert::allIsInstanceOf($actions, ActionInterface::class);
        $instance = clone $this;
        $instance->setActions($actions);
        $instance->dirty = true;

        return $instance;
    }

    public function stored(): void
    {
        $this->dirty = false;
    }

    public function dirty(): bool
    {
        return $this->dirty;
    }

    private function get(string $action): ActionInterface
    {
        if (! $this->has($action)) {
            throw new OutOfBoundsException();
        }
        
        return $this->actions[$action];
    }
}
