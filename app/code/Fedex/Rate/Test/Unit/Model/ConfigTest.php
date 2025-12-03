<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Rate\Test\Unit\Model;

use Fedex\Rate\Model\Config;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigTest extends TestCase
{
    protected $sopeConfigInterfaceMockup;
    protected $configModel;
    /**
     * Setup method to creating mock object
     */
    protected function setUp(): void
    {
        $this->sopeConfigInterfaceMockup = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(['critical'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->configModel = $objectManagerHelper->getObject(
            Config::class,
            [
               'scopeConfig' => $this->sopeConfigInterfaceMockup
            ]
        );
    }

    /**
     * @test getRateApiUrl
     * @return void
     *
     */
    public function testGetRateApiUrl()
    {
      $rateApiUrl = 'https://api.test.office.fedex.com/rate/fedexoffice/v2/rates';

      $this->sopeConfigInterfaceMockup->expects($this->exactly(1))
         ->method('getValue')
         ->willReturn($rateApiUrl);

      $this->assertEquals($rateApiUrl, $this->configModel->getRateApiUrl());
    }
}
