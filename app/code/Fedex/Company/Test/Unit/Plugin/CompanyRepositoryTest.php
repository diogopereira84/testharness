<?php

/**
 * Fedex
 * Copyright (C) 2021 Fedex <info@fedex.com>
 *
 * PHP version 7
 *
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Fedex <info@fedex.com>
 * @copyright 2006-2021 Fedex (http://www.fedex.com/)
 * @license   http://opensource.org/licenses/gpl-3.0.html
 * GNU General Public License,version 3 (GPL-3.0)
 * @link      http://fedex.com
 */

namespace Fedex\Company\Test\Unit\Plugin;

use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
//@codingStandardsIgnoreStart
use Fedex\Company\Model\ResourceModel\AdditionalData\Collection as AdditionalDataCollection;
//@codingStandardsIgnoreEnd
use Fedex\Company\Plugin\CompanyRepository as CompanyRepositoryPlugin;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyExtensionFactory;
use Magento\Company\Api\Data\CompanyExtensionInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Company Repository
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompanyRepositoryTest extends TestCase
{
    protected $companyRepositoryInterfaceMock;
    protected $companyInterfaceMock;
    protected $additionalDataFactoryMock;
    protected $additionalDataMock;
    protected $additionalDataCollectionMock;
    protected $companyExtensionFactoryMock;
    protected $companyExtensionInterfaceMock;
    protected $toggleConfigMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $plugin;
    /**
     * Test setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        //@codingStandardsIgnoreStart
        $this->companyRepositoryInterfaceMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        //@codingStandardsIgnoreEnd

        $this->companyInterfaceMock = $this
            ->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        //@codingStandardsIgnoreStart
        $this->additionalDataFactoryMock = $this->getMockBuilder(AdditionalDataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalDataMock = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCompanyPaymentOptions',
                    'getFedexAccountOptions',
                    'getCreditcardOptions',
                    'getDefaultPaymentMethod',
                    'getCcToken',
                    'getCcData',
                    'getCollection',
                    'getCcTokenExpiryDateTime',
                ]
            )
            ->getMock();

        $this->additionalDataCollectionMock = $this
            ->getMockBuilder(AdditionalDataCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'addFieldToSelect',
                    'addFieldToFilter',
                    'getFirstItem',
                    'getCompanyId',
                    'getData',
                ]
            )
            ->getMock();

        //@codingStandardsIgnoreStart
        $this->companyExtensionFactoryMock = $this->getMockBuilder(CompanyExtensionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyExtensionInterfaceMock = $this->getMockBuilder(CompanyExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setCompanyPaymentOptions',
                'getCompanyPaymentOptions',
                'setFedexAccountOptions',
                'setDefaultPaymentMethod',
                'setCcTokenExpiryDateTime',
                'setCcToken',
                'setCcData',
                'setCompanyAdditionalData',
                'setCreditcardOptions'
            ])
            ->getMockForAbstractClass();
        //@codingStandardsIgnoreEnd

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->plugin = $this->objectManager->getObject(
            CompanyRepositoryPlugin::class,
            [
                'additionalDataFactory' => $this->additionalDataFactoryMock,
                'companyExtensionFactory' => $this->companyExtensionFactoryMock,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );
    }

    /**
     * @test testAfterGet
     *
     * @return void
     */
    public function testAfterGet()
    {
        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->additionalDataMock);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCompanyPaymentOptions')
            ->willReturn([]);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('setCompanyPaymentOptions')
            ->willReturnSelf();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('setFedexAccountOptions')
            ->willReturnSelf();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('setCreditcardOptions')
            ->willReturnSelf();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('setDefaultPaymentMethod')
            ->willReturnSelf();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('setCcToken')
            ->willReturnSelf();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('setCcData')
            ->willReturnSelf();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('setCcTokenExpiryDateTime')
            ->willReturnSelf();

        $this->companyInterfaceMock->expects($this->any())
            ->method('setExtensionAttributes')
            ->willReturnSelf();

        $this->assertSame(
            $this->companyInterfaceMock,
            $this->plugin->afterGet(
                $this->companyRepositoryInterfaceMock,
                $this->companyInterfaceMock
            )
        );
    }

    /**
     * @test testAfterGetWithExtensionAttributesNull
     *
     * @return void
     */
    public function testAfterGetWithExtensionAttributesNull()
    {
        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->additionalDataMock);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn(null);

        $this->additionalDataMock->expects($this->any())
            ->method('getCompanyPaymentOptions')
            ->willReturn([]);

        $this->companyExtensionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('setCompanyPaymentOptions')
            ->willReturnSelf();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('setFedexAccountOptions')
            ->willReturnSelf();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('setCreditcardOptions')
            ->willReturnSelf();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('setDefaultPaymentMethod')
            ->willReturnSelf();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('setCcToken')
            ->willReturnSelf();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('setCcData')
            ->willReturnSelf();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('setCcTokenExpiryDateTime')
            ->willReturnSelf();

        $this->companyInterfaceMock->expects($this->any())
            ->method('setExtensionAttributes')
            ->willReturnSelf();

        $this->assertSame(
            $this->companyInterfaceMock,
            $this->plugin->afterGet(
                $this->companyRepositoryInterfaceMock,
                $this->companyInterfaceMock
            )
        );
    }

    /**
     * testGetToggleStatusForPerformanceImprovmentPhasetwo
     * @return void
     */
    public function testGetToggleStatusForPerformanceImprovmentPhasetwo()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->plugin->getToggleStatusForPerformanceImprovmentPhasetwo());
    }

}
