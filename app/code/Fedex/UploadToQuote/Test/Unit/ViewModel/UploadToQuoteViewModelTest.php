<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\UploadToQuote\Test\Unit\ViewModel;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper as UploadToQuoteAdminConfigHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Asset\Repository as AssestRepository;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Framework\Serialize\SerializerInterface;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Magento\Company\Api\CompanyManagementInterface;

class UploadToQuoteViewModelTest extends TestCase
{
    /**
     * @var (\Magento\Framework\View\Asset\Repository & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $assetRepoMock;

    /**
     * @var Quote $quote
     */
    protected $quote;

    /**
     * @var QuoteItem $quoteItem
     */
    protected $quoteItem;

     /**
      * @var UploadToQuoteViewModel $uloadToQuoteViewModelObj
      */
    protected $uloadToQuoteViewModelObj;
    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var UrlInterface $url
     */
    protected $url;

    /**
     * @var UploadToQuoteAdminConfigHelper $uploadToQuoteAdminConfigHelper
     */
    protected $uploadToQuoteAdminConfigHelper;

    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * @var AssestRepository $assestRepository
     */
    protected $assestRepository;

    /**
     * @var QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var SerializerInterface $serializerMock
     */
    protected $serializerMock;

    /**
     * @var FuseBidViewModel $fuseBidViewModel
     */
    protected $fuseBidViewModel;

    /**
     * @var Curl $curl
     */
    protected $curl;

    /**
     * @var CompanyManagementInterface $companyRepository
     */
    protected $companyRepository;

    public function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getId', 'getBaseUrl'])
            ->getMockForAbstractClass();

        $this->url = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMockForAbstractClass();

