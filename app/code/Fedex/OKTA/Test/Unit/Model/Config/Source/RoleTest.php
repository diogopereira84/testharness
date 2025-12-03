<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Config\Source;

use Fedex\OKTA\Model\Config\Source\Role;
use Magento\Authorization\Model\ResourceModel\Role\Collection;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    /**
     * @var CollectionFactory|MockObject
     */
    private CollectionFactory $collectionFactoryMock;

    /**
     * @var Role
     */
    private Role $role;

    /**
     * @var array|\string[][]
     */
    private array $options = [
        ['value' => '0', 'label' => 'None'],
        ['value' => '1', 'label' => 'Test 01'],
        ['value' => '2', 'label' => 'Test 02']
    ];

    /**
     * @var Collection|MockObject
     */
    private Collection $roleCollectionMock;

    protected function setUp(): void
    {

        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->roleCollectionMock = $this->createMock(Collection::class);
        $this->roleCollectionMock->expects($this->once())->method('toOptionArray')->willReturn($this->options);
        $this->collectionFactoryMock->expects($this->once())->method('create')->willReturn($this->roleCollectionMock);
        $this->role = new Role($this->collectionFactoryMock);
    }

    public function testGetAllOptions(): void
    {
        $this->assertEquals($this->options, $this->role->getAllOptions());
    }
}
