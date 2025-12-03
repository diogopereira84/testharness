<?php
namespace Fedex\Shipment\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Fedex\Shipment\Plugin\JsonFix;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class JsonFixTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Serialize\Serializer\Json & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $jsonMock;
    protected $subject;
    protected $product;
    protected $toggleConfig;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $plugin;
    protected function setUp(): void
    {
    	$this->jsonMock = $this->getMockBuilder(Json::class)
            ->setMethods(['unserialize','serialize'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = $this->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->product = $this->getMockBuilder(Product::class)
            ->setMethods(['getCustomOption'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->plugin = $this->objectManager->getObject(
            JsonFix::class,
            [
                'serializer' => $this->jsonMock,
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    /**
     * testAroundGetOrderOptions
     */
    public function testAroundGetOrderOptions()
    {
        $proceed = function () {
            $this->subject->getOrderOptions($this->product);
        };
        
        $itemsData = new DataObject([
                'code' => 'info_buyRequest',
                'value' => '{"external_prod": [{"userProductName":"Flyers"}]}'
            ]);
        
        $this->product->expects($this->any())->method('getCustomOption')->with('info_buyRequest')->willReturn($itemsData);

        $this->assertEquals(null, $this->plugin->aroundGetOrderOptions($this->subject, $proceed, $this->product));
    }

    /**
     * testAroundGetOrderOptionswithNull
     */
    public function testAroundGetOrderOptionswithNull()
    {
        $proceed = function () {
            $this->subject->getOrderOptions($this->product);
        };
        
        $itemsData = new DataObject(
            [
                'code' => 'info_buyRequest',
                'value' => null
            ]
        );
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->product->expects($this->any())->method('getCustomOption')->willReturn($itemsData);
        
        $expectedResult = ['super_product_config'=>['product_code'=>'info_buyRequest','product_type'=>null,'product_id'=>null],'info_buyRequest'=>null];

        $this->assertEquals($expectedResult, $this->plugin->aroundGetOrderOptions($this->subject, $proceed, $this->product));
    }

}