<?php
/**
 * @category    Fedex
 * @package     Fedex_FXOPricing
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\InStoreConfigurations\Test\Unit\Model;

use Fedex\GraphQl\Model\RequestQueryValidator;
use PHPUnit\Framework\TestCase;
use Fedex\InStoreConfigurations\Model\Organization;
use Magento\Framework\App\Request\Http;

/**
 * @covers \Fedex\InStoreConfigurations\Model\Organization
 */
class OrganizationTest extends TestCase
{
    /** @var Http */
    private Http $request;

    /** @var ConfigInterface */
    private ConfigInterface $config;

    /** @var RequestQueryValidator  */
    private RequestQueryValidator $validator;

    /** @var Organization  */
    private Organization $organization;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->request = $this->createMock(Http::class);
        $this->validator = $this->createMock(RequestQueryValidator::class);

        $this->organization = new Organization(
            $this->request,
            $this->validator
        );
    }

    public function testGetOrganization()
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->willReturn(
                [
                    'organization' => 'organization'
                ]
            );

        $this->validator->expects($this->once())
            ->method('isGraphQl')
            ->willReturn(true);

        $this->assertEquals('organization', $this->organization->getOrganization('test123'));
    }

    public function testGetOrganizationNoGraphql()
    {
        $this->request->expects($this->never())
            ->method('getParam');

        $this->validator->expects($this->once())
            ->method('isGraphQl')
            ->willReturn(false);

        $this->assertEquals('FXO', $this->organization->getOrganization('test123'));
    }

    public function testGetOrganizationNoContactInformation()
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->willReturn(null);

        $this->validator->expects($this->once())
            ->method('isGraphQl')
            ->willReturn(true);

        $this->assertEquals('test123', $this->organization->getOrganization('test123'));
    }

    public function testGetOrganizationNoOrganization()
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->willReturn(
                [
                    'test123' => 'testasdf'
                ]
            );

        $this->validator->expects($this->once())
            ->method('isGraphQl')
            ->willReturn(true);

        $this->assertEquals('test123', $this->organization->getOrganization('test123'));
    }

}
