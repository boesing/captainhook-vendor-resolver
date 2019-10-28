<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Hook\Action;

use Webmozart\Assert\Assert;

final class Condition implements ConditionInterface
{

    /**
     * @var string
     */
    private $exec;

    /**
     * @var array
     */
    private $arguments;

    public function __construct(string $exec, array $arguments)
    {
        Assert::stringNotEmpty($exec);
        $this->exec = $exec;
        Assert::isList($arguments);
        $this->arguments = $arguments;
    }

    public static function fromDefinition(array $definition): self
    {
        return new self($definition['exec'] ?? '', $definition['args'] ?? []);
    }

    public function arguments(): array
    {
        return $this->arguments;
    }

    public function equals(ConditionInterface $condition): bool
    {
        if ($this->exec !== $condition->exec()) {
            return false;
        }

        return $this->arguments === $condition->arguments();
    }

    public function data(): array
    {
        return [
            'exec' => $this->exec(),
            'args' => $this->arguments,
        ];
    }

    public function exec(): string
    {
        return $this->exec;
    }
}
