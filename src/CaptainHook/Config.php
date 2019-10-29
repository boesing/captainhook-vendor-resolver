<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\CaptainHook;

use Boesing\CaptainhookVendorResolver\Exception\HookAlreadyExistsException;
use Boesing\CaptainhookVendorResolver\Hook\Hook;
use Boesing\CaptainhookVendorResolver\Hook\HookInterface;
use CaptainHook\App\Hooks;
use OutOfBoundsException;
use RuntimeException;
use Webmozart\Assert\Assert;
use function array_diff_key;
use function array_intersect_key;
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
        if (!is_writable($this->path) || is_dir($this->path)) {
            throw new RuntimeException(sprintf('Unable to write to %s', $this->path));
        }

        return file_put_contents($this->path, $this->json()) !== false;
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

    public function remove(HookInterface $hook): void
    {
        if (!$this->exists($hook->name())) {
            return;
        }

        $stored = $this->get($hook->name());
        foreach ($hook->actions() as $action) {
            $stored->remove($action);
        }

        return;
    }

    public function get(string $name): HookInterface
    {
        $hook = $this->hooks[$name] ?? null;

        if ($hook === null) {
            throw new OutOfBoundsException();
        }

        return $hook;
    }
}
