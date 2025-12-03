<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Test\Unit\Block;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\EnhancedProfile\Block\EnhancedProfile;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile as EnhancedProfileViewModel;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EnhancedProfileTest extends TestCase
{
    protected $enhancedProfileData;
    /**
     * @var EnhancedProfileViewModel|MockObject
     */
    protected $enhancedProfileViewModel;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {

        $this->enhancedProfileViewModel = $this->getMockBuilder(EnhancedProfileViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['setProfileSession', 'getLoggedInProfileInfo', 'getPreferredDelivery', 'getOpeningHours'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->enhancedProfileData = $this->objectManager->getObject(
            EnhancedProfile::class,
            [
                'enhancedProfileViewModel' => $this->enhancedProfileViewModel
            ]
        );
    }

    /**
     * Test SetProfileSession
     */
    public function testSetProfileSession()
    {
        $this->enhancedProfileViewModel->expects($this->any())->method('setProfileSession')->willReturnSelf();
        $this->assertEquals(null, $this->enhancedProfileData->setProfileSession());
    }

    /**
     * Test GetLoggedInProfileInfo
     */
    public function testGetLoggedInProfileInfo()
    {
        $this->enhancedProfileViewModel->expects($this->any())->method('getLoggedInProfileInfo')->willReturnSelf();
        $this->assertNotEquals(null, $this->enhancedProfileData->getLoggedInProfileInfo());
    }

    /**
     * Test GetPreferredDelivery
     */
    public function testGetPreferredDelivery()
    {
        $this->enhancedProfileViewModel->expects($this->any())->method('getPreferredDelivery')->willReturnSelf();
        $this->assertNotEquals(null, $this->enhancedProfileData->getPreferredDelivery('1242'));
    }

    /**
     * Test GetOpeningHours
     */
    public function testGetOpeningHours()
    {
        $workingHours[0] = (object) [
                            'date' => '27-06-2021',
                            'day' => 'MONDAY',
                            'schedule' => '27-06-2021',
                            'openTime' => '9:01 AM',
                            'closeTime' => '6:01 PM'
                        ];

        $workingHours[1] = (object) [
                            'date' => '27-06-2022',
                            'day' => 'TUESDAY',
                            'schedule' => 'Open',
                            'openTime' => '9:02 AM',
                            'closeTime' => '6:02 PM'
                        ];

        $workingHours[2] = (object) [
                            'date' => '27-06-2023',
                            'day' => 'WEDNESDAY',
                            'schedule' => 'Open',
                            'openTime' => '9:03 AM',
                            'closeTime' => '6:03 PM'
                        ];

        $workingHours[3] = (object) [
                            'date' => '27-06-2022',
                            'day' => 'THURSDAY',
                            'schedule' => 'Open',
                            'openTime' => '9:04 AM',
                            'closeTime' => '6:05 PM'
                        ];

        $workingHours[4] = (object) [
                            'date' => '01-07-2022',
                            'day' => 'FRIDAY',
                            'schedule' => 'Open',
                            'openTime' => '9:06 AM',
                            'closeTime' => '6:07 PM'
                        ];

        $workingHours[5] = (object) [
                            'date' => '02-07-2022',
                            'day' => 'SATURDAY',
                            'schedule' => 'Open',
                            'openTime' => '9:08 AM',
                            'closeTime' => '6:09 PM'
                        ];

        $workingHours[6] = (object) [
                            'date' => '03-07-2022',
                            'day' => 'SUNDAY',
                            'schedule' => 'Closed',
                            'openTime' => '',
                            'closeTime' => '',
                        ];

        $this->enhancedProfileViewModel->expects($this->any())->method('getOpeningHours')->willReturnSelf();
        $this->assertNotEquals(null, $this->enhancedProfileData->getOpeningHours($workingHours));
    }
}
