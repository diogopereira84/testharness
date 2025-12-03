<?php
/**
 * @category  Fedex
 * @package   Fedex_SharedCatalogCustomization
 * @copyright Copyright (c) 2024 FedEx.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Ui\Component\Form\Field;

use Fedex\SharedCatalogCustomization\Ui\Component\Form\Field\AdvancedSearchSharedCatalog;
use Fedex\Company\Api\Data\ConfigInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use PHPUnit\Framework\TestCase;

class AdvancedSearchSharedCatalogTest extends TestCase
{
    /**
     * @var AdvancedSearch
     */
    private $advancedSearch;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var UiComponentFactory
     */
    private $uiComponentFactory;

    /**
     * @var ConfigInterface
     */
    private $companyConfigInterface;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->context = $this->createMock(
            ContextInterface::class
        );

        $this->uiComponentFactory = $this->createMock(
            UiComponentFactory::class
        );

        $this->companyConfigInterface = $this->createMock(
            ConfigInterface::class
        );

        $this->advancedSearch = new AdvancedSearchSharedCatalog(
            $this->context,
            $this->uiComponentFactory,
            $this->companyConfigInterface
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testGetConfigDefaultData()
    {
        $reflection = new \ReflectionClass(AdvancedSearchSharedCatalog::class);
        $method = $reflection->getMethod('getConfigDefaultData');
        $this->assertEquals(
            ['value' => AdvancedSearchSharedCatalog::CREATE_NEW_VALUE],
            $method->invoke($this->advancedSearch)
        );
    }
}
