<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Controller\Adminhtml\SharedCatalogsyncQueue;

use Fedex\SharedCatalogCustomization\Controller\Adminhtml\SharedCatalogSyncQueue\Configuration;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigurationTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $configuration;
    /**
     * @var PageFactory|MockObject
     */
    protected $pageFactoryMock;

    /**
     * @var Page|MockObject
     */
    protected $pageMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->configuration = $this->objectManager->getObject(
            Configuration::class,
            [
                'pageFactory' => $this->pageFactoryMock
            ]
        );
    }

    /**
     * Controller test
     */
    public function testExecute()
    {

        $this->pageFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->pageMock);

        $this->assertSame($this->pageMock, $this->configuration->execute());
    }
}
