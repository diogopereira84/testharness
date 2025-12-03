<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Test\Unit\Model\Resolver;

use Fedex\FuseBiddingQuote\Model\Resolver\UpdateQuoteDiscount;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Fedex\UploadToQuote\Helper\GraphqlApiHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;
use Fedex\FuseBiddingQuote\Helper\FuseBidGraphqlHelper;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;
use Fedex\FuseBiddingQuote\Helper\FuseBidHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class UpdateQuoteDiscountTest extends TestCase
{
    /**
     * @var UpdateQuoteDiscount|MockObject
     */
    private $updateQuoteDiscount;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var QuoteIdMask|MockObject
     */
    private $quoteIdMaskResourceMock;

    /**
     * @var GraphqlApiHelper|MockObject
     */
    private $graphqlApiHelperMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var FuseBidGraphqlHelper|MockObject
     */
    private $fuseBidGraphqlHelperMock;

    /**
     * @var Quote|MockObject
     */
    private $quoteMock;

    /**
     * @var Item|MockObject
     */
    private $quoteItemMock;

    /**
     * @var FuseBidHelper|MockObject
     */
    protected $fuseBidHelper;

    /**
     * Setup function
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        
        $this->quoteIdMaskResourceMock = $this->getMockBuilder(QuoteIdMask::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUnmaskedQuoteId'])
            ->getMock();
        
        $this->graphqlApiHelperMock = $this->getMockBuilder(GraphqlApiHelper::class)
            ->setMethods(['getQuoteContactInfo', 'getQuoteLineItems', 'getFxoAccountNumberOfQuote',
                'getQuoteCompanyName', 'getQuoteNotes','getQuoteInfo','getRateResponse'
                ,'addLogsForGraphqlApi','getRateResponseForDiscount'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();
        
        $this->fuseBidGraphqlHelperMock = $this->getMockBuilder(FuseBidGraphqlHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['validateToggleConfig'])
            ->getMock();
        
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['save','getAllVisibleItems'])
            ->getMock();
        $this->quoteItemMock= $this->getMockBuilder(Item::class)
            ->setMethods(
                [
                    'getOptionByCode',
                    'setValue',
                    'getValue',
                    'save'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->fuseBidHelper = $this->getMockBuilder(FuseBidHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isToggleTeamMemberInfoEnabled'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->updateQuoteDiscount = $objectManagerHelper->getObject(
            UpdateQuoteDiscount::class,
            [
                'quoteRepository' => $this->quoteRepositoryMock,
                'quoteIdMaskResource' => $this->quoteIdMaskResourceMock,
                'graphqlApiHelper' => $this->graphqlApiHelperMock,
                'fuseBidGraphqlHelper' => $this->fuseBidGraphqlHelperMock,
                'logger' => $this->loggerMock,
                'fuseBidHelper' => $this->fuseBidHelper
            ]
        );
    }

    /**
     * Test Resolve method
     *
     * @return void
     */
    public function testResolve()
    {
        $args = [
            'uid' => '123',
            'discountIntent' => 'apply_discount'
        ];
        $quoteId = 1;
        $this->fuseBidGraphqlHelperMock->expects($this->once())
            ->method('validateToggleConfig')
            ->willReturn(true);
        $this->quoteIdMaskResourceMock->expects($this->once())
            ->method('getUnmaskedQuoteId')
            ->with($args['uid'])
            ->willReturn($quoteId);
        $this->quoteRepositoryMock->expects($this->once())
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);
        $this->quoteMock->method('getAllVisibleItems')->willReturn([$this->quoteItemMock]);
        $this->quoteItemMock->method('getValue')->willReturn(json_encode(['key' => 'value']));
        $this->quoteItemMock->method('getOptionByCode')->with('info_buyRequest')->willReturn($this->quoteItemMock);

        $this->quoteItemMock->expects($this->once())->method('setValue')
        ->with(json_encode(['key' => 'value', 'discountIntent' => 'apply_discount']));
        $this->quoteItemMock->expects($this->once())->method('save');
        $quoteResponse = [
            'quote_id' => $quoteId,
            'quote_status' => 'created',
            'hub_centre_id' => '123',
            'location_id' => '123',
            'quote_creation_date' => '2024-01-01',
            'quote_updated_date' => '2024-01-02',
            'quote_expiration_date' => '2024-01-10',
            'contact_info' => null,
            'rateSummary' => [],
            'line_items' => [],
            'fxo_print_account_number' => '',
            'activities' => []
        ];
        $this->graphqlApiHelperMock->expects($this->once())
            ->method('getQuoteInfo')
            ->with($this->quoteMock)
            ->willReturn($quoteResponse);
        $resolveInfoMock = $this->createMock(ResolveInfo::class);
        $fieldMock = $this->createMock(Field::class);
        $result = $this->updateQuoteDiscount->resolve($fieldMock, null, $resolveInfoMock, null, $args);

        $this->assertIsArray($result);
    }

    /**
     * Test the resolve method with exception.
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testResolveThrowException()
    {
        $errorMsg = 'Some error message';
        $args = [
            'uid' => '123',
            'discountIntent' => 'apply_discount'
        ];
        $quoteId = 1;
        $this->fuseBidGraphqlHelperMock->expects($this->once())
            ->method('validateToggleConfig')
            ->willReturn(true);
        $this->quoteIdMaskResourceMock->expects($this->once())
            ->method('getUnmaskedQuoteId')
            ->with($args['uid'])
            ->willReturn($quoteId);
        $this->quoteRepositoryMock->expects($this->once())
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);
        $this->quoteMock->method('getAllVisibleItems')->willReturn([$this->quoteItemMock]);
        $this->quoteItemMock->method('getValue')->willReturn(json_encode(['key' => 'value']));
        $this->quoteItemMock->method('getOptionByCode')
        ->with('info_buyRequest')->willReturn($this->quoteItemMock);
        $this->quoteItemMock->expects($this->once())->method('setValue')
        ->with(json_encode(['key' => 'value', 'discountIntent' => 'apply_discount']));
        $this->quoteItemMock->expects($this->once())->method('save');
        $this->graphqlApiHelperMock->expects($this->once())
            ->method('getQuoteInfo')
            ->with($this->quoteMock)
            ->willThrowException(new \Exception($errorMsg));

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage($errorMsg);

        $this->updateQuoteDiscount->resolve(
            $this->createMock(Field::class),
            null,
            $this->createMock(ResolveInfo::class),
            null,
            $args
        );
    }
}
