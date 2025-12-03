<?php

namespace Fedex\SharedDetails\Test\Unit\Model\Config;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SharedDetails\Model\Config\Options;

class OptionsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $optionsMock;
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->optionsMock = $this->objectManager->getObject(Options::class, []);
    }

    /**
     * Test option array method
     *
     * @return void
     */
    public function testToOptionArray() : void
    {
        $this->assertIsArray($this->optionsMock->toOptionArray());
    }
}
