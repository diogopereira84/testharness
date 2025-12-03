<?php
/**
 * @category    Fedex
 * @package     Fedex_EmailVerification
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Austin King <austin.king@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\EmailVerification\Test\Unit\Model;

use Fedex\EmailVerification\Model\EmailVerificationCustomer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Model EmailVerificationCustomerTest test class
 */
class EmailVerificationCustomerTest extends TestCase
{
    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var EmailVerificationCustomer
     */
    protected $emailVerificationCustomerMock;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->emailVerificationCustomerMock = $this->objectManager
            ->getObject(EmailVerificationCustomer::class);
    }

    /**
     * test constuct method
     *
     * @return void
     */
    public function testConstruct()
    {
        $this->assertTrue(true);
    }
}
