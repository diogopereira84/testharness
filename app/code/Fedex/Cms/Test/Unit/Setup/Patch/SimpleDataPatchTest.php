<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cms\Setup\Patch;

use PHPUnit\Framework\TestCase;
use Fedex\Cms\Setup\Patch\SimpleDataPatch;
use Fedex\Cms\Api\Cms\SimpleBlock;
use Fedex\Cms\Api\Cms\SimplePage;
use Fedex\Cms\Api\Cms\SimpleContentReader;
use Magento\Framework\App\Config\Storage\WriterInterface as SimpleConfig;

/**
 * Test class for SimpleDataPatch
 */

class SimpleDataPatchTest extends TestCase
{
    /**
     * @var SimpleDataPatch $simpleDataPatch
     */
    protected $simpleDataPatch;

    /** @var SimpleBlock $blockMock */
    protected $blockMock;

    /** @var SimpleConfig $configMock */
    protected $configMock;

    /** @var SimplePage $pageMock */
    protected $pageMock;

    /**  @var SimpleContentReader $contentMock */
    protected $contentMock;

    /**
     * Test setup
     */
    public function setUp(): void
    {
        $this->blockMock = $this->getMockBuilder(SimpleBlock::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this->getMockBuilder(SimpleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageMock = $this->getMockBuilder(SimplePage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contentMock = $this->getMockBuilder(SimpleContentReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->simpleDataPatch = $this->getMockForAbstractClass(
            SimpleDataPatch::class,
            [
                'simpleBlock' => $this->blockMock,
                'simpleConfig' => $this->configMock,
                'simplePage' => $this->pageMock,
                'contentReader' => $this->contentMock
            ]
        );
    }

    /**
     * Test apply function
     */
    public function testapply()
    {

        $this->assertEquals(null, $this->simpleDataPatch->apply());
    }

    /**
     * Test getAliases function
     */
    public function testgetAliases()
    {
        $this->assertEquals([], $this->simpleDataPatch->getAliases());
    }

    /**
     * Test getDependencies function
     */
    public function testgetDependencies()
    {
        $this->assertEquals([], $this->simpleDataPatch->getDependencies());
    }
}
