<?php
/**
 * @category    Fedex
 * @package     Fedex_CartGraphQl
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver;

use Fedex\SubmitOrderSidebar\Model\TransactionApi\AbstractRequestBuilder;
use PHPUnit\Framework\TestCase;

class AbstractRequestBuilderTest extends TestCase
{
    /**
     * @var AbstractRequestBuilder
     */
    private AbstractRequestBuilder $abstractRequestBuilder;

    /**
     * Initialize
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->abstractRequestBuilder = $this->getMockForAbstractClass(AbstractRequestBuilder::class);
    }

    /**
     * Check that returned instance is correct
     *
     * @return void
     */
    public function testGetRequestCommand(): void
    {
        $curDate = date('Y-m-d H:i:s');
        $this->assertEquals($curDate, $this->abstractRequestBuilder->getDateFormatted());
    }
}
