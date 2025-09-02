<?php

namespace App\Swagger;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Astrade API",
 *     description="Astrade API Documentation",
 *     @OA\Contact(
 *         email="soporte@astrade.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Main API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     in="header",
 *     name="Authorization",
 *     description="Enter the token in the format: Bearer {token}"
 * )
 */
class SwaggerInfo
{
    //This file only contains annotations for Swagger, no code needed
}
