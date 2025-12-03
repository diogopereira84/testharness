<?php

/**
 * Copyright © By Fedex All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Login\Test\Unit\App\Request;

use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Login\App\Request\Http;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;

class HttpTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManagerInstance;
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var Http
     */
    private $http;
    private $cookieManager;
    private mixed $pathInfo;

    protected function setUp(): void
    {
        $this->objectManagerInstance = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $this->cookieManager = $this->getMockBuilder(CookieManagerInterface::class)
            ->setMethods(['getCookie'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->http = $this->objectManager->getObject(
            Http::class,
            [
                'objectManager' => $this->objectManagerInstance
            ]
        );
    }

    /**
     * Test case for getPathInfo
     */
    public function testgetPathInfo()
    {
        $_SESSION['customer_base']['ondemand_company_info']['company_data']['company_url_extention'] = "testcompany";
        $_SERVER['REQUEST_URI'] = '/ondemand';
        $this->pathInfo = null;
        $consecutive = $this->onConsecutiveCalls($this->cookieManager);
        $this->objectManagerInstance->expects($this->any())
            ->method('get')
            ->willReturn($consecutive);
        $this->cookieManager->expects($this->any())
            ->method('getCookie')
            ->willReturn("l6site51");

        $this->http->getPathInfo();
    }

    /**
     * Test case for getPathInfoWithoutSession Data
     */
    public function testgetPathInfoWithoutSessionData()
    {
        $this->pathInfo = null;
        $consecutive = $this->onConsecutiveCalls($this->cookieManager);
        $this->objectManagerInstance->expects($this->any())
            ->method('get')
            ->willReturn($consecutive);
        $this->cookieManager->expects($this->any())
            ->method('getCookie')
            ->willReturn("l6site51");
        $this->http->getPathInfo();
    }
}
