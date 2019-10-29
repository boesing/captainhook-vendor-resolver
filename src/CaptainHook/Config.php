<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\CaptainHook;

use Boesing\CaptainhookVendorResolver\Exception\HookAlreadyExistsException;
use Boesing\CaptainhookVendorResolver\Hook\ActionInterface;
use Boesing\CaptainhookVendorResolver\Hook\Hook;
use Boesing\CaptainhookVendorResolver\Hook\HookInterface;
use CaptainHook\App\Hooks;
use OutOfBoundsException;
use RuntimeException;
use Webmozart\Assert\Assert;
use function array_diff_key;
use function array_filter;
use function array_intersect_key;
use function array_values;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_writable;
use function json_decode;
use function json_encode;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

final class Config implements ConfigInterface
{

    /**
     * @var HookInterface[]
     */
    private $hooks;

    /**
     * @var string
     */
    private $path = '';

    /**
     * @var array
     */
    private $config = [];

    /**
     * @param HookInterface[] $hooks
     */
    private function __construct(array $hooks)
    {
        Assert::allIsInstanceOf($hooks, HookInterface::class);
        Assert::isMap($hooks);
        $this->hooks = $hooks;
    }

    public static function fromFile(string $path): self
    {
        $config = (array) json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $hooks = (array) array_intersect_key($config, Hooks::getValidHooks());
        $config = array_diff_key($config, $hooks);
        $instance = self::fromArray($hooks);
        $instance->path = $path;
        $instance->config = $config;

        return $instance;
    }

    public static function fromArray(array $hooks): self
    {
        Assert::isMap($hooks);
        $converted = [];
        foreach (array_intersect_key($hooks, Hooks::getValidHooks()) as $name => $hook) {
            $converted[$name] = Hook::fromDefinition($name, $hook);
        }

        return new self($converted);
    }

    public function add(HookInterface $hook): void
    {
        if ($this->exists($hook->name())) {
            throw HookAlreadyExistsException::create($hook->name());
        }

        $this->hooks[$hook->name()] = $hook;
    }

    public function exists(string $hook): bool
    {
        return ($this->hooks[$hook] ?? null) instanceof HookInterface;
    }

    public function store(): bool
    {
        if (!$this->dirty()) {
            return true;
        }

        $path = $this->path;
        if ((file_exists($path) && !is_writable($path)) || !is_writable(dirname($path))) {
            throw new RuntimeException(sprintf('Unable to write to %s', $this->path));
        }

        $stored =  file_put_contents($path, $this->json()) !== false;
        if ($stored) {
            $this->stored();
        }

        return $stored;
    }

    private function dirty(): bool
    {
        foreach ($this->hooks as $hook) {
            if ($hook->dirty()) {
                return true;
            }
        }

        return false;
    }

    private function json(): string
    {
        return json_encode($this->data(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    private function data(): array
    {
        $data = [];
        foreach ($this->hooks as $hook) {
            $data[$hook->name()] = $hook->data();
        }

        return array_merge($data, $this->config);
    }

    public function remove(HookInterface $hook, ActionInterface $action): void
    {
        if (!$this->exists($hook->name())) {
            return;
        }

        $stored = $this->get($hook->name());
        unset($this->hooks[$hook->name()]);

        $actions = array_filter($stored->actions(), function (ActionInterface $stored) use ($action): bool {
            return $action->action() !== $stored->action();
        });

        $this->hooks[$hook->name()] = $stored->replace(array_values($actions));
    }

    public function get(string $name): HookInterface
    {
        $hook = $this->hooks[$name] ?? null;

        if ($hook === null) {
            throw new OutOfBoundsException();
        }

        return $hook;
    }

    private function stored(): void
    {
        foreach ($this->hooks as $hook) {
            $hook->stored();
        }
    }
}
