<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogSearch\Test\Unit\ViewModel;

use Magento\Customer\Model\Session;
use Fedex\CatalogSearch\ViewModel\CatalogSearchResult;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class CatalogSearchResultTest extends TestCase
{
    protected $customer;
    protected $productCollectionFactory;
    protected $catalogSearchResult;
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * Setup mock objects
     */
    protected function setUp(): void
    {
        $this->customerSession = $this->getMockBuilder(Session::class)
        ->setMethods(['getCustomer','getCustomerCompany','getApiAccessToken','getApiAccessType','getGatewayToken'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
        ->disableOriginalConstructor()
        ->setMethods(['getGroupId'])
        ->getMock();

        $this->productCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create','getSelect', 'join','where'])
        ->getMock();

        $this->toggleConfig = $this->createMock(ToggleConfig::class);

        $this->catalogSearchResult = (new ObjectManager($this))->getObject(
            CatalogSearchResult::class,
            [
                '_customerSession' => $this->customerSession,
                '_toggleConfig' => $this->toggleConfig
            ]
        );
    }

    /**
     * @test searchProductCollection
     */
    public function testSearchProductCollection()
    {
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getGroupId')->willReturn(12);
        $this->productCollectionFactory->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->productCollectionFactory->expects($this->any())->method('join')->willReturnSelf();
        $this->productCollectionFactory->expects($this->any())->method('where')->willReturnSelf();
        $this->assertEquals(
            $this->productCollectionFactory,
            $this->catalogSearchResult->searchProductCollection($this->productCollectionFactory)
        );
    }
}
