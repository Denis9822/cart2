<?php

namespace App\Http\Controllers;

use App\Jobs\SendMail;
use App\Mail\TestEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    public function __invoke(): void
    {
        $address = "test@test.com";

        SendMail::dispatch($address);
    }
}
