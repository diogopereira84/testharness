<?php

/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver;

use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\Cart\Api\Data\CartIntegrationItemInterface;
use Fedex\Cart\Model\Quote\IntegrationItem\Repository as integrationItemRepository;
use Fedex\Cart\Model\Quote\Product\Add;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand as RequestCommand;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Message\MessageInterface;
use Magento\GraphQl\Model\Query\ContextExtensionInterface;
use Magento\Quote\Model\Cart\AddProductsToCart as AddProductsToCartModel;
use Magento\Quote\Model\Cart\Data\AddProductsToCartOutput;
use Magento\Quote\Model\Cart\Data\AddProductsToCartOutputFactory;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Fedex\CartGraphQl\Model\Address\Builder;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\CartGraphQl\Model\Address\CollectRates\ShippingRate;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Fedex\GraphQl\Exception\GraphQlInStoreException;
use \Magento\Quote\Model\Cart\Data\Error;

/**
 * @inheritdoc
 */
class AddProductsToCartTest extends TestCase
{
    /**
     * @var (\Fedex\GraphQl\Model\NewRelicHeaders & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $newRelicHeaders;

    /**
     * @var (\Magento\Quote\Model\Cart\AddProductsToCart & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $addProductsToCartMock;

    /**
     * @var (\Magento\Framework\GraphQl\Config\Element\Field & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fieldMock;

    /**
     * @var (\Magento\Framework\GraphQl\Schema\Type\ResolveInfo & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resolverInfoMock;

    /**
     * @var \Fedex\CartGraphQl\Model\RequestCommandFactory|\PHPUnit\Framework\MockObject\MockObject
     *
     * Mock object for the RequestCommandFactory used in unit tests.
     */
    protected $requestCommandFactoryMock;

    /**
     * @var (\Fedex\GraphQl\Model\Validation\ValidationBatchComposite & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $validationCompositeMock;

    /**
     * @var (\Fedex\CartGraphQl\Helper\LoggerHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $loggerHelperMock;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface|\PHPUnit\Framework\MockObject\MockObject
     * Mock object for cart integration used in unit tests.
     */
    protected $cartIntegrationMock;

    /**
     * @var \Fedex\CartGraphQl\Model\Resolver\AddProductsToCart
     */
    protected $addProductsToCart;

