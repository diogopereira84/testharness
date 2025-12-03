<?php
/**
 * @category    Fedex
 * @package     Fedex_EmailVerification
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Austin King <austin.king@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\EmailVerification\Test\Unit\Controller\Index;

use Fedex\EmailVerification\Controller\Index\Index;
use Fedex\EmailVerification\Model\EmailVerification;
use Fedex\EmailVerification\Model\EmailVerificationCustomer;
use Fedex\SelfReg\Block\Landing;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    /**
     * @var EmailVerification|MockObject
     */
    protected $emailVerificationMock;

    /**
     * @var Landing|MockObject
     */
    protected $selfRegLandingMock;

    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var Http|MockObject
     */
    protected $httpRequestMock;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilderMock;

    /**
     * @var StoreRepositoryInterface|MockObject
     */
    protected $storeRepositoryMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var RedirectFactory|MockObject
     */
    protected $resultRedirectFactoryMock;

    /**
     * @var Redirect|MockObject
     */
    protected $redirectMock;

    /**
     * @var StoreInterface|MockObject
     */
    protected $storeMock;

    /**
     * @var EmailVerificationCustomer|MockObject
     */
    protected $emailVerificationCustomer;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var Index
     */
    protected $indexMock;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->emailVerificationMock = $this->getMockBuilder(EmailVerification::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomerByEmailUuid', 'isVerificationLinkActive', 'changeCustomerStatus', 'setExpiredLinkErrorMessage'])
            ->getMock();
        $this->selfRegLandingMock = $this->getMockBuilder(Landing::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLoginUrl'])
            ->getMock();
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest', 'getResultRedirectFactory'])
            ->getMock();
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->getMockForAbstractClass();
        $this->httpRequestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParams'])
            ->getMock();
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUrl'])
            ->getMockForAbstractClass();
        $this->storeRepositoryMock = $this->getMockBuilder(StoreRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMockForAbstractClass();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setCurrentStore'])
            ->getMockForAbstractClass();
        $this->emailVerificationCustomer = $this->getMockBuilder(EmailVerificationCustomer::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeMock = $this->createMock(StoreInterface::class);
        $this->resultRedirectFactoryMock = $this->createMock(RedirectFactory::class);
        $this->redirectMock = $this->createMock(Redirect::class);
        $this->objectManager = new ObjectManager($this);
        $this->indexMock = $this->objectManager->getObject(
            Index::class,
            [
                'emailVerification' => $this->emailVerificationMock,
                'selfRegLanding' => $this->selfRegLandingMock,
                'context' => $this->context,
                'scopeConfig' => $this->scopeConfigMock,
                'request' => $this->httpRequestMock,
                'url' => $this->urlBuilderMock,
                'storeRepository' => $this->storeRepositoryMock,
                'storeManager' => $this->storeManagerMock,
                'resultRedirectFactory' => $this->resultRedirectFactoryMock,
                'context' => $this->context
            ]
        );
    }

    /**
     * Test Execute with request Params
     *
     * @param array $params
     * @param bool $isLinkActive
     * @param bool $hasStatusChanged
     * @param string $redirectUrl
     * @dataProvider getExecuteDataProvider
     */
    public function testExecute($params, $isLinkActive, $hasStatusChanged, $redirectUrl): void
    {
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->httpRequestMock);
        $this->httpRequestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $this->storeRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn('285');
        $this->emailVerificationMock->expects($this->any())
            ->method('getCustomerByEmailUuid')
            ->willReturn($this->emailVerificationCustomer);
        $this->emailVerificationMock->expects($this->any())
            ->method('isVerificationLinkActive')
            ->willReturn($isLinkActive);
        $this->emailVerificationMock->expects($this->any())
            ->method('changeCustomerStatus')
            ->willReturn($hasStatusChanged);
        $this->selfRegLandingMock->expects($this->any())
            ->method('getLoginUrl')
            ->willReturn($redirectUrl);
        $this->urlBuilderMock->expects($this->any())
            ->method('getUrl')
            ->willReturn($redirectUrl);
        $this->context->expects($this->once())
            ->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactoryMock);
        $this->resultRedirectFactoryMock->method('create')
            ->willReturn($this->redirectMock);
        $this->redirectMock->expects($this->any())
            ->method('setUrl')
            ->with($redirectUrl);
        $this->emailVerificationMock->expects($this->any())
            ->method('setExpiredLinkErrorMessage')
            ->willReturn(null);
        
        $result = $this->indexMock->execute();

        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getExecuteDataProvider(): array
    {
        return [
            [
                [
                    'key' => 'abcd1234'
                ],
                true,
                true,
                'https://dev.office.fedex.com/ondemand/'
            ],
            [
                [
                    'key' => 'abcd1234'
                ],
                true,
                false,
                'https://dev.office.fedex.com/ondemand/'
            ],
            [
                [
                    'key' => 'abcd1234'
                ],
                false,
                false,
                'https://dev.office.fedex.com/ondemand/oath/fail/'
            ],
            [
                ['error' => 'error'],
                false,
                false,
                'https://dev.office.fedex.com/ondemand/oath/fail'
            ],
        ];
    }
}
