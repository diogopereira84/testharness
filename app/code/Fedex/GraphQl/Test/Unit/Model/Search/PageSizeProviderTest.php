<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Model\Token;

use Fedex\GraphQl\Model\Search\PageSizeProvider;
use Magento\Search\Model\EngineResolver;
use PHPUnit\Framework\TestCase;

class PageSizeProviderTest extends TestCase
{
    /**
     * @var (\Magento\Search\Model\EngineResolver & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $engineResolver;
    protected $pageSizeProvider;
    protected function setUp(): void
    {
        $this->engineResolver = $this->getMockBuilder(EngineResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageSizeProvider = new PageSizeProvider(
            $this->engineResolver
        );
    }

    public function testGetMaxPageSizeWithFixLivesearchPageSizeLimit()
    {
        $this->assertEquals(500, $this->pageSizeProvider->getMaxPageSize());
    }
}
