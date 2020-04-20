<?php

declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Injector;

use Boesing\CaptainhookVendorResolver\CaptainHook\ConfigInterface;
use Boesing\CaptainhookVendorResolver\Config\ConfigInterface as ResolverConfigInterface;
use Boesing\CaptainhookVendorResolver\Exception\ActionAlreadyExistsException;
use Boesing\CaptainhookVendorResolver\Exception\ActionsAlreadyExistsException;
use Boesing\CaptainhookVendorResolver\Hook\ActionInterface;
use Boesing\CaptainhookVendorResolver\Hook\HookInterface;
use Composer\IO\IOInterface;

use function array_filter;
use function sprintf;

final class CaptainhookjsonInjector implements InjectorInterface
{
    /** @var ConfigInterface */
    private $captainhook;

    /** @var ResolverConfigInterface */
    private $resolver;

    /** @var IOInterface */
    private $io;

    public function __construct(
        IOInterface $io,
        ConfigInterface $captainhook,
        ResolverConfigInterface $resolver
    ) {
        $this->io          = $io;
        $this->captainhook = $captainhook;
        $this->resolver    = $resolver;
    }

    /**
     * @inheritDoc
     */
    public function inject(HookInterface $hook, bool $update = false): void
    {
        if (! $this->captainhook->exists($hook->name())) {
            $this->captainhook->add($hook);

            return;
        }

        $registered = $this->captainhook->get($hook->name());

        $skipped = [];

        $actionsToAdd = array_filter($hook->actions(), function (ActionInterface $action) use ($hook): bool {
            return ! $this->resolver->skipped($hook, $action);
        });

        foreach ($actionsToAdd as $action) {
            if ($update) {
                $registered->remove($action);
            }
            try {
                $registered->add($action);
                $this->io->write(sprintf(
                    '<info>%s action %s to hook %s</info>',
                    $update ? 'Updated ' : 'Added ',
                    $action->action(),
                    $hook->name()
                ));
            } catch (ActionAlreadyExistsException $exception) {
                $skipped[] = $action;
            }
        }

        if (! empty($skipped)) {
            throw ActionsAlreadyExistsException::create($hook->name(), $skipped);
        }
    }

    /**
     * @inheritDoc
     */
    public function remove(HookInterface $hook): void
    {
        if (! $this->captainhook->exists($hook->name())) {
            return;
        }

        $actionsToRemove = array_filter($hook->actions(), function (ActionInterface $action) use ($hook): bool {
            return ! $this->resolver->skipped($hook, $action);
        });

        foreach ($actionsToRemove as $action) {
            $this->captainhook->remove($hook, $action);
        }
    }

    /**
     * @inheritDoc
     */
    public function skipped(HookInterface $hook, array $actions): void
    {
        $this->resolver->markActionsSkipped($hook, $actions);
    }

    public function store(): bool
    {
        return $this->captainhook->store() && $this->resolver->store();
    }
}