    // @codingStandardsIgnoreStart
    public const ITEM_DATA = '{"fxoMenuId":"1614105200640-4","fxoProductInstance":{"id":"1641146269419","name":"Flyers","productConfig":{"product":{"productionContentAssociations":[],"userProductName":"Flyers","id":"1463680545590","version":1,"name":"Flyer","qty":50,"priceable":true,"instanceId":1641146269419,"proofRequired":false,"isOutSourced":false,"features":[{"id":"1448981549109","name":"Paper Size","choice":{"id":"1448986650332","name":"8.5x11","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"11"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"8.5"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"11"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"8.5"}]}},{"id":"1448981549581","name":"Print Color","choice":{"id":"1448988600611","name":"Full Color","properties":[{"id":"1453242778807","name":"PRINT_COLOR","value":"COLOR"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1470166759236","name":"SIDE_NAME","value":"Single Sided"},{"id":"1461774376168","name":"SIDE","value":"SINGLE"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448988664295","name":"Laser(32 lb.)","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"E32"},{"id":"1453234015081","name":"PAPER_COLOR","value":"#FFFFFF"},{"id":"1470166630346","name":"MEDIA_NAME","value":"32lb"},{"id":"1471275182312","name":"MEDIA_CATEGORY","value":"RESUME"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"12902125413169047140007771472401978681858","contentReference":"12901703829109282057207386197891193197015","contentType":"IMAGE","fileName":"nature1.jpeg","contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":false},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"8.5"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"11"},{"id":"1464709502522","name":"PRODUCT_QTY_SET","value":"50"},{"id":"1459784717507","name":"SKU","value":"40005"},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order.DO NOT use the Production Instructions listed above. Flyer Package specifications: Yield 50, Single Sided Color 8.5x11 32lb (E32), Full Page, Add Retail: SKU 40005"},{"id":"1494365340946","name":"PREVIEW_TYPE","value":"DYNAMIC"},{"id":"1470151737965","name":"TEMPLATE_AVAILABLE","value":"YES"},{"id":"1459784776049","name":"PRICE","value":null},{"id":"1490292304798","name":"MIGRATED_PRODUCT","value":"true"},{"id":"1558382273340","name":"PNI_TEMPLATE","value":"NO"},{"id":"1602530744589","name":"CONTROL_ID","value":"4"}]},"productPresetId":"1602518818916","fileCreated":"2022-01-02T17:58:49.452Z"},"productRateTotal":{"unitPrice":null,"currency":"USD","quantity":50,"price":"$34.99","priceAfterDiscount":"$34.99","unitOfMeasure":"EACH","totalDiscount":"$0.00","productLineDetails":[{"detailCode":"40005","description":"Full Pg Clr Flyr 50","detailCategory":"PRINTING","unitQuantity":1,"detailPrice":"$34.99","detailDiscountPrice":"$0.00","detailUnitPrice":"$34.9900","detailDiscountedUnitPrice":"$0.00"}]},"isUpdateButtonVisible":false,"link":{"href":"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHYAAABbCAYAgg=="},"quantityChoices":["50","100","250","500","1000"],"isEditable":true,"isEdited":false,"fileManagementState":{"availableFileItems":[{"file":{},"fileItem":{"fileId":"12902125413169047140007771472401978681858","fileName":"nature1.jpeg","fileExtension":"jpeg","fileSize":543986,"createdTimestamp":"2022-01-02T17:58:55.914Z"},"uploadStatus":"Success","errorMsg":"","uploadProgressPercentage":100,"uploadProgressBytesLoaded":544177,"selected":false,"httpRsp":{"successful":true,"output":{"document":{"documentId":"12902125413169047140007771472401978681858","documentName":"nature1.jpeg","documentSize":543984,"printReady":false}}}}],"projects":[{"fileItems":[{"uploadStatus":"Success","errorMsg":"","selected":false,"originalFileItem":{"fileId":"12902125413169047140007771472401978681858","fileName":"nature1.jpeg","fileExtension":"jpeg","fileSize":543986,"createdTimestamp":"2022-01-02T17:58:55.914Z"},"convertStatus":"Success","convertedFileItem":{"fileId":"12901703829109282057207386197891193197015","fileName":"nature1.jpeg","fileExtension":"pdf","fileSize":546132,"createdTimestamp":"2022-01-02T17:58:58.708Z","numPages":1},"orientation":"LANDSCAPE","conversionResult":{"parentDocumentId":"12902125413169047140007771472401978681858","originalDocumentName":"nature1.jpeg","printReadyFlag":true,"previewURI":"https://dunc6.dmz.fedex.com/document/fedexoffice/v1/documents/12901703829109282057207386197891193197015/preview","documentSize":546132,"documentType":"IMAGE","lowResImage":true,"documentId":"12901703829109282057207386197891193197015","metrics":{"pageCount":1,"pageGroups":[{"startPageNum":1,"endPageNum":1,"pageWidthInches":11,"pageHeightInches":8.5}]}},"contentAssociation":{"parentContentReference":"12902125413169047140007771472401978681858","contentReference":"12901703829109282057207386197891193197015","contentType":"IMAGE","fileSizeBytes":"546132","fileName":"nature1.jpeg","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}],"contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":""}}],"projectName":"Flyers","productId":"1463680545590","productPresetId":"1602518818916","productVersion":null,"controlId":"4","maxFiles":2,"productType":"Flyers","availableSizes":"8.5\"x11\"","convertStatus":"Success","showInList":true,"firstInList":false,"accordionOpen":true,"needsToBeConverted":false,"selected":false,"mayContainUserSelections":false,"supportedProductSizes":{"featureId":"1448981549109","featureName":"Size","choices":[{"choiceId":"1448986650332","choiceName":"8.5\"x11\"","properties":[{"name":"MEDIA_HEIGHT","value":"11"},{"name":"MEDIA_WIDTH","value":"8.5"},{"name":"DISPLAY_HEIGHT","value":"11"},{"name":"DISPLAY_WIDTH","value":"8.5"}]}]},"productConfig":{"product":{"productionContentAssociations":[],"userProductName":"Flyers","id":"1463680545590","version":1,"name":"Flyer","qty":50,"priceable":true,"instanceId":1641146269419,"proofRequired":false,"isOutSourced":false,"features":[{"id":"1448981549109","name":"Paper Size","choice":{"id":"1448986650332","name":"8.5x11","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"11"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"8.5"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"11"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"8.5"}]}},{"id":"1448981549581","name":"Print Color","choice":{"id":"1448988600611","name":"Full Color","properties":[{"id":"1453242778807","name":"PRINT_COLOR","value":"COLOR"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1470166759236","name":"SIDE_NAME","value":"Single Sided"},{"id":"1461774376168","name":"SIDE","value":"SINGLE"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448988664295","name":"Laser(32 lb.)","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"E32"},{"id":"1453234015081","name":"PAPER_COLOR","value":"#FFFFFF"},{"id":"1470166630346","name":"MEDIA_NAME","value":"32lb"},{"id":"1471275182312","name":"MEDIA_CATEGORY","value":"RESUME"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"12902125413169047140007771472401978681858","contentReference":"12901703829109282057207386197891193197015","contentType":"IMAGE","fileName":"nature1.jpeg","contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":false},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"8.5"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"11"},{"id":"1464709502522","name":"PRODUCT_QTY_SET","value":"50"},{"id":"1459784717507","name":"SKU","value":"40005"},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order.DO NOT use the Production Instructions listed above. Flyer Package specifications: Yield 50, Single Sided Color 8.5x11 32lb (E32), Full Page, Add Retail: SKU 40005"},{"id":"1494365340946","name":"PREVIEW_TYPE","value":"DYNAMIC"},{"id":"1470151737965","name":"TEMPLATE_AVAILABLE","value":"YES"},{"id":"1459784776049","name":"PRICE","value":null},{"id":"1490292304798","name":"MIGRATED_PRODUCT","value":"true"},{"id":"1558382273340","name":"PNI_TEMPLATE","value":"NO"},{"id":"1602530744589","name":"CONTROL_ID","value":"4"}]},"productPresetId":"1602518818916","fileCreated":"2022-01-02T17:58:49.452Z"}}],"catalogManageFilesToggle":true}},"productType":"PRINT_PRODUCT","instanceId":null}';
    public const EDIT_ITEM_DATA = '{"fxoMenuId":"1614105200640-4","fxoProductInstance":{"id":"1641146269419","name":"Flyers","productConfig":{"product":{"productionContentAssociations":[],"userProductName":"Flyers","id":"1463680545590","version":1,"name":"Flyer","qty":50,"priceable":true,"instanceId":1641146269419,"proofRequired":false,"isOutSourced":false,"features":[{"id":"1448981549109","name":"Paper Size","choice":{"id":"1448986650332","name":"8.5x11","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"11"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"8.5"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"11"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"8.5"}]}},{"id":"1448981549581","name":"Print Color","choice":{"id":"1448988600611","name":"Full Color","properties":[{"id":"1453242778807","name":"PRINT_COLOR","value":"COLOR"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1470166759236","name":"SIDE_NAME","value":"Single Sided"},{"id":"1461774376168","name":"SIDE","value":"SINGLE"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448988664295","name":"Laser(32 lb.)","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"E32"},{"id":"1453234015081","name":"PAPER_COLOR","value":"#FFFFFF"},{"id":"1470166630346","name":"MEDIA_NAME","value":"32lb"},{"id":"1471275182312","name":"MEDIA_CATEGORY","value":"RESUME"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"12902125413169047140007771472401978681858","contentReference":"12901703829109282057207386197891193197015","contentType":"IMAGE","fileName":"nature1.jpeg","contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":false},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"8.5"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"11"},{"id":"1464709502522","name":"PRODUCT_QTY_SET","value":"50"},{"id":"1459784717507","name":"SKU","value":"40005"},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order.DO NOT use the Production Instructions listed above. Flyer Package specifications: Yield 50, Single Sided Color 8.5x11 32lb (E32), Full Page, Add Retail: SKU 40005"},{"id":"1494365340946","name":"PREVIEW_TYPE","value":"DYNAMIC"},{"id":"1470151737965","name":"TEMPLATE_AVAILABLE","value":"YES"},{"id":"1459784776049","name":"PRICE","value":null},{"id":"1490292304798","name":"MIGRATED_PRODUCT","value":"true"},{"id":"1558382273340","name":"PNI_TEMPLATE","value":"NO"},{"id":"1602530744589","name":"CONTROL_ID","value":"4"}]},"productPresetId":"1602518818916","fileCreated":"2022-01-02T17:58:49.452Z"},"productRateTotal":{"unitPrice":null,"currency":"USD","quantity":50,"price":"$34.99","priceAfterDiscount":"$34.99","unitOfMeasure":"EACH","totalDiscount":"$0.00","productLineDetails":[{"detailCode":"40005","description":"Full Pg Clr Flyr 50","detailCategory":"PRINTING","unitQuantity":1,"detailPrice":"$34.99","detailDiscountPrice":"$0.00","detailUnitPrice":"$34.9900","detailDiscountedUnitPrice":"$0.00"}]},"isUpdateButtonVisible":false,"link":{"href":"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHYAAABbCAYAgg=="},"quantityChoices":["50","100","250","500","1000"],"isEditable":true,"isEdited":false,"fileManagementState":{"availableFileItems":[{"file":{},"fileItem":{"fileId":"12902125413169047140007771472401978681858","fileName":"nature1.jpeg","fileExtension":"jpeg","fileSize":543986,"createdTimestamp":"2022-01-02T17:58:55.914Z"},"uploadStatus":"Success","errorMsg":"","uploadProgressPercentage":100,"uploadProgressBytesLoaded":544177,"selected":false,"httpRsp":{"successful":true,"output":{"document":{"documentId":"12902125413169047140007771472401978681858","documentName":"nature1.jpeg","documentSize":543984,"printReady":false}}}}],"projects":[{"fileItems":[{"uploadStatus":"Success","errorMsg":"","selected":false,"originalFileItem":{"fileId":"12902125413169047140007771472401978681858","fileName":"nature1.jpeg","fileExtension":"jpeg","fileSize":543986,"createdTimestamp":"2022-01-02T17:58:55.914Z"},"convertStatus":"Success","convertedFileItem":{"fileId":"12901703829109282057207386197891193197015","fileName":"nature1.jpeg","fileExtension":"pdf","fileSize":546132,"createdTimestamp":"2022-01-02T17:58:58.708Z","numPages":1},"orientation":"LANDSCAPE","conversionResult":{"parentDocumentId":"12902125413169047140007771472401978681858","originalDocumentName":"nature1.jpeg","printReadyFlag":true,"previewURI":"https://dunc6.dmz.fedex.com/document/fedexoffice/v1/documents/12901703829109282057207386197891193197015/preview","documentSize":546132,"documentType":"IMAGE","lowResImage":true,"documentId":"12901703829109282057207386197891193197015","metrics":{"pageCount":1,"pageGroups":[{"startPageNum":1,"endPageNum":1,"pageWidthInches":11,"pageHeightInches":8.5}]}},"contentAssociation":{"parentContentReference":"12902125413169047140007771472401978681858","contentReference":"12901703829109282057207386197891193197015","contentType":"IMAGE","fileSizeBytes":"546132","fileName":"nature1.jpeg","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}],"contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":""}}],"projectName":"Flyers","productId":"1463680545590","productPresetId":"1602518818916","productVersion":null,"controlId":"4","maxFiles":2,"productType":"Flyers","availableSizes":"8.5\"x11\"","convertStatus":"Success","showInList":true,"firstInList":false,"accordionOpen":true,"needsToBeConverted":false,"selected":false,"mayContainUserSelections":false,"supportedProductSizes":{"featureId":"1448981549109","featureName":"Size","choices":[{"choiceId":"1448986650332","choiceName":"8.5\"x11\"","properties":[{"name":"MEDIA_HEIGHT","value":"11"},{"name":"MEDIA_WIDTH","value":"8.5"},{"name":"DISPLAY_HEIGHT","value":"11"},{"name":"DISPLAY_WIDTH","value":"8.5"}]}]},"productConfig":{"product":{"productionContentAssociations":[],"userProductName":"Flyers","id":"1463680545590","version":1,"name":"Flyer","qty":50,"priceable":true,"instanceId":1641146269419,"proofRequired":false,"isOutSourced":false,"features":[{"id":"1448981549109","name":"Paper Size","choice":{"id":"1448986650332","name":"8.5x11","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"11"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"8.5"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"11"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"8.5"}]}},{"id":"1448981549581","name":"Print Color","choice":{"id":"1448988600611","name":"Full Color","properties":[{"id":"1453242778807","name":"PRINT_COLOR","value":"COLOR"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1470166759236","name":"SIDE_NAME","value":"Single Sided"},{"id":"1461774376168","name":"SIDE","value":"SINGLE"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448988664295","name":"Laser(32 lb.)","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"E32"},{"id":"1453234015081","name":"PAPER_COLOR","value":"#FFFFFF"},{"id":"1470166630346","name":"MEDIA_NAME","value":"32lb"},{"id":"1471275182312","name":"MEDIA_CATEGORY","value":"RESUME"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"12902125413169047140007771472401978681858","contentReference":"12901703829109282057207386197891193197015","contentType":"IMAGE","fileName":"nature1.jpeg","contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":false},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"8.5"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"11"},{"id":"1464709502522","name":"PRODUCT_QTY_SET","value":"50"},{"id":"1459784717507","name":"SKU","value":"40005"},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order.DO NOT use the Production Instructions listed above. Flyer Package specifications: Yield 50, Single Sided Color 8.5x11 32lb (E32), Full Page, Add Retail: SKU 40005"},{"id":"1494365340946","name":"PREVIEW_TYPE","value":"DYNAMIC"},{"id":"1470151737965","name":"TEMPLATE_AVAILABLE","value":"YES"},{"id":"1459784776049","name":"PRICE","value":null},{"id":"1490292304798","name":"MIGRATED_PRODUCT","value":"true"},{"id":"1558382273340","name":"PNI_TEMPLATE","value":"NO"},{"id":"1602530744589","name":"CONTROL_ID","value":"4"}]},"productPresetId":"1602518818916","fileCreated":"2022-01-02T17:58:49.452Z"}}],"catalogManageFilesToggle":true}},"productType":"PRINT_PRODUCT","instanceId":57817263004095960}';
    // @codingStandardsIgnoreEnd

