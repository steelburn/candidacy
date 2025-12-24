<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Candidacy API Gateway",
 *     description="AI-Powered Candidate Tracking System - API Gateway Documentation",
 *     @OA\Contact(
 *         email="support@candidacy.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Gateway Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter JWT token in format: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and authorization endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Candidates",
 *     description="Candidate management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Vacancies",
 *     description="Job vacancy management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Matching",
 *     description="AI-powered candidate-vacancy matching endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Interviews",
 *     description="Interview scheduling and management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Offers",
 *     description="Job offer management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="System",
 *     description="System health and monitoring endpoints"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
