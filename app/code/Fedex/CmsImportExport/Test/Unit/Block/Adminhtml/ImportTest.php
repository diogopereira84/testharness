<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\CmsImportExport\Test\Unit\Block\Adminhtml;

use Fedex\CmsImportExport\Block\Adminhtml\Import;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Data\Form\FormKey;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportTest extends TestCase
{

    /**
     * @var (\Magento\Backend\Block\Widget\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $formKeyMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $import;
    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formKeyMock = $this->createMock(FormKey::class);

        $this->objectManager = new ObjectManager($this);

        $this->import = $this->objectManager->getObject(
            Import::class,
            [
                'context' => $this->contextMock,
                'formKey' => $this->formKeyMock,
            ]
        );
    }

    public function testGetFormKey()
    {
        $formKey = 'form_key';
        $this->formKeyMock->expects($this->once())->method('getFormKey')->willReturn($formKey);
        $this->assertEquals($formKey, $this->import->getFormKey());
    }
}
