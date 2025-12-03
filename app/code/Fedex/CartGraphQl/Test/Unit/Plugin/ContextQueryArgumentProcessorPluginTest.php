<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Plugin;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\LiveSearchAdapter\Model\QueryArgumentProcessor\ContextQueryArgumentProcessor;
use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Plugin\ContextQueryArgumentProcessorPlugin;

class ContextQueryArgumentProcessorPluginTest extends TestCase
{
    protected $plugin;
    protected function setUp(): void
    {
        $this->plugin = new ContextQueryArgumentProcessorPlugin();
    }

    public function testAfterGetQueryArgumentValue(): void
    {
        $searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $subjectMock = $this->createMock(ContextQueryArgumentProcessor::class);
        $result = ['some', 'array'];

        $actualResult = $this->plugin->afterGetQueryArgumentValue($subjectMock, $result, $searchCriteriaMock);
        $this->assertSame([], $actualResult);
    }
}