    /**
     * @var MockObject
     * Mock object for simulating the getCartForUser dependency in unit tests.
     */
    private MockObject $getCartForUserMock;

    /**
     * Mock object for the QuoteProductAdd class used in unit tests.
     *
     * @var MockObject $quoteProductAddMock
     */
    private MockObject $quoteProductAddMock;

    /**
     * Mock object for the GraphQL resolver context.
     *
     * @var MockObject $contextMock
     */
    private MockObject $contextMock;

    /**
     * Mock object for extension attributes used in unit tests.
     *
     * @var MockObject $extensionAttributesMock
     */
    private MockObject $extensionAttributesMock;

    /**
     * @var MockObject Mock object representing a Quote instance for unit testing.
     */
    private MockObject $quoteMock;

    /**
     * @var MockObject Mock instance of the integration item repository used for testing.
     */
    private MockObject $integrationItemRepository;

    /**
     * @var MockObject Mock instance of a builder used for testing purposes.
     */
    private MockObject $builderMock;

    /**
     * @var MockObject Instance of PHPUnit's MockObject used for mocking AddProductsToCartOutputFactory in unit tests.
     */
    private MockObject $addProductsToCartOutputFactory;

    /**
     * @var MockObject Instance of MockObject used to mock the output of the AddProductsToCart operation in unit tests.
     */
    private MockObject $addProductsToCartOutput;

