<?php
namespace Export;

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'form_elements' => [
        'factories' => [
            'Export\Form\ImportForm' => Service\Form\ImportFormFactory::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            'Export\Controller\Index' => Service\Controller\IndexControllerFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'export' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/export',
                            'defaults' => [
                                '__NAMESPACE__' => 'Export\Controller',
                                'controller' => 'Index',
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'download' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/download',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Export\Controller',
                                        'controller' => 'Index',
                                        'action' => 'download',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Export',
                'route' => 'admin/export',
                'resource' => 'Export\Controller\Index',
                'pages' => [
                    [
                        'label' => 'Export', // @translate
                        'route' => 'admin/export',
                        'resource' => 'Export\Controller\Index',
                    ],
                    [
                        'label' => 'Download', // @translate
                        'route' => 'admin/export/download',
                        'resource' => 'Export\Controller\Index',
                        'visible' => false,
                    ],
                ],
            ],
        ],
    ],
];
