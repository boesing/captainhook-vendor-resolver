<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolverTests\Hook;

use Boesing\CaptainhookVendorResolver\Exception\ActionAlreadyExistsException;
use Boesing\CaptainhookVendorResolver\Hook\Action;
use Boesing\CaptainhookVendorResolver\Hook\Hook;
use PHPUnit\Framework\TestCase;

final class HookTest extends TestCase
{

    public function testThrowExceptionDueToExistingAction()
    {
        $hook = new Hook('foo', false);
        $action = new Action('bar', new Action\Options(), []);
        $hook->add($action);

        $this->expectException(ActionAlreadyExistsException::class);

        $hook->add($action);
    }
}
