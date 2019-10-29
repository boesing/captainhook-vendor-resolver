<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Hook;

use Boesing\CaptainhookVendorResolver\Hook\Action\Condition;
use Boesing\CaptainhookVendorResolver\Hook\Action\ConditionInterface;
use Boesing\CaptainhookVendorResolver\Hook\Action\Options;
use OutOfBoundsException;
use Webmozart\Assert\Assert;
use function array_map;

final class Action implements ActionInterface
{

    /**
     * @var string
     */
    private $action;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var ConditionInterface[]
     */
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
        $action = $definition['action'] ?? '';
        $options = new Options($definition['options'] ?? []);
        $conditions = array_map(function (array $condition): ConditionInterface {
            return Condition::fromDefinition($condition);
        }, $definition['conditions'] ?? []);

        return new self($action, $options, $conditions);
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

    public function data(): array
    {
        $conditions = [];
        foreach ($this->conditions() as $condition) {
            $conditions[] = $condition->data();
        }

        return [
            'action' => $this->action(),
            'options' => $this->options,
            'conditions' => $conditions,
        ];
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
