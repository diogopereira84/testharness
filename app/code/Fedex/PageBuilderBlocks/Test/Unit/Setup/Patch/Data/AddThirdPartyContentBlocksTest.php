<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\PageBuilderBlocks\Test\Unit\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Eav\Api\AttributeRepositoryInterface;
use \Fedex\PageBuilderBlocks\Setup\Patch\Data\AddThirdPartyContentBlocks;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\Block;
use Magento\Cms\Api\Data\BlockInterface;

/**
 * Test class AddThirdPartyContentBlocksTest
 */
class AddThirdPartyContentBlocksTest extends TestCase
{
    protected $blockMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $addThirdPartyContentBlocks;
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetupMock;

    /**
     * @var SerializerInterface
     */
    private $serializerMock;

    /**
     * @var File
     */
    private $fileMock;

    /**
     * @var Reader
     */
    private $readerMock;

    /**
     * @var BlockFactory
     */
    private $blockFactoryMock;

    /**
     * @var BlockRepositoryInterface
     */
    private $blockRepositoryMock;

    /**
     * @var BlockInterface
     */
    private $blockInterfaceMock;

    /**
     * Test setup
     */
    public function setUp(): void
    {
        $this->moduleDataSetupMock = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(['startSetup', 'endSetup'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->blockRepositoryMock = $this->getMockBuilder(BlockRepositoryInterface::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->readerMock = $this->getMockBuilder(Reader::class)
            ->setMethods(['getModuleDir'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileMock = $this->getMockBuilder(File::class)
            ->setMethods(['read'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->setMethods(['unserialize'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->blockFactoryMock = $this->getMockBuilder(BlockFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->blockMock = $this->getMockBuilder(Block::class)
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->blockInterfaceMock = $this->getMockBuilder(BlockInterface::class)
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->addThirdPartyContentBlocks = $this->objectManager->getObject(
            AddThirdPartyContentBlocks::class,
            [
                'moduleDataSetup' => $this->moduleDataSetupMock,
                'serializer' => $this->serializerMock,
                'file' => $this->fileMock,
                'reader' => $this->readerMock,
                'blockFactory' => $this->blockFactoryMock,
                'blockRepository' => $this->blockRepositoryMock
            ]
        );
    }

    /**
     * Test apply function
     *
     * @return void
     */
    public function testApply()
    {
        $this->moduleDataSetupMock->expects($this->any())->method('startSetup')->willReturnSelf();
        $this->readerMock->expects($this->any())->method('getModuleDir')->willReturn('var');
        $this->fileMock->expects($this->any())->method('read')->willReturn('test');
        $this->serializerMock->expects($this->any())->method('unserialize')->willReturn(['test']);
        $this->blockFactoryMock->expects($this->any())->method('create')->willReturn($this->blockInterfaceMock);
        $this->blockRepositoryMock->expects($this->any())->method('save')->willReturnSelf();
        $this->moduleDataSetupMock->expects($this->any())->method('endSetup')->willReturnSelf();
        
        $this->assertEquals(null, $this->addThirdPartyContentBlocks->apply());
    }

    /**
     * Test revert function
     *
     * @return void
     */
    public function testRevert()
    {
        $this->moduleDataSetupMock->expects($this->any())->method('startSetup')->willReturnSelf();
        $this->readerMock->expects($this->any())->method('getModuleDir')->willReturn('var');
        $this->fileMock->expects($this->any())->method('read')->willReturn('test');
        $this->serializerMock->expects($this->any())->method('unserialize')->willReturn([['identifier' => 1]]);
        $this->blockFactoryMock->expects($this->any())->method('create')->willReturn($this->blockMock);
        $this->blockMock->expects($this->any())->method('load')->willReturnSelf();
        
        $this->assertEquals(null, $this->addThirdPartyContentBlocks->revert());
    }

    /**
     * Test getAliases function
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->addThirdPartyContentBlocks->getAliases());
    }

    /**
     * Test getDependencies function
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], $this->addThirdPartyContentBlocks->getDependencies());
    }
}
