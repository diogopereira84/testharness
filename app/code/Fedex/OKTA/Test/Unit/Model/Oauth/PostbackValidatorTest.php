<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Oauth;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Fedex\OKTA\Model\Oauth\PostbackValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PostbackValidatorTest extends TestCase
{
    /**
     * @var PostbackValidator
     */
    private PostbackValidator $postbackValidator;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface $loggerMock;

    /**
     * @var RequestInterface|MockObject
     */
    private RequestInterface $requestMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->postbackValidator = new PostbackValidator($this->loggerMock);
    }

    public function testValidate()
    {
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['state'], ['code'])
            ->willReturnOnConsecutiveCalls('okta_sso', 'some_code');
        $this->assertTrue($this->postbackValidator->validate($this->requestMock));
    }

    public function testValidateWrongState()
    {
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['state'], ['code'])
            ->willReturnOnConsecutiveCalls('other', 'some_code');
        $this->expectException(LocalizedException::class);
        $this->postbackValidator->validate($this->requestMock);
    }

    public function testValidateWrongCode()
    {
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['state'], ['code'])
            ->willReturnOnConsecutiveCalls('okta_sso', false);
        $this->expectException(LocalizedException::class);
        $this->postbackValidator->validate($this->requestMock);
    }
}
