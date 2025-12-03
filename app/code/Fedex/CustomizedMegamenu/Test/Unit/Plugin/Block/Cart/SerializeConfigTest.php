<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomizedMegamenu\Test\Unit\Plugin\Block\Cart;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\CustomizedMegamenu\Plugin\Block\Cart\SerializeConfig;
use Magento\Checkout\Block\Cart\Sidebar;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class plugin test
 */
class SerializeConfigTest extends TestCase
{
    /**
     * @var Json|MockObject
     */
    private $jsonSerializerMock;

    /**
     * @var SerializeConfig
     */
    private $plugin;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->jsonSerializerMock = $this->createMock(Json::class);

        $this->plugin = new SerializeConfig(
            $this->jsonSerializerMock
        );
    }

    /**
     * Test afterGetSerializedConfigWithToggleOn
     *
     * @return void
     */
    public function testAfterGetSerializedConfigWithToggleEnabled()
    {
        $subjectMock = $this->createMock(Sidebar::class);
        $subjectMock->method('getConfig')
            ->willReturn(['key' => 'value']);

        $this->jsonSerializerMock->method('serialize')
            ->with(['key' => 'value'])
            ->willReturn('{"key":"value"}');

        $result = $this->plugin->afterGetSerializedConfig($subjectMock, 'original_result');

        $this->assertEquals('{"key":"value"}', $result);
    }
}
