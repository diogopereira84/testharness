<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedDetails\Test\Unit\Helper;

use Fedex\CIDPSG\Api\MessageInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SharedDetails\Helper\CommercialReportHelper;
use Fedex\Shipment\Model\ProducingAddress;
use Fedex\Shipment\Model\ProducingAddressFactory;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class CommercialReportHelperTest extends TestCase
{
    protected $producingAddressFactoryMock;
    protected $producingAddressMock;
    protected $productMock;
    protected $attributeSetRepositoryInterfaceMock;
    protected $directoryListMock;
    protected $messageMock;
    protected $publisherMock;
    protected $scopeConfigMock;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfig;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $commercialReportHelperMock;
    /**
     * @var Context $contextMock;
     */
    protected $contextMock;

    protected function setUp(): void
    {
        $this->contextMock = $this
            ->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->producingAddressFactoryMock = $this->getMockBuilder(ProducingAddressFactory::class)
             ->disableOriginalConstructor()
             ->setMethods(['create', 'load'])
             ->getMock();
        $this->producingAddressMock = $this->getMockBuilder(ProducingAddress::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'load', 'getData'])
            ->getMock();
        $this->productMock = $this->getMockBuilder(Product::class)
             ->disableOriginalConstructor()
             ->getMock();
        $this->attributeSetRepositoryInterfaceMock = $this->getMockBuilder(AttributeSetRepositoryInterface::class)
             ->disableOriginalConstructor()
             ->setMethods(['get', 'getAttributeSetName'])
             ->getMockForAbstractClass();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
             ->disableOriginalConstructor()
             ->getMock();
        $this->messageMock = $this->getMockBuilder(MessageInterface::class)
             ->disableOriginalConstructor()
             ->getMockForAbstractClass();
        $this->publisherMock = $this->getMockBuilder(PublisherInterface::class)
             ->disableOriginalConstructor()
             ->getMockForAbstractClass();
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue', 'getToggleConfig'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->commercialReportHelperMock = $this->objectManager->getObject(
            CommercialReportHelper::class,
            [
                'producingAddressFactory' => $this->producingAddressFactoryMock,
                'product' => $this->productMock,
                'attributeSetRepository' => $this->attributeSetRepositoryInterfaceMock,
                'directoryList' => $this->directoryListMock,
                'message' => $this->messageMock,
                'toggleConfig' => $this->toggleConfig,
                'publisher' => $this->publisherMock,
                'scopeConfig' => $this->scopeConfigMock
            ]
        );
    }

    /**
     * Test method for getBranchId
     */
    public function testGetBranchId()
    {
        $branchId = 'TMBKO';
        $additionalData = '{"estimated_time":"2023-12-14T11:00:00","estimated_duration":null,"responsible_location_id":"TMBKO"}';

        $this->producingAddressFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->producingAddressMock);
        $this->producingAddressMock->expects($this->any())
                ->method('load')->willReturnSelf();
        $this->producingAddressMock->expects($this->any())
                ->method('getData')->willReturn($additionalData);

        $this->assertEquals($branchId, $this->commercialReportHelperMock->getBranchId($branchId));
    }

    /**
     * Test method for getAttributeSet
     */
    public function testGetAttributeSet()
    {
        $productId = 2;
        $attributeSet = 'PrintOnDemand';
        $this->productMock->expects($this->any())->method('load')->willReturnSelf();
        $this->attributeSetRepositoryInterfaceMock->expects($this->any())
            ->method('get')->willReturnSelf();
        $this->attributeSetRepositoryInterfaceMock->expects($this->any())
            ->method('getAttributeSetName')->willReturn($attributeSet);

        $this->assertEquals($attributeSet, $this->commercialReportHelperMock->getAttributeSet($productId));
    }

    /**
     * Test method for sendEmail
     */
    public function testSendEmail()
    {
        $toEmails = 'test@yopmail.com';
        $fromEmail = 'from@test.com';
        $fileName = 'filename.xls';
        $filePath = '/var/www/html/staging3.office.fedex.com/var/export';
        $genericEmailData = [
            "toEmailId"         => $toEmails,
            "fromEmailId"       => $fromEmail,
            "templateSubject"   => 'Order Data Report',
            "templateData"      => 'Orders Data',
            "attachment"        => $filePath. '/' . $fileName,
            "commercial_report" => true
        ];
        $this->directoryListMock->expects($this->any())->method('getPath')
            ->willReturn($filePath);
        $this->scopeConfigMock->expects($this->any())->method('getValue')->willReturn($fromEmail);
        $this->messageMock->expects($this->any())->method('setMessage')
            ->with(json_encode($genericEmailData))->willReturnSelf();
        $this->publisherMock->expects($this->any())->method('publish')->willReturnSelf();

        $this->assertNull($this->commercialReportHelperMock->sendEmail($fileName, $toEmails));
    }

    /**
     * Test method for sendEmail
     */
    public function testSendUserReportEmail()
    {
        $toEmails = 'test@yopmail.com';
        $fromEmail = 'from@test.com';
        $fileName = 'filename.xls';
        $filePath = '/var/www/html/staging3.office.fedex.com/var/export';
        $genericEmailData = [
            "toEmailId"         => $toEmails,
            "fromEmailId"       => $fromEmail,
            "templateSubject"   => 'Users Data Report',
            "templateData"      => 'Users Data',
            "attachment"        => $filePath. '/' . $fileName,
            "commercial_report" => true
        ];
        $this->directoryListMock->expects($this->any())->method('getPath')
            ->willReturn($filePath);
        $this->scopeConfigMock->expects($this->any())->method('getValue')->willReturn($fromEmail);
        $this->messageMock->expects($this->any())->method('setMessage')
            ->with(json_encode($genericEmailData))->willReturnSelf();
        $this->publisherMock->expects($this->any())->method('publish')->willReturnSelf();

        $this->assertNull($this->commercialReportHelperMock->sendUserReportEmail($fileName, $toEmails));
    }
}
