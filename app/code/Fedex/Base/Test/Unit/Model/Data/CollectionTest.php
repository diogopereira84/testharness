<?php
/**
 * @category  Fedex
 * @package   Fedex_Base
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Base\Test\Unit\Model\Data;

use Exception;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\DataObject;
use Fedex\Base\Model\Data\Collection;

class CollectionTest extends TestCase
{
    /**
     * Item object data one
     */
    private const ITEM_ONE = ['name' => 'item1'];

    /**
     * Item object data two
     */
    private const ITEM_TWO = ['name' => 'item2'];

    /**
     * Item object data three
     */
    private const ITEM_THREE = ['name' => 'item3'];

    /**
     * Items DataObject data as array
     */
    private const ITEMS = [
        self::ITEM_ONE,
        self::ITEM_TWO,
        self::ITEM_THREE,
    ];

    /**
     * @var Collection
     */
    private Collection $collection;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {;
        $this->collection = new Collection(
            $this->getMockForAbstractClass(
                EntityFactoryInterface::class
            )
        );
    }

    /**
     * Test toArrayItems method
     *
     * @throws Exception
     */
    public function testToArrayItems(): void
    {
        $this->collection->addItem(new DataObject(self::ITEM_ONE));
        $this->collection->addItem(new DataObject(self::ITEM_TWO));
        $this->collection->addItem(new DataObject(self::ITEM_THREE));

        $this->assertEquals(
            self::ITEMS,
            $this->collection->toArrayItems()
        );
    }
}
