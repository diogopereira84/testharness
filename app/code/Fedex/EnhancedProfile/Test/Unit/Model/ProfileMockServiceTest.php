<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\EnhancedProfile\Test\Unit\Model;

use Fedex\EnhancedProfile\Model\ProfileMockService;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProfileMockServiceTest extends TestCase
{
   
    protected $enhancedProfile;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $profileMockService;
    /** @var RequestInterface |MockObject */
    protected $request;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->enhancedProfile = $this->getMockBuilder(EnhancedProfile::class)
            ->setMethods(
                [
                    'getConfigValue'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
      
        $this->objectManager = new ObjectManager($this);
        $this->profileMockService = $this->objectManager->getObject(
            ProfileMockService::class,
            [
            'enhancedProfile' => $this->enhancedProfile
            ]
        );
    }

    /**
     * Test getProfileMockService function
     */
    public function testGetProfileMockService()
    {
          $customerProfile = 'json_data';

          $this->enhancedProfile->expects($this->any())->method('getConfigValue')->willReturn($customerProfile);

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
        $this->enhancedProfile->expects($this->any())->method('getConfigValue')->willThrowException($exception);

        $fcluuid = 'SD343GSFSFS';
        $fdxcbid = '2124141241241242112421';
      
        $this->assertSame(null, $this->profileMockService->getProfileMockService($fcluuid, $fdxcbid));
    }
}
