<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Test\Unit\Model\Resolver;

use Fedex\FuseBiddingQuote\Model\Resolver\GetCartUidFromQuoteId;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\TestCase;

class GetCartUidFromQuoteIdTest extends TestCase
{
    /**
     * @var QuoteIdMask|MockObject
     */
    private $quoteIdMaskResourceMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var GetCartUidFromQuoteId|MockObject
     */
    private $getCartUidFromQuoteId;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteIdMaskResourceMock = $this->getMockBuilder(QuoteIdMask::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMaskedQuoteId'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getHeader'])
            ->getMockForAbstractClass();

        $this->getCartUidFromQuoteId = new GetCartUidFromQuoteId(
            $this->quoteIdMaskResourceMock,
            $this->requestMock
        );
    }

    /**
     * Test function ResolveReturnsCartUid
     *
     * @return void
     */
    public function testResolveReturnsCartUid()
    {
        $quoteId = 123;
        $cartUid = 'masked_quote_id';
        $args = ['quote_id' => $quoteId];

        $this->requestMock->expects($this->once())
            ->method('getHeader')
            ->with('X-Unique-Header-Fuse')
            ->willReturn('xmen_fuse_bidding_quote');
        $this->quoteIdMaskResourceMock->expects($this->once())
            ->method('getMaskedQuoteId')
            ->with($quoteId)
            ->willReturn($cartUid);
        $result = $this->getCartUidFromQuoteId->resolve(
            $this->createMock(Field::class),
            null,
            $this->createMock(ResolveInfo::class),
            null,
            $args
        );

        $this->assertEquals(['cart_uid' => $cartUid], $result);
    }

    /**
     * Test function testResolveThrowsExceptionForInvalidHeader
     *
     * @return void
     */
    public function testResolveThrowsExceptionForInvalidHeader()
    {
        $this->requestMock->expects($this->once())
            ->method('getHeader')
            ->with('X-Unique-Header-Fuse')
            ->willReturn('invalid_header_value');
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Header X-Unique-Header-Fuse is missing or invalid.');

        $this->getCartUidFromQuoteId->resolve(
            $this->createMock(Field::class),
            null,
            $this->createMock(ResolveInfo::class),
            null,
            ['quote_id' => 123]
        );
    }

    /**
     * Test function testResolveThrowsExceptionForMissingQuoteId
     *
     * @return void
     */
    public function testResolveThrowsExceptionForMissingQuoteId()
    {
        $this->requestMock->expects($this->once())
            ->method('getHeader')
            ->with('X-Unique-Header-Fuse')
            ->willReturn('xmen_fuse_bidding_quote');
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('quote_id value must be specified.');

        $this->getCartUidFromQuoteId->resolve(
            $this->createMock(Field::class),
            null,
            $this->createMock(ResolveInfo::class),
            null,
            []
        );
    }
}
