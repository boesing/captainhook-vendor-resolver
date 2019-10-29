<?php
declare(strict_types=1);

namespace Boesing\CaptainhookVendorResolverTests\Hook;

use Boesing\CaptainhookVendorResolver\Exception\ActionAlreadyExistsException;
use Boesing\CaptainhookVendorResolver\Exception\IncompatibleHookException;
use Boesing\CaptainhookVendorResolver\Hook\Action;
use Boesing\CaptainhookVendorResolver\Hook\ActionInterface;
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

    public function testWillThrowExceptionDueToInvalidHookMerge()
    {
        $hook = new Hook('bar', true);
        $hook2 = new Hook('baz', true);

        $this->expectException(IncompatibleHookException::class);
        $hook->merge($hook2);
    }

    public function testWillOverwriteAction()
    {
        $hook = new Hook('foo', false);
        $action = new Action('bar', new Action\Options(), []);
        $hook->add($action);

        $anotherHook = new Hook('foo', false);
        $anotherAction = new Action('bar', new Action\Options(), []);
        $anotherHook->add($anotherAction);

        $hook->merge($anotherHook, true);

        $actions = $hook->actions();
        $this->assertNotEmpty($actions);
        $actionFromHook = $hook->actions()[$anotherAction->action()] ?? null;
        $this->assertInstanceOf(ActionInterface::class, $actionFromHook);
        $this->assertEquals($anotherAction, $actions[$anotherAction->action()]);
    }
}
