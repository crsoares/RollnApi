<?php

namespace Db;

return [
    'data-fixture' => [
        'Db_fixture' => __DIR__ . '/../src/Db/Fixture',
    ],

    'doctrine' => [
        'driver' => [
            'db_driver' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\XmlDriver',
                'paths' => [__DIR__ . '/xml'],
            ],
           'orm_default' => [
                'class'   => 'Doctrine\ORM\Mapping\Driver\DriverChain',
                'drivers' => [
                    __NAMESPACE__ . '\Entity' => 'db_driver',
                ],
            ],
        ],
    ],
];
