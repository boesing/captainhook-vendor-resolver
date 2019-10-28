<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolver\Injector;

use Boesing\CaptainhookVendorResolver\Exception\ExceptionInterface;
use Boesing\CaptainhookVendorResolver\Hook\HookInterface;

interface InjectorInterface
{

    /**
     * @throws ExceptionInterface
     */
    public function inject(HookInterface $hook, bool $overwrite): void;

    public function remove(HookInterface $hook): void;
}
