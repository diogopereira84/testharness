<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Controller\Product;

use Fedex\Cart\Controller\Product\Add;
use Fedex\Cart\Model\Quote\Product\Add as AddProductQuoteModel;
use Magento\Backend\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\InBranch\Model\InBranchValidation;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Fedex\FXOCMConfigurator\Helper\Batchupload as BatchuploadHelper;
use Magento\Framework\Controller\Result\Json;

class AddTest extends TestCase
{
    /**
     * @var (\Fedex\InBranch\Model\InBranchValidation & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $inValidBranchValidationMock;
    /**
     * @var (\Magento\Framework\Controller\Result\JsonFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resultJsonMock;
    /**
     * @var MockObject
     */
    protected $contextMock;
    /**
     * @var AddProductQuoteModel|MockObject
     */
    protected $quoteProductAddMock;
    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManagerInterfaceMock;
    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;
    /**
     * @var Add
     */
    protected $addMock;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfig;

    /**
     * @var BatchuploadHelper|MockObject
     */
    protected $batchuploadHelper;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerInterface;

    /**
     * @var Json|MockObject
     */
    protected $jsonMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->onlyMethods(['getRequest', 'getMessageManager'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->inValidBranchValidationMock = $this->getMockBuilder(InBranchValidation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJsonMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->onlyMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getPost'])
            ->getMockForAbstractClass();
        
        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);

        $this->messageManagerInterfaceMock = $this->getMockBuilder(ManagerInterface::class)
            ->onlyMethods(['addSuccess'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageManagerInterfaceMock->expects($this->any())->method('addSuccess')->willReturn(true);
        $this->contextMock->expects($this->any())->method('getMessageManager')
            ->willReturn($this->messageManagerInterfaceMock);

        $this->quoteProductAddMock = $this->getMockBuilder(AddProductQuoteModel::class)
            ->onlyMethods(['addItemToCart', 'setCart'])
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->getMock();

        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->batchuploadHelper = $this->getMockBuilder(BatchuploadHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addBatchUploadData'])
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData'])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->addMock = $objectManager->getObject(
            Add::class,
            [
                'context' => $this->contextMock,
                'quoteProductAdd' => $this->quoteProductAddMock,
                'quote' => $quoteMock,
                'logger' => $this->loggerInterface,
                'toggleConfig' =>  $this->toggleConfig,
                'inBranchValidation'=>$this->inValidBranchValidationMock,
                'resultJsonFactory'=>$this->resultJsonMock,
                'batchuploadhelper' =>  $this->batchuploadHelper
            ]
        );
    }

    /**
     * Get Payload Data
     */
    public function getPostData()
    {
        return '{"fxoMenuId":"89f09f13-379c-44c1-9768-f6ebd0ca1b80","fxoProductInstance":{"id":"89f09f13-379c-44c1-9768-f6ebd0ca1b80","name":"custom_jan11","productConfig":{"product":{"id":1508784838900,"version":0,"name":"Legacy+Catalog","qty":"1","priceable":true,"proofRequired":false,"contentAssociations":[{"parentContentReference":"13749320250167797321006037742230731582230","contentReference":"13749318404189066375519200949351498142801","contentType":"PDF","printReady":true,"pageGroups":[{"start":1,"end":1,"width":8.5,"height":11}],"fileName":"Single_Text+n+Image+Fields_Template.pdf"}],"catalogReference":{"catalogProductId":"89f09f13-379c-44c1-9768-f6ebd0ca1b80","version":"DOC_20230523_13243226554_2"},"isOutSourced":false,"instanceId":"0"},"documentSourceType":"catalog_file"},"productRateTotal":{"unitPrice":null,"currency":"USD","quantity":1,"price":"$0.16","priceAfterDiscount":"$0.14","unitOfMeasure":"EACH","totalDiscount":"($0.02)","productLineDetails":[{"detailCode":"0001","priceRequired":false,"priceOverridable":false,"description":"BW+1S+Copy/Print","unitQuantity":1,"quantity":1,"detailPrice":"$0.14","detailDiscountPrice":"($0.02)","detailUnitPrice":"$0.1600","detailDiscountedUnitPrice":"($0.0160)","detailDiscounts":[{"type":"AR_CUSTOMERS","amount":"($0.01)"}],"detailCategory":"PRINTING"}]},"isUpdateButtonVisible":false,"link":{"href":"https://dunctest.fedex.com/document/fedexoffice/v1/documents/13749318404189066375519200949351498142801/preview?pageNumber=1"},"quantityChoices":[],"expressCheckout":false,"isEditable":true,"catalogDocumentMetadata":null,"isEdited":false,"customDocState":{"customizableFields":[{"id":"MTMyNDMyMjY2NTI=","name":"ImageField1","description":"img","mandatory":true,"sequence":1,"documentAssociations":[{"pageNumber":1,"documentId":"13749318400037345544003787248650450292961"}],"inputType":"IMAGE","inputMethod":"FREEFORM"},{"id":"MTMyNDMyMjY2NTE=","name":"TextField1","description":"text","mandatory":true,"sequence":2,"documentAssociations":[{"pageNumber":1,"documentId":"13749318400037345544003787248650450292961"}],"inputType":"TEXT","inputMethod":"EITHER","options":[{"textValue":"ddd"},{"textValue":"bbb"},{"textValue":"xxx"},{"textValue":"aaa"}],"defaultValue":{"textValue":"aaa"}}],"customFields":[{"id":"MTMyNDMyMjY2NTI=","name":"ImageField1","description":"img","mandatory":true,"sequence":1,"documentAssociations":[{"pageNumber":1,"documentId":"13749318400037345544003787248650450292961"}],"inputType":"IMAGE","inputMethod":"FREEFORM","useCustomInput":false,"isLoading":false,"filename":"documentprinting.jpg","customizedField":{"id":"MTMyNDMyMjY2NTI=","mandatory":true,"sequence":1,"imageValue":{"documentId":"13749320238208253074915062331280902502738","previewURL":""}},"defaultValue":{"id":"MTMyNDMyMjY2NTI=","mandatory":true,"sequence":1,"imageValue":{"documentId":"13749320238208253074915062331280902502738","previewURL":""}}},{"id":"MTMyNDMyMjY2NTE=","name":"TextField1","description":"text","mandatory":true,"sequence":2,"documentAssociations":[{"pageNumber":1,"documentId":"13749318400037345544003787248650450292961"}],"inputType":"TEXT","inputMethod":"EITHER","options":[{"textValue":"ddd"},{"textValue":"bbb"},{"textValue":"xxx"},{"textValue":"aaa"}],"defaultValue":{"id":"MTMyNDMyMjY2NTE=","mandatory":true,"sequence":2,"textValue":"neeraj"},"useCustomInput":true,"isLoading":false,"filename":"","customizedField":{"id":"MTMyNDMyMjY2NTE=","mandatory":true,"sequence":2,"textValue":"neeraj"}}]}},"productType":"COMMERCIAL_PRODUCT","instanceId":null}';
    }
    public function testExecute()
    {
        $this->requestMock
            ->method('getPost')
            ->withConsecutive(
                ['data'],
                ['itemId']
            )
            ->willReturnOnConsecutiveCalls(
                $this->getPostData(),
                ''
            );
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->quoteProductAddMock->expects($this->any())->method('addItemToCart')->willReturn(
            ['updatedProductName' => null]
        );

        $this->assertNull($this->addMock->execute());
    }

    public function testUpdatedProductName()
    {
        $this->quoteProductAddMock->expects($this->any())->method('addItemToCart')->willReturn(
            ['updatedProductName' => 'Sample Product Name']
        );

        $this->assertNull($this->addMock->execute());
    }

    public function testUpdatedProductNameWithException()
    {
        $exception = new \Exception();
        $this->quoteProductAddMock->expects($this->any())->method('addItemToCart')->willThrowException($exception);

        $this->assertNull($this->addMock->execute());
    }

    public function testInBranchProductExist()
    {
        $this->requestMock
            ->method('getPost')
            ->withConsecutive(
                ['data'],
                ['itemId']
            )
            ->willReturnOnConsecutiveCalls(
                $this->getPostData(),
                ''
            );
        $this->inValidBranchValidationMock->expects($this->any())
            ->method('isInBranchValid')
            ->with($this->anything(), true)
            ->willReturn(true);

        $this->jsonMock->expects($this->any())
            ->method('setData')
            ->with(['isInBranchProductExist' => true])
            ->willReturnSelf();
        $this->resultJsonMock->expects($this->any())
            ->method('create')
            ->willReturn($this->jsonMock);

        $result = $this->addMock->execute();
        $this->assertEquals($result, $this->jsonMock);
    }

    public function testWorkspaceDataEncoding()
    {
        $requestDataArray = json_decode($this->getPostData(), true);
        $requestDataArray['userWorkspace'] = [
            'files' => [
                'file1.pdf',
                'file2.pdf'
            ]
        ];
        $requestData = json_encode($requestDataArray);

        $this->requestMock
            ->method('getPost')
            ->withConsecutive(
                ['data'],
                ['itemId']
            )
            ->willReturnOnConsecutiveCalls(
                $requestData,
                ''
            );

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->quoteProductAddMock->expects($this->any())
            ->method('addItemToCart')
            ->willReturn(['updatedProductName' => null]);

        $this->batchuploadHelper->expects($this->any())
            ->method('addBatchUploadData')
            ->with(json_encode($requestDataArray['userWorkspace']));

        $this->batchuploadHelper->expects($this->any())
            ->method('addBatchUploadData')
            ->with(json_encode($requestDataArray['userWorkspace']));
        $this->assertNull($this->addMock->execute());
    }
}
