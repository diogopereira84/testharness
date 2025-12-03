<?php
declare(strict_types=1);
namespace Fedex\Nuance\Controller;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\ActionList;
use Magento\Framework\App\RouterInterface;

class Router implements RouterInterface
{
    /**
     * @param ActionFactory $actionFactory
     * @param ActionList $actionList
     * @param ConfigInterface $routeConfig
     */
    public function __construct(
        protected readonly ActionFactory $actionFactory,
        protected readonly ActionList $actionList,
        protected readonly ConfigInterface $routeConfig
    )
    {}

    public function match(RequestInterface $request)
    {
        $pathInfo = trim($request->getPathInfo(), '/');
        if (str_contains($pathInfo, 'nuance/nuance') && str_contains($pathInfo, '.html')) {
            $modules = $this->routeConfig->getModulesByFrontName('nuance');
            if (empty($modules)) {
                return null;
            }

            $action = str_replace('.html', '', $pathInfo);
            $splitUrl = explode('/', $action);

            $actionClassName = $this->actionList->get($modules[0], null, $splitUrl[1], 'index');
            $actionInstance = $this->actionFactory->create($actionClassName);
            return $actionInstance;
        }
        return null;
    }
}
