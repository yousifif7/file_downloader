<?php

namespace App\Http\Controllers;

use App\Services\PlisioBillingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class PlisioWebhookController extends Controller
{
    public function handle(Request $request, PlisioBillingService $billing): Response
    {
        if (! $billing->isAvailable()) {
            return response('Crypto billing disabled', 503);
        }

        try {
            $billing->handleCallback($request->all());
        } catch (ValidationException) {
            return response('Invalid callback', 422);
        }

        return response('OK', 200);
    }
}
