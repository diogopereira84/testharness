<?php
/**
 * Copyright © fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Shipto\Test\Unit\Plugin\Checkout;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Model\Session;
use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Fedex\Shipto\Plugin\Checkout\LayoutProcessorPlugin;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class LayoutProcessorPluginTest extends TestCase
{
    protected $customerSession;
    protected $companyRepository;
    protected $companyInterface;
    protected $subject;
    protected $toggleConfigMock;
    protected $layoutProcessor;
    protected function setUp(): void
    {
        $this->customerSession = $this->getMockBuilder(Session::class)
        ->disableOriginalConstructor()
        ->setMethods(['getCustomerCompany','isLoggedIn'])
        ->getMock();

        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
        ->disableOriginalConstructor()
        ->setMethods(['get'])
        ->getMockForAbstractClass();

        $this->companyInterface = $this->getMockBuilder(CompanyInterface::class)
        ->disableOriginalConstructor()
        ->setMethods(['getRecipientAddressFromPo', 'getStorefrontLoginMethodOption'])
        ->getMockForAbstractClass();

        $this->subject = $this->getMockBuilder(LayoutProcessor::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->layoutProcessor = $objectManagerHelper->getObject(
            LayoutProcessorPlugin::class,
            [
                'customerSession' => $this->customerSession,
                'companyRepository' => $this->companyRepository,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );
    }

    public function testAfterProcess()
    {
        $layout = $this->getLayout();
        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn(2);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getStorefrontLoginMethodOption')
            ->willReturn('commercial_store_epro');
        $this->companyInterface->expects($this->any())->method('getRecipientAddressFromPo')->willReturn(1);
        $this->assertNotNull($this->layoutProcessor->afterProcess($this->subject, []));
    }

    public function getLayout()
    {
        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['firstname']['validation'] = ['required-entry' => false];

        /**** hide firstname *****/

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['firstname']['config']['additionalClasses']  = "hide shipto-firstname";

        /**** remove required from lastname *****/
        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['lastname']['validation'] = ['required-entry' => false];

        /**** hide lastname *****/

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['lastname']['config']['additionalClasses']  = "hide shipto-lastname";

        /**** remove required from city *****/
        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['city']['validation'] = ['required-entry' => false];

        /**** hide city *****/

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['city']['config']['additionalClasses']  = "hide shipto-city";

        /**** remove required from street *****/
        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['street']['required'] = false;

        /**** hide street *****/

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['street']['config']['additionalClasses']  = "hide shipto-street";

        /**** remove required from streetfirstinput *****/
        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['street']['children'][0]['validation']  = ['required-entry' => false];

        /**** remove required from telephone *****/
        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['telephone']['validation'] = ['required-entry' => false];

        /**** hide telephone *****/

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['telephone']['config']['additionalClasses']  = "hide shipto-telephone";

        /**** remove required from regionid *****/
        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['region_id']['validation'] = ['required-entry' => true];

        /**** hide regionid *****/

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['region_id']['config']['additionalClasses']  = "hide shipto-region-id";

        /**** hide countryid *****/

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['country_id']['config']['additionalClasses']  = "hide shipto-country-id";

        /**** hide company *****/

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['company']['config']['additionalClasses']  = "hide shipto-company";

        /**** tooltip add zipcode *****/

        $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['postcode']['tooltip']['description'] =
            "Please include recipient location  by entering  State  and ZIP code  for this order.
            The complete delivery address  is what  is provided on Purchase Order(PO).";

        /**** add Class regionid *****/

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['region_id']['config']['additionalClasses']  = "shipto-region";

        /**** add Class postcode *****/

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['postcode']['config']['additionalClasses']  = "shipto-postcode";

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['street']['component'] = 'Fedex_Shipto/js/form/components/group';

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']
        ['children']['shipping-address-fieldset']['children']['street']['children'][0]
        ['validation']['fedex-validate-not-number'] = true;

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['email_id']['validation']['fedex-validate-email'] = true;

        return $jsLayout;
    }

    /**
     * Test Method when we do not set receive Ship to from po
     */
    public function testAfterProcessWithoutReceiptPofromShip()
    {
        $layout = $this->getLayoutWithNoShipFromPo();
        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn(2);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getStorefrontLoginMethodOption')
            ->willReturn('commercial_store_epro');
        $this->companyInterface->expects($this->any())->method('getRecipientAddressFromPo')->willReturn(0);
        $this->toggleConfigMock->expects($this->any())
        ->method('getToggleConfigValue')
        ->willReturn(true);

        $this->assertNotNull($this->layoutProcessor->afterProcess($this->subject, []));
    }

    /**
     * Test Method for getLayoutWithNoShipFromPo
     */
    protected function getLayoutWithNoShipFromPo()
    {
        $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']
            ['children']['shipping-address-fieldset']['children']['email_id']
            ['validation']['fedex-validate-email'] = true;
        $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']
            ['children']['shipping-address-fieldset']['children']['company']
            ['validation']['fedex-validate-company'] = true;
        $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']
            ['children']['shipping-address-fieldset']['children']['street']['children'][0]
            ['validation']['fedex-validate-street'] = true;
        $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']
            ['children']['shipping-address-fieldset']['children']['street']['children'][1]
            ['validation']['fedex-validate-street'] = true;

        return $jsLayout;
    }
}
