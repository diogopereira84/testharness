<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Quote\Test\Unit\Plugin\DataObject;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Quote\Plugin\DataObject\Copy;

/**
 * Test class for Copy function
 */
class CopyTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $copyData;
   
    /**
     * setUp function for all contructor params
     */
    public function setUp(): void
    {

        $this->objectManager = new ObjectManager($this);
        $this->copyData = $this->objectManager->getObject(
            Copy::class
        );
    }

    /**
     * Test beforeCopyFieldsetToTarget
     *
     * @return void
     */
    public function testBeforeCopyFieldsetToTarget()
    {
        $this->assertIsArray($this->copyData->beforeCopyFieldsetToTarget('test', 'customer_account', 'to_quote', 'test', 'test'));
    }
}
