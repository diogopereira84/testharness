<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Controller\Index;

use Fedex\CIDPSG\Helper\Email;
use Fedex\UploadToQuote\Controller\Index\SendQuoteEmail;
use Fedex\UploadToQuote\Helper\QuoteEmailHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\RequestInterface;

/**
 * Test class for SendQuoteEmail Controller
 */
class SendQuoteEmailTest extends TestCase
{
   
    /**
     * @var (\Fedex\UploadToQuote\Helper\QuoteEmailHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteEmailHelperMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $sendQuoteEmail;
    /**
     * @var QuoteEmailHelper|MockObject
     */
    protected $quoteEmailHelper;

    /**
     * @var RequestInterface $requestMock
     */
    protected $requestMock;
    /**
     * Set up method.
     *
     * @return void
     */
    protected function setUp(): void
    {

        $this->quoteEmailHelperMock = $this->getMockBuilder(QuoteEmailHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'sendQuoteGenericEmail',
            ])
            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->sendQuoteEmail = $this->objectManager->getObject(
            SendQuoteEmail::class,
            [
                'quoteEmailHelperMock' => $this->quoteEmailHelperMock
            ]
        );
    }

    /**
     * Test method for Execute function
     *
     * @return void
     */
    public function testExecute()
    {
        $this->requestMock->expects($this->any())->method('getParam')->willReturn('confirmed');
        $this->requestMock->expects($this->any())->method('getParam')->willReturn('1');

        $this->assertNull($this->sendQuoteEmail->execute());
    }
}
