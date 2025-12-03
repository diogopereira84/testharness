<?php
/**
 * Copyright © FeDEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Fedex\SharedCatalogCustomization\Ui\Component\Listing\Column\ViewActions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewActionsTest extends TestCase
{
    /**
     * @var ContextInterface|MockObject
     */
    protected $context;

    /**
     * @var UiComponentFactory|MockObject
     */
    protected $uiComponentFactory;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilder;

    /**
     * @var ViewActions|MockObject
     */
    protected $viewActionsMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockForAbstractClass(ContextInterface::class);
        $this->uiComponentFactory = $this->createMock(UiComponentFactory::class);
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);
        $objectManager = new ObjectManager($this);
        $this->viewActionsMock = $objectManager->getObject(
            ViewActions::class,
            [
                'context' => $this->context,
                'uiComponentFactory' => $this->uiComponentFactory,
                'urlBuilder' => $this->urlBuilder,
                'components' => [],
                'data' => [],
                'editUrl' => ''
            ]
        );
    }

    /**
     * @test prepareDataSource method
     */
    public function testPrepareDataSource()
    {
        $dataSource['data']['items']['item'] = ['id' => 1, 'name' => 'test', 'company id' => 1, 'legacy_catalog_root_folder_id' => 'legacyCatalog123'] ;
        $this->assertIsArray($this->viewActionsMock->prepareDataSource($dataSource));
    }
}