    /**
     * Mock object for the CartRepository interface used for unit testing.
     *
     * @var MockObject $cartRepositoryMock
     */
    private MockObject $cartRepositoryMock;

    /**
     * Mock object for the Quote Address used in unit tests.
     *
     * @var MockObject $quoteAddressMock
     */
    private MockObject $quoteAddressMock;

    /**
     * @var MockObject Factory mock for creating batch response objects in unit tests.
     */
    private MockObject $batchResponseMockFactory;

    /**
     * @var MockObject Mock object representing a batch response, used for unit testing.
     */
    private MockObject $batchResponseMock;

    /**
     * @var MockObject
     */
    private MockObject $rateMock;

    /**
     * @var MockObject
     */
    private MockObject $cartIntegrationRepositoryMock;

    /**
     * Sets up the environment before each test.
     */
    protected function setUp(): void
    {
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);
        $this->contextMock = $this->getMockBuilder(ContextInterface::class)
            ->addMethods(
                [
                    'getExtensionAttributes',
                    'getUserId'
                ]
            )->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->getCartForUserMock = $this->getMockBuilder(GetCartForUser::class)
            ->onlyMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->addProductsToCartMock = $this->createMock(AddProductsToCartModel::class);
        $this->fieldMock = $this->createMock(Field::class);
        $this->resolverInfoMock = $this->createMock(ResolveInfo::class);
        $this->quoteProductAddMock = $this->getMockBuilder(Add::class)
            ->onlyMethods(
                [
                    'setCart',
                    'addItemToCart',
                    'findCartItemByInstanceIdExternal'
                ]
            )->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestCommandFactoryMock = $this->getMockBuilder(RequestCommandFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requestCommandMock = $this->createMock(RequestCommand::class);
        $this->requestCommandFactoryMock->method('create')->willReturn($requestCommandMock);
        $this->validationCompositeMock = $this->createMock(ValidationBatchComposite::class);
        $this->extensionAttributesMock = $this->getMockBuilder(ContextExtensionInterface::class)
            ->onlyMethods(['getStore'])
            ->addMethods(['getId'])
            ->getMockForAbstractClass();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods([
                    'getData',
                    'getErrors',
                    'getShippingAddress',
                    'getId',
                    'getByQuoteId'
                ])->disableOriginalConstructor()
            ->getMock();
        $this->integrationItemRepository = $this->getMockBuilder(integrationItemRepository::class)
            ->onlyMethods(['saveByQuoteItemId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->addProductsToCartOutputFactory = $this->createMock(AddProductsToCartOutputFactory::class);
        $this->addProductsToCartOutput = $this->createMock(AddProductsToCartOutput::class);
        $this->addProductsToCartOutputFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->addProductsToCartOutput);
        $this->addProductsToCartOutput->expects($this->any())
            ->method('getCart')
            ->willReturn($this->quoteMock);
        $this->builderMock = $this->createMock(Builder::class);
        $this->cartRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->quoteAddressMock = $this->createMock(Address::class);
        $this->batchResponseMockFactory = $this->createMock(BatchResponseFactory::class);
        $this->batchResponseMock = $this->createMock(BatchResponse::class);
        $this->batchResponseMockFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->batchResponseMock);
        $this->loggerHelperMock = $this->createMock(LoggerHelper::class);
        $this->rateMock = $this->createMock(ShippingRate::class);
        $this->cartIntegrationRepositoryMock = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->cartIntegrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->addProductsToCart = new AddProductsToCart(
            $this->getCartForUserMock,
            $this->quoteProductAddMock,
            $this->integrationItemRepository,
            $this->cartRepositoryMock,
            $this->addProductsToCartOutputFactory,
            $this->rateMock,
            $this->cartIntegrationRepositoryMock,
            $this->requestCommandFactoryMock,
            $this->batchResponseMockFactory,
            $this->loggerHelperMock,
            $this->validationCompositeMock,
            $this->newRelicHeaders
        );
    }

