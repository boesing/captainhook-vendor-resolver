<?php

declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Exception;

use Boesing\CaptainhookVendorResolver\Hook\ActionInterface;
use RuntimeException;
use Webmozart\Assert\Assert;

use function array_map;
use function implode;
use function sprintf;

final class ActionsAlreadyExistsException extends RuntimeException implements ExceptionInterface
{
    /** @var ActionInterface[] */
    private $actions;

    /**
     * @param ActionInterface[] $actions
     */
    private function __construct(string $hook, array $actions)
    {
        Assert::allIsInstanceOf($actions, ActionInterface::class);
        Assert::notEmpty($actions);

        parent::__construct(sprintf(
            'The following action(s) for hook "%s" do already exist: %s',
            $hook,
            implode(', ', array_map(function (ActionInterface $action): string {
                return $action->action();
            }, $actions))
        ));

        $this->actions = $actions;
    }

    /**
     * @param ActionInterface[] $actions
     */
    public static function create(string $hook, array $actions): self
    {
        return new self($hook, $actions);
    }

    public function actions(): array
    {
        return $this->actions;
    }
}
