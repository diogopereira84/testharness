<?php
/**
 * @category  Fedex
 * @package   Fedex_Canva
 * @copyright Copyright (c) 2023 Fedex.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Block;

use PHPUnit\Framework\TestCase;
use Fedex\Canva\Block\CanvaHeader;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class CanvaHeaderTest extends TestCase
{
    private CanvaHeader $block;
    private ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject $scopeConfigMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $contextMock = $this->createMock(Context::class);
        $contextMock->method('getScopeConfig')->willReturn($this->scopeConfigMock);

        $this->block = new CanvaHeader($contextMock);
    }

    /**
     * @return void
     */
    public function testGetTutorialCustomUrl()
    {
        $expectedUrl = 'https://example.com/tutorial';
        $this->scopeConfigMock->method('getValue')
            ->with('fedex/canva_design/tutorial_custom_url', ScopeInterface::SCOPE_STORE)
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->block->getTutorialCustomUrl());
    }
}
