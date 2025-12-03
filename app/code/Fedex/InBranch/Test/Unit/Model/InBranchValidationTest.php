<?php
/**
 * @category  Fedex
 * @package   Fedex_InBranch
 * @author    Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\InBranch\Test\Unit\Model\InBranchValidation;

use Fedex\InBranch\Model\InBranchValidation;
use Fedex\InBranch\Helper\Data;
use Fedex\Delivery\Helper\Data as deliveryHelper;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Event\Observer;
use Magento\Catalog\Model\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;

class InBranchValidationTest extends TestCase
{
    /**
     * @var (\Fedex\InBranch\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $dataMock;
    protected $deliveryHelperMock;
    protected $sessionMock;
    protected $productRepositoryMock;
    protected $productMock;
    protected $quote;
    protected $item;
    protected $productResourceMock;
    protected $inBranchValidationMock;
    /**
     * Setup mock objects
     */
    protected function setUp(): void
    {
        $this->dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryHelperMock = $this->getMockBuilder(DeliveryHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProductLocationBranchNumber','getResource','getData','getStoreId','getId'])
            ->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllVisibleItems'])
            ->getMock();
        $this->item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMock();

        $this->productResourceMock = $this->getMockBuilder(ProductResource::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributeRawValue'])
            ->getMock();

        $this->inBranchValidationMock = (new ObjectManager($this))->getObject(
            InBranchValidation::class,
            [
                'data' => $this->dataMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'quote' => $this->sessionMock,
                'productRepository' => $this->productRepositoryMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testIsInBranchValid()
    {
        $productInfo = '{"fxoMenuId":"89f09f13-379c-44c1-9768-f6ebd0ca1b80","fxoProductInstance":{"id":"89f09f13-379c-44c1-9768-f6ebd0ca1b80","name":"custom_jan11","productConfig":{"product":{"id":1508784838900,"version":0,"name":"Legacy+Catalog","qty":"1","priceable":true,"proofRequired":false,"contentAssociations":[{"parentContentReference":"13749320250167797321006037742230731582230","contentReference":"13749318404189066375519200949351498142801","contentType":"PDF","printReady":true,"pageGroups":[{"start":1,"end":1,"width":8.5,"height":11}],"fileName":"Single_Text+n+Image+Fields_Template.pdf"}],"catalogReference":{"catalogProductId":"89f09f13-379c-44c1-9768-f6ebd0ca1b80","version":"DOC_20230523_13243226554_2"},"isOutSourced":false,"instanceId":"0"},"documentSourceType":"catalog_file"},"productRateTotal":{"unitPrice":null,"currency":"USD","quantity":1,"price":"$0.16","priceAfterDiscount":"$0.14","unitOfMeasure":"EACH","totalDiscount":"($0.02)","productLineDetails":[{"detailCode":"0001","priceRequired":false,"priceOverridable":false,"description":"BW+1S+Copy/Print","unitQuantity":1,"quantity":1,"detailPrice":"$0.14","detailDiscountPrice":"($0.02)","detailUnitPrice":"$0.1600","detailDiscountedUnitPrice":"($0.0160)","detailDiscounts":[{"type":"AR_CUSTOMERS","amount":"($0.01)"}],"detailCategory":"PRINTING"}]},"isUpdateButtonVisible":false,"link":{"href":"https://dunctest.fedex.com/document/fedexoffice/v1/documents/13749318404189066375519200949351498142801/preview?pageNumber=1"},"quantityChoices":[],"expressCheckout":false,"isEditable":true,"catalogDocumentMetadata":null,"isEdited":false,"customDocState":{"customizableFields":[{"id":"MTMyNDMyMjY2NTI=","name":"ImageField1","description":"img","mandatory":true,"sequence":1,"documentAssociations":[{"pageNumber":1,"documentId":"13749318400037345544003787248650450292961"}],"inputType":"IMAGE","inputMethod":"FREEFORM"},{"id":"MTMyNDMyMjY2NTE=","name":"TextField1","description":"text","mandatory":true,"sequence":2,"documentAssociations":[{"pageNumber":1,"documentId":"13749318400037345544003787248650450292961"}],"inputType":"TEXT","inputMethod":"EITHER","options":[{"textValue":"ddd"},{"textValue":"bbb"},{"textValue":"xxx"},{"textValue":"aaa"}],"defaultValue":{"textValue":"aaa"}}],"customFields":[{"id":"MTMyNDMyMjY2NTI=","name":"ImageField1","description":"img","mandatory":true,"sequence":1,"documentAssociations":[{"pageNumber":1,"documentId":"13749318400037345544003787248650450292961"}],"inputType":"IMAGE","inputMethod":"FREEFORM","useCustomInput":false,"isLoading":false,"filename":"documentprinting.jpg","customizedField":{"id":"MTMyNDMyMjY2NTI=","mandatory":true,"sequence":1,"imageValue":{"documentId":"13749320238208253074915062331280902502738","previewURL":""}},"defaultValue":{"id":"MTMyNDMyMjY2NTI=","mandatory":true,"sequence":1,"imageValue":{"documentId":"13749320238208253074915062331280902502738","previewURL":""}}},{"id":"MTMyNDMyMjY2NTE=","name":"TextField1","description":"text","mandatory":true,"sequence":2,"documentAssociations":[{"pageNumber":1,"documentId":"13749318400037345544003787248650450292961"}],"inputType":"TEXT","inputMethod":"EITHER","options":[{"textValue":"ddd"},{"textValue":"bbb"},{"textValue":"xxx"},{"textValue":"aaa"}],"defaultValue":{"id":"MTMyNDMyMjY2NTE=","mandatory":true,"sequence":2,"textValue":"neeraj"},"useCustomInput":true,"isLoading":false,"filename":"","customizedField":{"id":"MTMyNDMyMjY2NTE=","mandatory":true,"sequence":2,"textValue":"neeraj"}}]}},"productType":"COMMERCIAL_PRODUCT","instanceId":null}';
        $this->sessionMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->deliveryHelperMock->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->productRepositoryMock->expects($this->any())->method('get')->willReturn($this->productMock);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->item]);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('getResource')->willReturn($this->productResourceMock);
        $this->productResourceMock->expects($this->any())->method('getAttributeRawValue')->willReturn('0798');
        $this->productMock->expects($this->any())->method('getData')
            ->with('product_location_branch_number')
            ->willReturn('0798');
        $this->assertEquals(false, $this->inBranchValidationMock->isInBranchValid($productInfo, true));
    }

    /**
     * @return void
     */
    public function testIsInBranchValidTrue()
    {
        $this->sessionMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->deliveryHelperMock->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->productRepositoryMock->expects($this->any())->method('get')->willReturn($this->productMock);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->item]);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('getResource')->willReturn($this->productResourceMock);
        $this->productResourceMock->expects($this->any())->method('getAttributeRawValue')->willReturn('0798');
        $this->productMock->expects($this->any())->method('getData')->with('product_location_branch_number')
            ->willReturn('0124');
        $this->assertEquals(true, $this->inBranchValidationMock->isInBranchValid($this->productMock, false));
    }

    /**
     * @return bool|void
     */
    public function testIsInBranchValidReorder()
    {
        $reorderDatas = ['8781'=>[
            'order_id'=>'7626',
            'product_id'=>'578',
            'item_id'=>'87181'
        ]];
        $this->sessionMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->deliveryHelperMock->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->item]);
        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('getProductLocationBranchNumber')->willReturn('0798');
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('getProductLocationBranchNumber')->willReturn('0124');
        $this->assertEquals(null, $this->inBranchValidationMock->isInBranchValidReorder($reorderDatas));
    }
    /**
     * @return void
     */
    public function testGetAllowedInBranchLocation()
    {
        $locationNumber='';
        $this->sessionMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->deliveryHelperMock->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->item]);
        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('getProductLocationBranchNumber')->willReturn('');
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('getProductLocationBranchNumber')->willReturn('');
        $this->assertEquals($locationNumber, $this->inBranchValidationMock->getAllowedInBranchLocation());
    }

    /**
     * @return void
     */
    public function testIsInBranchProductWithContentAssociationsEmpty()
    {
        $mockItemWithContentAssociations = $this->createMock(Item::class);
        $mockItemWithContentAssociations->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productMock);
    
        $this->productMock->expects($this->any())
            ->method('getProductLocationBranchNumber')
            ->willReturn('1234');

        $mockItemWithContentAssociations->expects($this->any())
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($this->createMockOptionWithEmptyContentAssociations());

        $this->quote->expects($this->any())
            ->method('getAllVisibleItems')
            ->willReturn([$mockItemWithContentAssociations]);

        $this->assertFalse($this->inBranchValidationMock->isInBranchProductWithContentAssociationsEmpty([$mockItemWithContentAssociations]));

        $this->productMock->expects($this->any())
            ->method('getProductLocationBranchNumber')
            ->willReturn('');

        $this->assertFalse($this->inBranchValidationMock->isInBranchProductWithContentAssociationsEmpty([$mockItemWithContentAssociations]));
    }

    /**
     * Helper method to create a mock option with empty content associations.
     * @return object
     */
    private function createMockOptionWithEmptyContentAssociations()
    {
        $mockOption = $this->getMockBuilder('Magento\Quote\Model\Quote\Item\Option')
            ->disableOriginalConstructor()
            ->getMock();

        $mockOption->expects($this->any())
            ->method('getValue')
            ->willReturn(json_encode(['external_prod' => ['contentAssociations' => ['dfs']]]));

        return $mockOption;
    }

}
