<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Block\Adminhtml\Auth;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Url as FrontendUrlHelper;
use Fedex\OKTA\Block\Adminhtml\Auth\Logout;
use PHPUnit\Framework\TestCase;

class LogoutTest extends TestCase
{
    public function testGetFrontendUrlHelper(): void
    {
        $contextMock = $this->createMock(Context::class);
        $frontendUrlHelperMock = $this->createMock(FrontendUrlHelper::class);
        $logout = new Logout($frontendUrlHelperMock, $contextMock);

        $this->assertInstanceOf(FrontendUrlHelper::class, $logout->getFrontendUrlHelper());
    }
}