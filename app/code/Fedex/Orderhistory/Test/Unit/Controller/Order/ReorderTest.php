<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Orderhistory\Test\Unit\Controller\Order;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;
use Fedex\Orderhistory\Model\Reorder\Reorder as ReorderModel;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;
use Fedex\Orderhistory\Controller\Order\Reorder;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Reorder\Data\ReorderOutput;
use Psr\Log\LoggerInterface;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Magento\Checkout\Helper\Cart;
use Fedex\InBranch\Model\InBranchValidation;

class ReorderTest extends TestCase
{
    protected $inValidBranchValidationMock;
    protected $request;
    protected $resultJson;
    protected $reorderOutput;
    protected $cartInterface;
    protected $error;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $cartDataHelper;
    protected $cart;
    protected $reorderMock;
    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var JsonFactory|MockObject
     */
    protected $resultJsonFactory;

    /**
     * @var ReorderModel|MockObject
     */
    private $reorderModel;

    /**
     * @var CheckoutSession|MockObject
     */
    private $checkoutSession;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inValidBranchValidationMock = $this->getMockBuilder(InBranchValidation::class)
            ->disableOriginalConstructor()
            ->setMethods(['isInBranchValidReorder'])
            ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getContent'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJson = $this->getMockBuilder(ResultJson::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->reorderModel = $this->getMockBuilder(ReorderModel::class)
            ->setMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->reorderOutput = $this->getMockBuilder(ReorderOutput::class)
            ->setMethods(['getCart', 'getErrors'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartInterface = $this->getMockBuilder(CartInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->error = $this->getMockBuilder(\Magento\Sales\Model\Reorder\Data\Error::class)
            ->setMethods(['getMessage','getCode'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->setMethods(['setQuoteId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cartDataHelper = $this->getMockBuilder(CartDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cart = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->reorderMock = $objectManager->getObject(
            Reorder::class,
            [
                'context' => $this->context,
                'request' => $this->request,
                'resultJsonFactory' => $this->resultJsonFactory,
                'reorderModel' => $this->reorderModel,
                'checkoutSession' => $this->checkoutSession,
                'logger' => $this->loggerMock,
                'cartDataHelper' => $this->cartDataHelper,
                'cartHelper' => $this->cart,
                'inBranchValidation' => $this->inValidBranchValidationMock
            ]
        );
    }

    /**
     * @test
     *
     * @return null
     */
    public function testExecute()
    {
        $reorderData = '{
            "8781":{
                "order_id":"7626",
                "prduct_id":"578",
                "item_id":"87181"
            }
        }';

        $success = ['status' => true, 'success' => 1];

        $maxMinCartLimit = ["maxCartItemLimit" => 2, "minCartItemThreshold" => 1];
        $this->cartDataHelper->expects($this->any())->method('getMaxCartLimitValue')->willReturn($maxMinCartLimit);
        $this->cart->expects($this->any())->method('getItemsCount')->willReturn(1);
        $this->request->expects($this->any())->method('getContent')->willReturn($reorderData);
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->reorderModel->expects($this->any())->method('execute')->with(json_decode($reorderData, true))
            ->willReturn($this->reorderOutput);
        $this->reorderOutput->expects($this->any())->method('getCart')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getId')->willReturn(123);
        $this->checkoutSession->expects($this->any())->method('setQuoteId')->with(123)->willReturnSelf();
        $this->reorderOutput->expects($this->any())->method('getErrors')->willReturn([]);
        $this->resultJson->expects($this->any())->method('setData')->with($success)->willReturnSelf();

        $this->assertNotNull($this->reorderMock->execute());
    }

    /**
     * @test
     *
     * @return null
     */
    public function testExecuteWithInBranch()
    {
        $reorderData = '{
            "8781":{
                "order_id":"7626",
                "prduct_id":"578",
                "item_id":"87181"
            }
        }';

        $isInBranchProductExist = ['isInBranchProductExist' => true];

        $maxMinCartLimit = ["maxCartItemLimit" => 2, "minCartItemThreshold" => 1];
        $this->cartDataHelper->expects($this->any())->method('getMaxCartLimitValue')->willReturn($maxMinCartLimit);
        $this->cart->expects($this->any())->method('getItemsCount')->willReturn(1);
        $this->request->expects($this->any())->method('getContent')->willReturn($reorderData);
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);

        $this->inValidBranchValidationMock->expects($this->any())->method('isInBranchValidReorder')
        ->with(json_decode($reorderData, true))
        ->willReturn(true);

        $this->resultJson->expects($this->any())->method('setData')->with($isInBranchProductExist)->willReturnSelf();

        $this->assertNotNull($this->reorderMock->execute());
    }

    /**
     * @test
     *
     * @return null
     */
    public function testExecuteWithLineCartItem()
    {
        $reorderData = '{
            "8781":{
                "order_id":"76216",
                "prduct_id":"578",
                "item_id":"8781"
            }
        }';
        $maxMinCartLimit = ["maxCartItemLimit" => 0, "minCartItemThreshold" => 1];
        $this->cartDataHelper->expects($this->any())->method('getMaxCartLimitValue')->willReturn($maxMinCartLimit);
        $this->cart->expects($this->any())->method('getItemsCount')->willReturn(1);
        $this->request->expects($this->any())->method('getContent')->willReturn($reorderData);
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->reorderModel->expects($this->any())->method('execute')->with(json_decode($reorderData, true))
            ->willReturn($this->reorderOutput);
        $this->reorderOutput->expects($this->any())->method('getCart')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getId')->willReturn(123);
        $this->checkoutSession->expects($this->any())->method('setQuoteId')->with(123)->willReturnSelf();
        $this->reorderOutput->expects($this->any())->method('getErrors')->willReturn(['error'=> 'error']);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertNotNull($this->reorderMock->execute());
    }

    /**
     * @test
     *
     * @return null
     */
    public function testExecuteWithErrors()
    {
        $reorderData = '{
            "8781":{
                "order_id":"7626",
                "prduct_id":"578",
                "item_id":"8781"
            }
        }';
        $maxMinCartLimit = ["maxCartItemLimit" => 2, "minCartItemThreshold" => 1];
        $this->cartDataHelper->expects($this->any())->method('getMaxCartLimitValue')->willReturn($maxMinCartLimit);
        $this->cart->expects($this->any())->method('getItemsCount')->willReturn(1);
        $this->request->expects($this->any())->method('getContent')->willReturn($reorderData);
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->reorderModel->expects($this->any())->method('execute')->with(json_decode($reorderData, true))
            ->willReturn($this->reorderOutput);
        $this->reorderOutput->expects($this->any())->method('getCart')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getId')->willReturn(123);
        $this->checkoutSession->expects($this->any())->method('setQuoteId')->with(123)->willReturnSelf();
        $this->reorderOutput->expects($this->any())->method('getErrors')->willReturn([0=> $this->error]);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertNotNull($this->reorderMock->execute());
    }

    /**
     * Test for ExecuteWithLocalizedException
     */
    public function testExecuteWithLocalizedException()
    {
        $reorderData = '{
            "8781":{
                "order_id":"7626",
                "prduct_id":"578",
                "item_id":"8781"
            }
        }';

        $phrase = new Phrase(__('Exception message'));
        $localizedException = new LocalizedException($phrase);
        $maxMinCartLimit = ["maxCartItemLimit" => 2, "minCartItemThreshold" => 1];
        $this->cartDataHelper->expects($this->any())->method('getMaxCartLimitValue')->willReturn($maxMinCartLimit);
        $this->cart->expects($this->any())->method('getItemsCount')->willReturn(1);
        $this->request->expects($this->any())->method('getContent')->willReturn($reorderData);
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->reorderModel->expects($this->any())->method('execute')->with(json_decode($reorderData, true))
            ->willThrowException($localizedException);

        $this->assertNull($this->reorderMock->execute());
    }
}

