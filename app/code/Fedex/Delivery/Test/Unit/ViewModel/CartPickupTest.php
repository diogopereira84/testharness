<?php

namespace Fedex\Delivery\Test\Unit\ViewModel;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\Delivery\ViewModel\CartPickup;
use Magento\Framework\UrlInterface;

class CartPickupTest extends TestCase
{
    protected $storeManager;
    /**
     * @var (\Magento\Framework\UrlInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $url;
    protected $cartPickup;
    protected function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()->setMethods(['getStore','getBaseUrl'])
            ->getMockForAbstractClass();

        $this->url = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->cartPickup = $objectManagerHelper->getObject(CartPickup::class, ['storeManager' => $this->storeManager]);
    }

    /**
     * Test getMediaUrl.
     *
     * @return string
     */
    public function testGetMediaUrl()
    {
        $path = 'location.jpg';
        $mediaUrl = 'https://staging3.office.fedex.com/pub/media/'.$path;
        $this->storeManager->expects($this->once())->method('getStore')->will($this->returnSelf());
        $this->storeManager->expects($this->once())->method('getBaseUrl')->willReturn($mediaUrl);
        $response = $mediaUrl.$path;
        $this->assertEquals($response, $this->cartPickup->getMediaUrl($path));
    }
}
