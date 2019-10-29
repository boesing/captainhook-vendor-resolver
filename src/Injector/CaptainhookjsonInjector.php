<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Injector;


use Boesing\CaptainhookVendorResolver\CaptainHook\ConfigInterface;
use Boesing\CaptainhookVendorResolver\Config\ConfigInterface as ResolverConfigInterface;
use Boesing\CaptainhookVendorResolver\Exception\ActionAlreadyExistsException;
use Boesing\CaptainhookVendorResolver\Exception\ActionsAlreadyExistsException;
use Boesing\CaptainhookVendorResolver\Hook\ActionInterface;
use Boesing\CaptainhookVendorResolver\Hook\HookInterface;
use function array_filter;

final class CaptainhookjsonInjector implements InjectorInterface
{

    /**
     * @var ConfigInterface
     */
    private $captainhook;

    /**
     * @var ResolverConfigInterface
     */
    private $resolver;

    public function __construct(ConfigInterface $captainhook, ResolverConfigInterface $resolver)
    {
        $this->captainhook = $captainhook;
        $this->resolver = $resolver;
    }

    /**
     * @inheritDoc
     */
    public function inject(HookInterface $hook, bool $update = false): void
    {
        if (!$this->captainhook->exists($hook->name())) {
            $this->captainhook->add($hook);

            return;
        }

        $registered = $this->captainhook->get($hook->name());

        $skipped = [];

        $actionsToAdd = array_filter($hook->actions(), function (ActionInterface $action) use ($hook): bool {
            return !$this->resolver->skipped($hook, $action);
        });

        foreach ($actionsToAdd as $action) {
            if ($update) {
                $registered->remove($action);
            }
            try {
                $registered->add($action);
            } catch (ActionAlreadyExistsException $exception) {
                $skipped[] = $action;
            }
        }

        if (!empty($skipped)) {
            throw ActionsAlreadyExistsException::create($hook->name(), $skipped);
        }
    }

    /**
     * @inheritDoc
     */
    public function remove(HookInterface $hook): void
    {
        if (!$this->captainhook->exists($hook->name())) {
            return;
        }

        $this->captainhook->remove($hook);
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
