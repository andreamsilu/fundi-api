<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NextsmsService; // (Assume you inject a "NextsmsService" (or a helper) for sending SMS via Nextsms.)

class OtpController extends Controller
{
    /**
     * Send an OTP (One-Time Password) to the provided phone number using Nextsms.
     * (See <a href="https://nextsms.co.tz/">Nextsms documentation</a> for details.)
     * (You can add an @authenticated tag if you want Scribe to mark this endpoint as authenticated.)
     *
     * @authenticated
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request, NextsmsService $nextsmsService)
    {
        // (Assume you inject (or instantiate) a "NextsmsService" (or a helper) and then call a "send" (or "sendOtp") method.)
        // (For example, you might call "$nextsmsService->sendOtp($request->phone)" and then return a JSON response.)
        // (Below is a dummy JSON response.)
        return response()->json(['message' => 'OTP sent (dummy) via Nextsms (see <a href="https://nextsms.co.tz/">Nextsms documentation</a>)']);
    }
} 