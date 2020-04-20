<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolverTests\Config;

use Boesing\CaptainhookVendorResolver\Config\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{

    public function testCanInstantiateWithEmptyConfiguration(): void
    {
        $config = Config::fromArray([]);
        $this->assertEmpty($config->skipped);
    }

    public function testCanInstantiateWithoutSkippedHooks(): void
    {
        $config = Config::fromArray(['skipped' => []]);
        $this->assertEmpty($config->skipped);
    }
}
