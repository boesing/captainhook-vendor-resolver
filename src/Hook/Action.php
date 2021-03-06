<?php

declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Hook;

use Boesing\CaptainhookVendorResolver\Hook\Action\Condition;
use Boesing\CaptainhookVendorResolver\Hook\Action\ConditionInterface;
use Boesing\CaptainhookVendorResolver\Hook\Action\Options;
use OutOfBoundsException;
use Webmozart\Assert\Assert;

use function array_map;
use function count;

final class Action implements ActionInterface
{
    /** @var string */
    private $action;

    /** @var Options */
    private $options;

    /** @var ConditionInterface[] */
    private $conditions;

    /**
     * @param ConditionInterface[] $conditions
     */
    public function __construct(string $action, Options $options, array $conditions)
    {
        $this->action = $action;
        Assert::stringNotEmpty($action);
        $this->options = $options;
        Assert::allIsInstanceOf($conditions, ConditionInterface::class);
        $this->conditions = $conditions;
    }

    public static function fromDefinition(array $definition): self
    {
        $action     = $definition['action'] ?? '';
        $options    = new Options($definition['options'] ?? []);
        $conditions = array_map(function (array $condition): ConditionInterface {
            return Condition::fromDefinition($condition);
        }, $definition['conditions'] ?? []);

        return new self($action, $options, $conditions);
    }

    public function equals(ActionInterface $action): bool
    {
        if ($this->action !== $action->action()) {
            return false;
        }

        if (! $this->options->equals($action->options())) {
            return false;
        }

        if (count($this->conditions) !== count($action->conditions())) {
            return false;
        }

        foreach ($this->conditions as $condition) {
            if (! $action->has($condition->exec())) {
                return false;
            }

            $conditionFromAction = $action->condition($condition->exec());
            if (! $conditionFromAction->equals($condition)) {
                return false;
            }
        }

        return true;
    }

    public function has(string $exec): bool
    {
        foreach ($this->conditions as $condition) {
            if ($condition->exec() === $exec) {
                return true;
            }
        }

        return false;
    }

    public function options(): Options
    {
        return $this->options;
    }

    public function condition(string $exec): ConditionInterface
    {
        foreach ($this->conditions as $condition) {
            if ($condition->exec() === $exec) {
                return $condition;
            }
        }

        throw new OutOfBoundsException();
    }

    public function data(): object
    {
        $conditions = [];
        foreach ($this->conditions() as $condition) {
            $conditions[] = $condition->data();
        }

        $data = [
            'action'     => $this->action,
            'conditions' => $conditions,
        ];

        if ($this->options->count() > 0) {
            $data['options'] = $this->options->data();
        }

        return (object) $data;
    }

    /**
     * @return ConditionInterface[]
     */
    public function conditions(): array
    {
        return $this->conditions;
    }

    public function action(): string
    {
        return $this->action;
    }
}
