<?php

namespace Fedex\OrderGraphQl\Test\Unit\Model\Resolver;

use Exception;
use Fedex\Cart\Api\CartIntegrationItemRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\OrderGraphQl\Model\Resolver\DataProvider\OrderStatusMapping;
use Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest;
use Fedex\OrderGraphQl\Model\Resolver\DataProvider\OrderSearchRequest as OrderSearchRequestDataProvider;
use Fedex\OrderGraphQl\Model\Resolver\DataProvider\ShipmentStatusLabel;
use Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest\JobSummariesOrderData;
use Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest\OrderSearchRequestHelper;
use Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest\RecipientSummariesData;
use Fedex\OrderGraphQl\Test\Unit\MockDataProvider;
use Magento\Framework\DataObject;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Directory\Model\Currency;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Fedex\Cart\Api\Data\CartIntegrationItemInterface;
use Magento\Quote\Model\ResourceModel\Quote\Item\Collection;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Fedex\OrderGraphQl\Api\GetPoliticalDisclosureInformationInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Magento\Sales\Model\Order;

/**
 * @covers \Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest
 */
class OrderSearchRequestTest extends TestCase
{
    protected $orderStatusMappingMock;
    /**
     * @var (\Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest\JobSummariesOrderData & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $jobSummariesOrderDataMock;
    /**
     * @var (\Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest\OrderSearchRequestHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderSearchRequestHelperMock;
    /**
     * @var (\Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest\RecipientSummariesData & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $recipientSummariesDataMock;
    protected $toggleConfigMock;
    /** @var MockObject|OrderSearchRequest */
    private MockObject|OrderSearchRequest $orderSearchRequestProviderMock;

    /** @var CartIntegrationItemRepositoryInterface  */
    private CartIntegrationItemRepositoryInterface $cartIntegrationRepository;

    /** @var LoggerInterface  */
    private LoggerInterface $logger;

    /** @var CartIntegrationItemInterface  */
    private CartIntegrationItemInterface $cartIntegrationItem;

    /** @var Item  */
    private Item $item;

    /** @var ShopRepositoryInterface  */
    private ShopRepositoryInterface $shopRepository;

    /** @var OrderSearchRequest  */
    private OrderSearchRequest $testObject;

    /**
     * @var BatchResponseFactory|MockObject
     */
    private BatchResponseFactory|MockObject $batchResponseMockFactory;

    /**
     * @var BatchResponse|MockObject|(BatchResponse&MockObject)
     */
    private BatchResponse|MockObject $batchResponseMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject|GetPoliticalDisclosureInformationInterface */
    private $orderDisclosureRepositoryMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigInterface */
    private $configInterfaceMock;

