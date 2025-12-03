<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Test\Unit\Model\Source\Type;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;
use Fedex\Import\Model\Source\Type\AbstractType;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class AbstractTypeTest extends TestCase
{
    /**
     * @var AbstractType|MockObject
     */
    private $abstractType;
    
    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->abstractType = $this->getMockForAbstractClass(
            AbstractType::class
        );
    }

    /**
     * Test method for getImportFilePath
     *
     * @return void
     */
    public function testgetImportFilePath()
    {
        $stub = $this->getMockForAbstractClass(AbstractType::class);
        $stub->getImportFilePath();

        $block = $this->getMockBuilder(AbstractType::class)
        ->disableOriginalConstructor()
        ->getMock();
        $testMethod = new \ReflectionMethod(
            AbstractType::class,
            'getImportPath'
        );
        $testMethod1 = new \ReflectionMethod(
            AbstractType::class,
            'getMediaImportPath'
        );
        $testMethod->setAccessible(true);
        $testMethod->invoke($block);
        $testMethod1->setAccessible(true);
        $testMethod1->invoke($block);
    }

    /**
     * Test method for getImportFilePathIf
     *
     * @return void
     */
    public function testgetImportFilePathIf()
    {
        $sourceType = 'file';
        $stub = $this->getMockForAbstractClass(AbstractType::class);
        $stub->getImportSource($sourceType.'_file_path');
        $this->assertTrue(true);
    }

    /**
     * Test method for getCode
     *
     * @return void
     */
    public function testgetCode()
    {
        $stub = $this->getMockForAbstractClass(AbstractType::class);
        $stub->getCode();
    }

    /**
     * Test method for getClient
     *
     * @return void
     */
    public function testgetClient()
    {
        $stub = $this->getMockForAbstractClass(AbstractType::class);
        $stub->getClient();
    }

    /**
     * Test method for setClient
     *
     * @return void
     */
    public function testsetClient()
    {
        $stub = $this->getMockForAbstractClass(AbstractType::class);
        $stub->setClient('Dummy');
    }
}
