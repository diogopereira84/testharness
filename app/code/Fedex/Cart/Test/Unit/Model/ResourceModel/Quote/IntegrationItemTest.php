<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\ResourceModel\Quote;

use Fedex\Cart\Model\ResourceModel\Quote\IntegrationItem;
use PHPUnit\Framework\TestCase;

class IntegrationItemTest extends TestCase
{
    /**
     * @var IntegrationItem
     */
    protected IntegrationItem $model;

    public function testConstruct()
    {
        $context = $this->getMockBuilder(\Magento\Framework\Model\ResourceModel\Db\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $model = new IntegrationItem($context);

        $this->assertEquals('integration_item_id', $model->getIdFieldName());
        $this->assertEquals([], $model->getUniqueFields());
    }
}