    /**
     * Main set up method
     */
    public function setUp(): void
    {
        $this->orderSearchRequestProviderMock = $this->createMock(OrderSearchRequestDataProvider::class);
        $shipmentStatusLabelProviderMock = $this->createMock(ShipmentStatusLabel::class);
        $shipmentStatusLabelProviderMock->expects($this->any())
            ->method('getShipmentLabel')
            ->willReturn(MockDataProvider::SHIPMENT_STATUS[0][1]);
        $this->orderStatusMappingMock = $this->getMockBuilder(OrderStatusMapping::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jobSummariesOrderDataMock = $this->getMockBuilder(JobSummariesOrderData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderSearchRequestHelperMock = $this->getMockBuilder(OrderSearchRequestHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->recipientSummariesDataMock = $this->getMockBuilder(RecipientSummariesData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartIntegrationRepository = $this->createMock(
            CartIntegrationItemRepositoryInterface::class
        );
        $collection = $this->createMock(Collection::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cartIntegrationItem = $this->createMock(CartIntegrationItemInterface::class);
        $this->shopRepository = $this->createMock(ShopRepositoryInterface::class);

        $this->item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMiraklOfferId', 'getAdditionalData'])
            ->getMockForAbstractClass();
        $collection->method('getFirstItem')->willReturn($this->item);
        $this->item->method('getMiraklOfferId')->willReturn(false);
        $this->batchResponseMockFactory = $this->createMock(
            BatchResponseFactory::class
        );
        $this->batchResponseMock = $this->createMock(
            BatchResponse::class
        );

        $this->batchResponseMockFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->batchResponseMock);

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderDisclosureRepositoryMock = $this->getMockBuilder(GetPoliticalDisclosureInformationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configInterfaceMock = $this->getMockBuilder(configInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->testObject = new OrderSearchRequest(
            $this->orderSearchRequestProviderMock,
            $shipmentStatusLabelProviderMock,
            $this->orderStatusMappingMock,
            $this->cartIntegrationRepository,
            $this->logger,
            $this->shopRepository,
            $this->jobSummariesOrderDataMock,
            $this->recipientSummariesDataMock,
            $this->orderSearchRequestHelperMock,
            $this->batchResponseMockFactory,
            $this->toggleConfigMock,
            $this->orderDisclosureRepositoryMock,
            $this->configInterfaceMock
        );
    }

    /**
     * @throws Exception
     */
    public function testResolveMethod(): void
    {
        $result = [];
        $field = $this->createMock(Field::class);
        $context = $this->createMock(ContextInterface::class);
        $resolveRequestMock = $this->createMock(ResolveRequest::class);
        $requests = [$resolveRequestMock];

        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'getShippingAddress',
                'getShipmentsCollection',
                'getTracksCollection',
                'getOrderCurrency'
            ])
            ->getMockForAbstractClass();

        $shippingAddressMock = $this->createMock(Address::class);
        $shipmentMock = $this->createMock(Shipment::class);
        $itemMock = $this->createMock(OrderItem::class);
        $shipmentItemMock = $this->getMockBuilder(ShipmentItemInterface::class)
            ->addMethods(['getOrderItem'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $shipmentItemMock->expects($this->any())->method('getOrderItem')->willReturn($itemMock);

        $trackMock = $this->getMockBuilder(Track::class)
            ->disableOriginalConstructor()
            ->addMethods(['getTrackNumber'])
            ->getMock();
        $trackMock->expects($this->any())->method('getTrackNumber')->willReturn('123456');

        $trackCollectionMock = $this->createMock(Track\Collection::class);
        $trackCollectionMock->expects($this->any())->method('getItems')->willReturn([$trackMock]);

        $shipmentMock->expects($this->any())->method('getItems')->willReturn([$shipmentItemMock]);
        $shipmentMock->expects($this->any())->method('getShippingAddress')->willReturn($shippingAddressMock);
        $shipmentMock->expects($this->any())->method('getTracksCollection')->willReturn($trackCollectionMock);
        $shipmentMock->expects($this->any())->method('getShipmentStatus')->willReturn(7);

        $currencyMock = $this->createMock(Currency::class);

        $buyRequestMock = new DataObject(
            [
                "external_prod" => [
                    [
                        "product" => [
                            "name" => "Premium Greetings & Holiday Cards"
                        ],
                        "externalSkus" => [
                            [
                                "skuRef" => "39966"
                            ]
                        ],
                        "userProductName" => "logo",
                        "name" => "Postcards",
                    ]
                ],
                "fileManagementState" => [
                    "projects" => [
                        [
                            "projectName" => "logo",
                        ]
                    ]
                ]
            ]
        );
        $resolveRequestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn($buyRequestMock->getData());

        $orderItemMock = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderItemMock->expects($this->any())->method('getBuyRequest')->willReturn($buyRequestMock);

        $orderPaymentMock = $this->getMockBuilder(OrderPayment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderMock->expects($this->any())->method('getShippingAddress')->willReturn($shippingAddressMock);
        $orderMock->expects($this->any())->method('getShipmentsCollection')->willReturn([$shipmentMock]);
        $orderMock->expects($this->any())->method('getStatus')->willReturn('processing');
        $this->orderStatusMappingMock->expects($this->any())
            ->method('getMappingKey')->willReturn('CONFIRMED');
        $orderMock->expects($this->any())->method('getOrderCurrency')->willReturn($currencyMock);
        $orderMock->expects($this->any())->method('getItems')->willReturn([$orderItemMock]);
        $orderMock->expects($this->any())->method('getCreatedAt')->willReturn('2023-09-16 23:30:59');
        $orderMock->expects($this->any())->method('getPayment')->willReturn($orderPaymentMock);
        $orderMock->expects($this->any())->method('getIncrementId')->willReturn('2010337832377852');

        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteItemId')
            ->willReturn($this->cartIntegrationItem);

        $this->cartIntegrationItem->expects($this->any())
            ->method('getItemData')
            ->willReturn($this->getProductJson());

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->orderSearchRequestProviderMock->expects($this->any())
            ->method('orderSearchRequest')
            ->willReturn([
                'orders' => [$orderMock],
                'partial' => false
            ]);

        $this->recipientSummariesDataMock->expects($this->any())
            ->method('getData')
            ->willReturn([['recipient' => 'data']]);

        $this->jobSummariesOrderDataMock->expects($this->any())
            ->method('getData')
            ->willReturn([['job' => 'data']]);

        $this->orderSearchRequestHelperMock->expects($this->any())
            ->method('getFormattedCstDate')
            ->willReturn('2023-09-16T23:30:59Z');

        $this->testObject->resolve($context, $field, $requests);

        $this->assertIsArray($result);
    }


    private function getProductJson() {
        return '{"fxoMenuId":"1614105200640-4","fxoProductInstance":{"id":"1707392400193","name":"blue","productConfig":{"product":{"productionContentAssociations":[],"userProductName":"blue","id":"1447174746733","version":1,"name":"Flyer","qty":1,"priceable":true,"instanceId":1707392400193,"proofRequired":false,"isOutSourced":false,"minDPI":"150.0","features":[{"id":"1448981549109","name":"Paper+Size","choice":{"id":"1448986650332","name":"8.5x11","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"11"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"8.5"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"11"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"8.5"}]}},{"id":"1448981549741","name":"Paper+Type","choice":{"id":"1448988664295","name":"Laser(32+lb.)","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"E32"},{"id":"1453234015081","name":"PAPER_COLOR","value":"#FFFFFF"},{"id":"1471275182312","name":"MEDIA_CATEGORY","value":"RESUME"}]}},{"id":"1448981549581","name":"Print+Color","choice":{"id":"1448988600611","name":"Full+Color","properties":[{"id":"1453242778807","name":"PRINT_COLOR","value":"COLOR"}]}},{"id":"1448981554101","name":"Prints+Per+Page","choice":{"id":"1448990257151","name":"One","properties":[{"id":"1455387404922","name":"PRINTS_PER_PAGE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984877869","name":"Cutting","choice":{"id":"1448999392195","name":"None","properties":[]}},{"id":"1448984679442","name":"Lamination","choice":{"id":"1448999458409","name":"None","properties":[]}},{"id":"1448981555573","name":"Hole+Punching","choice":{"id":"1448999902070","name":"None","properties":[]}},{"id":"1448984877645","name":"Folding","choice":{"id":"1448999720595","name":"None","properties":[]}},{"id":"1680724699067","name":"Hole+Punching+Production","choice":{"id":"1680724828408","name":"Hand+Finishing","properties":[]}},{"id":"1680725097331","name":"Folding+Production","choice":{"id":"1680725112004","name":"Hand+Finishing","properties":[]}},{"id":"1679607670330","name":"Offset+Stacking","choice":{"id":"1679607706803","name":"Off","properties":[]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"16760372108156987607100102250251778464669","contentReference":"16760372110035124648901703918211389625019","contentType":"IMAGE","fileName":"blue.png","contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":false},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454606828294","name":"SPC_TYPE_ID","value":"4"},{"id":"1454606860996","name":"SPC_MODEL_ID","value":"3"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"3"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"8.5"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"11"},{"id":"1494365340946","name":"PREVIEW_TYPE","value":"DYNAMIC"},{"id":"1470151626854","name":"SYSTEM_SI","value":null},{"id":"1470151737965","name":"TEMPLATE_AVAILABLE","value":"YES"},{"id":"1490292304798","name":"MIGRATED_PRODUCT","value":"true"},{"id":"1558382273340","name":"PNI_TEMPLATE","value":"NO"}]},"productPresetId":"1602518818916","fileCreated":"2024-02-08T11:40:35.638Z","currentProjectContainLowResFile":true},"productRateTotal":{"unitPrice":null,"currency":"USD","quantity":1,"price":"$0.68","priceAfterDiscount":"$0.68","unitOfMeasure":"EACH","totalDiscount":"$0.00","productLineDetails":[{"detailCode":"0224","priceRequired":false,"priceOverridable":false,"description":"CLR+1S+on+32#+Wht","unitQuantity":1,"quantity":1,"detailPrice":"$0.68","detailDiscountPrice":"$0.00","detailUnitPrice":"$0.6800","detailDiscountedUnitPrice":"$0.0000","detailCategory":"PRINTING"}]},"isUpdateButtonVisible":false,"link":{"href":"https:\/\/dunctest.fedex.com\/document\/fedexoffice\/v1\/documents\/16760372110035124648901703918211389625019\/preview?pageNumber=1","useImageDirectLink":false},"quantityChoices":[],"expressCheckout":false,"isEditable":true,"isEdited":false,"fileManagementState":{"availableFileItems":[{"file":[],"fileItem":{"fileId":"16760372108156987607100102250251778464669","fileName":"blue.png","fileExtension":"png","fileSize":1156,"createdTimestamp":"2024-02-08T11:40:38.162Z"},"uploadStatus":"Success","errorMsg":"","uploadProgressPercentage":100,"uploadProgressBytesLoaded":1342,"selected":false,"httpRsp":{"successful":true,"output":{"document":{"documentId":"16760372108156987607100102250251778464669","documentName":"blue.png","documentSize":1154,"printReady":false}}}}],"projects":[{"fileItems":[{"uploadStatus":"Success","errorMsg":"","uploadProgressPercentage":100,"uploadProgressBytesLoaded":1342,"selected":false,"originalFileItem":{"fileId":"16760372108156987607100102250251778464669","fileName":"blue.png","fileExtension":"png","fileSize":1156,"createdTimestamp":"2024-02-08T11:40:38.162Z"},"convertStatus":"Success","convertedFileItem":{"fileId":"16760372110035124648901703918211389625019","fileName":"blue.png","fileExtension":"pdf","fileSize":6827,"createdTimestamp":"2024-02-08T11:40:40.071Z","numPages":1},"orientation":"LANDSCAPE","conversionResult":{"parentDocumentId":"16760372108156987607100102250251778464669","originalDocumentName":"blue.png","printReadyFlag":true,"previewURI":"https:\/\/dunc6.dmz.fedex.com\/document\/fedexoffice\/v1\/documents\/16760372110035124648901703918211389625019\/preview","documentSize":6827,"documentType":"IMAGE","lowResImage":true,"documentId":"16760372110035124648901703918211389625019","metrics":{"pageCount":1,"pageGroups":[{"startPageNum":1,"endPageNum":1,"pageWidthInches":11,"pageHeightInches":8.5}]}},"contentAssociation":{"parentContentReference":"16760372108156987607100102250251778464669","contentReference":"16760372110035124648901703918211389625019","contentType":"IMAGE","fileSizeBytes":"6827","fileName":"blue.png","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}],"contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":""},"lowResImage":true}],"projectName":"blue","productId":"1463680545590","productPresetId":"1602518818916","productVersion":null,"controlId":"4","maxFiles":2,"productType":"Flyers","availableSizes":"8.5\"+x+11\",+8.5\"+x+14\",+11\"+x+17\"","convertStatus":"Success","showInList":true,"firstInList":false,"accordionOpen":true,"needsToBeConverted":false,"selected":false,"mayContainUserSelections":false,"hasUserChangedProjectNameManually":false,"projectId":1498037396,"supportedProductSizes":{"featureId":"1448981549109","featureName":"Size","choices":[{"choiceId":"1448986650332","choiceName":"8.5\"+x+11\"","properties":[{"name":"MEDIA_HEIGHT","value":"11"},{"name":"MEDIA_WIDTH","value":"8.5"},{"name":"DISPLAY_HEIGHT","value":"11"},{"name":"DISPLAY_WIDTH","value":"8.5"}]},{"choiceId":"1448986650652","choiceName":"8.5\"+x+14\"","properties":[{"name":"MEDIA_HEIGHT","value":"14"},{"name":"MEDIA_WIDTH","value":"8.5"},{"name":"DISPLAY_HEIGHT","value":"14"},{"name":"DISPLAY_WIDTH","value":"8.5"}]},{"choiceId":"1448986651164","choiceName":"11\"+x+17\"","properties":[{"name":"MEDIA_HEIGHT","value":"17"},{"name":"MEDIA_WIDTH","value":"11"},{"name":"DISPLAY_HEIGHT","value":"17"},{"name":"DISPLAY_WIDTH","value":"11"}]}]},"productConfig":{"product":{"productionContentAssociations":[],"userProductName":"blue","id":"1447174746733","version":1,"name":"Flyer","qty":1,"priceable":true,"instanceId":1707392400193,"proofRequired":false,"isOutSourced":false,"features":[{"id":"1448981549109","name":"Paper+Size","choice":{"id":"1448986650332","name":"8.5x11","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"11"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"8.5"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"11"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"8.5"}]}},{"id":"1448981549741","name":"Paper+Type","choice":{"id":"1448988664295","name":"Laser(32+lb.)","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"E32"},{"id":"1453234015081","name":"PAPER_COLOR","value":"#FFFFFF"},{"id":"1471275182312","name":"MEDIA_CATEGORY","value":"RESUME"}]}},{"id":"1448981549581","name":"Print+Color","choice":{"id":"1448988600611","name":"Full+Color","properties":[{"id":"1453242778807","name":"PRINT_COLOR","value":"COLOR"}]}},{"id":"1448981554101","name":"Prints+Per+Page","choice":{"id":"1448990257151","name":"One","properties":[{"id":"1455387404922","name":"PRINTS_PER_PAGE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984877869","name":"Cutting","choice":{"id":"1448999392195","name":"None","properties":[]}},{"id":"1448984679442","name":"Lamination","choice":{"id":"1448999458409","name":"None","properties":[]}},{"id":"1448981555573","name":"Hole+Punching","choice":{"id":"1448999902070","name":"None","properties":[]}},{"id":"1448984877645","name":"Folding","choice":{"id":"1448999720595","name":"None","properties":[]}},{"id":"1680724699067","name":"Hole+Punching+Production","choice":{"id":"1680724828408","name":"Hand+Finishing","properties":[]}},{"id":"1680725097331","name":"Folding+Production","choice":{"id":"1680725112004","name":"Hand+Finishing","properties":[]}},{"id":"1679607670330","name":"Offset+Stacking","choice":{"id":"1679607706803","name":"Off","properties":[]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"16760372108156987607100102250251778464669","contentReference":"16760372110035124648901703918211389625019","contentType":"IMAGE","fileName":"blue.png","contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":false},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454606828294","name":"SPC_TYPE_ID","value":"4"},{"id":"1454606860996","name":"SPC_MODEL_ID","value":"3"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"3"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"8.5"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"11"},{"id":"1494365340946","name":"PREVIEW_TYPE","value":"DYNAMIC"},{"id":"1470151626854","name":"SYSTEM_SI","value":null},{"id":"1470151737965","name":"TEMPLATE_AVAILABLE","value":"YES"},{"id":"1490292304798","name":"MIGRATED_PRODUCT","value":"true"},{"id":"1558382273340","name":"PNI_TEMPLATE","value":"NO"}],"minDPI":"150.0"},"productPresetId":"1602518818916","fileCreated":"2024-02-08T11:40:35.638Z","currentProjectContainLowResFile":true}}],"catalogManageFilesToggle":true,"displayErrorIds":false,"fileWorkspaceFeatures":{"cloudDriveFlags":{"enableCloudDrives":true,"enableBox":true,"enableDropbox":true,"enableGoogleDrive":true,"enableMicrosoftOneDrive":true,"data_id":false},"useNewDocumentApi":false,"hideExpirationText":true,"fileSelectionSubTitleText":"All+files+uploaded+will+be+combined+for+this+product.+You+may+create+one+product+at+a+time.","autoBuildSingleProject":true,"forceStandardFileSizeConversion":true,"singleProjectMode":true}}},"productType":"PRINT_PRODUCT","instanceId":null}';
    }
}
