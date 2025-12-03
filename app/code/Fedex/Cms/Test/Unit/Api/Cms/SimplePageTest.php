<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\CMS\Test\Unit\Api\Cms;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\Cms\Api\Cms\SimplePage;
use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Cms\Model\PageRepository;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Test class for SimplePage
 */
class SimplePageTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $simplePageTestData;
    public const EXCEPTION_MESSAGE = 'Unable to save';

    /**
     * @var GetPageByIdentifierInterface $getPageByIdentifier
     */
    protected $getPageByIdentifier;

    /**
     * @var PageInterface $pageInterface
     */
    protected $pageInterface;

    /**
     * @var PageInterfaceFactory $pageInterfaceFactory
     */
    protected $pageInterfaceFactory;

    /**
     * @var PageRepository $pageRepository
     */
    protected $pageRepository;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->getPageByIdentifier = $this->getMockBuilder(GetPageByIdentifierInterface::class)
            ->setMethods(
                [
                    'execute'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->pageInterface = $this->getMockBuilder(PageInterface::class)
            ->setMethods(
                [
                    'setData'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->pageInterfaceFactory = $this->getMockBuilder(PageInterfaceFactory::class)
            ->setMethods(
                [
                    'create'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->pageRepository = $this->getMockBuilder(PageRepository::class)
            ->setMethods(
                [
                    'delete',
                    'save'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->simplePageTestData = $this->objectManager->getObject(
            SimplePage::class,
            [
                'getPageByIdentifier' => $this->getPageByIdentifier,
                'pageInterface' => $this->pageInterface,
                'pageInterfaceFactory' => $this->pageInterfaceFactory,
                'pageRepository' => $this->pageRepository
            ]
        );
    }

    /**
     * Test delete function
     *
     * @return void
     */
    public function testDelete()
    {
        $this->getPageByIdentifier->expects($this->any())->method('execute')->willReturn($this->pageInterface);
        $this->assertEquals(null, $this->simplePageTestData->delete('test', 1));
    }

    /**
     * Test delete with exception function
     *
     * @return void
     */
    public function testDeleteWithException()
    {
        $this->getPageByIdentifier->expects($this->any())
            ->method('execute')
            ->willThrowException(new NoSuchEntityException(new Phrase(self::EXCEPTION_MESSAGE)));
        $this->assertSame(null, $this->simplePageTestData->delete('test', 1));
    }

    /**
     * Test save function
     *
     * @return void
     */
    public function testSave()
    {
        $data = [
                    'identifier' => 'test',
                    'store_id' => 1,
                    'stores' => 'test',
                    'page_layout' => 1
                ];

        $this->getPageByIdentifier->expects($this->any())->method('execute')->willReturn($this->pageInterface);
        $this->pageInterface->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals(null, $this->simplePageTestData->save($data));
    }

    /**
     * Test save with execute exception function
     *
     * @return void
     */
    public function testSaveWithExecuteException()
    {
        $data = [
                    'identifier' => 'test',
                    'store_id' => 1,
                    'stores' => 'test',
                    'page_layout' => 1
                ];

        $this->getPageByIdentifier->expects($this->any())
            ->method('execute')
            ->willThrowException(new NoSuchEntityException(new Phrase(self::EXCEPTION_MESSAGE)));
        $this->pageInterfaceFactory->method('create')->willReturn($this->pageInterface);
        $this->pageInterface->method('setIdentifier')->willReturnSelf();

        $this->assertEquals(null, $this->simplePageTestData->save($data));
    }

    /**
     * Test save with exception function
     *
     * @return void
     */
    public function testSaveWithException()
    {
        $data = [
                    'identifier' => 'test',
                    'store_id' => 1,
                    'stores' => 'test',
                    'page_layout' => 1
                ];

        $this->getPageByIdentifier->expects($this->any())->method('execute')->willReturn($this->pageInterface);
        $this->pageInterface->expects($this->any())->method('setData')->willReturnSelf();
        $this->pageRepository->expects($this->any())
            ->method('save')
            ->willThrowException(new CouldNotSaveException(new Phrase(self::EXCEPTION_MESSAGE)));
        $this->assertEquals(null, $this->simplePageTestData->save($data));
    }
}
