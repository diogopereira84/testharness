<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Fedex\OKTA\Model\EntityDataValidator;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use PHPUnit\Framework\TestCase;

class EntityDataValidatorTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var array
     */
    private array $requiredFields;

    /**
     * @var EntityDataValidator
     */
    private EntityDataValidator $entityDataValidator;

    /**
     * @var OktaHelper
     */
    private OktaHelper $oktaHelper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->oktaHelper = $this->createMock(OktaHelper::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->requiredFields = [
            'email' => EntityDataValidator::KEY_EMAIL,
            'sub' => EntityDataValidator::KEY_SUB
        ];
        $this->entityDataValidator = new EntityDataValidator($this->requiredFields, $this->oktaHelper, $this->logger);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testValidateSuccess(): void
    {
        $this->assertTrue($this->entityDataValidator->validate([
            'email' => 'some@email.com',
            'sub' => 'some_sub'
        ]));
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testValidateFailed(): void
    {
        $this->expectException(LocalizedException::class);
        $this->entityDataValidator->validate([]);
    }
}
