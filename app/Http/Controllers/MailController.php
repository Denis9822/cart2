<?php

namespace App\Http\Controllers;

use App\Jobs\SendMail;

class MailController extends Controller
{
    public function __invoke(): void
    {
        $address = 'test@test.com';

        SendMail::dispatch($address);
    }
}
