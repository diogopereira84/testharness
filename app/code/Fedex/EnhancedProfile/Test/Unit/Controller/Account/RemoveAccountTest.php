<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
*/

namespace Fedex\EnhancedProfile\Test\Unit\Controller\Account;

use PHPUnit\Framework\TestCase;
use Fedex\EnhancedProfile\Controller\Account\RemoveAccount;
use Magento\Framework\App\RequestInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\Result\Json as ResultJson;

class RemoveAccountTest extends TestCase
{
    protected $company;
    private $request;
    private $companyRepository;
    private $logger;
    private $resultJsonFactory;
    private $json;
    private $controller;

    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParams'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyRepository = $this->createMock(CompanyRepositoryInterface::class);
        
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->company = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterface::class)
                    ->setMethods(['setData','save'])
                    ->disableOriginalConstructor()
                    ->getMockForAbstractClass();

        $this->json = $this->createMock(Json::class);

        $this->controller = new RemoveAccount(
            $this->request,
            $this->companyRepository,
            $this->logger,
            $this->resultJsonFactory,
            $this->json
        );
    }

    /**
     * TestExecute With Valid CompanyId And Print AccountType
     * 
     */
    public function testExecuteWithValidCompanyIdAndPrintAccountType()
    {
        $companyId = 1;
        $accountType = 'Print';
        
        $resultJson = $this->createMock(ResultJson::class);

        $this->request->method('getParams')->willReturn([
            'companyId' => $companyId,
            'accountType' => $accountType
        ]);

        $this->companyRepository->expects($this->once())
            ->method('get')
            ->with($companyId)
            ->willReturn($this->company);

        $this->company->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->company->expects($this->any())
            ->method('save')->willReturnSelf();

        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($resultJson);

        $resultJson->expects($this->once())
            ->method('setData')
            ->with(['error' => false, 'msg' => 'Account Removed Successfully']);

        $response = $this->controller->execute();

        $this->assertEquals(null, $response);
    }

    /**
     * TestExecute With Valid CompanyId And Ship AccountType
     * 
     */
    public function testExecuteWithValidCompanyIdAndShipAccountType()
    {
        $companyId = 1;
        $accountType = 'Ship';
        
        $resultJson = $this->createMock(ResultJson::class);

        $this->request->method('getParams')->willReturn([
            'companyId' => $companyId,
            'accountType' => $accountType
        ]);

        $this->companyRepository->expects($this->once())
            ->method('get')
            ->with($companyId)
            ->willReturn($this->company);

        $this->company->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->company->expects($this->any())
            ->method('save')->willReturnSelf();

        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($resultJson);

        $resultJson->expects($this->once())
            ->method('setData')
            ->with(['error' => false, 'msg' => 'Account Removed Successfully']);

        $response = $this->controller->execute();

        $this->assertEquals(null, $response);
    }

    /**
     * TestExecute With Exception
     * 
     */
    public function testExecuteWithLocalizedException()
    {
        $this->request->method('getParams')->willReturn([
            'companyId' => 1,
            'accountType' => 'Print'
        ]);

        $this->companyRepository->method('get')
            ->willThrowException(new LocalizedException(__('Error')));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error'));

        $resultJson = $this->createMock(ResultJson::class);
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($resultJson);

        $resultJson->expects($this->once())
            ->method('setData')
            ->with(['error' => true, 'msg' => 'Error']);

        $response = $this->controller->execute();

        $this->assertEquals(null, $response);
    }

    /**
     * TestExecute With Exception
     * 
     */
    public function testExecuteWithGeneralException()
    {
        $this->request->method('getParams')->willReturn([
            'companyId' => 1,
            'accountType' => 'Print'
        ]);

        $this->companyRepository->method('get')
            ->willThrowException(new \Exception('General error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('General error'));

        $resultJson = $this->createMock(ResultJson::class);
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($resultJson);

        $resultJson->expects($this->once())
            ->method('setData')
            ->with(['error' => true, 'msg' => 'General error']);

        $response = $this->controller->execute();

        $this->assertEquals(null, $response);
    }
}
