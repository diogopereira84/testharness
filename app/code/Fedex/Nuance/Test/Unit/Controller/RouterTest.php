<?php
declare(strict_types=1);

namespace Fedex\Nuance\Test\Unit\Controller;

use Fedex\Nuance\Controller\Router;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\ActionList;
use Magento\Framework\App\RouterInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class RouterTest extends TestCase
{
    /**
     * @var ActionFactory|MockObject
     */
    private $actionFactory;

    /**
     * @var ActionList|MockObject
     */
    private $actionList;

    /**
     * @var ConfigInterface|MockObject
     */
    private $routeConfig;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Router
     */
    private $router;

    protected function setUp(): void
    {
        $this->actionFactory = $this->createMock(ActionFactory::class);
        $this->actionList = $this->createMock(ActionList::class);
        $this->routeConfig = $this->createMock(ConfigInterface::class);
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getPathInfo'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->router = new Router(
            $this->actionFactory,
            $this->actionList,
            $this->routeConfig
        );
    }

    public function testMatchWithValidPath()
    {
        $this->request->method('getPathInfo')->willReturn('/nuance/nuance/index.html');

        $this->routeConfig->method('getModulesByFrontName')->with('nuance')->willReturn(['Fedex_Nuance']);
        $this->actionList->method('get')->with('Fedex_Nuance', null, 'nuance', 'index')->willReturn(Action::class);

        $actionInstance = $this->createMock(Action::class);
        $this->actionFactory->method('create')->with(Action::class)->willReturn($actionInstance);

        $result = $this->router->match($this->request);

        $this->assertSame($actionInstance, $result);
    }

    public function testMatchWithInvalidPath()
    {
        $this->request->method('getPathInfo')->willReturn('/invalid/path.html');

        $result = $this->router->match($this->request);

        $this->assertNull($result);
    }

    public function testMatchWithNoModules()
    {
        $this->request->method('getPathInfo')->willReturn('/nuance/nuance/index.html');

        $this->routeConfig->method('getModulesByFrontName')->with('nuance')->willReturn([]);

        $result = $this->router->match($this->request);

        $this->assertNull($result);
    }
}
