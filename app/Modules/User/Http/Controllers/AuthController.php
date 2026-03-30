<?php

namespace App\Modules\User\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\User\Http\Requests\LoginRequest;
use App\Modules\User\Interfaces\AuthServiceInterface;

class AuthController extends BaseController
{

    public function __construct(
        protected AuthServiceInterface $authService,
    )
    {
    }

    public function login(LoginRequest $request)
    {

    }

}
