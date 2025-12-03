<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\ResourceModel\Quote;

use Fedex\Cart\Model\ResourceModel\Quote\Integration;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    /**
     * @var Integration
     */
    protected Integration $model;

    public function testConstruct()
    {
        $resourceMock = $this->createMock(ResourceConnection::class);
        $objectManager = new ObjectManager($this);
        $arguments = $objectManager->getConstructArguments(Integration::class, ['resource' => $resourceMock]);
        $this->model = $objectManager->getObject(Integration::class, $arguments);

        $this->assertEquals('integration_id', $this->model->getIdFieldName());
        $this->assertEquals([], $this->model->getUniqueFields());
    }
}
