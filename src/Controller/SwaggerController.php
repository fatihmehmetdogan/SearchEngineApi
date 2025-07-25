<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SwaggerController extends AbstractController
{
    #[Route('/api/doc', name: 'swagger_ui', methods: ['GET'])]
    public function index(): Response
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Search Engine API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.0.0/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.0.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.0.0/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "/api/doc/openapi.json",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>';

        return new Response($html);
    }

    #[Route('/api/doc/openapi.json', name: 'swagger_spec', methods: ['GET'])]
    public function openApiSpec(): Response
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Search Engine API',
                'description' => 'Bu API, güçlü arama fonksiyonları sunan bir arama motoru servisidir.',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'API Support',
                    'email' => 'support@example.com'
                ]
            ],
            'servers' => [
                [
                    'url' => 'http://localhost:8080',
                    'description' => 'Development server'
                ]
            ],
            'paths' => [
                '/api/' => [
                    'get' => [
                        'tags' => ['Info'],
                        'summary' => 'API Bilgileri',
                        'responses' => [
                            '200' => [
                                'description' => 'API bilgileri',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'name' => ['type' => 'string'],
                                                'version' => ['type' => 'string'],
                                                'description' => ['type' => 'string'],
                                                'endpoints' => ['type' => 'object']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '/api/health' => [
                    'get' => [
                        'tags' => ['Info'],
                        'summary' => 'API Sağlık Kontrolü',
                        'responses' => [
                            '200' => [
                                'description' => 'API sağlık durumu',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'status' => ['type' => 'string'],
                                                'timestamp' => ['type' => 'string'],
                                                'services' => ['type' => 'object']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '/api/search' => [
                    'get' => [
                        'tags' => ['Search'],
                        'summary' => 'Dokuman Arama',
                        'parameters' => [
                            [
                                'name' => 'q',
                                'in' => 'query',
                                'description' => 'Arama sorgusu',
                                'schema' => ['type' => 'string']
                            ],
                            [
                                'name' => 'category',
                                'in' => 'query',
                                'description' => 'Kategori filtresi',
                                'schema' => ['type' => 'string']
                            ],
                            [
                                'name' => 'tags',
                                'in' => 'query',
                                'description' => 'Etiket filtresi (virgülle ayrılmış)',
                                'schema' => ['type' => 'string']
                            ],
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'description' => 'Sayfa numarası',
                                'schema' => ['type' => 'integer', 'default' => 1]
                            ],
                            [
                                'name' => 'limit',
                                'in' => 'query',
                                'description' => 'Sayfa başına sonuç',
                                'schema' => ['type' => 'integer', 'default' => 20]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Arama sonuçları',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'data' => [
                                                    'type' => 'array',
                                                    'items' => ['$ref' => '#/components/schemas/Document']
                                                ],
                                                'meta' => ['type' => 'object']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '/api/search/suggestions' => [
                    'get' => [
                        'tags' => ['Search'],
                        'summary' => 'Arama Önerileri',
                        'parameters' => [
                            [
                                'name' => 'q',
                                'in' => 'query',
                                'description' => 'Kısmi arama sorgusu',
                                'schema' => ['type' => 'string']
                            ],
                            [
                                'name' => 'limit',
                                'in' => 'query',
                                'description' => 'Öneri sayısı',
                                'schema' => ['type' => 'integer', 'default' => 10]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Arama önerileri',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'suggestions' => [
                                                    'type' => 'array',
                                                    'items' => ['type' => 'string']
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '/api/search/popular' => [
                    'get' => [
                        'tags' => ['Search'],
                        'summary' => 'Popüler Arama Sorguları',
                        'parameters' => [
                            [
                                'name' => 'limit',
                                'in' => 'query',
                                'description' => 'Popüler sorgu sayısı',
                                'schema' => ['type' => 'integer', 'default' => 10]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Popüler arama sorguları',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'popular_queries' => [
                                                    'type' => 'array',
                                                    'items' => ['type' => 'object']
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '/api/documents' => [
                    'get' => [
                        'tags' => ['Documents'],
                        'summary' => 'Tüm Dokomanları Listele',
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'description' => 'Sayfa numarası',
                                'schema' => ['type' => 'integer', 'default' => 1]
                            ],
                            [
                                'name' => 'limit',
                                'in' => 'query',
                                'description' => 'Sayfa başına sonuç',
                                'schema' => ['type' => 'integer', 'default' => 20]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dokoman listesi',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'data' => [
                                                    'type' => 'array',
                                                    'items' => ['$ref' => '#/components/schemas/Document']
                                                ],
                                                'meta' => ['type' => 'object']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'post' => [
                        'tags' => ['Documents'],
                        'summary' => 'Yeni Dokoman Oluştur',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['title', 'content'],
                                        'properties' => [
                                            'title' => ['type' => 'string', 'maxLength' => 255],
                                            'content' => ['type' => 'string'],
                                            'url' => ['type' => 'string', 'format' => 'url'],
                                            'category' => ['type' => 'string'],
                                            'tags' => [
                                                'type' => 'array',
                                                'items' => ['type' => 'string']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Dokoman oluşturuldu',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Document']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '/api/documents/{id}' => [
                    'get' => [
                        'tags' => ['Documents'],
                        'summary' => 'Belirli Dokomanı Getir',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'description' => 'Dokoman ID',
                                'schema' => ['type' => 'integer']
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dokoman detayları',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Document']
                                    ]
                                ]
                            ],
                            '404' => ['description' => 'Dokoman bulunamadı']
                        ]
                    ],
                    'put' => [
                        'tags' => ['Documents'],
                        'summary' => 'Dokomanı Güncelle',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'description' => 'Dokoman ID',
                                'schema' => ['type' => 'integer']
                            ]
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'title' => ['type' => 'string', 'maxLength' => 255],
                                            'content' => ['type' => 'string'],
                                            'url' => ['type' => 'string', 'format' => 'url'],
                                            'category' => ['type' => 'string'],
                                            'tags' => [
                                                'type' => 'array',
                                                'items' => ['type' => 'string']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Dokoman güncellendi',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Document']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'delete' => [
                        'tags' => ['Documents'],
                        'summary' => 'Dokomanı Sil',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'description' => 'Dokoman ID',
                                'schema' => ['type' => 'integer']
                            ]
                        ],
                        'responses' => [
                            '204' => ['description' => 'Dokoman silindi'],
                            '404' => ['description' => 'Dokoman bulunamadı']
                        ]
                    ]
                ]
            ],
            'components' => [
                'schemas' => [
                    'Document' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'title' => ['type' => 'string', 'example' => 'Symfony Framework'],
                            'content' => ['type' => 'string', 'example' => 'Symfony is a PHP framework...'],
                            'url' => ['type' => 'string', 'format' => 'uri', 'example' => 'https://symfony.com'],
                            'category' => ['type' => 'string', 'example' => 'Framework'],
                            'tags' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'example' => ['php', 'web', 'framework']
                            ],
                            'createdAt' => ['type' => 'string', 'format' => 'date-time'],
                            'updatedAt' => ['type' => 'string', 'format' => 'date-time']
                        ]
                    ]
                ]
            ]
        ];

        return $this->json($spec);
    }
}
