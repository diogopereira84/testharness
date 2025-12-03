<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\ThirdPartyLegalDisclaimer\Test\Unit\Block;

use Fedex\ThirdPartyLegalDisclaimer\Block\AdminValues;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class AdminValuesTest extends TestCase
{
    protected $adminValues;
    private const TAX_EXEMPT_TOGGLE_PATH = 'environment_toggle_configuration/environment_toggle/sgc_b1392314_pass_tax_exempt_modal_data';
    private const XML_PATH_THIRD_PARTY_MODAL_TITLE = 'web/third_party_modal/third_party_modal_title';
    private const XML_PATH_THIRD_PARTY_MODAL_TOP_DESCRIPTION = 'web/third_party_modal/third_party_modal_top_description';
    private const XML_PATH_THIRD_PARTY_MODAL_BOTTOM_DESCRIPTION = 'web/third_party_modal/third_party_modal_bottom_description';

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfigMock;

    /**
     * @var MockObject|ObjectManager
     */
    protected $objectManager;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->onlyMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->onlyMethods(['getToggleConfig'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->adminValues = $this->objectManager->getObject(
            AdminValues::class,
            [
                'scopeConfigInterface' => $this->scopeConfigMock,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );
    }

    /**
     * Test getThirdPartyModalAdminDataToggle.
     * 
     * @return void
     */
    public function getThirdPartyModalAdminDataToggle(): void
    {
        $this->toggleConfigMock->expects($this->once())->method('getToggleConfig')
            ->with(self::TAX_EXEMPT_TOGGLE_PATH)
            ->willReturn(1);

        $this->assertIsBool($this->adminValues->getThirdPartyModalAdminDataToggle());
    }

    /**
     * Test getThirdPartyModalTitle
     * 
     * @param array $adminData
     * @dataProvider thirdPartyModalAdminDataProvider
     * 
     * @return void
     */
    public function testGetTaxExemptModalTitle(array $adminData): void
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_THIRD_PARTY_MODAL_TITLE)
            ->willReturn($adminData[0]);

        $this->assertEquals($adminData[1], $this->adminValues->getThirdPartyModalTitle());
    }

    /**
     * Test getThirdPartyModalTopDescription
     * 
     * @param array $adminData
     * @dataProvider thirdPartyModalAdminDataProvider
     * 
     * @return void
     */
    public function testGetThirdPartyModalTopDescription(array $adminData): void
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_THIRD_PARTY_MODAL_TOP_DESCRIPTION)
            ->willReturn($adminData[0]);

        $this->assertEquals($adminData[1], $this->adminValues->getThirdPartyModalTopDescription());
    }

    /**
     * Test getThirdPartyModalBottomDescription
     * 
     * @param array $adminData
     * @dataProvider thirdPartyModalAdminDataProvider
     * 
     * @return void
     */
    public function testGetThirdPartyModalBottomDescription(array $adminData): void
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_THIRD_PARTY_MODAL_BOTTOM_DESCRIPTION)
            ->willReturn($adminData[0]);

        $this->assertEquals($adminData[1], $this->adminValues->getThirdPartyModalBottomDescription());
    }

    /**
     * @return array
     */
    public function thirdPartyModalAdminDataProvider(): array
    {
        return [
            [
                [ '<p>Third Party Modal Data</p>', 'Third Party Modal Data' ]
            ],
            [
                [ '', '' ]
            ]
        ];
    }
}
