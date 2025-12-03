<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Model;

use Fedex\GraphQl\Model\CompositeUserContext;
use Magento\Authorization\Model\UserContextInterface;
use PHPUnit\Framework\TestCase;

class CompositeUserContextTest extends TestCase
{
    /**
     * @var CompositeUserContext
     */
    private CompositeUserContext $compositeUserContext;

    protected function setUp(): void
    {
        $this->compositeUserContext = new CompositeUserContext();
    }

    public function testGetUserId()
    {
        $this->assertEquals(0, $this->compositeUserContext->getUserId());
    }

    public function testGetUserType()
    {
        $this->assertEquals(UserContextInterface::USER_TYPE_INTEGRATION, $this->compositeUserContext->getUserType());
    }
}
