<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolverTests\Config;

use Boesing\CaptainhookVendorResolver\Config\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{

    public function testCanInstantiateWithEmptyConfiguration()
    {
        $config = Config::fromArray([]);
        $this->assertEmpty($config->skipped);
    }

    public function testCanInstantiateWithoutSkippedHooks()
    {
        $config = Config::fromArray(['skipped' => []]);
        $this->assertEmpty($config->skipped);
    }
}
