<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpressCheckout\Test\Unit\Controller\Customer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\ExpressCheckout\Controller\Customer\FedexAccountList;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Response\Http as ResponseHttp;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FedexAccountListTest extends TestCase
{

    /**
     * @var PageFactory|MockObject
     */
    protected $resultPageFactory;

    /**
     * @var ResponseHttp|MockObject
     */
    protected $response;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var FedexAccountList|MockObject
     */
    protected $fedexAccountList;

    /**
     * Function setUp
     */
    protected function setUp(): void
    {
        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->setMethods(
                [
                    'create',
                    'getLayout',
                    'createBlock',
                    'setTemplate',
                    'toHtml'

                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->response = $this->createMock(ResponseHttp::class);
        $this->objectManager = new ObjectManager($this);
        $this->fedexAccountList = $this->objectManager->getObject(
            FedexAccountList::class,
            [
                'resultPageFactory' => $this->resultPageFactory,
                'response' => $this->response
            ]
        );
    }

    /**
     * Test execute
     */
    public function testExecuteWithoutLogin()
    {
        $this->resultPageFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->resultPageFactory->expects($this->once())->method('getLayout')->willReturnSelf();
        $this->resultPageFactory->expects($this->once())->method('createBlock')->willReturnSelf();
        $this->resultPageFactory->expects($this->once())->method('setTemplate')->willReturnSelf();
        $this->resultPageFactory->expects($this->once())->method('toHtml')->willReturnSelf();
        $this->assertSame(null, $this->fedexAccountList->execute());
    }
}
