<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnvironmentManager\Model\Adminhtml\System\Config\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\Model\Adminhtml\System\Config\Source\FclType;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FclTypeTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $_objectManager;
    /**
     * @var ObjectManager $objectManager
     */
    protected $objectManager;

    /**
     * ConfigObserver
     *
     * @var FclType $fclTYpe
     */
    protected $fclTYpe;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->_objectManager = new ObjectManager($this);

        $this->fclTYpe = $this->_objectManager->getObject(
            FclType::class,
            [
            ]
        );
    }

    /**
     * Test toOptionsArray
     */
    public function testToOptionArray()
    {
        $arrOptions = [
                        ['value' => 'module', 'label' => __('Module')],
                        ['value' => 'feature', 'label' => __('Feature')]
                      ];
        $this->assertEquals($arrOptions, $this->fclTYpe->toOptionArray());
    }
}
