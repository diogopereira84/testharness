<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Helper;

use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\UploadToQuote\Helper\QueueHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteFactory;

class QueueHelperTest extends TestCase
{
    protected $quote;
    protected $quoteItem;
    public const DATETIME = '2024-01-10 05:40:08';
    public const DATE = '2024-01-10';
    public const TIME = '05:40:08';

    /**
     * @var TimezoneInterface $timezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * @var AdminConfigHelper $adminConfigHelper
     */
    protected $adminConfigHelper;

    /**
     * @var FXORateQuote
     */
    protected $fxoRateQuote;

    /**
     * @var QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var SerializerInterface $serializerMock
     */
    protected $serializerMock;

    /**
     * @var ObjectManagerHelper $objectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var QueueHelper $queueHelper
     */
    protected $queueHelper;

    /**
     * @var NegotiableQuoteFactory $negotiableQuoteFactory
     */
    protected NegotiableQuoteFactory $negotiableQuoteFactory;

    public function setUp(): void
    {
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getUploadToQuoteActionQueue',
                'setUploadToQuoteActionQueue',
                'unsUploadToQuoteActionQueue',
                'unsSiItems'
            ])->getMock();

        $this->timezoneInterface = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'format'])
            ->getMockForAbstractClass();

        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'updateStatusLog',
                'updateQuoteStatusByKey',
                'addCustomLog',
                'getFormattedDate',
                'removeQuoteItem',
                'updateQuoteStatusWithDeclined'
                ])
            ->getMock();
        
        $this->fxoRateQuote = $this->getMockBuilder(FXORateQuote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFXORateQuote'])
            ->getMock();

        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'load', 'getAllVisibleItems', 'save', 'getItemById'])
            ->getMock();
        
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['load'])
            ->getMock();
        
        $this->quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->setMethods(
                [
                    'getOptionByCode',
                    'getItemId',
                    'getOptionId',
                    'setValue',
                    'getValue'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->negotiableQuoteFactory = $this->getMockBuilder(NegotiableQuoteFactory::class)
            ->setMethods(
                [
                    'create',
                    'load',
                    'getStatus'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->onlyMethods(['unserialize'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->queueHelper = $objectManagerHelper->getObject(
            QueueHelper::class,
            [
                'customerSession' => $this->customerSession,
                'timezoneInterface' => $this->timezoneInterface,
                'adminConfigHelper' => $this->adminConfigHelper,
                'fxoRateQuote' => $this->fxoRateQuote,
                'quoteFactory' => $this->quoteFactory,
                'quote' => $this->quote,
                'serializer' => $this->serializerMock,
                'negotiableQuoteFactory' => $this->negotiableQuoteFactory
            ]
        );
    }

    /**
     * Test setQueue
     *
     * @return void
     */
    public function testSetQueue()
    {
        $postData = [
            'action' => 'declined',
            'quoteId' => 12345,
            'reasonForDeclining' => 'Price too high',
            'additionalComments' => 'test',
            'declinedDate' => self::DATE,
            'declinedTime' => self::TIME,
        ];
        $this->timezoneInterface->expects($this->once())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->once())->method('format')->willReturn('Y-m-d H:i:s');
        $this->customerSession->expects($this->once())->method('getUploadToQuoteActionQueue')->willReturn([]);
        $this->adminConfigHelper->expects($this->any())->method('getFormattedDate')->willReturn(self::DATETIME);
        $this->negotiableQuoteFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->negotiableQuoteFactory->expects($this->once())->method('load')->willReturnSelf();
        $this->negotiableQuoteFactory->expects($this->once())->method('getStatus')->willReturn('submitted_by_admin');

        $this->assertIsArray($this->queueHelper->setQueue($postData));
    }

    /**
     * Test setQueue for delete Item
     *
     * @return void
     */
    public function testSetQueueForDeleteItem()
    {
        $postData = [
            'action' => 'deleteItem',
            'quoteId' => 12345,
            'itemId' => 13345,
            'deletedDate' => self::DATE,
            'deletedTime' => self::TIME,
        ];
        $rateQuoteResponse = [
            'output' => [
                'rateQuote' => 'test'
            ]
        ];
        $this->timezoneInterface->expects($this->once())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->once())->method('format')->willReturn('Y-m-d H:i:s');
        $this->quoteFactory->expects($this->once())->method('create')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('load')->willReturnSelf();
        $this->fxoRateQuote->expects($this->once())->method('getFXORateQuote')->willReturn($rateQuoteResponse);
        $this->customerSession->expects($this->once())->method('getUploadToQuoteActionQueue')->willReturn([]);
        $this->adminConfigHelper->expects($this->any())->method('getFormattedDate')->willReturn(self::DATETIME);

        $this->assertIsArray($this->queueHelper->setQueue($postData));
    }

    /**
     * Test setQueue for change requested
     *
     * @return void
     */
    public function testSetQueueForChangeRequested()
    {
        $postData = [
            'action' => 'changeRequested',
            'quoteId' => 12345,
            'changeRequestedDate' => self::DATE,
            'changeRequestedTime' => self::TIME,
            'items' => [
                [
                    'name' => 'si',
                    'value' => 'test'
                ],
                [
                    'name' => 'item_id',
                    'value' => 1234
                ]
            ]
        ];
        $rateQuoteResponse = [
            'output' => [
                'alerts' => [
                    [
                        'code' => 'QCXS.SERVICE.ZERODOLLARSKU'
                    ]
                ]
            ]
        ];
        $this->timezoneInterface->expects($this->once())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->once())->method('format')->willReturn('Y-m-d H:i:s');
        $this->quoteFactory->expects($this->once())->method('create')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('load')->willReturnSelf();
        $this->fxoRateQuote->expects($this->once())->method('getFXORateQuote')->willReturn($rateQuoteResponse);
        $this->customerSession->expects($this->once())->method('getUploadToQuoteActionQueue')->willReturn([]);
        $this->adminConfigHelper->expects($this->any())->method('getFormattedDate')->willReturn(self::DATETIME);

        $this->assertIsArray($this->queueHelper->setQueue($postData));
    }

    /**
     * Test processQueue
     *
     * @return void
     */
    public function testProcessQueue()
    {
        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                    'properties' => [
                        [
                            'name' => 'USER_SPECIAL_INSTRUCTIONS',
                            'value' => 'Test'
                        ]
                    ]
                ],
            ],
        ];

        $queueData = $this->getQueueData();
        $this->customerSession->expects($this->once())->method('getUploadToQuoteActionQueue')->willReturn($queueData);
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')->willReturn(self::DATETIME);
        $this->adminConfigHelper->expects($this->any())->method('removeQuoteItem')->willReturn(true);

        $this->quoteFactory->expects($this->once())->method('create')->willReturnSelf($this->quote);
        $this->quoteFactory->expects($this->once())->method('load')->willReturnSelf();
        $this->quoteFactory->expects($this->any())->method('getItemById')->willReturn($this->quoteItem);
        $this->quoteFactory->expects($this->once())->method('getAllVisibleItems')->willReturn([0 => $this->quoteItem]);
        $this->quoteItem->expects($this->any())->method('getItemId')->willReturn(2453);
        $this->quoteItem->expects($this->any())->method('getOptionByCode')->willReturnSelf();
        $this->quoteItem->expects($this->any())->method('getOptionId')->willReturn(2);
        $this->quoteItem->expects($this->any())->method('getValue')->willReturn(json_encode($decodedData));
        $this->serializerMock->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->serializerMock->expects($this->any())->method('serialize')->willReturn('test string');
        $this->quoteItem->expects($this->any())->method('setvalue')->willReturn($this->quoteFactory);
        $this->quoteFactory->expects($this->any())->method('save')->willReturnSelf();

        $this->assertIsArray($this->queueHelper->processQueue());
    }

    /**
     * Test processQueue with unset
     *
     * @return void
     */
    public function testProcessQueueWithUnset()
    {
        $queueData = $this->getQueueData('same');
        $this->customerSession->expects($this->once())->method('getUploadToQuoteActionQueue')->willReturn($queueData);
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')->willReturn(self::DATETIME);

        $this->assertIsArray($this->queueHelper->processQueue());
    }

    /**
     * Test undoActionQueue
     *
     * @return void
     */
    public function testUndoActionQueue()
    {
        $queueData = $this->getQueueData();
        $this->customerSession->expects($this->once())->method('getUploadToQuoteActionQueue')->willReturn($queueData);
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')->willReturn(self::DATETIME);

        $this->assertIsArray($this->queueHelper->undoActionQueue('declined', 12345, 12345, ['test']));
    }

    /**
     * Test undoActionQueue with unset
     *
     * @return void
     */
    public function testUndoActionQueueWithUnset()
    {
        $queueData = $this->getQueueData('same');
        $this->customerSession->expects($this->once())->method('getUploadToQuoteActionQueue')->willReturn($queueData);
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')->willReturn(self::DATETIME);

        $this->assertIsArray($this->queueHelper->undoActionQueue('declined', 12345, 12345, ['test']));
    }

    /**
     * Test updateQuoteStatusByKey
     *
     * @return void
     */
    public function testUpdateQuoteStatusByKey()
    {
        $this->adminConfigHelper->expects($this->once())->method('updateQuoteStatusWithDeclined')->willReturn(true);

        $this->assertNull($this->queueHelper->updateQuoteStatusByKey(12345));
    }

    /**
     * Get queue data
     *
     * @param string $actionType
     * @return array
     */
    public function getQueueData($actionType = '')
    {
        $rateQuoteResponse = [
            'rateQuote' => [
                'rateQuoteDetails' => [
                    [
                        'productLines' => [
                            ['instanceId' => 2453]
                        ]
                    ]
                ]
            ]
        ];
        if ($actionType == 'same') {
            return [
                [
                    'requestedDateTime' => '2024-01-10 05:39:03',
                    'action' => 'declined',
                    'quoteId' => 12345,
                    'itemId' => 12345,
                    'reasonForDeclining' => 'Test 1',
                    'additionalComments' => 'test',
                    'declinedDate' => self::DATE,
                    'declinedTime' => self::TIME,
                ]
            ];
        } else {
            return [
                [
                    'requestedDateTime' => '2024-01-10 05:39:03',
                    'action' => 'declined',
                    'quoteId' => 12345,
                    'itemId' => 12345,
                    'reasonForDeclining' => 'Test 1',
                    'additionalComments' => 'test',
                    'declinedDate' => self::DATE,
                    'declinedTime' => self::TIME,
                ],
                [
                    'requestedDateTime' => '2024-01-10 05:39:03',
                    'action' => 'deleteItem',
                    'quoteId' => 12345,
                    'itemId' => 12345,
                    'declinedDate' => self::DATE,
                    'declinedTime' => self::TIME,
                ],
                [
                    'requestedDateTime' => '2024-01-10 05:39:03',
                    'action' => 'changeRequested',
                    'quoteId' => 12345,
                    'itemIds' => '12334',
                    'changeRequestedDate' => self::DATE,
                    'changeRequestedTime' => self::TIME,
                    'items' =>  [
                        [
                            'item_id' => 2453,
                            'si' => '242'
                        ]
                    ],
                    'rateQuoteResponse' => $rateQuoteResponse
                ],
                [
                    'requestedDateTime' => '2024-01-10 05:40:08',
                    'action' => 'request_change',
                    'quoteId' => 12345,
                    'itemId' => 12345,
                    'reasonForDeclining' => 'Test 2',
                    'additionalComments' => 'test',
                    'declinedDate' => self::DATE,
                    'declinedTime' => self::TIME,
                ]
            ];
        }
    }
}
