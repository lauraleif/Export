<?php
namespace Export;

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'export_background_exports' => Api\Adapter\BackgroundExportAdapter::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\Element\OptionalMultiCheckbox::class => Form\Element\OptionalMultiCheckbox::class,
        ],
        'factories' => [
            'Export\Form\ExportItemSetForm' => Service\Form\ExportItemSetFormFactory::class,
            'Export\Form\ExportButtonForm' => Service\Form\ExportButtonFormFactory::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Export\Controller\Admin\List' => Controller\Admin\ListController::class,
        ],
        'factories' => [
            'Export\Controller\Admin\Index' => Service\Controller\Admin\IndexControllerFactory::class,
            'Export\Controller\Site\Index' => Service\Controller\Site\IndexControllerFactory::class,
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
                                '__NAMESPACE__' => 'Export\Controller\Admin',
                                'controller' => 'Index',
                                'action' => 'exportItemSet',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'download' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/download',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Export\Controller\Admin',
                                        'controller' => 'Index',
                                        'action' => 'download',
                                    ],
                                ],
                            ],
                            'list' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/list',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Export\Controller\Admin',
                                        'controller' => 'List',
                                        'action' => 'list',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/delete',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Export\Controller\Admin',
                                        'controller' => 'Index',
                                        'action' => 'delete',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'site' => [
                'child_routes' => [
                    'export' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/export',
                            'defaults' => [
                                '__NAMESPACE__' => 'Export\Controller\Site',
                                'controller' => 'Index',
                                'action' => 'download',
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
                'resource' => 'Export\Controller\Admin\Index',
                'pages' => [
                    [
                        'label' => 'Download', // @translate
                        'route' => 'admin/export/download',
                        'resource' => 'Export\Controller\Admin\Index',
                        'visible' => false,
                    ],
                    [
                        'label' => 'Export items from an item set', // @translate
                        'route' => 'admin/export',
                        'controller' => 'Index',
                        'action' => 'ExportItemSet',
                        'resource' => 'Export\Controller\Admin\Index',
                    ],
                    [
                        'label' => 'Export List', // @translate
                        'route' => 'admin/export/list',
                        'controller' => 'List',
                        'action' => 'list',
                        'resource' => 'Export\Controller\Admin\List',
                    ],

                ],
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'exportButton' => Service\ViewHelper\ExportButtonFactory::class,
        ],
        'invokables' => [
            'exportDetail' => View\Helper\ExportDetail::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Export\Exporter' => Service\ExporterFactory::class,
        ],
    ],
];
