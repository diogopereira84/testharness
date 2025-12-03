<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Test\Unit\Controller\Customer;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Fedex\SelfReg\Controller\Customer\Delete;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\ReturnStub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeleteTest extends TestCase
{
    /**
     * @var Delete
     */
    private $delete;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Json|MockObject
     */
    private $resultJson;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CustomerInterface|MockObject
     */
    private $customer;

    /**
     * @var Structure|MockObject
     */
    private $structureManager;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->structureManager = $this->createMock(Structure::class);
        $companyContext = $this->getMockForAbstractClass(
            CompanyContext::class,
            [],
            '',
            false,
            true,
            true,
            ['getCustomerId']
        );
        $companyContext->expects($this->atLeastOnce())->method('getCustomerId')->willReturn(1);
        $this->customerRepository = $this->getMockForAbstractClass(CustomerRepositoryInterface::class);
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $resultFactory = $this->createPartialMock(ResultFactory::class, ['create']);
        $this->resultJson = $this->createPartialMock(Json::class, ['setData']);
        $resultFactory->expects($this->once())->method('create')->willReturn($this->resultJson);
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->customer = $this->getMockForAbstractClass(CustomerInterface::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->delete = $objectManagerHelper->getObject(
            Delete::class,
            [
                'resultFactory' => $resultFactory,
                'structureManager' => $this->structureManager,
                'customerRepository' => $this->customerRepository,
                'logger' => $logger,
                '_request' => $this->request,
                'companyContext' => $companyContext
            ]
        );
    }

    /**
     * Test execute.
     *
     * @param int $customerId
     * @param ReturnStub|\PHPUnit\Framework\MockObject\Stub\Exception $saveResult
     * @param MockObject|null $structure
     * @param string $expect
     * @param int $structureCallCount
     * @param int $statusCallCount
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        $customerId,
        $saveResult,
        $structure,
        $expect,
        $statusCallCount
    ) {
        $this->request->expects($this->once())->method('getParam')->with('customer_id')->willReturn($customerId);
        $companyAttributes = $this->getMockForAbstractClass(CompanyCustomerInterface::class);
        $companyAttributes->expects($this->exactly($statusCallCount))
            ->method('setStatus')->with(CompanyCustomerInterface::STATUS_INACTIVE)->willReturnSelf();
        $customerExtension = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setCompanyAttributes', 'getCompanyAttributes']
        );
        $customerExtension->expects($this->exactly($statusCallCount))
            ->method('getCompanyAttributes')->willReturn($companyAttributes);
        $companyAttributes->expects($this->exactly($statusCallCount))->method('setStatus')->willReturnSelf();
        $this->customer->expects($this->exactly($statusCallCount))
            ->method('getExtensionAttributes')->willReturn($customerExtension);
        $this->customerRepository->expects($this->exactly($statusCallCount))
            ->method('getById')->willReturn($this->customer);
        $this->customerRepository->expects($this->exactly($statusCallCount))
            ->method('save')->with($this->customer)->will($saveResult);
        $result = '';
        $setDataCallback = function ($data) use (&$result) {
            $result = $data['status'];
        };
        $this->resultJson->expects($this->once())->method('setData')->willReturnCallback($setDataCallback);
        $this->delete->execute();
        $this->assertEquals($expect, $result);
    }

    /**
     * Execute data provider.
     *
     * @return array
     */
    public function executeDataProvider()
    {
        $structure = $this->getMockBuilder(Structure::class)
            ->addMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        return [
            [
                1,
                $this->returnValue($this->customer),
                $structure,
                'error',
                0,
                0
            ], //delete yourself
            [
                2,
                $this->returnValue($this->customer),
                $structure,
                'ok',
                1,
                1
            ],
            [
                2,
                $this->returnValue($this->customer),
                null,
                'ok',
                1,
                0
            ],
            [
                2,
                $this->throwException(new LocalizedException(__('Exception message'))),
                $structure,
                'error',
                1,
                1
            ],
            [
                2,
                $this->throwException(new \Exception()),
                $structure,
                'error',
                1,
                1
            ],
        ];
    }
}

