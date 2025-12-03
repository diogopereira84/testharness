<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 * <!-- B-1053021 - Sanchit Bhatia - RT-ECVS - ePro - Search Capability for Quotes  -->
 */

namespace Fedex\Orderhistory\Test\Unit\Ui\DataProvider;

use Magento\Framework\App\Helper\Context;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\Helper\Data;
use Fedex\Orderhistory\Ui\DataProvider\DataProvider;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\AbstractSimpleObject;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteRepository;

class DataProviderTest extends \PHPUnit\Framework\TestCase
{
    
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Magento\Framework\App\Request\Http & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $requestMock;
    protected $helperDataMock;
    protected $session;
    protected $filterBuilder;
    protected $searchCriteriaBuilder;
    protected $storeManager;
    protected $storeInterfaceMock;
    protected $storeMock;
    protected $searchCriteria;
    protected $negotiableQuoteRepository;
    protected $authorization;
    protected $structure;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $dataProviderMock;
    /**
     * Is called before running a test
     */
    protected function setUp(): void
    {
        $this->contextMock = $this
            ->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this
            ->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->setMethods(['getFullActionName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->helperDataMock = $this
            ->getMockBuilder(\Fedex\Orderhistory\Helper\Data::class)
            ->setMethods(['isModuleEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->session = $this
            ->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->setMethods(['create','getSearchdata'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->filterBuilder = $this
            ->getMockBuilder(\Magento\Framework\Api\FilterBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->storeInterfaceMock = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsite'])
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreIds'])
            ->getMock();
        
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);

        $this->searchCriteria = $this->createMock(SearchCriteria::class);

        $this->negotiableQuoteRepository = $this->getMockBuilder(NegotiableQuoteRepository::class)
        ->disableOriginalConstructor()
        ->setMethods(['getList'])
        ->getMockForAbstractClass();
        
        $this->authorization = $this->getMockBuilder(\Magento\Company\Api\AuthorizationInterface::class)
        ->disableOriginalConstructor()
        ->setMethods(['isAllowed'])
        ->getMockForAbstractClass();
        
        $this->structure = $this->getMockBuilder(\Magento\Company\Model\Company\Structure::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->dataProviderMock = $this->objectManager->getObject(
            DataProvider::class,
            [
                'request' => $this->requestMock,
                'customerSession' => $this->session,
                'filterBuilder' => $this->filterBuilder,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'storeManager' => $this->storeManager,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'searchCriteria' => $this->searchCriteria,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'helper' => $this->helperDataMock,
                'authorization' => $this->authorization,
                'structure' => $this->structure
                
            ]
        );
    }

    /**
     * Test
     * <!-- B-1053021 - Sanchit Bhatia - RT-ECVS - ePro - Search Capability for Quotes  -->
     */
    public function testGetSearchResult()
    {
            $customerid = ['1','2'];
            $this->authorization->expects($this->any())->method('isAllowed')->willReturn(true);
            $this->structure->expects($this->any())->method('getAllowedChildrenIds')->willReturn($customerid);
            $filter = $this->createMock(Filter::class);
            $this->filterBuilder->expects($this->any())->method('setField')->willReturnSelf();
            $this->filterBuilder->expects($this->any())->method('setConditionType')->willReturnSelf();
            $this->filterBuilder->expects($this->any())->method('setValue')->willReturnSelf();
            $this->filterBuilder->expects($this->any())->method('create')->willReturn($filter);
            $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);
            $this->storeInterfaceMock->expects($this->any())->method('getWebsite')->willReturn($this->storeMock);
            $this->storeMock->expects($this->any())->method('getStoreIds')->willReturn(50);
            $this->helperDataMock->expects($this->any())->method('isModuleEnabled')->willReturn(true);
            $this->session->expects($this->any())->method('getSearchdata')->willReturn('asd');
            $this->searchCriteria->expects($this->any())->method('setRequestName')->willReturnSelf();
            $this->searchCriteriaBuilder->expects($this->any())->method('create')->willReturn($this->searchCriteria);
            $searchResult = $this->getMockForAbstractClass(SearchResultsInterface::class);
            $this->negotiableQuoteRepository->expects($this->any())->method('getList')->willReturn($searchResult);
            $this->dataProviderMock->getSearchResult();
    }
    public function testGetSearchResultWithInt()
    {
            $customerid = ['1','2'];
            $this->authorization->expects($this->any())->method('isAllowed')->willReturn(true);
            $this->structure->expects($this->any())->method('getAllowedChildrenIds')->willReturn($customerid);
            $filter = $this->createMock(Filter::class);
            $this->filterBuilder->expects($this->any())->method('setField')->willReturnSelf();
            $this->filterBuilder->expects($this->any())->method('setConditionType')->willReturnSelf();
            $this->filterBuilder->expects($this->any())->method('setValue')->willReturnSelf();
            $this->filterBuilder->expects($this->any())->method('create')->willReturn($filter);
            $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);
            $this->storeInterfaceMock->expects($this->any())->method('getWebsite')->willReturn($this->storeMock);
            $this->storeMock->expects($this->any())->method('getStoreIds')->willReturn(50);
            $this->helperDataMock->expects($this->any())->method('isModuleEnabled')->willReturn(true);
            $this->session->expects($this->any())->method('getSearchdata')->willReturn('12');
            $this->searchCriteria->expects($this->any())->method('setRequestName')->willReturnSelf();
            $this->searchCriteriaBuilder->expects($this->any())->method('create')->willReturn($this->searchCriteria);
            $searchResult = $this->getMockForAbstractClass(SearchResultsInterface::class);
            $this->negotiableQuoteRepository->expects($this->any())->method('getList')->willReturn($searchResult);
            $this->dataProviderMock->getSearchResult();
    }
    public function testGetSearchResultWithSepo()
    {
            $customerid = ['1','2'];
            $this->authorization->expects($this->any())->method('isAllowed')->willReturn(true);
            $this->structure->expects($this->any())->method('getAllowedChildrenIds')->willReturn($customerid);
            $filter = $this->createMock(Filter::class);
            $this->filterBuilder->expects($this->any())->method('setField')->willReturnSelf();
            $this->filterBuilder->expects($this->any())->method('setConditionType')->willReturnSelf();
            $this->filterBuilder->expects($this->any())->method('setValue')->willReturnSelf();
            $this->filterBuilder->expects($this->any())->method('create')->willReturn($filter);
            $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);
            $this->storeInterfaceMock->expects($this->any())->method('getWebsite')->willReturn($this->storeMock);
            $this->storeMock->expects($this->any())->method('getStoreIds')->willReturn(50);
            $this->helperDataMock->expects($this->any())->method('isModuleEnabled')->willReturn(true);
            $this->session->expects($this->any())->method('getSearchdata')->willReturn('SEPO');
            $this->searchCriteria->expects($this->any())->method('setRequestName')->willReturnSelf();
            $this->searchCriteriaBuilder->expects($this->any())->method('create')->willReturn($this->searchCriteria);
            $searchResult = $this->getMockForAbstractClass(SearchResultsInterface::class);
            $this->negotiableQuoteRepository->expects($this->any())->method('getList')->willReturn($searchResult);
            $this->dataProviderMock->getSearchResult();
    }
    public function testGetSearchResultWithCombine()
    {
            $customerid = ['1','2'];
            $this->authorization->expects($this->any())->method('isAllowed')->willReturn(true);
            $this->structure->expects($this->any())->method('getAllowedChildrenIds')->willReturn($customerid);
            $filter = $this->createMock(Filter::class);
            $this->filterBuilder->expects($this->any())->method('setField')->willReturnSelf();
            $this->filterBuilder->expects($this->any())->method('setConditionType')->willReturnSelf();
            $this->filterBuilder->expects($this->any())->method('setValue')->willReturnSelf();
            $this->filterBuilder->expects($this->any())->method('create')->willReturn($filter);
            $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);
            $this->storeInterfaceMock->expects($this->any())->method('getWebsite')->willReturn($this->storeMock);
            $this->storeMock->expects($this->any())->method('getStoreIds')->willReturn(50);
            $this->helperDataMock->expects($this->any())->method('isModuleEnabled')->willReturn(true);
            $this->session->expects($this->any())->method('getSearchdata')->willReturn('12-SEPO');
            $this->searchCriteria->expects($this->any())->method('setRequestName')->willReturnSelf();
            $this->searchCriteriaBuilder->expects($this->any())->method('create')->willReturn($this->searchCriteria);
            $searchResult = $this->getMockForAbstractClass(SearchResultsInterface::class);
            $this->negotiableQuoteRepository->expects($this->any())->method('getList')->willReturn($searchResult);
            $this->dataProviderMock->getSearchResult();
    }
    public function testGetSearchResultWithDash()
    {
            $customerid = ['1','2'];
            $this->authorization->expects($this->any())->method('isAllowed')->willReturn(true);
            $this->structure->expects($this->any())->method('getAllowedChildrenIds')->willReturn($customerid);
            $filter = $this->createMock(Filter::class);
            $this->filterBuilder->expects($this->any())->method('setField')->willReturnSelf();
            $this->filterBuilder->expects($this->any())->method('setConditionType')->willReturnSelf();
            $this->filterBuilder->expects($this->any())->method('setValue')->willReturnSelf();
            $this->filterBuilder->expects($this->any())->method('create')->willReturn($filter);
            $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);
            $this->storeInterfaceMock->expects($this->any())->method('getWebsite')->willReturn($this->storeMock);
            $this->storeMock->expects($this->any())->method('getStoreIds')->willReturn(50);
            $this->helperDataMock->expects($this->any())->method('isModuleEnabled')->willReturn(true);
            $this->session->expects($this->any())->method('getSearchdata')->willReturn('12-');
            $this->searchCriteria->expects($this->any())->method('setRequestName')->willReturnSelf();
            $this->searchCriteriaBuilder->expects($this->any())->method('create')->willReturn($this->searchCriteria);
            $searchResult = $this->getMockForAbstractClass(SearchResultsInterface::class);
            $this->negotiableQuoteRepository->expects($this->any())->method('getList')->willReturn($searchResult);
            $this->dataProviderMock->getSearchResult();
    }
}
