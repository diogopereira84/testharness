<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Test\Unit\Model\Resolver;

use Fedex\FuseBiddingQuote\Model\Resolver\RetreiveBidLetter;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Context;
use Fedex\FuseBiddingQuote\Helper\FuseBidGraphqlHelper;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\View\LayoutInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;

/**
 * Test class for RetreiveBidLetter
 */
class RetreiveBidLetterTest extends TestCase
{
    /**
     * @var (\Magento\GraphQl\Model\Query\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $quote;
    protected $retreiveBidLetter;
    /**
     * @var FuseBidGraphqlHelper $fuseBidGraphqlHelper
     */
    protected $fuseBidGraphqlHelper;

    /**
     * @var QuoteIdMask $quoteIdMaskResource
     */
    protected $quoteIdMaskResource;

    /**
     * @var Filesystem $filesystem
     */
    protected $filesystem;

    /**
     * @var QuoteEmailHelper $quoteEmailHelper
     */
    protected $quoteEmailHelper;

    /**
     * @var FilterProvider $filterProvider
     */
    protected $filterProvider;

    /**
     * @var LayoutInterface $layout
     */
    protected $layout;

    /**
     * @var CartRepositoryInterface $quoteRepository
     */
    protected $quoteRepository;

    /**
     * @var Dompdf $dompdf
     */
    protected $dompdf;

    /**
     * @var Options $options
     */
    protected $options;

    /**
     * Setup mock
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->fuseBidGraphqlHelper = $this->getMockBuilder(FuseBidGraphqlHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['validateCartUid', 'validateTemplate'])
            ->getMock();
        
        $this->quoteIdMaskResource = $this->getMockBuilder(QuoteIdMask::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUnmaskedQuoteId'])
            ->getMock();

        $this->filterProvider = $this->getMockBuilder(FilterProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBlockFilter', 'filter'])
            ->getMock();

        $this->layout = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['createBlock', 'setData', 'setTemplate', 'toHtml'])
            ->getMockForAbstractClass();

        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->dompdf = $this->getMockBuilder(Dompdf::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setOptions',
                'setHttpContext',
                'load_html',
                'setPaper',
                'render',
                'output'
            ])->getMock();
        
        $this->options = $this->getMockBuilder(Options::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'set'
            ])->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods(['getId', 'getCustomerEmail'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->retreiveBidLetter = new RetreiveBidLetter(
            $this->fuseBidGraphqlHelper,
            $this->quoteIdMaskResource,
            $this->filterProvider,
            $this->layout,
            $this->quoteRepository,
            $this->dompdf,
            $this->options
        );
    }

    /**
     * Test Resolve method
     *
     * @return void
     */
    public function testResolve()
    {
        $args = ['uid' => '132123124543DSDS', 'template' => 'NBC_SUPPORT'];
        $this->quoteRepository->expects($this->once())->method('get')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getId')->willReturn(123);
        $this->quote->expects($this->once())->method('getCustomerEmail')->willReturn('abc@test.com');
        $this->layout->expects($this->once())->method('createBlock')->willReturnSelf();
        $this->layout->expects($this->once())->method('setData')->willReturnSelf();
        $this->layout->expects($this->once())->method('setTemplate')->willReturnSelf();
        $this->layout->expects($this->once())->method('toHtml')->willReturn('<span>test</span>');
        $this->filterProvider->expects($this->once())->method('getBlockFilter')->willReturnSelf();
        $this->filterProvider->expects($this->once())->method('filter')->willReturn('<span>test</span>');
        $this->dompdf->expects($this->once())->method('output')->willReturn('242dfw=');
        $result = $this->retreiveBidLetter->resolve(
            $this->createMock(Field::class),
            $this->createMock(Context::class),
            $this->createMock(ResolveInfo::class),
            null,
            $args
        );

        $this->assertIsArray($result);
    }

}
