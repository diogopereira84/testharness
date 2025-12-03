<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\PageBuilderPromoBanner\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\CartFactory;
use Fedex\FXOPricing\Helper\FXORate;
use Magento\Framework\Escaper;
use Magento\Framework\Message\ManagerInterface;
use Fedex\PageBuilderPromoBanner\Observer\CheckCouponStatus;
use Magento\Framework\DataObject;

class CheckCouponStatusTest extends \PHPUnit\Framework\TestCase
{
    protected $_request;
    protected $_checkCouponCodeStatus;
    /**
     * SDE SSO cookie name
     */
    const INVALID_COUPON_CODE = 'Invalid Coupon Code';

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;
    
    /**
     * @var Session|MockObject
     */
    protected $checkoutSession;
    
    /**
     * @var CartFactory|MockObject
     */
    protected $cartFactory;
    
    /**
     * @var FXORate|MockObject
     */
    protected $fXORateHelper;
    
    /**
     * @var Escaper|MockObject
     */
    protected $escaper;
    
    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManager;
    
    /**
     * @var Observer|MockObject
     */
    protected $observer;
    
    /**
     * @var CheckCouponStatus|MockObject
     */
    protected $checkCouponStatus;
     
    protected function setUp(): void
    {
        $this->checkoutSession = $this->getMockBuilder(Session::class)
                                ->setMethods(['getIsApplyCoupon', 'setIsApplyCoupon'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->cartFactory = $this->getMockBuilder(CartFactory::class)
                                ->setMethods(['create', 'getQuote', 'getCouponCode', 'setCouponCode', 'getItemsCount'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->fXORateHelper = $this->getMockBuilder(FXORate::class)
                                ->setMethods(['getFXORate'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->escaper = $this->getMockBuilder(Escaper::class)
                                ->setMethods(['escapeHtml'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->observer = $this->getMockBuilder(Observer::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['getHandles','getUpdate', 'getData'])
                                ->getMock();

        $this->_request = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['getFullActionName'])
                                ->getMock();
                    
        
        $this->objectManager = new ObjectManager($this);
        $this->_checkCouponCodeStatus = $this->objectManager->getObject(
            CheckCouponStatus::class,
            [
                'checkoutSession' => $this->checkoutSession,
                'cartFactory' => $this->cartFactory,
                'fxoRateHelper' => $this->fXORateHelper,
                'escaper' => $this->escaper,
                'messageManager' => $this->messageManager,
                '_request' => $this->_request
            ]
        );
    }

    /**
     * Test execution to identified, is the coupon code is applied by the promotion banner.
     */
    public function testExecute()
    {
        $orderData = ['output'=>[
                'coupon_code' => 'MGT001',
                'alerts' => [
                    0 => [
                        'code' => 'COUPONS.CODE.INVALID',
                    ],
                ],
            ]
        ];
        
        $varienObject = new DataObject();
        $varienObject->setData($orderData);
        $this->_request->expects($this->any())->method('getFullActionName')->willReturn("cms_index_index");
        $this->observer->expects($this->any())->method('getData')->willreturnSelf();
        $this->observer->expects($this->any())->method('getUpdate')->willreturnSelf();
        $this->observer->expects($this->any())->method('getHandles')->willreturn(['checkout_cart_index']);
        $this->checkoutSession->expects($this->any())->method('getIsApplyCoupon')->willreturn(true);
        $this->cartFactory->expects($this->any())->method('create')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getItemsCount')->willreturn(1);
        $this->cartFactory->expects($this->any())->method('getCouponCode')->willreturn('XYZ');
        $this->fXORateHelper->expects($this->any())->method('getFXORate')->willReturn($varienObject);
        $this->messageManager->expects($this->any())->method('addErrorMessage')->willReturn(self::INVALID_COUPON_CODE);
        $this->cartFactory->expects($this->any())->method('setCouponCode')->willreturn('');
        $this->escaper->expects($this->any())->method('escapeHtml')->willreturn('XYZ');
        $this->checkoutSession->expects($this->any())->method('setIsApplyCoupon')->willReturn(false);
        
        $this->assertNotNull($this->_checkCouponCodeStatus->execute($this->observer));
    }

    /**
     * Test execution to identified, is the coupon code is applied by the promotion banner.
     */
    public function testExecuteWithDifferentAlert()
    {
        $orderData = ['output'=>[
                'coupon_code' => 'MGT001',
                'alerts' => [
                    0 => [
                        'code' => 'MINIMUM.PURCHASE.REQUIRED',
                    ],
                ],
            ]
        ];
        
        $varienObject = new DataObject();
        $varienObject->setData($orderData);
        $this->_request->expects($this->any())->method('getFullActionName')->willReturn("cms_index_index");
        $this->observer->expects($this->any())->method('getData')->willreturnSelf();
        $this->observer->expects($this->any())->method('getUpdate')->willreturnSelf();
        $this->observer->expects($this->any())->method('getHandles')->willreturn(['checkout_cart_index']);
        $this->checkoutSession->expects($this->any())->method('getIsApplyCoupon')->willreturn(true);
        $this->cartFactory->expects($this->any())->method('create')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getItemsCount')->willreturn(1);
        $this->cartFactory->expects($this->any())->method('getCouponCode')->willreturn('XYZ');
        $this->fXORateHelper->expects($this->any())->method('getFXORate')->willReturn($varienObject);
        $this->messageManager->expects($this->any())->method('addErrorMessage')->willReturn(self::INVALID_COUPON_CODE);
        $this->cartFactory->expects($this->any())->method('setCouponCode')->willreturn('');
        $this->escaper->expects($this->any())->method('escapeHtml')->willreturn('XYZ');
        $this->checkoutSession->expects($this->any())->method('setIsApplyCoupon')->willReturn(false);
        
        $this->assertNotNull($this->_checkCouponCodeStatus->execute($this->observer));
    }

    /**
     * Test execution to identified, is the coupon code is applied by the promotion banner.
     */
    public function testExecuteWithWithDifferentAlertToggleOff()
    {
        $orderData = [
            'coupon_code' => 'MGT001',
            'alerts' => [
                0 => [
                    'code' => 'MINIMUM.PURCHASE.REQUIRED',
                ],
            ],
        ];
        
        $varienObject = new DataObject();
        $varienObject->setData($orderData);
        $this->observer->expects($this->any())->method('getData')->willreturnSelf();
        $this->observer->expects($this->any())->method('getUpdate')->willreturnSelf();
        $this->observer->expects($this->any())->method('getHandles')->willreturn([]);
        $this->checkoutSession->expects($this->any())->method('getIsApplyCoupon')->willreturn(true);
        $this->cartFactory->expects($this->any())->method('create')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getItemsCount')->willreturn(1);
        $this->cartFactory->expects($this->any())->method('getCouponCode')->willreturn('XYZ');
        $this->fXORateHelper->expects($this->any())->method('getFXORate')->willReturn($varienObject);
        $this->messageManager->expects($this->any())->method('addErrorMessage')->willReturn(self::INVALID_COUPON_CODE);
        $this->cartFactory->expects($this->any())->method('setCouponCode')->willreturn('');
        $this->escaper->expects($this->any())->method('escapeHtml')->willreturn('XYZ');
        $this->checkoutSession->expects($this->any())->method('setIsApplyCoupon')->willReturn(false);
        
        $this->assertNotNull($this->_checkCouponCodeStatus->execute($this->observer));
    }

    /**
     * Test execution to identified, is the coupon code is applied by the promotion banner.
     */
    public function testExecuteWithToggleOff()
    {
        $orderData = [
            'coupon_code' => 'MGT001',
            'alerts' => [
                0 => [
                    'code' => 'COUPONS.CODE.INVALID',
                ],
            ],
        ];
        
        $varienObject = new DataObject();
        $varienObject->setData($orderData);
        $this->observer->expects($this->any())->method('getData')->willreturnSelf();
        $this->observer->expects($this->any())->method('getUpdate')->willreturnSelf();
        $this->observer->expects($this->any())->method('getHandles')->willreturn([]);
        $this->checkoutSession->expects($this->any())->method('getIsApplyCoupon')->willreturn(true);
        $this->cartFactory->expects($this->any())->method('create')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getItemsCount')->willreturn(1);
        $this->cartFactory->expects($this->any())->method('getCouponCode')->willreturn('XYZ');
        $this->fXORateHelper->expects($this->any())->method('getFXORate')->willReturn($varienObject);
        $this->messageManager->expects($this->any())->method('addErrorMessage')->willReturn(self::INVALID_COUPON_CODE);
        $this->cartFactory->expects($this->any())->method('setCouponCode')->willreturn('');
        $this->escaper->expects($this->any())->method('escapeHtml')->willreturn('XYZ');
        $this->checkoutSession->expects($this->any())->method('setIsApplyCoupon')->willReturn(false);
        
        $this->assertNotNull($this->_checkCouponCodeStatus->execute($this->observer));
    }

    /**
     * Test execution to identified, is the coupon code is applied by the promotion banner.
     */
    public function testExecuteWithNoAlertAndToggleOff()
    {
        $orderData = [
            'coupon_code' => 'MGT001',
        ];
        
        $varienObject = new DataObject();
        $varienObject->setData($orderData);
        $this->observer->expects($this->any())->method('getData')->willreturnSelf();
        $this->observer->expects($this->any())->method('getUpdate')->willreturnSelf();
        $this->observer->expects($this->any())->method('getHandles')->willreturn([]);
        $this->checkoutSession->expects($this->any())->method('getIsApplyCoupon')->willreturn(true);
        $this->cartFactory->expects($this->any())->method('create')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getItemsCount')->willreturn(1);
        $this->cartFactory->expects($this->any())->method('getCouponCode')->willreturn('XYZ');
        $this->fXORateHelper->expects($this->any())->method('getFXORate')->willReturn($varienObject);
        $this->messageManager->expects($this->any())->method('addErrorMessage')->willReturn(self::INVALID_COUPON_CODE);
        $this->cartFactory->expects($this->any())->method('setCouponCode')->willreturn('');
        $this->escaper->expects($this->any())->method('escapeHtml')->willreturn('XYZ');
        $this->checkoutSession->expects($this->any())->method('setIsApplyCoupon')->willReturn(false);
        
        $this->assertNotNull($this->_checkCouponCodeStatus->execute($this->observer));
    }

    /**
     * Test case for applyCouponCode
     */
    public function testapplyCouponCode()
    {
        $this->checkoutSession->expects($this->any())->method('getIsApplyCoupon')->willreturn(true);
        $this->cartFactory->expects($this->any())->method('create')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getItemsCount')->willreturn(1);
        $this->cartFactory->expects($this->any())->method('getCouponCode')->willreturn('XYZ');
        $this->assertNull($this->_checkCouponCodeStatus->applyCouponCode());
    }

    /**
     * Test case for applyCouponCode
     */
    public function testapplyCouponCodeWithToggleOff()
    {
        $orderData = [
            'coupon_code' => 'MGT001',
            'alerts' => [
                0 => [
                    'code' => 'MINIMUM.PURCHASE.REQUIRED',
                ],
            ],
        ];
        $this->checkoutSession->expects($this->any())->method('getIsApplyCoupon')->willreturn(true);
        $this->cartFactory->expects($this->any())->method('create')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getItemsCount')->willreturn(1);
        $this->cartFactory->expects($this->any())->method('getCouponCode')->willreturn('XYZ');
        $this->fXORateHelper->expects($this->any())->method('getFXORate')->willReturn($orderData);
        $this->assertNull($this->_checkCouponCodeStatus->applyCouponCode());
    }

    /**
     * Test case for applyPromoCode
     */
    public function testapplyPromoCode()
    {
        $orderData = [
            'output' => [
                'alerts' => [
                    0 => [
                        'code' => 'COUPONS.CODE.INVALID'
                    ]
                ]
            ]
        ];
        $this->checkoutSession->expects($this->any())->method('getIsApplyCoupon')->willreturn(true);
        $this->cartFactory->expects($this->any())->method('create')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getItemsCount')->willreturn(1);
        $this->cartFactory->expects($this->any())->method('getCouponCode')->willreturn('XYZ');
        $this->fXORateHelper->expects($this->any())->method('getFXORate')->willReturn($orderData);
        $this->assertNull($this->_checkCouponCodeStatus->applyPromoCode());
    }

    /**
     * Test case for applyPromoCode
     */
    public function testapplyPromoCodeWithAlert()
    {
        $orderData = [
            'output' => [
                'alerts' => [
                    0 => [
                        'code' => 'MINIMUM.PURCHASE.REQUIRED'
                    ]
                ]
            ]
        ];
        $this->checkoutSession->expects($this->any())->method('getIsApplyCoupon')->willreturn(true);
        $this->cartFactory->expects($this->any())->method('create')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willreturnSelf();
        $this->cartFactory->expects($this->any())->method('getItemsCount')->willreturn(1);
        $this->cartFactory->expects($this->any())->method('getCouponCode')->willreturn('XYZ');
        $this->fXORateHelper->expects($this->any())->method('getFXORate')->willReturn($orderData);
        $this->assertNull($this->_checkCouponCodeStatus->applyPromoCode());
    }
}
