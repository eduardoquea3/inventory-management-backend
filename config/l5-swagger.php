<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => ['title' => env('APP_NAME', 'Legacy API') . ' API'],
            'routes' => ['api' => 'api/documentation'],
            'paths' => [
                'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', true),
                'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),
                'docs_json' => 'api-docs.json',
                'docs_yaml' => 'api-docs.yaml',
                'format_to_use_for_docs' => 'json',
                'annotations' => [base_path('app/OpenApi')],
            ],
        ],
    ],
    'defaults' => [
        'routes' => [
            'docs' => 'docs',
            'oauth2_callback' => 'api/oauth2-callback',
            'middleware' => ['api' => [], 'asset' => [], 'docs' => [], 'oauth2_callback' => []],
            'group_options' => [],
        ],
        'paths' => [
            'docs' => storage_path('api-docs'),
            'views' => base_path('resources/views/vendor/l5-swagger'),
            'base' => null,
            'excludes' => [],
        ],
        'scanOptions' => [
            'default_processors_configuration' => [],
            'analyser' => null,
            'analysis' => null,
            'processors' => [],
            'pattern' => null,
            'exclude' => [],
            'open_api_spec_version' => \L5Swagger\Generator::OPEN_API_DEFAULT_SPEC_VERSION,
        ],
        'securityDefinitions' => [
            'securitySchemes' => [
                'LegacyTokenAuth' => [
                    'type' => 'http',
                    'description' => 'Legacy token in users.api_token. Enter the raw token; Swagger UI sends it as Authorization: Bearer <token>.',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'opaque token',
                ],
            ],
            'security' => [],
        ],
        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),
        'generate_yaml_copy' => false,
        'proxy' => false,
        'additional_config_url' => null,
        'operations_sort' => null,
        'validator_url' => null,
        'ui' => [
            'display' => ['doc_expansion' => 'none', 'filter' => true],
            'authorization' => ['persist_authorization' => false],
            'custom' => [],
        ],
        'constants' => [],
    ],
];
