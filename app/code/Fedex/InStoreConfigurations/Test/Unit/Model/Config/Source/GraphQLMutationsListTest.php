<?php
/**
 * @category  Fedex
 * @package   Fedex_InstoreConfigurations
 * @copyright Copyright (c) 2025 FedEx.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\InStoreConfigurations\Test\Unit\Model\Config\Source;

use Fedex\InStoreConfigurations\Model\Config\Source\GraphQLMutationsList;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;

class GraphQLMutationsListTest extends TestCase
{
    /**
     * @var GraphQLMutationsList
     */
    private $graphQLMutationsList;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        $this->graphQLMutationsList = new GraphQLMutationsList();
    }

    /**
     * Test that toOptionArray returns expected array structure
     */
    public function testToOptionArray(): void
    {
        $result = $this->graphQLMutationsList->toOptionArray();

        // Check that result is an array
        $this->assertIsArray($result, 'toOptionArray should return an array');

        // Check that array is not empty
        $this->assertNotEmpty($result, 'toOptionArray should not return an empty array');

        // Check structure of first element
        $firstElement = reset($result);
        $this->assertArrayHasKey('value', $firstElement, 'Each element should have a value key');
        $this->assertArrayHasKey('label', $firstElement, 'Each element should have a label key');

        // Check that all elements have the expected structure
        foreach ($result as $element) {
            $this->assertArrayHasKey('value', $element);
            $this->assertArrayHasKey('label', $element);

            // Check that label is a Phrase object
            $this->assertInstanceOf(
                Phrase::class,
                $element['label'],
                'Label should be an instance of Magento\Framework\Phrase'
            );
        }

        // Check for presence of specific mutations
        $foundMutations = array_column($result, 'value');
        $expectedMutations = [
            'addOrUpdateDueDate',
            'addOrUpdateFedexAccountNumber',
            'addProductsToCart',
            'createOrUpdateOrder',
            'notes',
            'placeOrder',
            'updateCartItems',
            'updateGuestCartContactInformation',
            'updateOrderDelivery',
            'createEmptyCart',
            'cart',
            'products',
            'getQuoteDetails',
            'updateNegotiableQuote',
            'lateOrder'
        ];

        foreach ($expectedMutations as $mutation) {
            $this->assertContains($mutation, $foundMutations, "Mutation '$mutation' should be present in the options array");
        }

        // Assert that the number of elements matches expected count
        $this->assertCount(
            count($expectedMutations),
            $result,
            'Number of mutations in toOptionArray should match expected count'
        );
    }

    /**
     * Test that toArray returns expected array structure
     */
    public function testToArray(): void
    {
        $result = $this->graphQLMutationsList->toArray();

        // Check that result is an array
        $this->assertIsArray($result, 'toArray should return an array');

        // Check that array is not empty
        $this->assertNotEmpty($result, 'toArray should not return an empty array');

        // Expected keys in the array
        $expectedKeys = [
            'addOrUpdateDueDate',
            'addOrUpdateFedexAccountNumber',
            'addProductsToCart',
            'createOrUpdateOrder',
            'notes',
            'placeOrder',
            'updateCartItems',
            'updateGuestCartContactInformation',
            'updateOrderDelivery',
            'createEmptyCart',
            'cart',
            'products',
            'getQuoteDetails',
            'updateNegotiableQuote',
            'lateOrder'
        ];

        // Check for presence of specific keys
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result, "Key '$key' should be present in the array");

            // Check that the value is a Phrase object
            $this->assertInstanceOf(
                Phrase::class,
                $result[$key],
                "Value for key '$key' should be an instance of Magento\Framework\Phrase"
            );
        }

        // Assert that the number of elements matches expected count
        $this->assertCount(
            count($expectedKeys),
            $result,
            'Number of elements in toArray should match expected count'
        );

        // Verify key-value structure matches expectation
        // Compare first element with expected value
        $firstKey = 'addOrUpdateDueDate';
        $this->assertInstanceOf(Phrase::class, $result[$firstKey]);
    }

    /**
     * Test consistency between toOptionArray and toArray methods
     */
    public function testConsistencyBetweenMethods(): void
    {
        $optionArray = $this->graphQLMutationsList->toOptionArray();
        $array = $this->graphQLMutationsList->toArray();

        // Check that both arrays have the same number of elements
        $this->assertCount(
            count($optionArray),
            $array,
            'toOptionArray and toArray should return the same number of elements'
        );

        // Check that all values in toOptionArray exist as keys in toArray
        foreach ($optionArray as $option) {
            $this->assertArrayHasKey(
                $option['value'],
                $array,
                "Value '{$option['value']}' from toOptionArray should exist as key in toArray"
            );
        }
    }
}
