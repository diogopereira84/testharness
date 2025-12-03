<?php
namespace Fedex\Purchaseorder\Test\Unit\Model;

use Fedex\Purchaseorder\Model\QuoteCreation;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;

class QuoteCreationTest extends TestCase
{
    /**
     * @var (\Magento\Quote\Api\Data\AddressInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $addressInterface;
    /**
     * @var (\Magento\Checkout\Api\Data\ShippingInformationInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $shippingInformationInterface;
    /**
     * @var (\Magento\Checkout\Model\ShippingInformationManagement & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $shippingInformationManagement;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $quoteCreation;
    protected function setUp(): void
    {
        $this->addressInterface = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
            
        $this->shippingInformationInterface = $this->getMockBuilder(ShippingInformationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
            
        $this->shippingInformationManagement = $this->getMockBuilder(ShippingInformationManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->objectManager = new ObjectManager($this);
        $this->quoteCreation = $this->objectManager->getObject(
            QuoteCreation::class,
            [
                'shippingInformationManagement' => $this->shippingInformationManagement,
                'addressInterface' => $this->addressInterface,
                'shippingInformationInterface' => $this->shippingInformationInterface
            ]
        );
    }
    /**
     * Test Case for saving shipping Address.
     *
     */
    public function testsaveShippingAddress()
    {
        $quoteId = 1;
        $requestData = [
            'addressInformation' => [
                'shipping_address' => [
                    'region' => 'FL',
                    'region_id' => 53,
                    'region_code' => 'FL',
                    'country_id' => 'US',
                    'street' => [0 => '4900 S University Dr'],
                    'postcode' => 33328,
                    'city' => 'Davie',
                    'company' => 'Company',
                    'firstname' => 'Neeraj',
                    'lastname' => 'Gupta',
                    'email' => 'neeraj2.gupta@infogain.com',
                    'telephone' => 9789876567,
                ],
                'billing_address' => [
                    'region' => 'FL',
                    'region_id' => 53,
                    'region_code' => 'FL',
                    'country_id' => 'US',
                    'street' => [0 => '4900 S University Dr'],
                    'postcode' => 33328,
                    'city' => 'Davie',
                    'company' => 'Company',
                    'firstname' => 'Neeraj',
                    'lastname' => 'Gupta',
                    'email' => 'neeraj2.gupta@infogain.com',
                    'telephone' => 9789876567,
                ],
                'shipping_carrier_code' => 'fedexshipping',
                'shipping_method_code' => 'PICKUP',
            ],
        ];
        
        $result = $this->quoteCreation->saveShippingAddress($requestData, $quoteId);
        $this->assertEquals('success', $result);
    }
    
    public function testsaveShippingAddressWithoutData()
    {
        $quoteId = 1;
        $requestData = [];
        
        $result = $this->quoteCreation->saveShippingAddress($requestData, $quoteId);
        $this->assertEquals(false, $result);
    }
}
