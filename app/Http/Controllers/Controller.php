<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Fundi API Documentation",
 *     description="API documentation for the Fundi App - A platform connecting customers with local technicians",
 *     @OA\Contact(
 *         email="support@fundiapp.co.tz",
 *         name="Fundi Support Team"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="https://api.fundiapp.co.tz/v1",
 *     description="Production Server"
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Local Development Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for user authentication"
 * )
 * 
 * @OA\Tag(
 *     name="Fundis",
 *     description="API Endpoints for managing fundi profiles and services"
 * )
 * 
 * @OA\Tag(
 *     name="Jobs",
 *     description="API Endpoints for managing service jobs"
 * )
 * 
 * @OA\Tag(
 *     name="Bookings",
 *     description="API Endpoints for managing bookings"
 * )
 * 
 * @OA\Tag(
 *     name="Reviews",
 *     description="API Endpoints for managing reviews"
 * )
 * 
 * @OA\Tag(
 *     name="Service Categories",
 *     description="API Endpoints for managing service categories"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
