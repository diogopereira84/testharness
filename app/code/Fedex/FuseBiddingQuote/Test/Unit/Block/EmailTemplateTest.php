<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Test\Unit\Block;

use Fedex\FuseBiddingQuote\Block\EmailTemplate;
use Fedex\UploadToQuote\Helper\QuoteEmailHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Email Template Block test class
 */
class EmailTemplateTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $objEmailTemplate;
    /**
     * @var QuoteEmailHelper $quoteEmailHelper
     */
    protected $quoteEmailHelper;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteEmailHelper = $this->getMockBuilder(QuoteEmailHelper::class)
            ->setMethods(['getEmailTemplate'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this
            ->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore','getBaseUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->objEmailTemplate = $this->objectManager->getObject(
            EmailTemplate::class,
            [
                'quoteEmailHelper' => $this->quoteEmailHelper,
                'storeManager' => $this->storeManager
            ]
        );
    }

    /**
     * Test getEmailTemplate.
     *
     * @return string
     */
    public function testGetEmailTemplate()
    {
        $qouteData = 'test';
        $this->quoteEmailHelper->expects($this->once())->method('getEmailTemplate')->willReturn($qouteData);

        $this->assertIsString($this->objEmailTemplate->getEmailTemplate($qouteData));
    }

    /**
     * Test getMediaPath.
     *
     * @return string
     */
    public function testGetMediaPath()
    {
        $baseUrl = 'https//office.fedex.com';
        $this->storeManager->expects($this->once())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getBaseUrl')->willReturn($baseUrl);

        $this->assertIsString($this->objEmailTemplate->getMediaPath());
    }
}