    /**
     * Tests the resolve method for adding products to the cart.
     *
     * @return void
     */
    public function testResolveAddProductsToCart()
    {
        $resolveRequestMock = $this->createMock(ResolveRequest::class);
        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $this->requestCommandFactoryMock->method('create')->willReturn($requestCommandMock);
        $args = [
            'cartId' => 'PPHJrpNFTk8XWMHmqWyAPSKRA9eKMaaJ',
            'cartItems' => [
                [
                    'data' => self::ITEM_DATA
                ],
                [
                    'data' => self::EDIT_ITEM_DATA
                ]
            ]
        ];

        $resolveRequestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn($args);
        $this->extensionAttributesMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->extensionAttributesMock->expects($this->any())->method('getId')->willReturn(1);

        $this->contextMock->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);

        $this->getCartForUserMock->expects($this->any())->method('execute')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getByQuoteId')->with($this->quoteMock)
            ->willReturnSelf();

        $this->quoteProductAddMock->expects($this->any())->method('setCart')->willReturnSelf();
        $this->quoteProductAddMock->expects($this->any())
            ->method('findCartItemByInstanceIdExternal')->willReturn(15);
        $this->quoteProductAddMock->expects($this->any())
            ->method('addItemToCart')->willReturn(null);

        $cartIntegrationItem = $this->getMockBuilder(CartIntegrationItemInterface::class)->getMock();
        $this->integrationItemRepository->expects($this->any())
            ->method('saveByQuoteItemId')->willReturn($cartIntegrationItem);

        $this->quoteMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($this->quoteAddressMock);
        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->quoteAddressMock->expects($this->any())
            ->method('getCountryId')
            ->willReturn("US");

        $this->cartIntegrationRepositoryMock->expects($this->any())
            ->method('getByQuoteId')
            ->with(1)
            ->willReturn($this->cartIntegrationMock);

        $this->rateMock->expects($this->any())
            ->method('collect')
            ->with($this->quoteAddressMock, $this->cartIntegrationMock);

        $this->cartRepositoryMock->expects($this->any())
            ->method('save')
            ->with($this->quoteMock);

