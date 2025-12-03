<?php
namespace Fedex\EnhancedProfile\Test\Unit\Block\Account\CompanySettings;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\EnhancedProfile\Block\Account\CompanySettings\DeliveryOptions;
use Magento\Framework\View\Element\Template\Context;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile as EnhancedProfileViewModel;
use Fedex\Company\Model\Source\ShippingOptions;
use PHPUnit\Framework\TestCase;

class DeliveryOptionsTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var DeliveryOptions
     */
    protected $deliveryOptions;

    /**
     * @var EnhancedProfileViewModel
     */
    protected $enhancedProfileViewModelMock;

    /**
     * @var ShippingOptions
     */
    protected $shippingOptionsMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->enhancedProfileViewModelMock = $this->getMockBuilder(EnhancedProfileViewModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingOptionsMock = $this->getMockBuilder(ShippingOptions::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryOptions = $this->objectManager->getObject(
            DeliveryOptions::class,
            [
                'context' => $context,
                'enhancedProfileViewModel' => $this->enhancedProfileViewModelMock,
                'shippingOptions' => $this->shippingOptionsMock,
            ]
        );
    }

    /**
     * Test getInfoIconUrl
     */
    public function testGetInfoIconUrl()
    {
        $this->enhancedProfileViewModelMock->expects($this->once())
            ->method('getMediaUrl')
            ->willReturn('/pub/media/');

        $expectedUrl = '/pub/media/wysiwyg/information.png';
        $this->assertEquals($expectedUrl, $this->deliveryOptions->getInfoIconUrl());
    }

    /**
     * Test getDeliveryOptions
     */
    public function testGetDeliveryOptions()
    {
        $optionsArray = [
            ['value' => 1, 'label' => 'Option 1'],
            ['value' => 2, 'label' => 'Option 2'],
        ];

        $this->shippingOptionsMock->expects($this->once())
            ->method('toOptionArray')
            ->willReturn($optionsArray);

        $this->assertEquals($optionsArray, $this->deliveryOptions->getDeliveryOptions());
    }
}
