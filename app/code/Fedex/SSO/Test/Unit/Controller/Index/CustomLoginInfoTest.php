<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Test\Unit\Controller\Index;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SSO\Controller\Index\CustomLoginInfo;
use Magento\Framework\View\Result\PageFactory;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\App\Response\Http as ResponseHttp;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomLoginInfoTest extends TestCase
{

    /**
     * @var PageFactory|MockObject
     */
    protected $resultPageFactory;

    /**
     * @var SsoConfiguration|MockObject
     */
    protected $ssoConfiguration;

    /**
     * @var ResponseHttp|MockObject
     */
    protected $response;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var CustomLoginInfo|MockObject
     */
    protected $customerLoginInfoData;

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

        $this->ssoConfiguration = $this->getMockBuilder(SsoConfiguration::class)
            ->setMethods(
                [
                    'isFclCustomer'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->response = $this->createMock(ResponseHttp::class);
        $this->objectManager = new ObjectManager($this);
        $this->customerLoginInfoData = $this->objectManager->getObject(
            CustomLoginInfo::class,
            [
                'resultPageFactory' => $this->resultPageFactory,
                'ssoConfiguration' => $this->ssoConfiguration,
                'response' => $this->response

            ]
        );
    }

    /**
     * Function testExecuteWithoutLogin
     */
    public function testExecuteWithoutLogin()
    {
        $this->resultPageFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('getLayout')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('createBlock')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('setTemplate')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('toHtml')->willReturnSelf();
        $this->assertSame(null, $this->customerLoginInfoData->execute());
    }

    /**
     * Function testExecuteWithLogin
     */
    public function testExecuteWithLogin()
    {
        $this->resultPageFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(1);
        $this->resultPageFactory->expects($this->any())->method('getLayout')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('createBlock')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('setTemplate')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('toHtml')->willReturnSelf();
        $this->assertSame(null, $this->customerLoginInfoData->execute());
    }
}
