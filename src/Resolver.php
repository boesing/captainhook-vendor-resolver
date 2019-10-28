<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver;

use Boesing\CaptainhookVendorResolver\CaptainHook\Config;
use Boesing\CaptainhookVendorResolver\Exception\ActionAlreadyExistsException;
use Boesing\CaptainhookVendorResolver\Exception\ExceptionInterface;
use Boesing\CaptainhookVendorResolver\Hook\Action\Condition;
use Boesing\CaptainhookVendorResolver\Hook\Action\ConditionInterface;
use Boesing\CaptainhookVendorResolver\Hook\Action\Options;
use Boesing\CaptainhookVendorResolver\Hook\HookInterface;
use Boesing\CaptainhookVendorResolver\Injector\CaptainhookjsonInjector;
use CaptainHook\App\CH;
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
use function dirname;
use function file_get_contents;
use function json_decode;
use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;

final class Resolver implements EventSubscriberInterface, PluginInterface
{

    private const HOOKS_IDENTIFIER = 'captainhook-hooks';

    /**
     * @var string
     */
    private $projectRoot = '';

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    public function __construct(string $projectRoot = '')
    {
        if (is_string($projectRoot) && !empty($projectRoot) && is_dir($projectRoot)) {
            $this->projectRoot = $projectRoot;
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall',
            PackageEvents::POST_PACKAGE_UNINSTALL => 'onPostPackageUninstall',
        ];
    }

    /**
     * @inheritDoc
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function onPostPackageInstall(PackageEvent $event): void
    {
        if (!$event->isDevMode()) {
            // Do nothing in production mode.
            return;
        }

        /** @var InstallOperation $operation */
        $operation = $event->getOperation();
        /** @var PackageInterface $package */
        $package = $operation->getPackage();
        $extra = $this->getExtraMetadata($package->getExtra());

        if (empty($extra)) {
            // Package does not define anything of interest; do nothing.
            return;
        }

        $hooks = $this->discoverPackageHooks($extra);
        if (empty($hooks)) {
            // Package does not provide any valid hook configuration
            return;
        }

        $captainhookJson = $this->discoverCaptainhookJson();
        $injector = new CaptainhookjsonInjector($captainhookJson);
        foreach ($hooks as $hook) {
            try {
                $this->inject($injector, $hook);
            } catch (ExceptionInterface $exception) {
                $this->io->writeError($exception->getMessage());

                return;
            }
        }

        $captainhookJson->store();
    }

    private function getExtraMetadata(array $extra): array
    {
        $metadata = $extra[self::HOOKS_IDENTIFIER] ?? [];
        Assert::isMap($metadata);

        return $metadata;
    }

    /**
     * @param array $metadata
     *
     * @return HookInterface[]
     */
    private function discoverPackageHooks(array $metadata): array
    {
        return $this->convertPackageHooks(array_intersect_key($metadata, Hooks::getValidHooks()));
    }

    /**
     * @param array $hooks
     *
     * @return HookInterface[]
     */
    private function convertPackageHooks(array $hooks): array
    {
        $converted = [];
        foreach ($hooks as $name => $configuration) {
            $hook = new Hook\Hook($name, $configuration['enabled'] ?? false);
            $actions = $configuration['actions'] ?? [];
            foreach ($actions as $definition) {
                $action = $definition['action'] ?? '';
                Assert::stringNotEmpty($action);
                $options = $definition['options'] ?? [];
                Assert::isArray($options);
                $conditions = $definition['conditions'] ?? [];
                Assert::isArray($conditions);

                $hook->add(new Hook\Action($action, new Options($options),
                    array_map(function (array $condition): ConditionInterface {
                        return new Condition($condition['exec'] ?? '', $condition['args'] ?? []);
                    }, $conditions)));
            }

            $converted[$hook->name()] = $hook;
        }

        return $converted;
    }

    private function discoverCaptainhookJson(): Config
    {
        $projectJson = Factory::getComposerFile();
        $fromComposer = $this->extractCaptainhookConfigFromComposerJson($projectJson);
        if ($fromComposer) {
            return Config::fromFile($fromComposer);
        }

        return Config::fromFile(($this->projectRoot ?: realpath(dirname($projectJson))) . DIRECTORY_SEPARATOR . CH::CONFIG);
    }

    private function extractCaptainhookConfigFromComposerJson(string $projectJson): string
    {
        return json_decode((string) file_get_contents($projectJson), true, 512,
                JSON_THROW_ON_ERROR)['extra'][CH::COMPOSER_CONFIG] ?? '';
    }

    /**
     * @throws ExceptionInterface
     */
    private function inject(CaptainhookjsonInjector $injector, HookInterface $hook): void
    {
        try {
            $injector->inject($hook, false);
        } catch (ActionAlreadyExistsException $exception) {
            $overwrite = $this->io->askConfirmation('One or more actions do already exists. Do you want to overwrite them? (Y/n)');
            if (!$overwrite) {
                return;
            }

            $injector->inject($hook, true);
        }
    }

    public function onPostPackageUninstall(PackageEvent $event): void
    {
        if (!$event->isDevMode()) {
            // Do nothing in production mode.
            return;
        }

        /** @var UninstallOperation $operation */
        $operation = $event->getOperation();
        $package = $operation->getPackage();
        $extra = $this->getExtraMetadata($package->getExtra());

        if (empty($extra)) {
            // Package does not define anything of interest; do nothing.
            return;
        }

        $hooks = $this->discoverPackageHooks($extra);
        if (empty($hooks)) {
            // Package does not provide any valid hook configuration
            return;
        }

        $captainhookJson = $this->discoverCaptainhookJson();
        $injector = new CaptainhookjsonInjector($captainhookJson);
        foreach ($hooks as $hook) {
            $injector->remove($hook);
        }

        $captainhookJson->store();
    }
}