        $this->uploadToQuoteAdminConfigHelper = $this->getMockBuilder(UploadToQuoteAdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                        'isUploadToQuoteEnable',
                        'getUploadToQuoteConfigValue',
                        'isUploadToQuoteEproMyQuoteToggle',
                        'updateQuoteStatusByKey',
                        'getQuoteEditButtonMsg',
                        'isProductLineItems',
                        'isUploadToQuoteGloballyEnabled',
                        'checkoutQuotePriceisDashable',
                        'isItemPriceable',
                        'isNonStandardFile',
                        'isSiItemNonEditable',
                        'getDeletedItems',
                        'isQuoteNegotiated',
                        'getSiType',
                        'updateQuoteStatusWithHisotyUpdate',
                        'isSiItemEditBtnDisable',
                        'updateLogHistory',
                        'isMarkAsDeclinedEnabled',
                        'getMyQuoteMaitenanceFixToggle'
                    ]
            )
            ->getMock();

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOndemandCompanyInfo'])
            ->getMock();

        $this->curl = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->setMethods(['setOptions', 'get', 'getBody'])
            ->getMock();

        $this->assetRepoMock = $this->getMockBuilder(AssestRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMock();

        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['save', 'create', 'load'])
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'create', 'getItemById', 'getAllVisibleItems', 'save'])
            ->getMock();

        $this->quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->setMethods(
                [
                    'getOptionByCode',
                    'getItemId',
                    'getOptionId',
                    'setValue',
                    'getValue'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->onlyMethods(['unserialize'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->fuseBidViewModel = $this->getMockBuilder(FuseBidViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isFuseBidToggleEnabled'])
            ->getMock();

        $this->companyRepository = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getByCustomerId', 'getCompanyUrlExtention'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->uloadToQuoteViewModelObj = $objectManagerHelper->getObject(
            UploadToQuoteViewModel::class,
            [
                'storeManager' => $this->storeManager,
                'url' => $this->url,
                'uploadToQuoteAdminConfigHelper' => $this->uploadToQuoteAdminConfigHelper,
                'customerSession' => $this->customerSession,
                'curl' => $this->curl,
                'assestRepository' => $this->assetRepoMock,
                'quoteFactory' => $this->quoteFactory,
                'quote' => $this->quote,
                'serializer' => $this->serializerMock,
                'companyRepository' => $this->companyRepository
            ]
        );
    }

    /**
     * Test getUploadToQuoteToggle
     *
     * @return void
     */
    public function testIsUploadToQuoteEnable()
    {
        $this->customerSession->expects($this->once())->method('getOndemandCompanyInfo')->willReturn(71);
        $this->storeManager->expects($this->once())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getId')->willReturn(2);
        $this->uploadToQuoteAdminConfigHelper->expects($this->once())
        ->method('isUploadToQuoteEnable')->willReturn(true);

        $this->assertEquals(true, $this->uloadToQuoteViewModelObj->isUploadToQuoteEnable());
    }

    /**
     * Test getUploadToQuoteToggle
     *
     * @return void
     */
    public function testGetUploadToQuoteConfigValue()
    {
        $returnData = 'Print instruction message';

        $this->storeManager->expects($this->once())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getId')->willReturn(2);
        $this->uploadToQuoteAdminConfigHelper->expects($this->once())
        ->method('getUploadToQuoteConfigValue')->willReturn($returnData);

        $this->assertEquals($returnData, $this->uloadToQuoteViewModelObj->getUploadToQuoteConfigValue('qoute_message'));
    }

    /**
     * Test getUploadToQuoteSuccessUrl
     *
     * @return void
     */
    public function testGetUploadToQuoteSuccessUrl()
    {
        $this->url->expects($this->once())->method('getUrl')->willReturnSelf();

        $this->assertNotNull($this->uloadToQuoteViewModelObj->getUploadToQuoteSuccessUrl());
    }

    /**
     * Test updateQuoteStatusByKey
     *
     * @return void
     */
    public function testUpdateQuoteStatusByKey()
    {
        $quoteId = 2432123;
        $statusKey = 'created';
        $this->isUploadToQuoteEnable();
        $this->uploadToQuoteAdminConfigHelper
        ->expects($this->once())->method('updateQuoteStatusByKey')
        ->willReturn(null);
        $this->quoteFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('load')->willReturnSelf();

        $this->assertNull($this->uloadToQuoteViewModelObj->updateQuoteStatusByKey($quoteId, $statusKey));
    }

    /**
     * Test updateQuoteStatusByKey with update history
     *
     * @return void
     */
    public function testUpdateQuoteStatusByKeyWithupdateHistory()
    {
        $quoteId = 2432123;
        $statusKey = 'created';
        $this->isUploadToQuoteEnable();
        $this->uploadToQuoteAdminConfigHelper
        ->expects($this->once())->method('updateQuoteStatusWithHisotyUpdate')
        ->willReturn(null);
        $this->quoteFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('load')->willReturnSelf();

        $this->assertNull($this->uloadToQuoteViewModelObj->updateQuoteStatusByKey($quoteId, $statusKey, true));
    }

    /**
     * Test getQuoteEditButtonMsg
     *
     * @return string
     */
    public function testGetQuoteEditButtonMsg()
    {
        $uplaodToQuoteEditMsg = 'test message';
        $this->uploadToQuoteAdminConfigHelper
        ->expects($this->once())->method('getQuoteEditButtonMsg')
        ->willReturn($uplaodToQuoteEditMsg);

        $this->assertEquals($uplaodToQuoteEditMsg, $this->uloadToQuoteViewModelObj->getQuoteEditButtonMsg());
    }

    /**
     * Test isProductLineItems
     *
     * @return string
     */
    public function testIsProductLineItems()
    {
        $productJson = ['abc','abc'];
        $specialInstruction = true;
        $this->uploadToQuoteAdminConfigHelper
        ->expects($this->any())->method('isProductLineItems')
        ->with($productJson, $specialInstruction)
        ->willReturn(true);

        $this->uloadToQuoteViewModelObj->isProductLineItems($productJson, true);
        $result = $this->uloadToQuoteViewModelObj->isProductLineItems($productJson, true);

        $this->assertTrue($result);
    }

    /**
     * Test isSiItemEditable
     *
     * @return void
     */
    public function testIsSiItemNonEditable()
    {
        $productJson = ['abc','abc'];
        $this->isUploadToQuoteEnable();
        $this->uploadToQuoteAdminConfigHelper->expects($this->any())
        ->method('isSiItemNonEditable')->willReturn(true);

        $this->assertEquals(true, $this->uloadToQuoteViewModelObj->isSiItemNonEditable($productJson));
    }

    /**
     * Test isSiItemEditable with false
     *
     * @return void
     */
    public function testIsSiItemNonEditableWithFalse()
    {
        $productJson = ['abc','abc'];
        $this->isUploadToQuoteEnableWithFalse();

        $this->assertEquals(false, $this->uloadToQuoteViewModelObj->isSiItemNonEditable($productJson));
    }

    /**
     * Test isSiItemEditable
     *
     * @return void
     */
    public function testIsSiItemEditBtnDisable()
    {
        $productJson = ['abc','abc'];
        $this->uploadToQuoteAdminConfigHelper->expects($this->any())
        ->method('isSiItemEditBtnDisable')->willReturn(true);

        $this->assertEquals(true, $this->uloadToQuoteViewModelObj->isSiItemEditBtnDisable($productJson));
    }

    /**
     * Test isUploadToQuoteGloballyEnabled
     *
     * @return void
     */
    public function testIsUploadToQuoteGloballyEnabled()
    {
        $this->uploadToQuoteAdminConfigHelper->expects($this->once())
        ->method('isUploadToQuoteGloballyEnabled')->willReturn(true);

        $this->assertTrue($this->uloadToQuoteViewModelObj->isUploadToQuoteGloballyEnabled());
    }

    /**
     * Test checkoutQuotePriceisDashable
     *
     * @return void
     */
    public function testCheckoutQuotePriceisDashable()
    {
        $returnValue = 1;

        $this->isUploadToQuoteEnable();
        $this->uploadToQuoteAdminConfigHelper->expects($this->any())->method('checkoutQuotePriceisDashable')->willReturn($returnValue);
        $this->assertEquals($returnValue, $this->uloadToQuoteViewModelObj->checkoutQuotePriceisDashable());
    }

    /**
     * Test isQuotePriceable
     *
     * @return void
     */
    public function testIsQuotePriceable()
    {
        $quote = 'test';
        $this->isUploadToQuoteEnable();
        $this->uploadToQuoteAdminConfigHelper->expects($this->once())
        ->method('checkoutQuotePriceisDashable')->willReturn(true);

        $this->assertEquals(false, $this->uloadToQuoteViewModelObj->isQuotePriceable($quote));
    }

    /**
     * Test isQuotePriceable with false
     *
     * @return void
     */
    public function testIsQuotePriceableWithFalse()
    {
        $quote = 'test';
        $this->isUploadToQuoteEnable();
        $this->uploadToQuoteAdminConfigHelper->expects($this->once())
        ->method('checkoutQuotePriceisDashable')->willReturn(false);

        $this->assertEquals(true, $this->uloadToQuoteViewModelObj->isQuotePriceable($quote));
    }

    /**
     * Test isItemPriceable
     *
     * @return void
     */
    public function testIsItemPriceable()
    {
        $productJson = 'test';
        $this->isUploadToQuoteEnable();
        $this->uploadToQuoteAdminConfigHelper->expects($this->once())
        ->method('isItemPriceable')->willReturn(true);

        $this->assertEquals(true, $this->uloadToQuoteViewModelObj->isItemPriceable($productJson));
    }

    /**
     * Test isNonStandardFile
     *
     * @return void
     */
    public function testIsNonStandardFile()
    {
        $productJson = 'test';
        $this->isUploadToQuoteEnable();
        $this->uploadToQuoteAdminConfigHelper->expects($this->once())
        ->method('isNonStandardFile')->willReturn(true);

        $this->assertEquals(true, $this->uloadToQuoteViewModelObj->isNonStandardFile($productJson));
    }

    /**
     * Test isNonStandardFile with false
     *
     * @return void
     */
    public function testIsNonStandardFileWithFalse()
    {
        $productJson = 'test';
        $this->isUploadToQuoteEnableWithFalse();

        $this->assertEquals(false, $this->uloadToQuoteViewModelObj->isNonStandardFile($productJson));
    }

    /**
     * Test getPriceDash
     *
     * @return void
     */
    public function testGetPriceDash()
    {
        $this->assertEquals('$--.--', $this->uloadToQuoteViewModelObj->getPriceDash());
    }

    /**
     * Test getNonStandardImageUrl
     *
     * @return void
     */
    public function testGetNonStandardImageUrl()
    {
        $mediaUrl = 'https://staging3.fedex.com/';
        $imageUrl = $mediaUrl.'wysiwyg/nostandard-image.png';
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getBaseUrl')->willReturn($mediaUrl);

        $this->assertNull($this->uloadToQuoteViewModelObj->getNonStandardImageUrl());
    }

    /**
     * Common for isUploadToQuoteEnable with true
     *
     * @return void
     */
    public function isUploadToQuoteEnable()
    {
        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn(71);
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturn(2);
        $this->uploadToQuoteAdminConfigHelper->expects($this->any())
        ->method('isUploadToQuoteEnable')->willReturn(true);
    }

    /**
     * Common for isUploadToQuoteEnable with false
     *
     * @return void
     */
    public function isUploadToQuoteEnableWithFalse()
    {
        $this->customerSession->expects($this->once())->method('getOndemandCompanyInfo')->willReturn(71);
        $this->storeManager->expects($this->once())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getId')->willReturn(2);
        $this->uploadToQuoteAdminConfigHelper->expects($this->once())
        ->method('isUploadToQuoteEnable')->willReturn(false);
    }

    /**
     * Test method for updateItemsSI
     *
     * @return void
     */
    public function testUpdateItemsSI()
    {
        $siItems = [
            'action' => 'changeRequested',
            'items' => [
                ['item_id' => 132789, 'si' => 'SI 132789 for testing']
            ]
        ];
        $productJson = [
            "external_prod" => [
                "id" => 1456773326927,
                "instanceId" => 132789,
                "version" => 2,
                "name" => "Multi Sheet",
                "qty" => 1,
                "priceable" => false,
                "properties" => [
                    [
                        "id" => 1453895478444,
                        "name" => "MIN_DPI",
                        "value" => "150.0"
                    ],
                    [
                        "id" => 1454950109636,
                        "name" => "USER_SPECIAL_INSTRUCTIONS",
                        "value" => "SI 132789 for testing"
                    ]
                ],
                "preview_url" => "16281407547082372410010469293581070429190",
                "isEditable" => true,
                "isEdited" => false,
                "fxoMenuId" => "1534436209752-2"
            ]
        ];

        $this->assertEquals($productJson, $this->uloadToQuoteViewModelObj->updateItemsSI($productJson, $siItems));
    }

    /**
     * Test excludeDeletedItem
     *
     * @return void
     */
    public function testExcludeDeletedItem()
    {
        $arrItems = [
                'id' => 234
        ];
        $objItems[] = new \Magento\Framework\DataObject($arrItems);

        $this->uploadToQuoteAdminConfigHelper->expects($this->once())->method('getDeletedItems')->willReturn([71]);

        $this->assertIsArray($this->uloadToQuoteViewModelObj->excludeDeletedItem($objItems, 71));
    }

    /**
     * Test isQuoteNegotiated
     *
     * @return void
     */
    public function testIsQuoteNegotiated()
    {
        $this->uploadToQuoteAdminConfigHelper->expects($this->once())->method('isQuoteNegotiated')->willReturn(true);

        $this->assertTrue($this->uloadToQuoteViewModelObj->isQuoteNegotiated(1234));
    }

    /**
     * Test getSiType
     *
     * @return void
     */
    public function testGetSiType()
    {
        $returnValue = 'NON_STANDARD_FILE';
        $this->isUploadToQuoteEnable();
        $this->uploadToQuoteAdminConfigHelper->expects($this->once())
        ->method('getSiType')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->uloadToQuoteViewModelObj->getSiType('productJson'));
    }

    /**
     * Test method for updateLineItemsSkuDetails
     *
     * @return void
     */
    public function testUpdateLineItemsSkuDetails()
    {
        $rateQuoteResponse = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        [
                            'productLines' => [
                                ['instanceId' => 2453]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                    'properties' => [
                        [
                            'name' => 'USER_SPECIAL_INSTRUCTIONS',
                            'value' => 'Test'
                        ]
                    ]
                ],
            ],
        ];

        $this->quote->expects($this->any())->method('create')->willReturnSelf($this->quote);
        $this->quote->expects($this->any())->method('load')->willReturnSelf();
        $this->quote->expects($this->any())->method('getItemById')->willReturn($this->quoteItem);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->quoteItem]);
        $this->quoteItem->expects($this->any())->method('getItemId')->willReturn(2453);
        $this->quoteItem->expects($this->any())->method('getOptionByCode')->willReturnSelf();
        $this->quoteItem->expects($this->any())->method('getOptionId')->willReturn(2);
        $this->quoteItem->expects($this->any())->method('getValue')->willReturn(json_encode($decodedData));
        $this->serializerMock->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->serializerMock->expects($this->any())->method('serialize')->willReturn('test string');
        $this->quoteItem->expects($this->any())->method('setvalue')->willReturn($this->quoteFactory);
        $this->quoteFactory->expects($this->any())->method('save')->willReturnSelf();

        $this->assertNotEquals(
            true,
            $this->uloadToQuoteViewModelObj->updateLineItemsSkuDetails($rateQuoteResponse, $this->quote)
        );
    }

    /**
     * Test method for updateItemsForFuse
     *
     * @return void
     */
    public function testUpdateItemsForFuse()
    {
        $rateQuoteRequest = [
            [
                'instanceId' => 123,
                'product' => '{"name":"Product 1","price":100, "externalSkus":123}'
            ],
            [
                'instanceId' => 456,
                'product' => '{"name":"Product 2","price":200, "externalSkus":123}'
            ]
        ];

        $quoteItemArray = [
            [
                'item_action' => 'add',
                'product' => '{"name":"Product 3","price":300, "externalSkus":123}'
            ],
            [
                'item_action' => 'update',
                'item_id' => 123,
                'product' => '{"name":"Updated Product 1","price":150, "externalSkus":123}'
            ],
            [
                'item_action' => 'delete',
                'item_id' => 456
            ]
        ];

        $this->assertIsArray($this->uloadToQuoteViewModelObj->updateItemsForFuse($rateQuoteRequest, $quoteItemArray));
    }

    /**
     * Test method for UpdateRateRequestForFuseBiddingDiscount
     *
     * @return void
     */
    public function testUpdateRateRequestForFuseBiddingDiscount()
    {
        $rateQuoteRequest = [
            [
                'instanceId' => 123,
                'product' => '{"name":"Product 1","price":100, "externalSkus":123}'
            ],
            [
                'instanceId' => 456,
                'product' => '{"name":"Product 2","price":200, "externalSkus":123}'
            ]
        ];

        $discountIntent = [
            "quoteDiscounts" =>
            [
                "discountAmount" => 1.23,
                "discountPercentage" => 0,
                "reasonCode" => "BIDDING"
            ]
        ];

        $this->assertIsArray($this->uloadToQuoteViewModelObj
        ->updateRateRequestForFuseBiddingDiscount($rateQuoteRequest, $discountIntent));
    }

    /**
     * Test updateLogHistory
     *
     * @return void
     */
    public function testUpdateLogHistory()
    {
        $this->uploadToQuoteAdminConfigHelper->expects($this->once())->method('updateLogHistory')->willReturn(true);

        $this->assertNull($this->uloadToQuoteViewModelObj->updateLogHistory('1234', 'processing_by_admin'));
    }

    /**
     * Test method for resetCustomerSI
     *
     * @return void
     */
    public function testresetCustomerSI()
    {
        $info = [
            'external_prod' => [
                [
                    'properties' => [
                        ['name' => 'CUSTOMER_SI', 'value' => 'some_value'],
                        ['name' => 'OTHER_PROPERTY', 'value' => 'other_value'],
                    ]
                ],
                [
                    'properties' => [
                        ['name' => 'ANOTHER_PROPERTY', 'value' => 'another_value'],
                    ]
                ]
            ]
        ];

        $expected = [
            'external_prod' => [
                [
                    'properties' => [
                        ['name' => 'CUSTOMER_SI', 'value' => ''],
                        ['name' => 'OTHER_PROPERTY', 'value' => 'other_value'],
                    ]
                ],
                [
                    'properties' => [
                        ['name' => 'ANOTHER_PROPERTY', 'value' => 'another_value'],
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $this->uloadToQuoteViewModelObj->resetCustomerSI($info));
    }

    /**
     * Test isMarkAsDeclinedEnabled
     *
     * @return void
     */
    public function testisMarkAsDeclinedEnabled()
    {
        $this->uploadToQuoteAdminConfigHelper->expects($this->once())
        ->method('isMarkAsDeclinedEnabled')->willReturn(true);

        $this->assertTrue($this->uloadToQuoteViewModelObj->isMarkAsDeclinedEnabled());
    }

    /**
     * Test getCompanyExtentionUrl
     *
     * @return void
     */
    public function testGetCompanyExtentionUrl()
    {
        $this->uploadToQuoteAdminConfigHelper->expects($this->once())
        ->method('getMyQuoteMaitenanceFixToggle')->willReturn(true);
        $this->companyRepository->expects($this->once())->method('getByCustomerId')->willReturnSelf();
        $this->companyRepository->expects($this->once())->method('getCompanyUrlExtention')
        ->willReturn('uploadtoquote-dev');

        $this->assertIsString($this->uloadToQuoteViewModelObj->getCompanyExtentionUrl($this->quote));
    }

    /**
     * Test getCompanyExtentionUrlException
     *
     * @return void
     */
    public function testGetCompanyExtentionUrlException()
    {
        $exception = new \Exception("User id does not exist");
        $this->uploadToQuoteAdminConfigHelper->expects($this->once())
        ->method('getMyQuoteMaitenanceFixToggle')->willReturn(true);
        $this->companyRepository->expects($this->once())->method('getByCustomerId')->willThrowException($exception);

        $this->assertIsString($this->uloadToQuoteViewModelObj->getCompanyExtentionUrl($this->quote));
    }
}
