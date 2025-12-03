<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\SSO\Test\Unit\Model\Session;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SSO\Model\Session\Storage;
use PHPUnit\Framework\TestCase;

/**
 * Test class for StorageTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class StorageTest extends TestCase
{
    protected $storage;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerInterfaceMock;
    /**
     * Test setUp
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->storeManagerInterfaceMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->storage = $objectManager->getObject(
            Storage::class,
            [
                'storeManager' => $this->storeManagerInterfaceMock,
                'namespace' => 'fedex_sso',
                'data' => [],
            ]
        );
    }
/**
 * Function test for Constructor
 *
 * @return void
 */
    public function testConstructor()
    {
        $storageObject = new Storage($this->storeManagerInterfaceMock, 'fedex_sso', []);
        $this->assertEquals($this->storage, $storageObject);
    }
}
