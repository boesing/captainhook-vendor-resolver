<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Config;

use Boesing\CaptainhookVendorResolver\Hook\ActionInterface;
use Boesing\CaptainhookVendorResolver\Hook\HookInterface;
use RuntimeException;
use Webmozart\Assert\Assert;
use function array_map;
use function file_exists;
use function file_put_contents;
use function in_array;
use function is_dir;
use function json_encode;
use function unlink;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

final class Config implements ConfigInterface
{

    /**
     * @var array<string, string[]>
     */
    private $skipped;

    /**
     * @var string
     */
    private $path = '';

    private function __construct(array $skipped)
    {
        Assert::isMap(
            $skipped,
            'Provided skipped actions must contain a map of hook name and skipped actions for that hook.'
        );
        foreach ($skipped as $actions) {
            Assert::allStringNotEmpty($actions, 'Provided actions must contain string (action name).');
        }

        $this->skipped = $skipped;
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

    private static function fromArray(array $config): self
    {
        return new self($config['skipped'] ?? []);
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
    }

    public function skipped(HookInterface $hook, ActionInterface $action): bool
    {
        $actions = $this->skipped[$hook->name()] ?? [];

        return in_array($action->action(), $actions, true);
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
        return [
            'skipped' => (object) $this->skipped,
        ];
    }

    public function remove(): bool
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }
}
