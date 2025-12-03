<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Gateway\Request;

use Fedex\Punchout\Helper\Data as PunchOutHelper;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Canva\Gateway\Request\Builder;

class BuilderTest extends TestCase
{
    /**
     * @var Customer
     */
    private Customer $customer;

    /**
     * @var CustomerSession|MockObject
     */
    private $customerSessionMock;

    /**
     * @var Builder
     */
    private Builder $builder;

    /**
     * @var PunchOutHelper|MockObject
     */
    private $punchOutHelper;

    /**
     * @var ToggleConfig|MockObject
     */
    private $toggleConfig;

    protected function setUp():void
    {
        $this->customer = (new ObjectManager($this))->getObject(Customer::class);
        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->setMethods(['setId', 'setEntityId', 'setCustomerCanvaId', 'getId', 'getCustomerCanvaId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->punchOutHelper = $this->getMockBuilder(PunchOutHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->builder = (new ObjectManager($this))->getObject(Builder::class, [
            'json' => new Json(),
            'toggleConfig' => $this->toggleConfig,
            'punchOutHelper' => $this->punchOutHelper,
            'customerSession' => $this->customerSessionMock,
        ]);
    }

    /**
     * Test builder method with toggle check
     */
    public function testBuilderWithToggle()
    {
        $accessTokenJson = 'VALID_TAZ_TOKEN';
        $gateWayToken = 'VALID_GATEWAY_TOKEN';
        $buildSubject =
        '{"headers":{"Content-Type":"application\/json","Accept":"application\/json","Authorization":"Bearer VALID_GATEWAY_TOKEN","Cookie":"Bearer=VALID_TAZ_TOKEN"},"body":"{\"userTokensRequest\":{\"canvaUserId\":\"7bb1-4305-5cdb-40\"}}"}';
        $this->punchOutHelper->expects($this->once())->method('getTazToken')->willReturn($accessTokenJson);
        $this->punchOutHelper->expects($this->once())->method('getAuthGatewayToken')->willReturn($gateWayToken);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->customerSessionMock->expects($this->any())->method('getId')->willReturn(1);
        $this->customerSessionMock->expects($this->any())->method('getCustomerCanvaId')->willReturn('shdfjds');

        $this->assertNotNull($this->builder->build(json_decode($buildSubject, true)));
    }
}
