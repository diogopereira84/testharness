<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Yash Rajeshbhai solanki
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Plugin;

use Fedex\GraphQl\Plugin\ValidateCartIdExceptionChange;
use Magento\Framework\GraphQl\Query\QueryProcessor;
use Magento\Framework\GraphQl\Schema;
use PHPUnit\Framework\TestCase;

class ValidateCartIdExceptionChangeTest extends TestCase
{
    protected $validateCartIdExceptionChange;
    private const RESULT_MOCK = '{"errors":[{"message":"Variable \"$cart_id\" of non-null type \"String!\" must not be null.","locations":[{"line":2,"column":2}]}]}';
    private const VARIABLE_VALUES = '{"cart_id":null,"pickup_location_id":"0798","pickup_store_id":"0798","pickup_location_name":"FedEx Office Print & Ship Center","pickup_location_street":"Frisco","pickup_location_city":"Frisco","pickup_location_state":"TX","pickup_location_zipcode":"75034","pickup_location_country":"US","pickup_location_date":"2024-01-23T03:00:00","firstname":"Eduardo","lastname":"Dias","email":"eduardodias.osv@fedex.com","telephone":"5056000000","ext":"","retail_customer_id":"1577918361","organization":"McFadyen2","alternate_contact":[],"note_text":"note2","note_creationTime":"2023-04-28T10:30:00Z","note_user":"Angel","note_user_reference_reference":"Testing","note_user_reference_source":"MAGENTO"}';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->validateCartIdExceptionChange = new ValidateCartIdExceptionChange();
    }

    /**
     * @return void
     */
    public function testAfterProcessMissingParameter(): void
    {
        $queryProcessor = $this->createMock(QueryProcessor::class);
        $schema = $this->createMock(Schema::class);

        $result = $this->validateCartIdExceptionChange->afterProcess(
            $queryProcessor,
            json_decode(self::RESULT_MOCK, true),
            $schema,
            '',
            null,
            json_decode(self::VARIABLE_VALUES, true)
        );

        $this->assertEquals(
            $result['errors'][0]['message'],
            'Missing Parameter "cart_id". Variable "$cart_id" of non-null type "String!" must not be null.'
        );
    }
}
