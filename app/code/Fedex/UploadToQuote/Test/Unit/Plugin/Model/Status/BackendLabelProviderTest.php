<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Plugin\Model\Status;

use Fedex\UploadToQuote\Plugin\Model\Status\BackendLabelProvider;
use Magento\NegotiableQuote\Model\Status\BackendLabelProvider as NegotiableBackendLabelProvider;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Framework\App\Request\Http;

class BackendLabelProviderTest extends TestCase
{
    protected $negotiableBackendLabelProvider;
    protected $backendLabelProvider;
    public const STORE_REVIEW = "Store Review";

    /**
     * @var AdminConfigHelper $adminConfigHelper
     */
    protected $adminConfigHelper;

    /**
     * @var Http $http
     */
    protected $http;

    public function setUp(): void
    {
        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isUploadToQuoteToggle', 'getNegotiableQuoteStatus'])
            ->getMock();

        $this->http = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMock();

        $this->negotiableBackendLabelProvider = $this->getMockBuilder(NegotiableBackendLabelProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->backendLabelProvider = $objectManagerHelper->getObject(
            BackendLabelProvider::class,
            [
                'adminConfigHelper' => $this->adminConfigHelper,
                'http' => $this->http
            ]
        );
    }

    /**
     * Test afterGetStatusLabels
     *
     * @return void
     */
    public function testAfterGetStatusLabels()
    {
        $this->adminConfigHelper
            ->expects($this->once())
            ->method('isUploadToQuoteToggle')
            ->willReturn(true);
        $this->http
            ->expects($this->once())
            ->method('getParam')
            ->willReturn("1");
        $this->adminConfigHelper
            ->expects($this->once())
            ->method('getNegotiableQuoteStatus')
            ->willReturn(static::STORE_REVIEW);

        $expectedResult = [
            "created" => static::STORE_REVIEW,
            "processing_by_admin" => static::STORE_REVIEW,
            "submitted_by_customer" => static::STORE_REVIEW,
            "submitted_by_admin" => static::STORE_REVIEW,
            "ordered" => static::STORE_REVIEW,
            "closed" => static::STORE_REVIEW,
            "nbc_priced" => static::STORE_REVIEW,
            "nbc_support" => static::STORE_REVIEW
        ];

        $this->assertEquals(
            $expectedResult,
            $this->backendLabelProvider->afterGetStatusLabels(
                $this->negotiableBackendLabelProvider,
                []
            )
        );
    }
}
