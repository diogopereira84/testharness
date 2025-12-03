<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Model\QuoteHistory;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\UploadToQuote\Model\QuoteHistory\GetAllQuotes;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\SortOrder;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\UploadToQuote\Model\ResourceModel\QuoteGrid\CollectionFactory as NegQuoteCollectionFactory;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class GetAllQuotesTest extends TestCase
{
    protected $getAllQuotesMock;
    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var NegQuoteCollectionFactory $negQuoteCollectionFactory
     */
    protected $negQuoteCollectionFactory;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * Set up method.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

        $this->userContext = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUserId'])
            ->getMockForAbstractClass();

        $this->negQuoteCollectionFactory = $this->getMockBuilder(NegQuoteCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'create',
                'addFieldToSelect',
                'addFieldToFilter',
                'getTable',
                'getSelect',
                'columns',
                'join',
                'setOrder',
                'setPageSize',
                'setCurPage'
            ])
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->getAllQuotesMock = $objectManagerHelper->getObject(
            GetAllQuotes::class,
            [
                'request' => $this->request,
                'userContext' => $this->userContext,
                'negQuoteCollectionFactory' => $this->negQuoteCollectionFactory,
                'toggleConfig'  => $this->toggleConfig
            ]
        );
    }

    /**
     * Test method for getAllNegotiableQuote
     *
     * @return void
     */
    public function testGetSearchResult()
    {
        $this->request->expects($this->any())->method('getParam')->willReturn(true);
        $this->negQuoteCollectionFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->negQuoteCollectionFactory->expects($this->any())->method('getTable')->willReturn('quote');
        $this->negQuoteCollectionFactory->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->negQuoteCollectionFactory->expects($this->any())->method('columns')->willReturnSelf();
        $this->negQuoteCollectionFactory->expects($this->once())->method('join')->willReturnSelf();
        $this->negQuoteCollectionFactory->expects($this->once())->method('addFieldToSelect')->willReturnSelf();
        $this->negQuoteCollectionFactory->expects($this->any())->method('addFieldToFilter')->willReturnSelf();

        $this->assertNotNull($this->getAllQuotesMock->getAllNegotiableQuote());
    }

    /**
     * Test method for getAllNegotiableQuote with ASC order
     *
     * @return void
     */
    public function testGetSearchResultWithAscOrder()
    {
        $this->request->expects($this->any())->method('getParam')->willReturn('ASC');
        $this->negQuoteCollectionFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->negQuoteCollectionFactory->expects($this->once())->method('addFieldToSelect')->willReturnSelf();
        $this->negQuoteCollectionFactory->expects($this->any())->method('addFieldToFilter')->willReturnSelf();

        $this->assertNotNull($this->getAllQuotesMock->getAllNegotiableQuote());
    }
}
