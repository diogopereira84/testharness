<?php
/**
 * @category    Fedex
 * @package     Fedex_Customer
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare (strict_types = 1);

namespace Fedex\Customer\Test\Unit\Fedex\Customer\Model\Attribute\Source;
use Fedex\Customer\Model\Attribute\Source\CustomOptions;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class CustomOptionsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $customOptions;
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->customOptions = $this->objectManager->getObject(
            CustomOptions::class
        );
    }

    /**
     * Test Execute method
     * @return void
     */
    public function testGetAllOptions()
    {
        $this->assertIsArray($this->customOptions->getAllOptions());
    }
}
