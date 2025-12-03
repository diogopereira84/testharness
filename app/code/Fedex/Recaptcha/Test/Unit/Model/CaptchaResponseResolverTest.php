<?php
namespace Fedex\Recaptcha\Test\Unit\Model;

use Fedex\Recaptcha\Model\CaptchaResponseResolver;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\TestCase;

class CaptchaResponseResolverTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Serialize\SerializerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $serializerMock;
    protected $requestInterfaceMock;
    protected $captchaResponseResolverMock;
    protected function setUp(): void
    {
        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requestInterfaceMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getContent', 'getParams'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->captchaResponseResolverMock = new CaptchaResponseResolver(
            $this->serializerMock
        );
    }

    public function testResolve()
    {

        $this->requestInterfaceMock->expects($this->once())->method('getContent')->willReturn(true);
        $this->requestInterfaceMock->expects($this->once())->method('getParams')
            ->willReturn(['g-recaptcha-response' => '12345']);

        $this->assertIsString($this->captchaResponseResolverMock->resolve($this->requestInterfaceMock));
    }

    public function testResolveSubmitOrder()
    {

        $this->requestInterfaceMock->expects($this->once())->method('getContent')->willReturn(true);
        $this->requestInterfaceMock->expects($this->once())->method('getParams')
            ->willReturn(['data' => json_encode(['g-recaptcha-response' => '12345'])]);

        $this->assertIsString($this->captchaResponseResolverMock->resolve($this->requestInterfaceMock));
    }

    public function testResolveNoContent()
    {

        $this->requestInterfaceMock->expects($this->once())->method('getContent')->willReturn([]);

        $this->expectException(InputException::class);
        $this->captchaResponseResolverMock->resolve($this->requestInterfaceMock);
    }

    public function testResolveNoParam()
    {

        $this->requestInterfaceMock->expects($this->once())->method('getContent')->willReturn(true);
        $this->requestInterfaceMock->expects($this->once())->method('getParams')
            ->willThrowException(new \InvalidArgumentException());

        $this->expectException(InputException::class);
        $this->captchaResponseResolverMock->resolve($this->requestInterfaceMock);
    }

    public function testResolveEmptyParam()
    {

        $this->requestInterfaceMock->expects($this->once())->method('getContent')->willReturn(true);
        $this->requestInterfaceMock->expects($this->once())->method('getParams')
            ->willReturn(['wrong-param' => []]);

        $this->expectException(InputException::class);
        $this->captchaResponseResolverMock->resolve($this->requestInterfaceMock);
    }
}
