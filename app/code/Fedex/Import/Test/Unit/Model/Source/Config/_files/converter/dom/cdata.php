<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

return [
    'TestName' => [
        'fields' => [
            'testname1' => [
                'id'=> "test_1",
                'label' => "12345",
                'type' => "test",
                'notice' => "testnotice",
                'required' => true,
                'value' => ''
            ]
        ],
        'label' => 'TestLabel',
        'model' => 'Test',
        'sort_order' => '2'
    ]
];
