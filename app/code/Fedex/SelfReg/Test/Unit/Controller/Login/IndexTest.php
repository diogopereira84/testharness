<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Test\Unit\Controller\Login;

use Fedex\SelfReg\Controller\Login\Index;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;
use Fedex\SSO\Helper\Data as SSOHelper;

class IndexTest extends TestCase
{
    protected $cookieManagerMock;
    protected $selfRegHelperMock;
    protected $urlInterfaceMock;
    protected $redirectFactoryMock;
    protected $sessionFactoryMock;
    protected $sessionMock;
    protected $redirectMock;
    protected $loggerMock;
    protected $ssoHelperMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $selfRegLoginMock;
    protected $contextMock;
    protected $ssoConfigMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->ssoConfigMock = $this->getMockBuilder(SsoConfiguration::class)
            ->setMethods(['getConfigValue', 'getGeneralConfig'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieManagerMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->setMethods(['getCookie'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->selfRegHelperMock = $this->getMockBuilder(SelfReg::class)
            ->setMethods(['selfRegWlgnLogin'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
            ->setMethods(['getUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->redirectFactoryMock = $this->getMockBuilder(RedirectFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionFactoryMock = $this->getMockBuilder(\Magento\Customer\Model\SessionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionMock = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->setMethods(['setSelfRegLoginError'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Redirect::class)
            ->setMethods(['setUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();

        $this->ssoHelperMock = $this->getMockBuilder(SSOHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFCLCookieNameToggle', 'getFCLCookieConfigValue'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->selfRegLoginMock = $this->objectManager->getObject(
            Index::class,
            [
                'ssoConfig' => $this->ssoConfigMock,
                'cookieManager' => $this->cookieManagerMock,
                'selfRegHelper' => $this->selfRegHelperMock,
                'url' => $this->urlInterfaceMock,
                'resultRedirectFactory' => $this->redirectFactoryMock,
                'sessionFactory' => $this->sessionFactoryMock,
                'logger' => $this->loggerMock,
                'ssoHelper' => $this->ssoHelperMock,
            ]
        );

    }

    /**
     * Test for  executeWithSuccessfullyLogin
     *
     * @return  PageFactory
     */
    public function testExecute()
    {
        $fdxLogin = 'ssotest-cos2.a342.5086d6d70b8139b248d0bee7b9715755';
        $endUrl = 'https://api.test.office.fedex.com/customer/fedexoffice/v1/profiles';

        $loginSuccess = ['error' => false, 'redirectUrl' => 'https://staging3.office.fedex.com/me/'];

        $this->ssoConfigMock->expects($this->any())->method('getConfigValue')->willReturn($endUrl);
        $this->ssoHelperMock->expects($this->any())->method('getFCLCookieNameToggle')->willReturn(true);
        $this->ssoHelperMock->expects($this->any())->method('getFCLCookieConfigValue')->willReturn('sdfds');
        $this->cookieManagerMock->expects($this->any())->method('getCookie')->willReturn($fdxLogin);
        $this->selfRegHelperMock->expects($this->any())->method('selfRegWlgnLogin')->willReturn($loginSuccess);

        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);
        $this->redirectMock->expects($this->any())->method('setUrl')->willReturnSelf();

        $result = $this->selfRegLoginMock->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }

    /**
     * Test for testExecuteLogginFail
     *
     * @return array
     */
    public function testExecuteLogginFail()
    {
        $fdxLogin = 'ssotest-cos2.a342.5086d6d70b8139b248d0bee7b9715755';
        $endUrl = 'https://api.test.office.fedex.com/customer/fedexoffice/v1/profiles';
        $logoutUrl = 'https://api.test.office.fedex.com/customer/fedexoffice/v1/logout';
        $loginFailUrl = 'https://staging3.office.fedex.com/me/selfreg/login/fail';
        $loginError = ['error' => true, 'msg' => 'Login Fail'];

        $this->ssoConfigMock->expects($this->any())->method('getConfigValue')->willReturn($endUrl);
        $this->cookieManagerMock->expects($this->any())->method('getCookie')->willReturn($fdxLogin);
        $this->selfRegHelperMock->expects($this->any())->method('selfRegWlgnLogin')->willReturn($loginError);

        $this->urlInterfaceMock->expects($this->any())->method('getUrl')->willReturn($loginFailUrl);
        $this->ssoConfigMock->expects($this->any())->method('getGeneralConfig')->willReturn($logoutUrl);

        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);
        $this->redirectMock->expects($this->any())->method('setUrl')->willReturnSelf();

        $result = $this->selfRegLoginMock->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }

    /**
     * Test for testExecuteWlgnLoginFail
     *
     * @return array
     */
    public function testExecuteWlgnLoginFail()
    {
        $fdxLogin = '';
        $loginFailUrl = 'https://staging3.office.fedex.com/me/selfreg/login/fail';

        $this->cookieManagerMock->expects($this->any())->method('getCookie')->willReturn($fdxLogin);
        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->sessionMock);
        $this->sessionMock->expects($this->any())->method('setSelfRegLoginError')->willReturnSelf();

        $this->urlInterfaceMock->expects($this->any())->method('getUrl')->willReturn($loginFailUrl);

        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);
        $this->redirectMock->expects($this->any())->method('setUrl')->willReturnSelf();

        $result = $this->selfRegLoginMock->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }
}
