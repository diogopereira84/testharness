<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Controller\Adminhtml\Index;

use Fedex\CIDPSG\Controller\Adminhtml\Index\MassDelete;
use Fedex\CIDPSG\Model\Customer;
use Fedex\CIDPSG\Model\ResourceModel\Customer\Collection;
use Fedex\CIDPSG\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class MassDeleteTest Action
 */
class MassDeleteTest extends TestCase
{
    protected $filterMock;
    protected $resultRedirectFactory;
    protected $messageManagerMock;
    /**
     * @var ObjectManager $objectManager
     */
    protected $objectManager;

    /**
     * @var MassDelete $massDeleteController
     */
    protected $massDeleteController;

    /**
     * @var ManagerInterface $managerInterface
     */
    protected $managerInterface;

    /**
     * @var Filter $filter
     */
    protected $filter;

    /**
     * @var RedirectFactory $resultRedirect
     */
    protected $resultRedirect;

    /**
     * @var CollectionFactory|MockObject $collectionFactoryMock
     */
    protected $collectionFactoryMock;

    /**
     * @var Collection|MockObject $customerCollectionMock
     */
    protected $customerCollectionMock;

    /**
     * Test setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->filterMock = $this->createMock(Filter::class);

        $this->resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
            ->setMethods(['create', 'setPath'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactoryMock = $this->createPartialMock(CollectionFactory::class, ['create']);
        $this->customerCollectionMock = $this->createMock(Collection::class);

        $this->massDeleteController = $this->objectManager->getObject(
            MassDelete::class,
            [
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'filter' => $this->filterMock,
                'collectionFactory' => $this->collectionFactoryMock,
                'messageManager' => $this->messageManagerMock
            ]
        );
    }

    /**
     * test execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $deletedCustomersCount = 2;

        $collection = [
            $this->getCustomerMock(),
            $this->getCustomerMock(),
        ];

        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();

        $this->collectionFactoryMock->expects($this->once())->method('create')
            ->willReturn($this->customerCollectionMock);

        $this->filterMock->expects($this->once())
            ->method('getCollection')
            ->with($this->customerCollectionMock)
            ->willReturn($this->customerCollectionMock);

        $this->customerCollectionMock->expects($this->any())->method('getSize')->willReturn($deletedCustomersCount);

        $this->customerCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($collection));

        $this->messageManagerMock->expects($this->any())->method('addErrorMessage')
            ->with('default customer can not be deleted.');

        $this->resultRedirectFactory->expects($this->any())
            ->method('setPath')
            ->with('*/*/')
            ->willReturnSelf();

        $this->assertEquals($this->resultRedirectFactory, $this->massDeleteController->execute());
    }

    /**
     * test execute method without Default customer
     *
     * @return void
     */
    public function testExecuteWithoutDefaultCustomer()
    {
        $deletedCustomersCount = 2;

        $collection = [
            $this->getCustomerWithoutDefaultCustomerMock(),
            $this->getCustomerWithoutDefaultCustomerMock(),
        ];

        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();

        $this->collectionFactoryMock->expects($this->once())->method('create')
            ->willReturn($this->customerCollectionMock);

        $this->filterMock->expects($this->once())
            ->method('getCollection')
            ->with($this->customerCollectionMock)
            ->willReturn($this->customerCollectionMock);

        $this->customerCollectionMock->expects($this->any())->method('getSize')->willReturn($deletedCustomersCount);
        $this->customerCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($collection));

        $this->messageManagerMock->expects($this->any())->method('addErrorMessage')
            ->with('default customer can not be deleted.');

        $this->resultRedirectFactory->expects($this->any())
            ->method('setPath')
            ->with('*/*/')
            ->willReturnSelf();

        $this->assertEquals($this->resultRedirectFactory, $this->massDeleteController->execute());
    }

    /**
     * test execute method with Default Customer
     *
     * @return void
     */
    public function testExecutWithDefaulltCustomer()
    {
        $deletedCustomersCount = 1;

        $collection = [
            $this->getCustomerMock(),
            $this->getCustomerMock(),
        ];

        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();

        $this->collectionFactoryMock->expects($this->once())->method('create')
            ->willReturn($this->customerCollectionMock);

        $this->filterMock->expects($this->once())
            ->method('getCollection')
            ->with($this->customerCollectionMock)
            ->willReturn($this->customerCollectionMock);

        $this->customerCollectionMock->expects($this->any())->method('getSize')->willReturn($deletedCustomersCount);

        $this->customerCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($collection));

        $this->resultRedirectFactory->expects($this->any())
            ->method('setPath')
            ->with('*/*/')
            ->willReturnSelf();

        $this->assertEquals($this->resultRedirectFactory, $this->massDeleteController->execute());
    }

    /**
     * Create Customer Collection Mock
     *
     * @return void
     */
    protected function getCustomerMock()
    {
        $customerMock = $this->getMockBuilder(Collection::class)
            ->addMethods(['delete', 'getClientId'])
            ->disableOriginalConstructor()
            ->getMock();
        $customerMock->expects($this->any())->method('delete')->willReturn(true);
        $customerMock->expects($this->any())->method('getClientId')->willReturn('default');

        return $customerMock;
    }

    /**
     * Create Without Default Customer Collection Mock
     *
     * @return void
     */
    protected function getCustomerWithoutDefaultCustomerMock()
    {
        $customerMock = $this->getMockBuilder(Collection::class)
            ->addMethods(['delete', 'getClientId'])
            ->disableOriginalConstructor()
            ->getMock();
        $customerMock->expects($this->any())->method('delete')->willReturn(true);

        return $customerMock;
    }
}
