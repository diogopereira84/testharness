<?php
namespace Fedex\Delivery\Test\Unit\Controller\Index;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SSO\Helper\Data;
use Fedex\SSO\Controller\Index\UpdateAccount;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class UpdateAccountTest extends TestCase
{
    protected $cookieManager;
    protected $ssoConfiguration;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $_objectManager;

    /**
     * @var \Fedex\SSO\Helper\Data|MockObject
     */
    protected $_ssoHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface|MockObject
     */
    protected $_messageManager;

    /**
     * @var \Fedex\SSO\Controller\Index\UpdateAccount|MockObject
     */
    protected $_updateAccount;

    /**
     * @var \Psr\Log\LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * CookieMetadata Variable
     *
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadata
     */
    protected $_cookieMetadata;

    /**
     * RedirectFactory Variable
     *
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $_resultRedirectFactory;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->_ssoHelper = $this->getMockBuilder(Data::class)
                ->setMethods(
                    [
                        'getCustomCookie',
                        'getCustomerProfile',
                        'getBaseUrl',
                        'getFCLCookieNameToggle',
                        'getFCLCookieConfigValue'
                    ]
                )
                ->disableOriginalConstructor()
                ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
                ->setMethods(
                    [
                        'critical'
                    ]
                )
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

        $this->_messageManager = $this->getMockBuilder(ManagerInterface::class)
                ->setMethods(
                    [
                        'addSuccess'
                    ]
                )
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

        $this->_resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
                ->setMethods(['create', 'setPath'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->cookieManager = $this->getMockBuilder(CookieManagerInterface::class)
                ->setMethods(['getCookie'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

        $this->ssoConfiguration = $this->getMockBuilder(SsoConfiguration::class)
                ->setMethods(['getConfigValue'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->_objectManager = new ObjectManager($this);

        $this->_updateAccount = $this->_objectManager->getObject(
            UpdateAccount::class,
            [
                'ssoHelper' => $this->_ssoHelper,
                'messageManager' => $this->_messageManager,
                'logger' => $this->logger,
                'resultRedirectFactory' => $this->_resultRedirectFactory,
                'cookieManager' => $this->cookieManager,
                'ssoConfiguration' => $this->ssoConfiguration
            ]
        );
    }

    /**
     * Test execute.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testExecute()
    {
        $cookieCode = 'FXOSESSIONID';
        $successMessage = 'Account has been updated successfully.';

        $this->_ssoHelper->expects($this->any())
        ->method('getFCLCookieNameToggle')->willReturn(true);

        $this->_ssoHelper->expects($this->any())
        ->method('getFCLCookieConfigValue')->willReturn('sdfasda');

        $this->ssoConfiguration->expects($this->any())
                        ->method('getConfigValue')
                        ->willReturn('https://api.test.office.fedex.com/customer/fedexoffice/v1/profiles');
        $this->cookieManager->expects($this->any())
                        ->method('getCookie')
                        ->willReturn(1);

        $this->_messageManager->expects($this->any())
                        ->method('addSuccess')
                        ->willReturn($successMessage);

        $this->_resultRedirectFactory->expects($this->any())
                                        ->method('create')
                                        ->willReturnSelf();

        $this->_resultRedirectFactory->expects($this->any())
                                    ->method('setPath')
                                    ->willReturnSelf("*/*/edit");

        $this->assertSame($this->_resultRedirectFactory, $this->_updateAccount->execute());
    }

    /**
     * Test execute with FCL cookie toggle disabled.
     *
     * @return void
     */
    public function testExecuteWithFCLToggleDisabled()
    {
        $this->_ssoHelper->expects($this->any())
        ->method('getFCLCookieNameToggle')
        ->willReturn(false);

        $this->_resultRedirectFactory->expects($this->any())
        ->method('create')
        ->willReturnSelf();

        $this->_resultRedirectFactory->expects($this->any())
        ->method('setPath')
        ->willReturnSelf("*/*/edit");

        $this->assertSame($this->_resultRedirectFactory, $this->_updateAccount->execute());
    }

    /**
     * Test execute with exception.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->ssoConfiguration->expects($this->any())
                        ->method('getConfigValue')
                        ->willThrowException($exception);
        $this->_resultRedirectFactory->expects($this->any())
                        ->method('create')
                        ->willReturnSelf();

        $this->_resultRedirectFactory->expects($this->any())
                    ->method('setPath')
                    ->willReturnSelf("*/*/edit");

        $this->assertSame($this->_resultRedirectFactory, $this->_updateAccount->execute());
    }
}
