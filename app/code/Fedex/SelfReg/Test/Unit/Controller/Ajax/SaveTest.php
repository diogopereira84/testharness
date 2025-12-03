<?php

namespace Fedex\SelfReg\Test\Unit\Controller\Ajax;

use Fedex\SelfReg\Controller\Ajax\Save;
use Fedex\SelfReg\Model\CategoryPermissionProcessor;
use Fedex\SaaSCommon\Api\ConfigInterface as FedexSaaSCommonConfig;
use Fedex\SaaSCommon\Api\CustomerGroupAttributeHandlerInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Magento\SharedCatalog\Model\State;
use PHPUnit\Framework\TestCase;
use Exception;

class SaveTest extends TestCase
{
    private $resultJsonFactory;
    private $resultJson;
    private $request;
    private $logger;
    private $categoryPermissionProcessor;
    private $sharedCatalogState;
    private $fedexSaaSCommonConfig;
    private $customerGroupAttributeHandler;
    private $controller;

    protected function setUp(): void
    {
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->resultJson = $this->createMock(Json::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->categoryPermissionProcessor = $this->createMock(CategoryPermissionProcessor::class);
        $this->sharedCatalogState = $this->createMock(State::class);
        $this->fedexSaaSCommonConfig = $this->createMock(FedexSaaSCommonConfig::class);
        $this->customerGroupAttributeHandler = $this->createMock(CustomerGroupAttributeHandlerInterface::class);

        $this->resultJsonFactory->method('create')->willReturn($this->resultJson);

        $this->controller = new Save(
            $this->resultJsonFactory,
            $this->request,
            $this->logger,
            $this->categoryPermissionProcessor,
            $this->sharedCatalogState,
            $this->fedexSaaSCommonConfig,
            $this->customerGroupAttributeHandler
        );
    }

    public function testExecuteSuccessGlobalCatalog()
    {
        $params = [
            'groupIds' => [1, 2],
            'categoryId' => '10',
            'isFolderRestricted' => true
        ];
        $this->request->expects($this->once())->method('getParams')->willReturn($params);

        $this->sharedCatalogState->expects($this->once())->method('isGlobal')->willReturn(true);
        $this->categoryPermissionProcessor->expects($this->once())->method('getActiveWebsiteIds')->willReturn([100, 200]);
        $this->categoryPermissionProcessor->expects($this->once())
            ->method('processPermissions')
            ->with(null, 10, true, [1, 2]);

        $this->fedexSaaSCommonConfig->expects($this->once())->method('isTigerD200529Enabled')->willReturn(true);
        $this->customerGroupAttributeHandler->expects($this->once())
            ->method('pushEntityToQueue')
            ->with(10, Category::ENTITY);

        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with([
                'status' => 'success',
                'message' => __('Category Permissions saved successfully.')
            ]);

        $result = $this->controller->execute();
        $this->assertSame($this->resultJson, $result);
    }

    public function testExecuteSuccessNonGlobalCatalog()
    {
        $params = [
            'groupIds' => [3, 4],
            'categoryId' => '20',
            'isFolderRestricted' => true
        ];
        $this->request->expects($this->once())->method('getParams')->willReturn($params);

        $this->sharedCatalogState->expects($this->once())->method('isGlobal')->willReturn(false);
        $this->categoryPermissionProcessor->expects($this->once())->method('getActiveWebsiteIds')->willReturn([100, 200]);
        $this->categoryPermissionProcessor->expects($this->exactly(2))
            ->method('processPermissions')
            ->withConsecutive([100, 20, true, [3, 4]], [200, 20, true, [3, 4]]);

        $this->fedexSaaSCommonConfig->expects($this->once())->method('isTigerD200529Enabled')->willReturn(false);
        $this->customerGroupAttributeHandler->expects($this->never())->method('pushEntityToQueue');

        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with([
                'status' => 'success',
                'message' => __('Category Permissions saved successfully.')
            ]);

        $result = $this->controller->execute();
        $this->assertSame($this->resultJson, $result);
    }

    public function testExecuteNoCategoryId()
    {
        $params = [
            'groupIds' => [5, 6],
            'categoryId' => '',
            'isFolderRestricted' => true
        ];
        $this->request->expects($this->once())->method('getParams')->willReturn($params);

        $this->sharedCatalogState->expects($this->never())->method('isGlobal');
        $this->categoryPermissionProcessor->expects($this->never())->method('getActiveWebsiteIds');
        $this->categoryPermissionProcessor->expects($this->never())->method('processPermissions');
        $this->fedexSaaSCommonConfig->expects($this->never())->method('isTigerD200529Enabled');
        $this->customerGroupAttributeHandler->expects($this->never())->method('pushEntityToQueue');

        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with([
                'status' => 'success',
                'message' => __('Category Permissions saved successfully.')
            ]);

        $result = $this->controller->execute();
        $this->assertSame($this->resultJson, $result);
    }

    public function testExecuteNoPostData()
    {
        $this->request->expects($this->once())->method('getParams')->willReturn([]);
        $this->resultJson->expects($this->never())->method('setData');
        $result = $this->controller->execute();
        $this->assertSame($this->resultJson, $result);
    }

    public function testExecuteExceptionInPermissions()
    {
        $params = [
            'groupIds' => [7, 8],
            'categoryId' => '30',
            'isFolderRestricted' => true
        ];
        $this->request->expects($this->once())->method('getParams')->willReturn($params);

        $this->sharedCatalogState->expects($this->once())->method('isGlobal')->willReturn(true);
        $this->categoryPermissionProcessor->expects($this->once())
            ->method('processPermissions')
            ->willThrowException(new Exception('fail-permission'));

        $this->fedexSaaSCommonConfig->expects($this->never())->method('isTigerD200529Enabled');
        $this->customerGroupAttributeHandler->expects($this->never())->method('pushEntityToQueue');

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to set permissions for category ID 30: fail-permission'));

        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with([
                'status' => 'success',
                'message' => __('Category Permissions saved successfully.')
            ]);

        $result = $this->controller->execute();
        $this->assertSame($this->resultJson, $result);
    }

    public function testExecuteExceptionInExecute()
    {
        $this->request->expects($this->once())->method('getParams')->willThrowException(new Exception('fail-execute'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error in execute method: fail-execute'));

        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with([
                'status' => 'error',
                'message' => __('An error occurred while saving permissions.')
            ]);

        $result = $this->controller->execute();
        $this->assertSame($this->resultJson, $result);
    }
}

