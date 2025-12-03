<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\SSO\Test\Unit\Model;

use Fedex\SSO\Model\ProfileMockService;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProfileMockServiceTest extends TestCase
{
   
    protected $ssoConfiguration;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $profileMockService;
    /** @var RequestInterface |MockObject */
    protected $_request;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->ssoConfiguration = $this->getMockBuilder(SsoConfiguration::class)
            ->setMethods(
                [
                    'getGeneralConfig'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
      
        $this->objectManager = new ObjectManager($this);
        $this->profileMockService = $this->objectManager->getObject(
            ProfileMockService::class,
            [
            'ssoConfiguration' => $this->ssoConfiguration
            ]
        );
    }

    /**
     * Test getProfileMockService function
     */
    public function testGetProfileMockService()
    {
          $customerProfile = 'json_data';

          $this->ssoConfiguration->expects($this->any())->method('getGeneralConfig')->willReturn($customerProfile);

          $fcluuid = 'SD343GSFSFS';
          $fdxcbid = '2124141241241242112421';
          $this->assertSame($customerProfile, $this->profileMockService->getProfileMockService($fcluuid, $fdxcbid));
    }

    /**
     * Test testGetProfileMockServiceWithException function
     */
    public function testGetProfileMockServiceWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->ssoConfiguration->expects($this->any())->method('getGeneralConfig')->willThrowException($exception);

        $fcluuid = 'SD343GSFSFS';
        $fdxcbid = '2124141241241242112421';
      
        $this->assertSame(null, $this->profileMockService->getProfileMockService($fcluuid, $fdxcbid));
    }
}
