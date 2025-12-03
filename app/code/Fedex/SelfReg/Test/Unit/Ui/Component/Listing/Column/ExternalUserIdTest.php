<?php

declare(strict_types=1);

namespace Fedex\SelfReg\Test\Unit\Ui\Component\Listing\Column;

use Fedex\Base\Helper\Auth;
use Fedex\SelfReg\Ui\Component\Listing\Column\ExternalUserId;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExternalUserIdTest extends TestCase
{
    /** @var MockObject|ContextInterface */
    private $contextMock;

    /** @var MockObject|UiComponentFactory */
    private $uiComponentFactoryMock;

    /** @var MockObject|Auth */
    private $authHelperMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(ContextInterface::class);
        $this->uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $this->authHelperMock = $this->createMock(Auth::class);
    }

    public function testConstructorWithAuthFcl(): void
    {
        $this->authHelperMock->method('getCompanyAuthenticationMethod')->willReturn(Auth::AUTH_FCL);

        $components = ['component1' => 'value1'];
        $data = ['key1' => 'value1'];

        $externalUserId = new ExternalUserId(
            $this->contextMock,
            $this->uiComponentFactoryMock,
            $this->authHelperMock,
            $components,
            $data
        );

        $this->assertInstanceOf(ExternalUserId::class, $externalUserId);
        $this->assertEquals($data, $externalUserId->getData());
    }

    public function testConstructorWithoutAuthFcl(): void
    {
        $this->authHelperMock->method('getCompanyAuthenticationMethod')->willReturn('OTHER_AUTH_METHOD');

        $components = ['component1' => 'value1'];
        $data = ['key1' => 'value1'];

        $externalUserId = new ExternalUserId(
            $this->contextMock,
            $this->uiComponentFactoryMock,
            $this->authHelperMock,
            $components,
            $data
        );

        $this->assertInstanceOf(ExternalUserId::class, $externalUserId);
        $this->assertEmpty($externalUserId->getData());
    }
}