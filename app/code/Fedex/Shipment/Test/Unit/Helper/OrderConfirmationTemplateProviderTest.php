<?php

namespace Fedex\Shipment\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Company\Api\CompanyManagementInterface;
use Fedex\MarketplaceCheckout\Model\Email;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Mail\Template\Factory as TemplateFactory;
use Magento\Framework\App\Area;
use Fedex\Shipment\Helper\OrderConfirmationTemplateProvider;

/**
 * Test class for Fedex\Shipment\Helper\StatusOption
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class OrderConfirmationTemplateProviderTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $orderConfirmationTemplate;
    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;

    /**
     * @var CompanyManagementInterface $companyManager
     */
    private $companyManager;

    /**
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;

    /**
     * @var TemplateFactory $template
     */
    private $template;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyManager = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getByCustomerId',
                    'getBccCommaSeperatedEmail',
                    'getIsSuccessEmailEnable',
                    'getOrderConfirmationEmailTemplate'
                ]
            )
            ->getMockForAbstractClass();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(
                [
                    'getStore',
                    'getId'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->template = $this->getMockBuilder(TemplateFactory::class)
            ->setMethods(
                [
                    'get',
                    'setVars',
                    'setOptions',
                    'processTemplate',
                    'getSubject'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->orderConfirmationTemplate = $this->objectManagerHelper->getObject(
            OrderConfirmationTemplateProvider::class,
            [
                'context' => $this->context,
                'companyManager' => $this->companyManager,
                'storeManager' => $this->storeManager,
                'template' => $this->template,
                'toggleConfig' => $this->toggleConfig,
            ]
        );
    }

    /**
     * Test case for testGetFormattedAddressArray3pShipping
     */
    public function testGetBccEmail()
    {
        $shipmentStatus = 'confirmed';
        $customerid = 2342;
        $bccEmail = 'test@gmail.com, test2@gmail.com';
        $this->companyManager->expects($this->any())
        ->method('getByCustomerId')->willReturnSelf();
        $this->companyManager->expects($this->any())
        ->method('getBccCommaSeperatedEmail')->willReturn($bccEmail);
        $this->companyManager->expects($this->any())
        ->method('getIsSuccessEmailEnable')->willReturn(1);
        $this->toggleConfig->expects($this->any())
        ->method('getToggleConfigValue')->willReturn(true);
        $this->assertNotNull($this->orderConfirmationTemplate
        ->getBccEmail($shipmentStatus, $customerid));
    }

    /**
     * Test case for testGetFormattedAddressArray3pShipping
     */
    public function testGetConfirmationstatus()
    {
        $shipmentStatus = 'confirmed';
        $customerid = 2342;
        $this->companyManager->expects($this->any())
        ->method('getByCustomerId')->willReturnSelf();
        $this->companyManager->expects($this->any())
        ->method('getIsSuccessEmailEnable')->willReturn(1);
        $this->toggleConfig->expects($this->any())
        ->method('getToggleConfigValue')->willReturn(true);
        $this->assertNotNull($this->orderConfirmationTemplate
        ->getConfirmationstatus($shipmentStatus, $customerid));
    }

    /**
     * Test case for testGetFormattedAddressArray3pShipping
     */
    public function testGetEmailTemplateById()
    {
        $orderData = ['customer_id' => 3434];
        $this->companyManager->expects($this->any())
        ->method('getByCustomerId')->willReturnSelf();
        $this->template->expects($this->any())
        ->method('get')->willReturnSelf();
        $this->template->expects($this->any())
        ->method('setVars')->willReturnSelf();
        $this->template->expects($this->any())
        ->method('setOptions')->willReturnSelf();
        $this->storeManager->expects($this->any())
        ->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())
        ->method('getId')->willReturnSelf();
        $this->assertNotNull($this->orderConfirmationTemplate
        ->getEmailTemplateById($orderData));
    }
}
