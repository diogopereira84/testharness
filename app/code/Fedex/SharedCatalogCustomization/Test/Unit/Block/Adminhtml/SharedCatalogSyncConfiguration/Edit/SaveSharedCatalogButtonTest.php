<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Fedex\SharedCatalogCustomization\Test\Unit\Block\Adminhtml\SharedCatalogSyncConfiguration\Edit;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\SharedCatalogCustomization\Block\Adminhtml\SharedCatalogSyncConfiguration\Edit\SaveSharedCatalogButton;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Block SaveSharedCatalogButton.
 */
class SaveSharedCatalogButtonTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var SaveSharedCatalogButton|MockObject
     */
    private $saveSharedCatalogButton;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->saveSharedCatalogButton = $this->objectManagerHelper->getObject(
            SaveSharedCatalogButton::class,
            []
        );
    }

    /**
     * Test for getButtonData().
     *
     * @return void
     */
    public function testGetButtonData()
    {
        $expected = [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order' => 90,
        ];

        $result = $this->saveSharedCatalogButton->getButtonData();
        $this->assertEquals($expected, $result);
    }
}
