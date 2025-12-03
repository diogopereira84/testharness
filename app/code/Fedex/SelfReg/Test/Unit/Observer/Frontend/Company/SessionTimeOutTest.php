<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Test\Unit\Observer\Frontend\Company;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Observer\Frontend\Company\SessionTimeOut;
use Magento\Company\Model\CompanyFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Context;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\Base\Helper\Auth;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SessionTimeOutTest extends TestCase
{
    protected $companyMock;
    protected $companyCollection;
    protected $responseMock;
    protected $observerMock;
    protected $contextMock;
    protected $eventMock;
    protected $sdeHelper;
    protected $sessionTimeOutMock;
    /**
     * @var ObjectManager $_objectManager
     */
    protected $_objectManager;

    /**
     * @var SessionTimeOut $sessionTimeOut
     */
    protected $sessionTimeOut;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * @var StoreManagerInterface $storeManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var CompanyFactory $companyFactory
     */
    protected $companyFactory;

    /**
     * @var Session $customerSession
     */
    protected $customerSession;

    /**
     * @var UrlInterface $urlInterface
     */
    protected $urlInterface;

    /**
     * @var Store|MockObject
     */
    protected $storeMock;

    protected Auth|MockObject $baseAuthMock;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getToggleConfigValue',
                ]
            )
            ->getMock();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn'])
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->companyFactory = $this->getMockBuilder(CompanyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->companyMock = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getCollection'])
            ->getMock();

        $this->companyCollection = $this->getMockBuilder(\Magento\Company\Model\ResourceModel\Company\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIterator', 'addFieldToFilter', 'getFirstItem'])
            ->getMock();

        $this->storeManagerInterface = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode', 'getBaseUrl', 'getUrl'])
            ->getMock();

        $this->urlInterface = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMockForAbstractClass();

        $this->responseMock = $this->getMockBuilder(\Magento\Framework\App\Response\Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRedirect'])
            ->getMock();

        $this->observerMock = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent', 'getControllerAction', 'getResponse'])
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->setMethods(['getModuleName', 'getFullActionName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getIsSdeStore'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->_objectManager = new ObjectManager($this);

        $this->sessionTimeOutMock = $this->_objectManager->getObject(
            SessionTimeOut::class,
            [
                'storeManagerInterface' => $this->storeManagerInterface,
                'companyFactory' => $this->companyFactory,
                'customerSession' => $this->customerSession,
                'url' => $this->urlInterface,
                'toggleConfig' => $this->toggleConfig,
                'sdeHelper' => $this->sdeHelper,
                'authHelper' => $this->baseAuthMock
            ]
        );
    }

    /**
     * Test excute with toggle Enable
     */
    public function testExecuteWithToggleEnable()
    {
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(0);
        $this->observerMock->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->any())->method('getRequest')->willReturn($this->contextMock);
        $this->contextMock->expects($this->any())->method('getModuleName')->willReturn('not_punchout');

        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('l6site51');

        $this->storeMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/l6site51/');

        $this->urlInterface->expects($this->any())
            ->method('getCurrentUrl')
            ->willReturn('https://staging3.office.fedex.com/l6site51/checkout');

        $this->storeMock
            ->expects($this->exactly(2))
            ->method('getUrl')
            ->withConsecutive(['session-timeout'], ['success'])
            ->willReturnOnConsecutiveCalls(
                'https://staging3.office.fedex.com/l6site51/session-timeout',
                'https://staging3.office.fedex.com/l6site51/success'
            );

        $this->companyFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->companyMock);

        $this->companyMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->companyCollection);

        $this->companyCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->companyCollection->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->companyMock);

        $this->companyMock->expects($this->any())
            ->method('getId')
            ->willReturn(23);

        $this->companyMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->companyCollection);

        $this->observerMock->expects($this->any())
            ->method('getControllerAction')
            ->willReturnSelf();

        $this->observerMock->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);

        $this->responseMock->expects($this->any())
            ->method('setRedirect')
            ->with('https://staging3.office.fedex.com/l6site51/session-timeout')
            ->willReturnSelf();

        $this->assertNull($this->sessionTimeOutMock->execute($this->observerMock));
    }

    /**
     * Test excute with toggle OFF
     */
    public function testExecuteInNegativeCase()
    {
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('l6site51');

        $this->observerMock->expects($this->any())->method('getEvent')->willReturn($this->eventMock);

        $this->eventMock->expects($this->any())->method('getRequest')->willReturn($this->contextMock);

        $this->assertNull($this->sessionTimeOutMock->execute($this->observerMock));
    }

}
