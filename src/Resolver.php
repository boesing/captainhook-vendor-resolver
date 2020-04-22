<?php

declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver;

use Boesing\CaptainhookVendorResolver\CaptainHook\Config;
use Boesing\CaptainhookVendorResolver\Config\Config as ResolverConfig;
use Boesing\CaptainhookVendorResolver\Config\ConfigInterface;
use Boesing\CaptainhookVendorResolver\Exception\ActionsAlreadyExistsException;
use Boesing\CaptainhookVendorResolver\Exception\ExceptionInterface;
use Boesing\CaptainhookVendorResolver\Exception\InvalidConfigurationException;
use Boesing\CaptainhookVendorResolver\Hook\Action\Condition;
use Boesing\CaptainhookVendorResolver\Hook\Action\ConditionInterface;
use Boesing\CaptainhookVendorResolver\Hook\Action\Options;
use Boesing\CaptainhookVendorResolver\Hook\HookInterface;
use Boesing\CaptainhookVendorResolver\Injector\CaptainhookjsonInjector;
use Boesing\CaptainhookVendorResolver\Injector\InjectorInterface;
use CaptainHook\App\Hooks;
use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Webmozart\Assert\Assert;

use function array_intersect_key;
use function array_map;
use function dirname;
use function is_dir;
use function is_string;
use function realpath;
use function sprintf;
use function strlen;

use const DIRECTORY_SEPARATOR;

final class Resolver implements EventSubscriberInterface, PluginInterface
{
    private const HOOKS_IDENTIFIER       = 'captainhook-hooks';
    private const RESOLVER_CONFIGURATION = 'captainhook-vendor-resolver.json';

    /** @var string */
    private $projectRoot = '';

    /** @var IOInterface */
    private $io;

    /** @var InjectorInterface|null */
    private $injector;

    public function __construct(string $projectRoot = '')
    {
        if (is_string($projectRoot) && ! empty($projectRoot) && is_dir($projectRoot)) {
            $this->projectRoot = $projectRoot;
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL   => 'onPostPackageInstallOrUpdate',
            PackageEvents::POST_PACKAGE_UPDATE    => 'onPostPackageInstallOrUpdate',
            PackageEvents::POST_PACKAGE_UNINSTALL => 'onPostPackageUninstall',
        ];
    }

    /**
     * @inheritDoc
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;
    }

    public function onPostPackageInstallOrUpdate(PackageEvent $event): void
    {
        if (! $event->isDevMode()) {
            // Do nothing in production mode.
            return;
        }

        /** @var InstallOperation $operation */
        $operation = $event->getOperation();
        /** @var PackageInterface $package */
        $package = $operation->getPackage();
        $extra   = $this->getExtraMetadata($package->getExtra());

        if (empty($extra)) {
            // Package does not define anything of interest; do nothing.
            return;
        }

        $hooks = $this->discoverPackageHooks($extra);
        if (empty($hooks)) {
            // Package does not provide any valid hook configuration
            return;
        }

        $injector = $this->injector();

        foreach ($hooks as $hook) {
            try {
                $this->inject($injector, $hook);
            } catch (ExceptionInterface $exception) {
                $this->io->writeError($exception->getMessage());

                return;
            }
        }

        $injector->store();
    }

    private function getExtraMetadata(array $extra): array
    {
        $metadata = $extra[self::HOOKS_IDENTIFIER] ?? null;
        Assert::nullOrIsMap($metadata);

        return $metadata ?? [];
    }

    /**
     * @return HookInterface[]
     */
    private function discoverPackageHooks(array $metadata): array
    {
        return $this->convertPackageHooks(array_intersect_key($metadata, Hooks::getValidHooks()));
    }

    /**
     * @return HookInterface[]
     */
    private function convertPackageHooks(array $hooks): array
    {
        $converted = [];
        foreach ($hooks as $name => $configuration) {
            $hook    = new Hook\Hook($name, $configuration['enabled'] ?? false);
            $actions = $configuration['actions'] ?? [];
            foreach ($actions as $definition) {
                $action = $definition['action'] ?? '';
                Assert::stringNotEmpty($action);
                $options = $definition['options'] ?? [];
                Assert::isArray($options);
                $conditions = $definition['conditions'] ?? [];
                Assert::isArray($conditions);

                $hook->add(new Hook\Action(
                    $action,
                    new Options($options),
                    array_map(function (array $condition): ConditionInterface {
                        return new Condition($condition['exec'] ?? '', $condition['args'] ?? []);
                    },
                        $conditions)
                ));
            }

            $converted[$hook->name()] = $hook;
        }

        return $converted;
    }

    private function injector(): InjectorInterface
    {
        if ($this->injector) {
            return $this->injector;
        }

        $resolverConfiguration = $this->discoverResolverConfiguration();
        $captainhookJson       = $this->discoverCaptainhookJson($resolverConfiguration);
        $injector              = new CaptainhookjsonInjector(
            $this->io,
            $captainhookJson,
            $resolverConfiguration
        );

        return $this->injector = $injector;
    }

    private function discoverResolverConfiguration(): ConfigInterface
    {
        return ResolverConfig::fromFile($this->path(self::RESOLVER_CONFIGURATION));
    }

    private function path(string $filename): string
    {
        if (strlen($filename) === 0) {
            throw InvalidConfigurationException::fromInvalidPath($filename);
        }

        // If provided filename is absolute, use the absolute path
        if ($filename[0] === '/') {
            return (string) realpath($filename);
        }

        return $this->projectRoot ?: dirname(Factory::getComposerFile()) . DIRECTORY_SEPARATOR . $filename;
    }

    private function discoverCaptainhookJson(ConfigInterface $resolverConfiguration): Config
    {
        return Config::fromFile($this->path($resolverConfiguration->captainhookLocation()));
    }

    /**
     * @throws ExceptionInterface
     */
    private function inject(InjectorInterface $injector, HookInterface $hook): void
    {
        try {
            $injector->inject($hook);
        } catch (ActionsAlreadyExistsException $exception) {
            $update = $this->io->askConfirmation(sprintf(
                '%s Do you want to update these? (Y/n) ',
                $exception->getMessage()
            ));
            if (! $update) {
                $injector->skipped($hook, $exception->actions());

                return;
            }

            $injector->inject($hook->replace($exception->actions()), $update);
        }
    }

    public function onPostPackageUninstall(PackageEvent $event): void
    {
        if (! $event->isDevMode()) {
            // Do nothing in production mode.
            return;
        }

        /** @var UninstallOperation $operation */
        $operation = $event->getOperation();
        $package   = $operation->getPackage();
        $extra     = $this->getExtraMetadata($package->getExtra());

        if (empty($extra)) {
            // Package does not define anything of interest; do nothing.
            return;
        }

        $hooks = $this->discoverPackageHooks($extra);
        if (empty($hooks)) {
            // Package does not provide any valid hook configuration
            return;
        }

        $injector = $this->injector();
        foreach ($hooks as $hook) {
            $injector->remove($hook);
        }
    }
}
