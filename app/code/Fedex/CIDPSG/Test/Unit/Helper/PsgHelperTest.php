<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Helper;

use Fedex\CIDPSG\Helper\PsgHelper;
use Fedex\CIDPSG\Model\Customer;
use Fedex\CIDPSG\Model\CustomerFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Respons;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class PsgHelperTest extends TestCase
{
    /**
     * @var PsgHelper|MockObject
     */
    private $psgHelper;

    /**
     * @var CustomerFactory|MockObject
     */
    private $customerFactory;

    /**
     * @var Customer|MockObject
     */
    private $customer;

    /**
     * @var ResponseFactory|MockObject
     */
    private $responseFactory;

    /**
     * @var Response|MockObject
     */
    private $response;

    /**
     * Set up method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerFactory = $this->getMockBuilder(CustomerFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->setMethods(['getCollection', 'getSelect', 'joinLeft', 'getTable', 'addFieldToFilter', 'load',
                'getParticipationAgreement', 'reset', 'order'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseFactory = $this->getMockBuilder(ResponseFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->response = $this->getMockBuilder(Response::class)
            ->setMethods(['setRedirect', 'sendResponse'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->psgHelper = $objectManager->getObject(PsgHelper::class, [
            'context' => $contextMock,
            'customerFactory' => $this->customerFactory,
            'customer' => $this->customer,
            'responseFactory' => $this->responseFactory,
            'response' => $this->response,
        ]);
    }

    /**
     * Test method for get PSG Customer Info
     *
     * @return void
     */
    public function testGetPSGCustomerInfo()
    {
        $this->customerFactory->expects($this->once())->method('create')->willReturn($this->customer);
        $this->customer->expects($this->once())->method('getCollection')->willReturnSelf();
        $this->customer->expects($this->exactly(2))->method('getSelect')->willReturnSelf();
        $this->customer->expects($this->once())->method('joinLeft')->willReturnSelf();
        $this->customer->expects($this->once())->method('getTable')->willReturnSelf();
        $this->customer->expects($this->once())->method('addFieldToFilter')->willReturnSelf();
        $this->customer->expects($this->once())->method('reset')->willReturnSelf();
        $this->customer->expects($this->once())->method('order')->willReturnSelf();
        $this->responseFactory->expects($this->once())->method('create')->willReturn($this->response);
        $this->response->expects($this->once())->method('setRedirect')->willReturnSelf();

        $this->assertIsObject($this->psgHelper->getPSGCustomerInfo('default'));
    }

    /**
     * Test method for get PSG agreement Info
     *
     * @return void
     */
    public function testGetPSGPaAgreementInfoByClientId()
    {
        $paAgreementContent = [
            "pa_agreement" =>"Dummy Content",
            "participation_code" => "IND1234455",
            "company_name" => "Test Company"
        ];

        $this->customerFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->customer);

        $this->customer->expects($this->once())
            ->method('load')->willReturnSelf();

        $this->customer->expects($this->once())
            ->method('getParticipationAgreement')->willReturn($paAgreementContent);

        $this->assertIsArray($this->psgHelper->getPSGPaAgreementInfoByClientId('default'));
    }
}
