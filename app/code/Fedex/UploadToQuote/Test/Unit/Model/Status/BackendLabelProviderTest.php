<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Model\Status;

use Fedex\UploadToQuote\Model\Status\BackendLabelProvider;
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
    public function testGetStatusLabels()
    {
        $expectedResult = [
            'draft_by_admin' => __('Draft'),
            'created' => __('New'),
            'processing_by_customer' => __('Client reviewed'),
            'processing_by_admin' => __('Open'),
            'submitted_by_customer' => __('Updated'),
            'submitted_by_admin' => __('Submitted'),
            'ordered' => __('Ordered'),
            'expired' => __('Expired'),
            'declined' => __('Declined'),
            'closed' => __('Closed'),
            AdminConfigHelper::NBC_PRICED => __(AdminConfigHelper::STATUS_NBC_PRICED),
            AdminConfigHelper::NBC_SUPPORT => __(AdminConfigHelper::STATUS_NBC_SUPPORT),
        ];

        $this->assertEquals(
            $expectedResult,
            $this->backendLabelProvider->getStatusLabels(
                $this->negotiableBackendLabelProvider,
                []
            )
        );
    }
}


