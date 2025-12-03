<?php

/**
 * Copyright © By Fedex All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Login\Test\Unit\Plugin;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig as ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store as CoreStore;
use Magento\Customer\Model\SessionFactory;
use Magento\Customer\Model\Session;
use Fedex\Login\Plugin\Store;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\Base\Helper\Auth;
use PHPUnit\Framework\MockObject\MockObject;

class StoreTest extends \PHPUnit\Framework\TestCase
{
    protected $toggleConfigMock;
    /**
     * @var CoreStore
     */
    private $coreStore;
    /**
     * @var SessionFactory
     */
    private $sessionFactory;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var Store
     */
    private $store;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    protected Auth|MockObject $baseAuthMock;

    protected function setUp(): void
    {
        $this->coreStore = $this->getMockBuilder(CoreStore::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode'])
            ->getMockForAbstractClass();
        $this->sessionFactory = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn', 'getOndemandCompanyInfo'])
            ->getMock();
        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->store = $this->objectManager->getObject(
            Store::class,
            [
                'sessionFactory' => $this->sessionFactory,
                'storeManager' => $this->storeManager,
                'authHelper' => $this->baseAuthMock,
                'toggleConfig' => $this->toggleConfigMock,
            ]
        );
    }

    /**
     * Test case for afterGetBaseUrl
     */
    public function testAfterGetBaseUrl()
    {
        $_SERVER['REQUEST_URI'] = '/ondemand';
        $companyData = [];
        $companyData['company_data']['company_url_extention'] = "testcompany";
        $this->sessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->session);
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->session->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn($companyData);
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->coreStore);
        $this->coreStore->expects($this->any())
            ->method('getCode')
            ->willReturn('ondemand');
        $result = $this->store->afterGetBaseUrl($this->coreStore, "http://stging3.office.fedex.com/ondemand/", "link");
        $this->assertEquals("http://stging3.office.fedex.com/ondemand/testcompany/", $result);
    }

    /**
     * Test case for afterGetUrl
     */
    public function testAfterGetUrl()
    {
        $companyData = [];
        $_SERVER['REQUEST_URI'] = '/ondemand';
        $companyData['company_data']['company_url_extention'] = "testcompany";
        $this->sessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->session);
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->coreStore);
        $this->coreStore->expects($this->any())
            ->method('getCode')
            ->willReturn('ondemand');
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->session->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn($companyData);
        $result = $this->store->afterGetUrl($this->coreStore, "http://stging3.office.fedex.com/ondemand/");
        $this->assertEquals("http://stging3.office.fedex.com/ondemand/testcompany/", $result);
    }

    /**
     * testGetToggleStatusForPerformanceImprovmentPhasetwo
     * @return void
     */
    public function testGetToggleStatusForPerformanceImprovmentPhasetwo()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->store->getToggleStatusForPerformanceImprovmentPhasetwo());
    }

}
