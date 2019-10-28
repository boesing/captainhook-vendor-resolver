<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Injector;


use Boesing\CaptainhookVendorResolver\CaptainHook\ConfigInterface;
use Boesing\CaptainhookVendorResolver\Hook\HookInterface;

final class CaptainhookjsonInjector implements InjectorInterface
{

    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function inject(HookInterface $hook, bool $overwrite): void
    {
        if (!$this->config->exists($hook->name())) {
            $this->config->add($hook);

            return;
        }

        $registered = $this->config->get($hook->name());
        $registered->merge($hook, $overwrite);
    }

    public function remove(HookInterface $hook): void
    {
        if (!$this->config->exists($hook->name())) {
            return;
        }

        $this->config->remove($hook);
    }
}
