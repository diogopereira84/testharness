<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Block\Adminhtml\Index\Edit\Button;

use Fedex\MarketplaceCheckout\Block\Adminhtml\Index\Edit\Button\Save;
use PHPUnit\Framework\TestCase;

class SaveTest extends TestCase
{
    /**
     * @var Save
     */
    private $saveButton;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->saveButton = $this->getMockBuilder(Save::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUrl'])
            ->getMock();
    }

    /**
     * Test getButtonData method
     */
    public function testGetButtonData()
    {
        $expected = [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'fedex_mirakl_shop_edit_ui.fedex_mirakl_shop_edit_ui',
                                'actionName' => 'save',
                                'params' => [false],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->saveButton->getButtonData());
    }
}
