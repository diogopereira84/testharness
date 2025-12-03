<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\CMS\Test\Unit\Api\Cms;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\Cms\Api\Cms\SimpleBlock;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Cms\Model\BlockRepository;
use Magento\Cms\Api\GetBlockByIdentifierInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Test class for SimpleBlock
 */
class SimpleBlockTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $simpleBlockTestData;
    public const EXCEPTION_MESSAGE = 'Unable to save';

    /**
     * @var BlockInterfaceFactory $blockInterfaceFactory
     */
    protected $blockInterfaceFactory;

    /**
     * @var BlockRepository $blockRepository
     */
    protected $blockRepository;

    /**
     * @var GetBlockByIdentifierInterface $getBlockByIdentifier
     */
    protected $getBlockByIdentifier;

    /**
     * @var BlockInterface $blockInterface
     */
    protected $blockInterface;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->blockInterfaceFactory = $this->getMockBuilder(BlockInterfaceFactory::class)
            ->setMethods(
                [
                    'create',
                    'setData'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->blockRepository = $this->getMockBuilder(BlockRepository::class)
            ->setMethods(
                [
                    'save'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->getBlockByIdentifier = $this->getMockBuilder(GetBlockByIdentifierInterface::class)
            ->setMethods(
                [
                    'execute'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->blockInterface = $this->getMockBuilder(BlockInterface::class)
            ->setMethods(
                [
                    'setData',
                    'execute'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->simpleBlockTestData = $this->objectManager->getObject(
            SimpleBlock::class,
            [
                'blockInterfaceFactory' => $this->blockInterfaceFactory,
                'blockRepository' => $this->blockRepository,
                'getBlockByIdentifier' => $this->getBlockByIdentifier,
                'blockInterface' => $this->blockInterface
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
        $this->getBlockByIdentifier->expects($this->any())->method('execute')->willReturn($this->blockInterface);
        $this->assertEquals(null, $this->simpleBlockTestData->delete('test', 1));
    }

    /**
     * Test delete function with exception
     *
     * @return void
     */
    public function testDeleteWithException()
    {
        $this->getBlockByIdentifier->expects($this->any())
            ->method('execute')
            ->willThrowException(new NoSuchEntityException(new Phrase(self::EXCEPTION_MESSAGE)));
        $this->assertSame(null, $this->simpleBlockTestData->delete('test', 1));
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
                    'content' => 'test',
                    'is_active' => 1,
                    'stores' => 1,
                    'title' => 'test'
                ];

        $this->getBlockByIdentifier->expects($this->any())->method('execute')->willReturn($this->blockInterface);
        $this->blockInterface->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals(null, $this->simpleBlockTestData->save($data));
    }

    /**
     * Test save function with execute exception
     *
     * @return void
     */
    public function testSaveWithExecuteException()
    {
        $data = [
                    'identifier' => 'test',
                    'store_id' => 1,
                    'content' => 'test',
                    'is_active' => 1,
                    'stores' => 1,
                    'title' => 'test'
                ];

        $this->getBlockByIdentifier->expects($this->any())
            ->method('execute')
            ->willThrowException(new NoSuchEntityException(new Phrase(self::EXCEPTION_MESSAGE)));
        $this->blockInterfaceFactory->method('create')->willReturn($this->blockInterface);
        $this->blockInterface->method('setIdentifier')->willReturnSelf();
        $this->assertEquals(null, $this->simpleBlockTestData->save($data));
    }

    /**
     * Test save function with exception
     *
     * @return void
     */
    public function testSaveWithException()
    {
        $data = [
                    'identifier' => 'test',
                    'store_id' => 1,
                    'content' => 'test',
                    'is_active' => 1,
                    'stores' => 1,
                    'title' => 'test'
                ];

        $this->getBlockByIdentifier->expects($this->any())->method('execute')->willReturn($this->blockInterface);
        $this->blockInterface->expects($this->any())->method('setData')->willReturnSelf();
        $this->blockRepository->expects($this->any())
            ->method('save')
            ->willThrowException(new CouldNotSaveException(new Phrase(self::EXCEPTION_MESSAGE)));
        $this->assertEquals(null, $this->simpleBlockTestData->save($data));
    }
}
