<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Tax\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Magento\Ui\Component\Wysiwyg\ConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Tax\Plugin\EditorConfig;

class EditorConfigTest extends TestCase
{
    protected $editorConfig;
    /**
     * @var ConfigInterface|MockObject
     */
    protected $configInterfaceMock;

    /**
     * @var DataObject|MockObject
     */
    protected $resultMock;

    /**
     * @var MockObject|ObjectManager
     */
    protected $objectManager;

    /**
     * Test setUp
     */
    public function setUp() : void
    {
        $this->configInterfaceMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultMock = $this->getMockBuilder(DataObject::class)
            ->onlyMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->editorConfig = $this->objectManager->getObject(EditorConfig::class);
    }

    /**
     * Test afterGetConfig
     * 
     * @param array $getDataValues
     * @dataProvider getAfterGetConfigProvider
     */
    public function testAfterGetConfig(array $getDataValues) : void
    {
        $this->resultMock->expects($this->any())->method('getData')
            ->withConsecutive(['isModalEditor'], ['settings'])
            ->willReturnOnConsecutiveCalls($getDataValues[0], $getDataValues[1]);

        $this->assertNotNull($this->editorConfig->afterGetConfig($this->configInterfaceMock, $this->resultMock));
    }

    /**
     * @return array
     */
    public function getAfterGetConfigProvider(): array
    {
        return [
            [ [ true, null ] ], [ [ false, null ] ], [ [ true, [] ] ]
        ];
    }
}
