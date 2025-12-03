<?php

namespace Fedex\SaaSCommon\Test\Unit\Model\Data;

use Fedex\SaaSCommon\Model\Data\AllowedCustomerGroupsRequest;
use PHPUnit\Framework\TestCase;

class AllowedCustomerGroupsRequestTest extends TestCase
{
    public function testEntityIdGetterAndSetter()
    {
        $model = new AllowedCustomerGroupsRequest();
        $model->setEntityId(123);
        $this->assertEquals(123, $model->getEntityId());
    }

    public function testEntityTypeGetterAndSetter()
    {
        $model = new AllowedCustomerGroupsRequest();
        $model->setEntityType('product');
        $this->assertEquals('product', $model->getEntityType());
    }
}

