<?php
/**
 * @category    Fedex
 * @package     Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderSidebarConfigProvider;

class SubmitOrderSidebarConfigProviderTest extends TestCase
{
        protected $submitOrderSidebarConfigProviderMock;
        protected $toggleConfigMock;
        /**
         * @return void
         */
        public function setup():void
        {
            $this->submitOrderSidebarConfigProviderMock = (new ObjectManager($this))->getObject(
                SubmitOrderSidebarConfigProvider::class,
                    []);

                $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
                    ->setMethods(['getToggleConfigValue','getToggleConfig'])
                    ->disableOriginalConstructor()
                    ->getMock();
        }

        /**
         * @return void
         */
        public function testGetConfig()
        {
            $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
                ->with(SubmitOrderSidebarConfigProvider::TOGGLE_FEATURE_KEY)->willReturn(true);
            $response = [
                'tiger_customer_friendly_cc_msg_toggle' => false,
                'promise_time_warning_enabled' => false
            ];
            $this->assertEquals($response, $this->submitOrderSidebarConfigProviderMock
                ->getConfig());
        }

        /**
         * @return void
         */
        public function testIsCustomerFriendlyCcMsgToggleEnabled()
        {
            $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
                ->with(SubmitOrderSidebarConfigProvider::TOGGLE_FEATURE_KEY)->willReturn(false);
            $this->assertEquals(false, $this->submitOrderSidebarConfigProviderMock
                ->isCustomerFriendlyCcMsgToggleEnabled());
        }

}
