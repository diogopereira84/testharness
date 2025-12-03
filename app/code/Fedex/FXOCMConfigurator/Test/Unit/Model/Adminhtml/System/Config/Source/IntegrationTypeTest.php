<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FXOCMConfigurator\Model\Adminhtml\System\Config\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\FXOCMConfigurator\Model\Adminhtml\System\Config\Source\IntegrationType;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IntegrationTypeTest extends TestCase
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
     * @var IntegrationType $integrationType
     */
    protected $integrationType;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->_objectManager = new ObjectManager($this);

        $this->integrationType = $this->_objectManager->getObject(
            IntegrationType::class,
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
                        ['value' => 'IFRAME', 'label' => __('IFRAME')],
                        ['value' => 'URL_REDIRECT', 'label' => __('URL_REDIRECT')]
                      ];
        $this->assertEquals($arrOptions, $this->integrationType->toOptionArray());
    }
}
