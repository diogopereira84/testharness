<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Tax\Test\Unit\Model;

use Fedex\Tax\Model\TaxExemptModalConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * TaxExemptModalConfigProviderTest Model
 */
class TaxExemptModalConfigProviderTest extends TestCase
{
    protected $taxExemptModalConfigProvider;
    private const TAX_EXEMPT_TOGGLE_PATH = 'environment_toggle_configuration/environment_toggle/sgc_b1392314_pass_tax_exempt_modal_data';
    private const XML_PATH_TAX_EXEMPT_TITLE = 'web/tax_exempt/tax_exempt_title';
    private const XML_PATH_TAX_EXEMPT_BODY = 'web/tax_exempt/tax_exempt_body';
    private const XML_PATH_TAX_EXEMPT_PRIMARY_CTA = 'web/tax_exempt/tax_exempt_primary_cta';
    private const XML_PATH_TAX_EXEMPT_SECONDARY_CTA = 'web/tax_exempt/tax_exempt_secondary_cta';
    private const XML_PATH_TAX_EXEMPT_FOOTER_TEXT = 'web/tax_exempt/tax_exempt_footer_text';

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var ToggleConfig|MockObject
     */
    private $toggleConfigMock;

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

        $objectManagerHelper = new ObjectManager($this);

        $this->taxExemptModalConfigProvider = $objectManagerHelper->getObject(
            TaxExemptModalConfigProvider::class,
            [
                'scopeConfigInterface' => $this->scopeConfigMock,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );
    }

    /**
     * Test getConfig
     *
     * @return void
     */
    public function testGetConfig(): void
    {
        $expectedResponse = [
            'is_tax_exempt_modal_admin_data' => false,
            'tax_exempt_modal_title' => '',
            'tax_exempt_modal_body' => '',
            'tax_exempt_modal_primary_cta' => '',
            'tax_exempt_modal_secondary_cta' => '',
            'tax_exempt_modal_footer' => ''
        ];

        $this->assertEquals($expectedResponse, $this->taxExemptModalConfigProvider->getConfig());
    }

    /**
     * Test getTaxExemptModalAdminDataToggle
     * 
     * @return void
     */
    public function testGetTaxExemptModalAdminDataToggle(): void
    {
        $this->toggleConfigMock->expects($this->once())->method('getToggleConfig')
            ->with(self::TAX_EXEMPT_TOGGLE_PATH)
            ->willReturn(1);

        $this->assertIsBool($this->taxExemptModalConfigProvider->getTaxExemptModalAdminDataToggle());
    }

    /**
     * Test getTaxExemptModalTitle
     * 
     * @param array $adminValues
     * @dataProvider taxModalAdminDataProvider
     * 
     * @return void
     */
    public function testGetTaxExemptModalTitle(array $adminValues): void
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_TAX_EXEMPT_TITLE)
            ->willReturn($adminValues[0]);

        $this->assertEquals($adminValues[1], $this->taxExemptModalConfigProvider->getTaxExemptModalTitle());
    }

    /**
     * Test getTaxExemptModalBody
     * 
     * @param array $adminValues
     * @dataProvider taxModalAdminDataProvider
     * 
     * @return void
     */
    public function testGetTaxExemptModalBody(array $adminValues): void
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_TAX_EXEMPT_BODY)
            ->willReturn($adminValues[0]);

        $this->assertEquals($adminValues[1], $this->taxExemptModalConfigProvider->getTaxExemptModalBody());
    }

    /**
     * Test getTaxExemptModalPrimaryCTA
     * 
     * @param array $adminValues
     * @dataProvider taxModalAdminDataProvider
     * 
     * @return void
     */
    public function testGetTaxExemptModalPrimaryCTA(array $adminValues): void
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_TAX_EXEMPT_PRIMARY_CTA)
            ->willReturn($adminValues[0]);

        $this->assertEquals($adminValues[1], $this->taxExemptModalConfigProvider->getTaxExemptModalPrimaryCTA());
    }

    /**
     * Test getTaxExemptModalSecondaryCTA
     * 
     * @param array $adminValues
     * @dataProvider taxModalAdminDataProvider
     * 
     * @return void
     */
    public function testGetTaxExemptModalSecondaryCTA(array $adminValues): void
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_TAX_EXEMPT_SECONDARY_CTA)
            ->willReturn($adminValues[0]);

        $this->assertEquals($adminValues[1], $this->taxExemptModalConfigProvider->getTaxExemptModalSecondaryCTA());
    }

    /**
     * Test getTaxExemptModalFooter
     * 
     * @param array $adminValues
     * @dataProvider taxModalAdminDataProvider
     * 
     * @return void
     */
    public function testGetTaxExemptModalFooter(array $adminValues): void
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_TAX_EXEMPT_FOOTER_TEXT)
            ->willReturn($adminValues[0]);

        $this->assertEquals($adminValues[1], $this->taxExemptModalConfigProvider->getTaxExemptModalFooter());
    }

    /**
     * @return array
     */
    public function taxModalAdminDataProvider(): array
    {
        return [
            [
                [ '<p>Tax Modal Data</p>', 'Tax Modal Data' ]
            ],
            [
                [ '', '' ]
            ]
        ];
    }
}