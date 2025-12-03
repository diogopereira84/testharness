<?php
/**
 * @category  Fedex
 * @package   Fedex_SharedCatalogCustomization
 * @copyright Copyright (c) 2024 FedEx.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Ui\Component\Form\Field;

use Fedex\SharedCatalogCustomization\Ui\Component\Form\Field\AdvancedSearchCustomerGroup;
use Fedex\Company\Api\Data\ConfigInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use PHPUnit\Framework\TestCase;

class AdvancedSearchCustomerGroupTest extends TestCase
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
     * @var GroupManagementInterface
     */
    private $groupManagementInterface;

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

        $this->groupManagementInterface = $this->createMock(
            GroupManagementInterface::class
        );

        $this->advancedSearch = new AdvancedSearchCustomerGroup(
            $this->context,
            $this->uiComponentFactory,
            $this->groupManagementInterface,
            $this->companyConfigInterface
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testGetConfigDefaultData()
    {
        $reflection = new \ReflectionClass(AdvancedSearchCustomerGroup::class);
        $method = $reflection->getMethod('getConfigDefaultData');
        $this->assertEquals(
            ['value' => AdvancedSearchCustomerGroup::CREATE_NEW_VALUE],
            $method->invoke($this->advancedSearch)
        );
    }
}
