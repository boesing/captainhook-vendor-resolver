<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Hook;

use Boesing\CaptainhookVendorResolver\Exception\ActionAlreadyExistsException;
use Webmozart\Assert\Assert;

final class Hook implements HookInterface
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var ActionInterface[]
     */
    private $actions = [];

    public function __construct(string $name, bool $enabled)
    {
        $this->name = $name;
        $this->enabled = $enabled;
    }

    public static function fromDefinition(string $name, array $hook): self
    {
        $enabled = $hook['enabled'] ?? true;
        $instance = new self($name, $enabled);

        $actions = array_map(function (array $action): ActionInterface {
            return Action::fromDefinition($action);
        }, $hook['actions'] ?? []);

        $instance->setActions($actions);

        return $instance;
    }

    private function setActions(array $actions): void
    {
        Assert::allIsInstanceOf($actions, ActionInterface::class);
        $this->actions = $actions;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function merge(HookInterface $hook, bool $overwrite): void
    {
        foreach ($hook->actions() as $action) {
            if ($overwrite === true) {
                $this->remove($action);
            }

            $this->add($action);
        }
    }

    public function remove(ActionInterface $action): void
    {
        if (!$this->has($action->action())) {
            return;
        }

        unset($this->actions[$action->action()]);
    }

    private function has(string $action): bool
    {
        return ($this->actions[$action] ?? null) instanceof ActionInterface;
    }

    public function add(ActionInterface $action): void
    {
        if ($this->has($action->action())) {
            throw ActionAlreadyExistsException::create($action->action());
        }

        $this->actions[$action->action()] = $action;
    }

    /**
     * @return ActionInterface[]
     */
    public function actions(): array
    {
        return $this->actions;
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
}
