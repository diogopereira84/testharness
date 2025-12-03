<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Plugin\Ui\Component\Listing\Column;

use Fedex\UploadToQuote\Plugin\Ui\Component\Listing\Column\Status;
use Magento\NegotiableQuote\Ui\Component\Listing\Column\Status as NegotiableStatus;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;

class StatusTest extends TestCase
{
    protected $negotiableStatus;
    protected $status;
    /**
     * @var AdminConfigHelper $adminConfigHelper
     */
    protected $adminConfigHelper;

    public function setUp(): void
    {
        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isUploadToQuoteToggle', 'getNegotiableQuoteStatus'])
            ->getMock();

        $this->negotiableStatus = $this->getMockBuilder(NegotiableStatus::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->status = $objectManagerHelper->getObject(
            Status::class,
            [
                'adminConfigHelper' => $this->adminConfigHelper
            ]
        );
    }

    /**
     * Test afterPrepareDataSource
     *
     * @return void
     */
    public function testAfterPrepareDataSource()
    {
        $result = [
            "data" => [
                "items" => [
                    "item" => [
                        "entity_id" => 1
                    ]
                ]
            ]
        ];

        $this->adminConfigHelper
            ->expects($this->once())
            ->method('isUploadToQuoteToggle')
            ->willReturn(true);
        $this->adminConfigHelper
            ->expects($this->once())
            ->method('getNegotiableQuoteStatus')
            ->willReturn("Store Review");

        $expectedResult = [
            "data" => [
                "items" => [
                    "item" => [
                        "entity_id" => 1,
                        "status" => "Store Review"
                    ]
                ]
            ]
        ];

        $this->assertEquals(
            $expectedResult,
            $this->status->afterPrepareDataSource($this->negotiableStatus, $result, [])
        );
    }
}
