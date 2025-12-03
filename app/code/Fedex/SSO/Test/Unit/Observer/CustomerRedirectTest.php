<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\SSO\Test\Unit\Observer;

use Fedex\SSO\Observer\CustomerRedirect;
use Magento\Framework\App\ResponseFactory;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\Observer;

/**
 * Test class for CustomerRedirect
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class CustomerRedirectTest extends TestCase
{

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $customerRedirect;
    /**
     * @var ResponseFactory $responseFactory
     */
    protected $responseFactory;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var LoggerInterface $loggerMock
     */
    protected $loggerMock;
    private Observer|MockObject $observer;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->responseFactory = $this->getMockBuilder(ResponseFactory::class)
            ->setMethods(
                [
                    'create', 'setRedirect', 'sendResponse', 'getResponse', 'setBody'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(
                [
                        'getStore', 'getBaseUrl'
                    ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->customerRedirect = $this->objectManager->getObject(
            CustomerRedirect::class,
            [
                'responseFactory' => $this->responseFactory,
                'storeManager' => $this->storeManager,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test execute function
     *
     * @return void
     */
    public function testExecute()
    {
        $this->observer = $this->getMockBuilder("\Magento\Framework\Event\Observer")
                  ->getMock();
        $url = "https://www.fedex.com";
        $this->storeManager->expects($this->once())->method('getStore')->will($this->returnSelf());
        $this->storeManager->expects($this->any())->method('getBaseUrl')->willReturn($url);
        $this->responseFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->responseFactory->expects($this->any())->method('setRedirect')->with($url)->willReturnSelf();
        $this->responseFactory->expects($this->any())->method('sendResponse')->willReturnSelf();

        $this->assertNotNull($this->customerRedirect->execute($this->observer));
    }

    /**
     * Test execute function with exception
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->storeManager->expects($this->any())->method('getStore')->willThrowException($exception);
        $this->observer = $this->getMockBuilder("\Magento\Framework\Event\Observer")
                  ->getMock();
        $this->assertSame(null, $this->customerRedirect->execute($this->observer));
    }
}
