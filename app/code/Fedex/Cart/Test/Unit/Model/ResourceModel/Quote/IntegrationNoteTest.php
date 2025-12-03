<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\ResourceModel\Quote;

use Fedex\Cart\Model\ResourceModel\Quote\IntegrationNote;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class IntegrationNoteTest extends TestCase
{
    /**
     * @var IntegrationNote
     */
    protected IntegrationNote $model;

    public function testConstruct()
    {
        $resourceMock = $this->createMock(ResourceConnection::class);
        $objectManager = new ObjectManager($this);
        $arguments = $objectManager->getConstructArguments(IntegrationNote::class, ['resource' => $resourceMock]);
        $this->model = $objectManager->getObject(IntegrationNote::class, $arguments);

        $this->assertEquals('entity_id', $this->model->getIdFieldName());
        $this->assertEquals([], $this->model->getUniqueFields());
    }
}
