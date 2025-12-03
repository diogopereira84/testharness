<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SubmitOrderSidebar\Test\Unit\Block;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\RequestInterface;
use Fedex\SubmitOrderSidebar\Block\CjOrderCookies;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;

class CjOrderCookiesTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $request;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfig;
    protected $cookieManagerInterface;
    protected $cookieMetadataFactory;
    protected $publicCookie;
    protected $blockData;
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->request = $this->getMockBuilder(RequestInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->cookieManagerInterface = $this->getMockBuilder(CookieManagerInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->cookieMetadataFactory = $this->getMockBuilder(CookieMetadataFactory::class)
        ->setMethods(['createPublicCookieMetadata'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->publicCookie = $this->getMockBuilder(PublicCookieMetadata::class)
        ->setMethods(['setDurationOneYear','setPath','setDomain','setHttpOnly'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->blockData = $this->objectManager->getObject(
            CjOrderCookies::class,
            [
                'request' => $this->request,
                'toggleConfig' => $this->toggleConfig,
                'cookieManager' => $this->cookieManagerInterface,
                'cookieMetadataFactory' => $this->cookieMetadataFactory
            ]
        );
    }
    public function testsetCustomCookie()
    {
        $this->request->expects($this->any())->method('getParams')
        ->willReturn(['cjevent'=>'Ayush']);
        $this->cookieMetadataFactory->expects($this->any())
                                    ->method('createPublicCookieMetadata')
                                    ->willReturn($this->publicCookie);
        $this->publicCookie->expects($this->any())->method('setDurationOneYear')->willReturnSelf();
        $this->publicCookie->expects($this->any())->method('setPath')->willReturnSelf();
        $this->publicCookie->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->publicCookie->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieManagerInterface->expects($this->any())->method('setPublicCookie')->willReturnSelf();
        $this->assertEquals($this->cookieManagerInterface, $this->blockData->setCustomCookie());
    }

    /**
     * Test Method for Get Cookie
     */
    public function testGetCookie()
    {
        $this->cookieManagerInterface->expects($this->any())->method('getCookie')->willReturn('test');
        $this->assertEquals('test', $this->blockData->getCookie());
    }
}
