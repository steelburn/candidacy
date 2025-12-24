<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

/**
 * API Documentation Controller
 * Provides dynamically updatable API documentation
 */
class ApiDocController extends Controller
{
    /**
     * Get API documentation in OpenAPI 3.0 format
     */
    public function getOpenApiSpec(): JsonResponse
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Candidacy API Gateway',
                'description' => 'AI-Powered Candidate Tracking System - Complete API Documentation',
                'version' => '1.0.0',
                'contact' => [
                    'email' => 'support@candidacy.com'
                ]
            ],
            'servers' => [
                [
                    'url' => config('app.url', 'http://localhost:8080'),
                    'description' => 'API Gateway Server'
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                        'description' => 'Enter JWT token in format: Bearer {token}'
                    ]
                ],
                'schemas' => $this->getSchemas()
            ],
            'paths' => $this->getPaths(),
            'tags' => $this->getTags()
        ];

        return response()->json($spec);
    }

    /**
     * Render API documentation UI
     */
    public function renderDocs()
    {
        return view('api-docs');
    }

    /**
     * Get all API paths/endpoints
     */
    private function getPaths(): array
    {
        return [
            '/api/health' => [
                'get' => [
                    'tags' => ['System'],
                    'summary' => 'Gateway health check',
                    'description' => 'Check if the API Gateway is operational',
                    'responses' => [
                        '200' => [
                            'description' => 'Gateway is healthy',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'status' => ['type' => 'string', 'example' => 'healthy'],
                                            'service' => ['type' => 'string', 'example' => 'api-gateway'],
                                            'timestamp' => ['type' => 'string', 'format' => 'date-time']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '/api/system-health' => [
                'get' => [
                    'tags' => ['System'],
                    'summary' => 'Check all microservices health',
                    'description' => 'Returns health status of all microservices in the system',
                    'responses' => [
                        '200' => [
                            'description' => 'System health status',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'services' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/ServiceHealth']
                                            ],
                                            'timestamp' => ['type' => 'string', 'format' => 'date-time']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '/api/auth/login' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'User login',
                    'description' => 'Authenticate user and receive JWT token',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['email', 'password'],
                                    'properties' => [
                                        'email' => ['type' => 'string', 'format' => 'email', 'example' => 'admin@example.com'],
                                        'password' => ['type' => 'string', 'format' => 'password', 'example' => 'password123']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Login successful',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'user' => ['$ref' => '#/components/schemas/User'],
                                            'token' => ['type' => 'string', 'example' => 'eyJ0eXAiOiJKV1QiLCJhbGc...']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        '401' => [
                            'description' => 'Invalid credentials'
                        ]
                    ]
                ]
            ],
            '/api/candidates' => [
                'get' => [
                    'tags' => ['Candidates'],
                    'summary' => 'List candidates',
                    'description' => 'Get paginated list of candidates with filtering and sorting',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 1]],
                        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 15, 'maximum' => 100]],
                        ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['active', 'inactive', 'hired']]],
                        ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ['name' => 'sort_by', 'in' => 'query', 'schema' => ['type' => 'string', 'default' => 'created_at']],
                        ['name' => 'sort_order', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['asc', 'desc'], 'default' => 'desc']]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Candidate list',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/Candidate']
                                            ],
                                            'meta' => ['$ref' => '#/components/schemas/PaginationMeta']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get reusable schemas
     */
    private function getSchemas(): array
    {
        return [
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'example' => 1],
                    'name' => ['type' => 'string', 'example' => 'John Doe'],
                    'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                    'role' => ['type' => 'string', 'example' => 'admin']
                ]
            ],
            'Candidate' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'example' => 1],
                    'name' => ['type' => 'string', 'example' => 'Jane Smith'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'phone' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['active', 'inactive', 'hired']],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time']
                ]
            ],
            'ServiceHealth' => [
                'type' => 'object',
                'properties' => [
                    'service' => ['type' => 'string', 'example' => 'auth-service'],
                    'status' => ['type' => 'string', 'example' => 'online'],
                    'response_time' => ['type' => 'string', 'example' => '15ms']
                ]
            ],
            'PaginationMeta' => [
                'type' => 'object',
                'properties' => [
                    'current_page' => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                    'total' => ['type' => 'integer'],
                    'last_page' => ['type' => 'integer']
                ]
            ]
        ];
    }

    /**
     * Get API tags
     */
    private function getTags(): array
    {
        return [
            ['name' => 'System', 'description' => 'System health and monitoring endpoints'],
            ['name' => 'Authentication', 'description' => 'User authentication and authorization'],
            ['name' => 'Candidates', 'description' => 'Candidate management'],
            ['name' => 'Vacancies', 'description' => 'Job vacancy management'],
            ['name' => 'Matching', 'description' => 'AI-powered candidate-vacancy matching'],
            ['name' => 'Interviews', 'description' => 'Interview scheduling and management'],
            ['name' => 'Offers', 'description' => 'Job offer management']
        ];
    }
}
