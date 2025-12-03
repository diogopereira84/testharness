<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Block;

use Fedex\CIDPSG\Block\AuthorizedUser;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\CIDPSG\Helper\AdminConfigHelper;

/**
 * Test class for AuthorizedUser Block
 */
class AuthorizedUserTest extends TestCase
{
    protected $authorizedUserMock;
    /**
     * @var AdminConfigHelper $adminConfigHelperMock
     */
    protected $adminConfigHelperMock;

    /**
     * Set up method.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->adminConfigHelperMock = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllStates', 'getConfirmationPopupMessage'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->authorizedUserMock = $objectManagerHelper->getObject(
            AuthorizedUser::class,
            [
                'adminConfigHelper' => $this->adminConfigHelperMock
            ]
        );
        $this->authorizedUserMock->allStates = [
            ['label' => 'AL', 'title'=>'Alabama'],
            ['label' => 'AK', 'title'=>'Alaska'],
            ['label' => 'CA', 'title'=>'California'],
            ['label' => 'IN', 'title'=>'Indiana'],
            ['label' => 'AB', 'title'=>'Alberta'],
            ['label' => 'MB', 'title'=>'Manitoba'],
            ['label' => 'NB', 'title'=>'New Brunswick'],
            ['label' => 'NL', 'title'=>'Newfoundland']
        ];
    }

    /**
     * Test method for getAllStates
     *
     * @return void
     */
    public function testGetAllStates()
    {
        $this->adminConfigHelperMock
            ->expects($this->once())
            ->method('getAllStates')
            ->with('CA')
            ->willReturn($this->authorizedUserMock->allStates);

        $this->assertEquals($this->authorizedUserMock->allStates, $this->authorizedUserMock->getAllStates('CA'));
    }

    /**
     * Test method for getConfirmationPopupMessage
     *
     * @return void
     */
    public function testGetConfirmationPopupMessage()
    {
        $popupTestMessage = "test popup message";
        $this->adminConfigHelperMock
            ->expects($this->once())
            ->method('getConfirmationPopupMessage')
            ->willReturn($popupTestMessage);

        $this->assertEquals($popupTestMessage, $this->authorizedUserMock->getConfirmationPopupMessage());
    }
}
