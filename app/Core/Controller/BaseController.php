<?php

namespace App\Core\Controller;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="NHM Product Dat Xe Backend API",
 *         version="1.0.0",
 *         description="API documentation for NHM Product Dat Xe Backend"
 *     )
 * )
 */
abstract class BaseController
{
    use AuthorizesRequests, HandleApi,  ValidatesRequests;
}
