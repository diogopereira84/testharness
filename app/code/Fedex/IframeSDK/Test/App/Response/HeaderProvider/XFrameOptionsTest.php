<?php
declare(strict_types=1);

namespace Fedex\IframeSDK\Test\App\Response\HeaderProvider;

use Fedex\IframeSDK\App\Response\HeaderProvider\XFrameOptions;
use \Magento\Framework\App\Response\Http;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class XFrameOptionsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    const DEPLOYMENT_CONFIG_X_FRAME_OPT = 'x-frame-options';
    const BACKEND_X_FRAME_OPT = '*';
    protected $headerName = Http::HEADER_X_FRAME_OPT;
    protected $headerValue;

    private XFrameOptions $xframeOptionsMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->xframeOptionsMock = $this->objectManager->getObject(
            XFrameOptions::class,
            [
                'headerValue' => '*'
            ]
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals(Http::HEADER_X_FRAME_OPT, $this->xframeOptionsMock->getName());
    }

    public function testGetValue(): void
    {
        $this->assertEquals('*', $this->xframeOptionsMock->getValue());
    }
}
