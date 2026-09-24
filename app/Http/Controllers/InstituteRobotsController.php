<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class InstituteRobotsController
{
    public function __invoke(): Response
    {
        return response(
            view('institutes.robots')->render(),
            200,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }
}
