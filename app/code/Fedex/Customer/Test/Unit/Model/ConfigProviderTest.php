<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Customer\Test\Unit\Model;

use Fedex\Customer\Api\Data\ConfigInterface;
use Fedex\Customer\Model\ConfigProvider;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * ConfigProviderTest Model
 */
class ConfigProviderTest extends TestCase
{
    /**
     * @var ConfigProvider|MockObject
     */
    private $configProvider;

    /**
     * @var ConfigInterface $configInterface
     */
    protected $config;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->configProvider = $objectManagerHelper->getObject(
            ConfigProvider::class,
            [
                'config' => $this->config
            ]
        );
    }
    /**
     * Test getConfig
     *
     * @return void
     */
    public function testGetConfig()
    {
        $this->config->expects($this->once())->method('isMarketingOptInEnabled')->willReturn(true);
        $response = [
            'marketing_opt_in_toggle' => true
        ];

        $this->assertEquals($response, $this->configProvider->getConfig());
    }
}
