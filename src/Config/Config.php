<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Config;

use Boesing\CaptainhookVendorResolver\Hook\ActionInterface;
use Boesing\CaptainhookVendorResolver\Hook\HookInterface;
use CaptainHook\App\CH;
use RuntimeException;
use Webmozart\Assert\Assert;
use function array_map;
use function array_search;
use function file_exists;
use function file_put_contents;
use function in_array;
use function json_encode;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

final class Config implements ConfigInterface
{

    /**
     * @var array<string, string[]>
     */
    public $skipped = [];

    /**
     * @var string
     */
    private $path = '';

    /**
     * @var bool
     */
    private $dirty = false;

    /**
     * @var string
     */
    private $captainhookLocation = CH::CONFIG;

    /**
     * @param array<string,string[]> $skipped
     */
    private function __construct(string $captainhookLocation, array $skipped)
    {
        $this->setSkipped($skipped);
        if ($captainhookLocation) {
            $this->captainhookLocation = $captainhookLocation;
        }
    }

    public static function fromFile(string $path): self
    {
        $config = [];

        if (file_exists($path)) {
            $config = (array) json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        }

        $instance = self::fromArray($config);
        $instance->path = $path;

        return $instance;
    }

    public static function fromArray(array $config): self
    {
        return new self($config['captainhook'] ?? '', $config['skipped'] ?? []);
    }

    /**
     * @param ActionInterface[] $actions
     */
    public function markActionsSkipped(HookInterface $hook, array $actions): void
    {
        Assert::allIsInstanceOf($actions, ActionInterface::class);
        $this->skipped[$hook->name()] = array_map(function (ActionInterface $action): string {
            return $action->action();
        }, $actions);

        $this->dirty = true;
    }

    public function store(): bool
    {
        $path = $this->path;
        if ((file_exists($path) && !is_writable($path)) || !is_writable(dirname($path))) {
            throw new RuntimeException(sprintf('Unable to write to %s', $this->path));
        }

        if ($this->empty()) {
            $this->delete();
            return true;
        }

        $stored = file_put_contents($path, $this->json()) !== false;
        if ($stored) {
            $this->dirty = false;
        }

        return $stored;
    }

    private function json(): string
    {
        return json_encode($this->data(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    private function data(): object
    {
        return (object) [
            'skipped' => (object) $this->skipped,
            'captainhook' => $this->captainhookLocation,
        ];
    }

    public function remove(HookInterface $hook, ActionInterface $action): void
    {
        if (!$this->skipped($hook, $action)) {
            return;
        }

        $key = array_search($action->action(), $this->skipped[$hook->name()], true);
        if ($key === false) {
            return;
        }
        unset($this->skipped[$hook->name()][$key]);
        $this->dirty = true;
    }

    public function skipped(HookInterface $hook, ActionInterface $action): bool
    {
        $actions = $this->skipped[$hook->name()] ?? [];

        return in_array($action->action(), $actions, true);
    }

    private function empty(): bool
    {
        return empty($this->skipped);
    }

    private function delete(): void
    {
        if (!file_exists($this->path)) {
            return;
        }

        unlink($this->path);
    }

    /**
     * @param array<string,string[]> $skipped
     */
    private function setSkipped(array $skipped): void
    {
        if (empty($skipped)) {
            return;
        }

        Assert::isMap(
            $skipped,
            'Provided skipped actions must contain a map of hook name and skipped actions for that hook.'
        );
        foreach ($skipped as $actions) {
            Assert::allStringNotEmpty($actions, 'Provided actions must contain string (action name).');
        }

        $this->skipped = $skipped;
    }

    public function captainhookLocation(): string
    {
        return $this->captainhookLocation;
    }
}
