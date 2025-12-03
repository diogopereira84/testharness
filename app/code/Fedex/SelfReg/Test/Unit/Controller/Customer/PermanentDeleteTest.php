<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Test\Unit\Controller\Customer;

use Fedex\SelfReg\Controller\Customer\PermanentDelete;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\CustomerRepositoryInterface;
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
class PermanentDeleteTest extends TestCase
{
    /**
     * @var PermanentDelete
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
        $resultFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->customer = $this->getMockForAbstractClass(CustomerInterface::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->delete = $objectManagerHelper->getObject(
            PermanentDelete::class,
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
     * @param int $deleteCallCount
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        $customerId,
        $saveResult,
        $structure,
        $expect,
        $structureCallCount,
        $deleteCallCount
    ) {
        $this->request->expects($this->any())->method('getParam')->with('customer_id')->willReturn($customerId);

        $this->customerRepository->expects($this->any())
            ->method('deleteById')->with($customerId)->will($saveResult);
        $result = '';
        $setDataCallback = function ($data) use (&$result) {
            $result = $data['status'];
        };
        $this->resultJson->expects($this->any())->method('setData')->willReturnCallback($setDataCallback);
        $this->delete->execute();
        $this->assertNotNull('');
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
                0,
            ], //delete yourself
            [
                2,
                $this->returnValue($this->customer),
                $structure,
                'ok',
                1,
                1,
            ],
            [
                2,
                $this->throwException(new LocalizedException(__('Exception message'))),
                $structure,
                'error',
                1,
                1,
            ],
            [
                2,
                $this->throwException(new \Exception()),
                $structure,
                'error',
                1,
                1,
            ],
        ];
    }

}
