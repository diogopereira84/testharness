<?php
/**
 * Fedex
 * Copyright (C) 2021 Fedex <info@fedex.com>
 *
 * PHP version 7
 *
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Fedex <info@fedex.com>
 * @copyright 2006-2021 Fedex (http://www.fedex.com/)
 * @license   http://opensource.org/licenses/gpl-3.0.html
 * GNU General Public License,version 3 (GPL-3.0)
 * @link      http://fedex.com
 */

namespace Fedex\Company\Test\Unit\Plugin\Adminhtml\Controller\Index;

use Fedex\Company\Helper\Data as CompanyData;
use Fedex\Company\Plugin\Adminhtml\Controller\Index\Edit;
use Magento\Backend\App\Action\Context;
use Magento\Company\Controller\Adminhtml\Index\Edit as MagentoCompanyEditController;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Company Edit Plugin.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditTest extends TestCase
{
    /**
     * @var (\Magento\Backend\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $companyDataMock;
    protected $requestMock;
    protected $messageManagerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $editMock;
    private \Closure $proceed;

    /**
     * Test setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyDataMock = $this->getMockBuilder(CompanyData::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCreditCardTokenExpiryDateTime',
                    'isValidCreditCardTokenExpiryDate',
                ]
            )
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'addWarningMessage',
                ]
            )
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->editMock = $this->objectManager->getObject(
            Edit::class,
            [
                'request' => $this->requestMock,
                'messageManager' => $this->messageManagerMock,
                'companyHelper' => $this->companyDataMock,
            ]
        );
    }

    /**
     * Test Execute With Valid Company
     *
     * @return void
     */
    public function testaroundExecute()
    {
        $className = MagentoCompanyEditController::class;

        /**
         * Create Mock For Company Edit Class
         *
         * @var MockObject $subject
         */
        $subject = $this->createMock($className);

        $this->proceed = function () use ($subject) {
            return $subject;
        };
        $companyId = 31;
        $ccTokenExpiryDate = "2027-01-01 01:01:01";

        $this->requestMock->method('getParam')
            ->with('id')
            ->willReturn($companyId);

        $this->companyDataMock->expects($this->any())
            ->method('getCreditCardTokenExpiryDateTime')
            ->willReturn($ccTokenExpiryDate);

        $this->companyDataMock->expects($this->any())
            ->method('isValidCreditCardTokenExpiryDate')
            ->willReturn(false);
        $this->messageManagerMock->expects($this->any())
            ->method('addWarningMessage')
            ->willReturnSelf();

        $this->assertSame($subject, $this->editMock->aroundExecute($subject, $this->proceed));
    }

    /**
     * Test Execute With CC token expiry date as NULL
     *
     * @return void
     */
    public function testaroundExecuteWithCcTokenExpiryDateNull()
    {
        $className = MagentoCompanyEditController::class;

        /**
         * Create Mock For Company Edit Class
         *
         * @var MockObject $subject
         */
        $subject = $this->createMock($className);

        $this->proceed = function () use ($subject) {
            return $subject;
        };
        $companyId = 31;
        $ccTokenExpiryDate = null;

        $this->requestMock->method('getParam')
            ->with('id')
            ->willReturn($companyId);

        $this->companyDataMock->expects($this->any())
            ->method('getCreditCardTokenExpiryDateTime')
            ->willReturn($ccTokenExpiryDate);

        $this->companyDataMock->expects($this->any())
            ->method('isValidCreditCardTokenExpiryDate')
            ->willReturn(false);

        $this->messageManagerMock->expects($this->any())
            ->method('addWarningMessage')
            ->willReturnSelf();

        $this->assertSame($subject, $this->editMock->aroundExecute($subject, $this->proceed));
    }
}
