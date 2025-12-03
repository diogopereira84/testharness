<?php
/**
 * @category  Fedex
 * @package   Fedex_EnvironmentManager
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Test\Unit\Setup\Patch\Data;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Fedex\EnvironmentManager\Setup\Patch\Data\FixUnableToLoadTheme;

class FixUnableToLoadThemeTest extends TestCase
{
    /**
     * Old theme path value
     */
    private const OLD_THEME_PATH = 'Fedex/poc';

    /**
     * New theme path value
     */
    private const NEW_THEME_PATH = 'Fedex/office';

    /**
     * @var MockObject|ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface|MockObject $moduleDataSetupMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private AdapterInterface|MockObject $connectionMock;

    /**
     * @var FixUnableToLoadTheme
     */
    private FixUnableToLoadTheme $fixUnableToLoadTheme;

    /**
     * @var Select|MockObject
     */
    private Select|MockObject $selectMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->moduleDataSetupMock = $this->createMock(ModuleDataSetupInterface::class);
        $this->connectionMock = $this->createMock(AdapterInterface::class);
        $this->selectMock = $this->createMock(Select::class);
        $this->selectMock->method('from')
            ->willReturnSelf();
        $this->selectMock->method('where')
            ->willReturnSelf();

        $this->moduleDataSetupMock->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->connectionMock->method('select')
            ->willReturn($this->selectMock);

        $this->fixUnableToLoadTheme = new FixUnableToLoadTheme($this->moduleDataSetupMock);
    }

    /**
     * Test getAliases method
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->fixUnableToLoadTheme->getAliases());
    }

    /**
     * Test getDependencies method
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], $this->fixUnableToLoadTheme->getDependencies());
    }

    /**
     * Test apply method
     *
     * @return void
     */
    public function testApply(): void
    {
        $oldThemeRow = ['theme_id' => 1];
        $newThemeRow = ['theme_id' => 2];
        $customDesignProductAttributeRow = ['attribute_id' => 10];
        $customDesignCategoryAttributeRow = ['attribute_id' => 20];

        $this->connectionMock->expects($this->exactly(4))
            ->method('fetchRow')
            ->willReturnOnConsecutiveCalls(
                $oldThemeRow,
                $newThemeRow,
                $customDesignProductAttributeRow,
                $customDesignCategoryAttributeRow
            );

        $this->connectionMock->expects($this->exactly(5))
            ->method('update')
            ->withConsecutive(
                [
                    $this->anything(),
                    ['theme_id' => $newThemeRow['theme_id']],
                    ['theme_id = ?' => $oldThemeRow['theme_id']]
                ],
                [
                    $this->anything(),
                    ['custom_theme' => self::NEW_THEME_PATH],
                    ['custom_theme = ?' => self::OLD_THEME_PATH]
                ],
                [
                    $this->anything(),
                    ['theme_id' => $newThemeRow['theme_id']],
                    ['theme_id = ?' => $oldThemeRow['theme_id']]
                ],
                [
                    $this->anything(),
                    ['value' => $newThemeRow['theme_id']],
                    ['attribute_id = ?' => $customDesignProductAttributeRow['attribute_id']]
                ],
                [
                    $this->anything(),
                    ['value' => $newThemeRow['theme_id']],
                    ['attribute_id = ?' => $customDesignCategoryAttributeRow['attribute_id']]
                ]
            );

        $this->connectionMock->expects($this->once())->method('startSetup');
        $this->connectionMock->expects($this->once())->method('endSetup');

        $this->fixUnableToLoadTheme->apply();
    }

    /**
     * Test apply method with theme not found
     *
     * @return void
     */
    public function testApplyThemeNotFound(): void
    {
        $oldThemeRow = null;
        $newThemeRow = null;
        $customDesignProductAttributeRow = ['attribute_id' => 10];
        $customDesignCategoryAttributeRow = ['attribute_id' => 20];

        $this->connectionMock->expects($this->exactly(2))
            ->method('fetchRow')
            ->willReturnOnConsecutiveCalls(
                $oldThemeRow,
                $newThemeRow,
                $customDesignProductAttributeRow,
                $customDesignCategoryAttributeRow
            );

        $this->connectionMock->expects($this->once())->method('startSetup');

        $this->fixUnableToLoadTheme->apply();
    }

    /**
     * Test apply method with attributes not found
     *
     * @return void
     */
    public function testApplyAttributesNotFound(): void
    {
        $oldThemeRow = ['theme_id' => 1];
        $newThemeRow = ['theme_id' => 2];
        $customDesignProductAttributeRow = null;
        $customDesignCategoryAttributeRow = null;

        $this->connectionMock->expects($this->exactly(4))
            ->method('fetchRow')
            ->willReturnOnConsecutiveCalls(
                $oldThemeRow,
                $newThemeRow,
                $customDesignProductAttributeRow,
                $customDesignCategoryAttributeRow
            );

        $this->connectionMock->expects($this->exactly(3))
            ->method('update')
            ->withConsecutive(
                [
                    $this->anything(),
                    ['theme_id' => $newThemeRow['theme_id']],
                    ['theme_id = ?' => $oldThemeRow['theme_id']]
                ],
                [
                    $this->anything(),
                    ['custom_theme' => self::NEW_THEME_PATH],
                    ['custom_theme = ?' => self::OLD_THEME_PATH]
                ],
                [
                    $this->anything(),
                    ['theme_id' => $newThemeRow['theme_id']],
                    ['theme_id = ?' => $oldThemeRow['theme_id']]
                ]
            );

        $this->connectionMock->expects($this->once())->method('startSetup');

        $this->fixUnableToLoadTheme->apply();
    }
}
