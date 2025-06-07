<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NextsmsService
{
    /**
     * (Dummy) Send an OTP (One-Time Password) via SMS using Nextsms.
     * (See <a href="https://nextsms.co.tz/">Nextsms documentation</a> for details.)
     *
     * @param string $phone (The recipient's phone number.)
     * @return bool (Dummy return (true) – in a real integration, you'd call the Nextsms API (or a helper) and return a real result.)
     */
    public function sendOtp(string $phone): bool
    {
         // (Assume you call (or integrate) the real Nextsms API (or a helper) here.)
         // (For example, you might call "Http::post("https://nextsms.co.tz/api/send", ["phone" => $phone, "message" => "Your OTP is ..."])" and then return a real result.)
         // (Below is a dummy return.)
         return true;
    }
} 