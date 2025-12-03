<?php

namespace Fedex\CatalogMvp\Model\Test\Unit;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\CatalogMvp\Api\WebhookInterface;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory as sharedCollectionFactory;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem\CollectionFactory as sharedCollectionItemFactory;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\CatalogMvp\Model\ProductPriceWebhook;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ProductPriceWebhookTest extends TestCase
{
    protected $toggleConfig;
    /**
     * @var (\Fedex\CatalogMvp\Api\WebhookInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $WebhookInterface;
    protected $MessageInterface;
    /**
     * @var (\Magento\Framework\MessageQueue\PublisherInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $PublisherInterface;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterface;
    protected $CollectionFactoryMock;
    protected $CollectionMock;
    protected $CollectionItemFactory;
    protected $CollectionItem;
    protected $serializerJson;
    protected $ProductPriceWebhook;
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->DisableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->WebhookInterface = $this->getMockBuilder(WebhookInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['setMessage'])
            ->getMockForAbstractClass();

        $this->MessageInterface = $this->getMockBuilder(MessageInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['setMessage', 'getMessage'])
            ->getMockForAbstractClass();

        $this->PublisherInterface = $this->getMockBuilder(PublisherInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['setMessage'])
            ->getMockForAbstractClass();

        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();

        $this->CollectionFactoryMock = $this->getMockBuilder(sharedCollectionFactory::class)
            ->DisableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->CollectionMock = $this->getMockBuilder(Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection::class)
            ->DisableOriginalConstructor()
            ->setMethods(['load', 'setStatus', 'save', 'addFieldToFilter', 'getData'])
            ->getMock();

        $this->CollectionItemFactory = $this->getMockBuilder(sharedCollectionItemFactory::class)
            ->DisableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->CollectionItem = $this->getMockBuilder(Magento\SharedCatalog\Model\ResourceModel\ProductItem\Collection::class)
            ->DisableOriginalConstructor()
            ->setMethods(['create', 'addFieldToFilter', 'getData'])
            ->getMock();

        $this->serializerJson = $this->getMockBuilder(Json::class)
            ->DisableOriginalConstructor()
            ->setMethods(['unserialize'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->ProductPriceWebhook = $objectManagerHelper->getObject(
            ProductPriceWebhook::class,
            [
                'serializerJson'                    => $this->serializerJson,
                'message'                           =>  $this->MessageInterface,
                'publisher'                         => $this->PublisherInterface,
                'logger'                            => $this->loggerInterface,
                'toggleConfig'                      => $this->toggleConfig,
                'sharedCatalogCollectionFactory'    => $this->CollectionFactoryMock,
                'sharedCatalogProductItem'          => $this->CollectionItemFactory

            ]
        );
    }

    public function testExecute()
    {
        $arary = ['shared_catalog_id' => 35];

        $messageData = [0 => [
            'customer_group_id' => 35,
            'sku' => 'adfajsdfkjasdf'
        ]];

        $araryOut = [
            0 => [
                'customer_group_id' => 35,
                'sku' => 'adfajsdfkjasdf'
            ]
        ];

        $this->testgetCustomerGroupIdByShareCatalogId(35);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->CollectionMock->expects($this->any())
            ->method('getData')
            ->willReturn($araryOut);

        $this->CollectionItemFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->CollectionItem);

        $this->CollectionItem->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->CollectionItem->expects($this->any())
            ->method('getData')
            ->willReturn($messageData);

        $result = $this->ProductPriceWebhook->addProductToRM($arary);
        $this->assertNotNull($result);

    }

    public function testExecuteWithToggleOff()
    {
        $arary = ['shared_catalog_id' => 35];

        $messageData = [0 => [
            'customer_group_id' => 35,
            'sku' => 'adfajsdfkjasdf'
        ]];

        $araryOut = [
            0 => [
                'customer_group_id' => 35,
                'sku' => 'adfajsdfkjasdf'
            ]
        ];

        $this->testgetCustomerGroupIdByShareCatalogId(35);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $this->CollectionMock->expects($this->any())
            ->method('getData')
            ->willReturn($araryOut);

        $this->CollectionItemFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->CollectionItem);

        $this->CollectionItem->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->CollectionItem->expects($this->any())
            ->method('getData')
            ->willReturn($messageData);

        $result = $this->ProductPriceWebhook->addProductToRM($arary);
        $this->assertTrue($result);

    }

    public function testExecuteWithException()
    {
        $exception = new \Magento\Framework\Exception\NoSuchEntityException();
        $arary = ['shared_catalog_id' => 35];

        $messageData = [0 => [
            'customer_group_id' => 35,
            'sku' => 'adfajsdfkjasdf'
        ]];

        $araryOut = [
            0 => [
                'customer_group_id' => 35,
                'sku' => 'adfajsdfkjasdf'
            ]
        ];

        $this->testgetCustomerGroupIdByShareCatalogId(35);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->CollectionMock->expects($this->any())
            ->method('getData')
            ->willReturn($araryOut);

        $this->CollectionItemFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->CollectionItem);

        $this->CollectionItem->expects($this->any())
            ->method('addFieldToFilter')
            ->willThrowException($exception);

        $this->CollectionItem->expects($this->any())
            ->method('getData')
            ->willReturn($messageData);

        $result = $this->ProductPriceWebhook->addProductToRM($arary);
        $this->assertNotNull($result);

    }

    public function testgetCustomerGroupIdByShareCatalogId()
    {
        $this->CollectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->CollectionMock);

        $this->CollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $result = $this->ProductPriceWebhook->getCustomerGroupIdByShareCatalogId(32);
        $this->assertNotNull($result);
    }

    public function testExecuteWithIf()
    {
        $arary = ['shared_catalog_id' => null, "sku" => "hsdjfhajdhfjahdfafdafdsadf"];

        $messageData = [0 => [
            'customer_group_id' => 35,
            'sku' => 'adfajsdfkjasdf'
        ]];

        $araryOut = [
            0 => [
                'customer_group_id' => 35,
                'sku' => 'adfajsdfkjasdf'
            ]
        ];

        $this->testgetCustomerGroupIdByShareCatalogId(35);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->CollectionMock->expects($this->any())
            ->method('getData')
            ->willReturn($araryOut);

        $this->CollectionItemFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->CollectionItem);

        $this->CollectionItem->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->CollectionItem->expects($this->any())
            ->method('getData')
            ->willReturn($messageData);

        $result = $this->ProductPriceWebhook->addProductToRM($arary);
        $this->assertNotNull($result);
    }

    public function testExecuteWithelse()
    {
        $arary = ['shared_catalog_id' => null, "sku" => "hsdjfhajdhfjahdfafdafdsadf"];

        $messageData = [0 => [
            'customer_group_id' => 35,
            'sku' => 'adfajsdfkjasdf'
        ]];

        $araryOut = [
            0 => [
                'customer_group_id' => 35,
                'sku' => 'adfajsdfkjasdf'
            ]
        ];
        $araryOut = json_encode($araryOut);
        $araryDecoded = [
            'customer_group_id' => 35,
            'sku' => 'hsdjfhajdhfjahdfafdafdsadf'
        ];

        $this->testgetCustomerGroupIdByShareCatalogId(35);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->MessageInterface->expects($this->any())
            ->method('getMessage')
            ->willReturn($araryOut);

        $this->serializerJson->expects($this->any())
            ->method('unserialize')
            ->willReturn($araryDecoded);

        $this->CollectionItemFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->CollectionItem);

        $this->CollectionItem->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->CollectionItem->expects($this->any())
            ->method('getData')
            ->willReturn($messageData);

        $result = $this->ProductPriceWebhook->addProductToRM($arary);
        $this->assertNotNull($result);
    }
}
