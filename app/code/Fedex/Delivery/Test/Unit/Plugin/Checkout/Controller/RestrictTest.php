<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Test\Unit\Plugin\Checkout\Controller;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Controller\Index\Index;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Fedex\Delivery\Plugin\Checkout\Controller\Restrict;
use Magento\Framework\App\ResponseInterface;

/**
 * Restrict Model Plugin
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class RestrictTest extends \PHPUnit\Framework\TestCase
{
    protected $subject;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $restrict;
    /**
     * @var \Magento\Framework\UrlFactory $urlFactory
     */
    private $urlModel;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var ManagerInterface $messageManager
     */
    private $messageManager;

    /**
     * @var \Magento\Customer\Model\Session $customerSession
     */
    private $customerSession;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    private $customerRepository;

    /**
     * @var CompanyRepositoryInterface $companyRepository
     */
    private $companyRepository;

    /**
     * @var \Closure
     */
    private \Closure $proceed;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->urlModel = $this->getMockBuilder(UrlFactory::class)
            ->setMethods(['create', 'getUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
            ->setMethods(['create', 'setUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->setMethods(['addErrorMessage'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->setMethods(['getCustomer', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->setMethods([
                'getById',
                'getExtensionAttributes',
                'getCompanyAttributes',
                'getCompanyId',
                ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->setMethods([
                'get',
                'getIsPickup',
                'getIsDelivery'
                ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->subject = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockForAbstractClass(ResponseInterface::class);

        $this->proceed = function () use ($response) {
                return $response;
        };

        $this->objectManager = new ObjectManager($this);
        $this->restrict = $this->objectManager->getObject(
            Restrict::class,
            [
                'urlFactory' => $this->urlModel,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'messageManager' => $this->messageManager,
                'customerSession' => $this->customerSession,
                'customerRepository' => $this->customerRepository,
                'companyRepository' => $this->companyRepository
            ]
        );
    }

    public function testAroundExecute()
    {
        $this->urlModel->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getId')->willReturn('TestId');
        $this->customerRepository->expects($this->any())->method('getById')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('getExtensionAttributes')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('getCompanyAttributes')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('getCompanyId')->willReturn('12345');
        $this->companyRepository->expects($this->any())->method('get')->willReturnSelf();
        $this->companyRepository->expects($this->any())->method('getIsPickup')->willReturn(true);
        $this->companyRepository->expects($this->any())->method('getIsDelivery')->willReturn(true);
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setUrl')->willReturnSelf();

        $this->assertNotNull($this->restrict->aroundExecute($this->subject, $this->proceed));
    }

    public function testAroundExecuteForNullID()
    {
        $this->urlModel->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getId')->willReturn(null);

        $this->assertNotNull($this->restrict->aroundExecute($this->subject, $this->proceed));
    }

    public function testAroundExecuteWithNullCompanyAttribute()
    {
        $this->urlModel->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getId')->willReturn('TestId');
        $this->customerRepository->expects($this->any())->method('getById')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('getExtensionAttributes')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('getCompanyAttributes')->willReturn(null);

        $this->assertNotNull($this->restrict->aroundExecute($this->subject, $this->proceed));
    }

    public function testAroundExecuteWithCompanyIdAsNUll()
    {
        $this->urlModel->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getId')->willReturn('TestId');
        $this->customerRepository->expects($this->any())->method('getById')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('getExtensionAttributes')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('getCompanyAttributes')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('getCompanyId')->willReturn(null);

        $this->assertNotNull($this->restrict->aroundExecute($this->subject, $this->proceed));
    }

    public function testAroundExecuteWithErrorMessage()
    {
        $this->urlModel->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getId')->willReturn('TestId');
        $this->customerRepository->expects($this->any())->method('getById')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('getExtensionAttributes')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('getCompanyAttributes')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('getCompanyId')->willReturn('12345');
        $this->companyRepository->expects($this->any())->method('get')->willReturnSelf();
        $this->companyRepository->expects($this->any())->method('getIsPickup')->willReturn(false);
        $this->companyRepository->expects($this->any())->method('getIsDelivery')->willReturn(false);
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('setUrl')->willReturnSelf();

        $this->assertNotNull($this->restrict->aroundExecute($this->subject, $this->proceed));
    }
}
