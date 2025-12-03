<?php

namespace Fedex\SelfReg\Test\Unit\Model\Customer\Source;

use Fedex\SelfReg\Model\Customer\Source\Status;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    private $model;
    private $objectManager;
    protected function setUp(): void
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $this->objectManager->getObject(Status::class);
    }

    /**
     * Test for toOptionArray method.
     *
     * @return array
     */
    public function testToOptionArray()
    {
        $this->assertNotNull($this->model->ToOptionArray());
    }
}