        $this->cartRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->quoteMock);

        $this->addProductsToCart->proceed(
            $this->contextMock,
            $this->fieldMock,
            [$resolveRequestMock],
            []
        );
        $this->assertInstanceOf(BatchResponse::class, $this->batchResponseMock);
    }

    /**
     * Tests the resolve method for adding products to the cart when errors occur.
     *
     * @return void
     */
    public function testResolveAddProductsToCartWithErrors()
    {
        $resolveRequestMock = $this->createMock(ResolveRequest::class);
        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $this->requestCommandFactoryMock->method('create')->willReturn($requestCommandMock);
        $args = [
            'cartId' => 'PPHJrpNFTk8XWMHmqWyAPSKRA9eKMaaJ',
            'cartItems' => [
                [
                    'data' => self::ITEM_DATA
                ],
                [
                    'data' => self::EDIT_ITEM_DATA
                ]
            ]
        ];

        $resolveRequestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn($args);
        $this->extensionAttributesMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->extensionAttributesMock->expects($this->any())->method('getId')->willReturn(1);

        $this->contextMock->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);

        $messageMockOne = $this->createMock(MessageInterface::class);
        $messageMockTwo = $this->createMock(MessageInterface::class);
        $messageMockOne->expects($this->any())
            ->method('getText')
            ->willReturn('some error message');
        $messageMockTwo->expects($this->any())
            ->method('getText')
            ->willReturn('There are no source items');

        $this->quoteMock->expects($this->any())->method('getData')
            ->with('has_error')->willReturn(true);
        $this->quoteMock->expects($this->any())->method('getErrors')
            ->willReturn([$messageMockOne, $messageMockTwo]);

        $this->cartRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->quoteMock);

        $this->getCartForUserMock->expects($this->any())->method('execute')->willReturn($this->quoteMock);

        $this->quoteProductAddMock->expects($this->any())->method('setCart')->willReturn(null);
        $this->quoteProductAddMock->expects($this->any())->method('addItemToCart')->willReturn(null);

        $this->addProductsToCart->proceed(
            $this->contextMock,
            $this->fieldMock,
            [$resolveRequestMock],
            []
        );
        $this->assertInstanceOf(BatchResponse::class, $this->batchResponseMock);
    }

    /**
     * Tests the successful execution of the proceed method.
     *
     * @return void
     */
    public function testProceedSuccess()
    {
        /** @var \Magento\Framework\GraphQl\Query\Resolver\ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->getMockBuilder(ContextInterface::class)
            ->addMethods(['getExtensionAttributes', 'getUserId'])
            ->getMockForAbstractClass();
        $field = $this->fieldMock;
        $headerArray = ['header' => 'value'];
        $requestMock = $this->createMock(ResolveRequest::class);
        $requests = [$requestMock];
        $cartId = 'cart123';
        $cartItems = [
            [
                'data' =>
                json_encode(['fxoProductInstance' =>
                ['productConfig' => ['product' => ['instanceId' => 42]]]])
            ]
        ];
        $args = ['cartId' => $cartId, 'cartItems' => $cartItems];
        $requestMock->expects($this->once())
            ->method('getArgs')
            ->willReturn($args);
        $this->extensionAttributesMock
            ->method('getStore')->willReturnSelf();
        $this->extensionAttributesMock->method('getId')
            ->willReturn(1);
        $context->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);
        $context->method('getUserId')
            ->willReturn(5);
        $this->getCartForUserMock
            ->method('execute')
            ->willReturn($this->quoteMock);
        $this->quoteProductAddMock
            ->expects($this->once())
            ->method('setCart')
            ->with($this->quoteMock);
        $this->quoteMock->method('getShippingAddress')
            ->willReturn($this->quoteAddressMock);
        $this->quoteAddressMock->method('getCountryId')
            ->willReturn('US');
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')
            ->willReturn($this->cartIntegrationMock);
        $this->rateMock->expects($this->once())
            ->method('collect')
            ->with($this->quoteAddressMock, $this->cartIntegrationMock);
        $this->cartRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->quoteMock);
        $this->quoteProductAddMock
            ->expects($this->once())
            ->method('addItemToCart');
        $this->quoteProductAddMock
            ->expects($this->once())
            ->method('findCartItemByInstanceIdExternal')
            ->willReturn(15);
        $this->integrationItemRepository
            ->expects($this->once())
            ->method('saveByQuoteItemId')
            ->with(15, $cartItems[0]['data']);
        $this->quoteMock
            ->method('getData')
            ->with('has_error')
            ->willReturn(false);
        $this->addProductsToCartOutput
            ->method('getCart')
            ->willReturn($this->quoteMock);
        $this->addProductsToCartOutput
            ->method('getErrors')
            ->willReturn([]);
        $this->addProductsToCartOutputFactory
            ->method('create')
            ->willReturn($this->addProductsToCartOutput);
        $this->batchResponseMock->expects($this->once())
            ->method('addResponse')
            ->with(
                $requestMock,
                $this->callback(function ($returnData) {
                    return isset($returnData['cart']['model'])
                        && is_object($returnData['cart']['model'])
                        && isset($returnData['user_errors'])
                        && is_array($returnData['user_errors'])
                        && count($returnData['user_errors']) === 0;
                })
            );
        $result = $this->addProductsToCart->proceed($context, $field, $requests, $headerArray);
        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    /**
     * Tests that an exception is thrown when invalid cart item data is provided.
     *
     * @return void
     */
    public function testProceedInvalidCartItemDataThrowsException()
    {
        /** @var \Magento\Framework\GraphQl\Query\Resolver\ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->getMockBuilder(ContextInterface::class)
            ->addMethods(['getExtensionAttributes', 'getUserId'])
            ->getMockForAbstractClass();

        $field = $this->fieldMock;
        $headerArray = [];
        $requestMock = $this->createMock(ResolveRequest::class);
        $requests = [$requestMock];
        $cartId = 'cart123';
        $cartItems = [['data' => 'not-json']];
        $args = ['cartId' => $cartId, 'cartItems' => $cartItems];
        $requestMock->method('getArgs')->willReturn($args);
        $this->extensionAttributesMock
            ->method('getStore')
            ->willReturnSelf();
        $this->extensionAttributesMock->method('getId')->willReturn(1);
        $context->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);
        $context->method('getUserId')
            ->willReturn(5);
        $this->getCartForUserMock->method('execute')
            ->willReturn($this->quoteMock);
        $this->quoteProductAddMock->method('setCart')
            ->willReturn(null);
        $this->quoteMock->method('getShippingAddress')
            ->willReturn($this->quoteAddressMock);
        $this->quoteAddressMock->method('getCountryId')
            ->willReturn('US');
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')
            ->willReturn($this->cartIntegrationMock);
        $this->cartRepositoryMock->method('save')->willReturn(null);
        $this->expectException(GraphQlInStoreException::class);
        $this->addProductsToCart->proceed($context, $field, $requests, $headerArray);
    }

    /**
     * Tests that the proceedCartWithErrors method correctly adds user errors to the cart.
     *
     * @return void
     */
    public function testProceedCartWithErrorsAddsUserErrors()
    {
        /** @var \Magento\Framework\GraphQl\Query\Resolver\ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->getMockBuilder(ContextInterface::class)
            ->addMethods(['getExtensionAttributes', 'getUserId'])
            ->getMockForAbstractClass();
        $field = $this->fieldMock;
        $headerArray = [];
        $requestMock = $this->createMock(ResolveRequest::class);
        $requests = [$requestMock];
        $cartId = 'cart123';
        $cartItems = [
            [
                'data' => json_encode(
                    ['fxoProductInstance' => ['productConfig' => ['product' => ['instanceId' => 42]]]]
                )
            ]
        ];
        $args = ['cartId' => $cartId, 'cartItems' => $cartItems];
        $requestMock->method('getArgs')->willReturn($args);
        $this->extensionAttributesMock->method('getStore')
            ->willReturnSelf();
        $this->extensionAttributesMock->method('getId')
            ->willReturn(1);
        $context->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);
        $context->method('getUserId')
            ->willReturn(5);
        $this->getCartForUserMock->method('execute')
            ->willReturn($this->quoteMock);
        $this->quoteProductAddMock->method('setCart')
            ->willReturn(null);
        $this->quoteMock->method('getShippingAddress')
            ->willReturn($this->quoteAddressMock);
        $this->quoteAddressMock->method('getCountryId')
            ->willReturn('US');
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')
            ->willReturn($this->cartIntegrationMock);
        $this->cartRepositoryMock->method('save')
            ->willReturn(null);
        $this->quoteProductAddMock->method('addItemToCart')
            ->willReturn(null);
        $this->quoteProductAddMock->method('findCartItemByInstanceIdExternal')
            ->willReturn(15);
        $cartIntegrationItemMock = $this->createMock(CartIntegrationItemInterface::class);
        $this->integrationItemRepository->method('saveByQuoteItemId')
            ->willReturn($cartIntegrationItemMock);
        $this->quoteMock->method('getData')->with('has_error')->willReturn(true);

        $messageMock = $this->createMock(MessageInterface::class);
        $messageMock->method('getText')->willReturn('Could not find a product with SKU');

        $this->quoteMock->method('getErrors')->willReturn([$messageMock]);
        $this->addProductsToCartOutput->method('getCart')
            ->willReturn($this->quoteMock);
        $this->addProductsToCartOutput->method('getErrors')
            ->willReturn([]);
        $this->addProductsToCartOutputFactory->method('create')
            ->willReturn($this->addProductsToCartOutput);
        $this->batchResponseMock->expects($this->once())
            ->method('addResponse')
            ->with(
                $requestMock,
                $this->callback(function ($returnData) {
                    return isset($returnData['user_errors'])
                        && is_array($returnData['user_errors']);
                })
            );
        $result = $this->addProductsToCart->proceed($context, $field, $requests, $headerArray);
        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    /**
     * Tests that the proceed method correctly handles exceptions and logs errors.
     *
     * @return void
     */
    public function testProceedHandlesExceptionAndLogsError()
    {
        /** @var \Magento\Framework\GraphQl\Query\Resolver\ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->getMockBuilder(ContextInterface::class)
            ->addMethods(['getExtensionAttributes', 'getUserId'])
            ->getMockForAbstractClass();
        $field = $this->fieldMock;
        $headerArray = [];
        $requestMock = $this->createMock(ResolveRequest::class);
        $requests = [$requestMock];
        $cartId = 'cart123';
        $cartItems = [
            [
                'data' => json_encode(
                    ['fxoProductInstance' => ['productConfig' => ['product' => ['instanceId' => 42]]]]
                )
            ]
        ];
        $args = ['cartId' => $cartId, 'cartItems' => $cartItems];
        $requestMock->method('getArgs')->willReturn($args);
        $this->extensionAttributesMock->method('getStore')
            ->willReturnSelf();
        $this->extensionAttributesMock->method('getId')
            ->willReturn(1);
        $context->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);
        $context->method('getUserId')
            ->willReturn(5);
        $this->getCartForUserMock->method('execute')
            ->willThrowException(new \Exception('Some error'));
        $this->loggerHelperMock->expects($this->atLeastOnce())
            ->method('error');
        $this->expectException(GraphQlInStoreException::class);
        $this->addProductsToCart->proceed($context, $field, $requests, $headerArray);
    }

    /**
     * Tests the mapping of user errors array when proceeding with adding products to the cart.
     *
     * @return void
     */
    public function testProceedUserErrorsArrayMapping()
    {
        $context = $this->getMockBuilder(ContextInterface::class)
            ->addMethods(['getExtensionAttributes', 'getUserId'])
            ->getMockForAbstractClass();
        $field = $this->fieldMock;
        $headerArray = [];
        $requestMock = $this->createMock(ResolveRequest::class);
        $requests = [$requestMock];
        $cartId = 'cart123';
        $cartItems = [
            [
                'data' => json_encode(
                    ['fxoProductInstance' => ['productConfig' => ['product' => ['instanceId' => 42]]]]
                )
            ]
        ];
        $args = ['cartId' => $cartId, 'cartItems' => $cartItems];
        $requestMock->method('getArgs')->willReturn($args);
        $this->extensionAttributesMock->method('getStore')
            ->willReturnSelf();
        $this->extensionAttributesMock->method('getId')
            ->willReturn(1);
        $context->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);
        $context->method('getUserId')->willReturn(5);
        $this->getCartForUserMock->method('execute')
            ->willReturn($this->quoteMock);
        $this->quoteProductAddMock->method('setCart')
            ->willReturn(null);
        $this->quoteMock->method('getShippingAddress')
            ->willReturn($this->quoteAddressMock);
        $this->quoteAddressMock->method('getCountryId')
            ->willReturn('US');
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')
            ->willReturn($this->cartIntegrationMock);
        $this->rateMock->method('collect');
        $this->cartRepositoryMock->method('save')
            ->willReturn(null);
        $this->quoteProductAddMock->method('addItemToCart')
            ->willReturn(null);
        $this->quoteProductAddMock->method('findCartItemByInstanceIdExternal')
            ->willReturn(15);
        $cartIntegrationItemMock = $this->createMock(CartIntegrationItemInterface::class);
        $this->integrationItemRepository->method('saveByQuoteItemId')->willReturn($cartIntegrationItemMock);
        $this->quoteMock->method('getData')->with('has_error')->willReturn(false);

        $errorMock = $this->createMock(Error::class);
        $errorMock->method('getCode')->willReturn('PRODUCT_NOT_FOUND');
        $errorMock->method('getMessage')->willReturn('Could not find a product with SKU');
        $errorMock->method('getCartItemPosition')->willReturn(2);

        $this->addProductsToCartOutput->method('getCart')->willReturn($this->quoteMock);
        $this->addProductsToCartOutput->method('getErrors')->willReturn([$errorMock]);
        $this->addProductsToCartOutputFactory->method('create')->willReturn($this->addProductsToCartOutput);
        $this->batchResponseMock->expects($this->once())->method('addResponse')
            ->with(
                $requestMock,
                $this->callback(function ($returnData) {
                    return isset($returnData['user_errors'][0])
                        && $returnData['user_errors'][0]['code'] === 'PRODUCT_NOT_FOUND'
                        && $returnData['user_errors'][0]['message'] === 'Could not find a product with SKU'
                        && $returnData['user_errors'][0]['path'] === [2];
                })
            );
        $result = $this->addProductsToCart->proceed($context, $field, $requests, $headerArray);
        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    /**
     * Tests the setShippingData method when both shipping address and country are provided.
     *
     * @return void
     */
    public function testSetShippingDataWithShippingAddressAndCountry()
    {
        $cart = $this->quoteMock;
        $shippingAddress = $this->quoteAddressMock;
        $cart->method('getShippingAddress')
            ->willReturn($shippingAddress);
        $shippingAddress->method('getCountryId')
            ->willReturn('US');
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')
            ->willReturn($this->cartIntegrationMock);
        $this->rateMock->expects($this->once())->method('collect')
            ->with($shippingAddress, $this->cartIntegrationMock);
        $this->cartRepositoryMock->expects($this->once())
            ->method('save')
            ->with($cart);
        $reflection = new \ReflectionClass($this->addProductsToCart);
        $method = $reflection->getMethod('setShippingData');
        $method->setAccessible(true);
        $method->invoke($this->addProductsToCart, $cart);
    }

    /**
     * Tests the behavior of setting shipping data when no shipping address is provided.
     *
     * @return void
     */
    public function testSetShippingDataWithNoShippingAddress()
    {
        $cart = $this->quoteMock;
        $cart->method('getShippingAddress')->willReturn(null);
        $this->rateMock->expects($this->never())->method('collect');
        $this->cartRepositoryMock->expects($this->never())->method('save');
        $reflection = new \ReflectionClass($this->addProductsToCart);
        $method = $reflection->getMethod('setShippingData');
        $method->setAccessible(true);
        $method->invoke($this->addProductsToCart, $cart);
    }

    /**
     * Tests the behavior of setting shipping data when no country ID is provided.
     *
     * @return void
     */
    public function testSetShippingDataWithNoCountryId()
    {
        $cart = $this->quoteMock;
        $shippingAddress = $this->quoteAddressMock;
        $cart->method('getShippingAddress')->willReturn($shippingAddress);
        $shippingAddress->method('getCountryId')->willReturn(null);
        $this->rateMock->expects($this->never())->method('collect');
        $this->cartRepositoryMock->expects($this->never())->method('save');
        $reflection = new \ReflectionClass($this->addProductsToCart);
        $method = $reflection->getMethod('setShippingData');
        $method->setAccessible(true);
        $method->invoke($this->addProductsToCart, $cart);
    }

    /**
     * Tests the addition of errors to the cart, both with and without specifying the cart item position.
     *
     * @return void
     */
    public function testAddErrorWithAndWithoutCartItemPosition()
    {
        $reflection = new \ReflectionClass($this->addProductsToCart);
        $method = $reflection->getMethod('addError');
        $method->setAccessible(true);
        $method->invoke($this->addProductsToCart, 'Could not find a product with SKU', 2);
        $method->invoke($this->addProductsToCart, 'Some unknown error');
        $errorsProperty = $reflection->getProperty('errors');
        $errorsProperty->setAccessible(true);
        $errors = $errorsProperty->getValue($this->addProductsToCart);
        $this->assertCount(2, $errors);
        $this->assertEquals('PRODUCT_NOT_FOUND', $errors[0]->getCode());
        $this->assertEquals('UNDEFINED', $errors[1]->getCode());
        $this->assertEquals(2, $errors[0]->getCartItemPosition());
        $this->assertEquals(0, $errors[1]->getCartItemPosition());
    }

    /**
     * Tests that the getErrorCode method covers all possible error cases.
     *
     * @return void
     */
    public function testGetErrorCodeCoversAllCases()
    {
        $reflection = new \ReflectionClass($this->addProductsToCart);
        $method = $reflection->getMethod('getErrorCode');
        $method->setAccessible(true);
        $this->assertEquals('PRODUCT_NOT_FOUND', $method
            ->invoke($this->addProductsToCart, 'Could not find a product with SKU'));
        $this->assertEquals('NOT_SALABLE', $method
            ->invoke($this->addProductsToCart, 'There are no source items'));
        $this->assertEquals('INSUFFICIENT_STOCK', $method
            ->invoke($this->addProductsToCart, 'The fewest you may purchase is'));
        $this->assertEquals('UNDEFINED', $method
            ->invoke($this->addProductsToCart, 'Some totally unknown error'));
    }
}
