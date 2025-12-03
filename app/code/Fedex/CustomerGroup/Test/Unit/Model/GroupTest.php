<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomerGroup\Test\Unit\Model;

use Fedex\CustomerGroup\Model\Group;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    const GROUP_CODE_MAX_LENGTH = 200;

    /**
     * @var ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var Group
     */
    protected $group;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->group = $this->objectManagerHelper->getObject(Group::class);
    }

    /**
     * Test method to setCustomerGroupCode
     *
     * @return string
     */
    public function testSetCode()
    {
        $this->assertNotNull($this->group->setCode(''));
    }

    /**
     * Test method for customer group data
     *
     * @return string
     */
    public function testPrepareData()
    {
        $reflection = new \ReflectionClass(Group::class);
        $getPrepareData = $reflection->getMethod('_prepareData');
        $getPrepareData->setAccessible(true);
        $this->testSetCode();
        $expectedResult = $getPrepareData->invoke($this->group);
        
        $this->assertNotNull($expectedResult);
    }
}
