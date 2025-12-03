<?php
namespace Fedex\Delivery\Test\Unit\Block;

use Fedex\Delivery\Block\CartPickup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class CartPickupTest extends TestCase
{
 
    protected $store;
    /**
     * @var (\Magento\Framework\UrlInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $url;
    protected $cart;
    protected function setUp(): void
    {
        $this->store = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()->setMethods(['getStore','getBaseUrl'])
            ->getMockForAbstractClass();

        $this->url = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();


        $objectManagerHelper = new ObjectManager($this);
        $this->cart = $objectManagerHelper->getObject(
            CartPickup::class,
            [
              'storeManager' => $this->store
            ]
        );
    }
    /**
     * Test getMediaUrl.
     *
     * @return string
     */
    public function testGetMediaUrl()
    {
 
        $path = 'imageFile.jpg';
        $mediaUrl = 'https://shop-staging2.fedex.com/pub/media/'.$path;
        $this->store->expects($this->once())->method('getStore')->will($this->returnSelf());
        $this->store->expects($this->any())->method('getBaseUrl')->willReturn($mediaUrl);
        $response = $mediaUrl.$path;
        $this->assertEquals($response, $this->cart->getMediaUrl($path));
    }
}
