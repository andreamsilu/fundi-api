<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Database\Seeder;

class UserSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (X11; Linux x86_64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Edge/91.0.864.59'
        ];

        $ipAddresses = [
            '192.168.1.100', '192.168.1.101', '192.168.1.102', '192.168.1.103',
            '10.0.0.50', '10.0.0.51', '10.0.0.52', '10.0.0.53',
            '172.16.0.10', '172.16.0.11', '172.16.0.12', '172.16.0.13',
            '203.0.113.1', '203.0.113.2', '203.0.113.3', '203.0.113.4'
        ];

        $sessionStatuses = ['active', 'expired', 'terminated'];

        foreach ($users as $user) {
            // Each user gets 1-5 sessions
            $numSessions = rand(1, 5);
            
            for ($i = 0; $i < $numSessions; $i++) {
                $isActive = rand(1, 100) <= 30; // 30% chance of active session
                $status = $isActive ? 'active' : $sessionStatuses[array_rand($sessionStatuses)];
                
                $createdAt = now()->subDays(rand(0, 30));
                $lastActivity = $isActive 
                    ? now()->subMinutes(rand(0, 60))
                    : $createdAt->addMinutes(rand(5, 180));

                UserSession::create([
                    'user_id' => $user->id,
                    'session_id' => $this->generateSessionId(),
                    'ip_address' => $ipAddresses[array_rand($ipAddresses)],
                    'user_agent' => $userAgents[array_rand($userAgents)],
                    'status' => $status,
                    'last_activity' => $lastActivity,
                    'expires_at' => $createdAt->addHours(24), // 24-hour session
                    'metadata' => [
                        'device_type' => $this->getDeviceType(),
                        'browser' => $this->getBrowser(),
                        'os' => $this->getOperatingSystem(),
                        'location' => $this->getLocation(),
                        'login_method' => 'phone'
                    ],
                    'created_at' => $createdAt,
                    'updated_at' => $lastActivity
                ]);
            }
        }
    }

    private function generateSessionId(): string
    {
        return 'sess_' . strtoupper(uniqid()) . '_' . rand(1000, 9999);
    }

    private function getDeviceType(): string
    {
        $devices = ['desktop', 'mobile', 'tablet'];
        $weights = [60, 35, 5]; // 60% desktop, 35% mobile, 5% tablet
        
        $random = rand(1, 100);
        $cumulative = 0;
        
        for ($i = 0; $i < count($devices); $i++) {
            $cumulative += $weights[$i];
            if ($random <= $cumulative) {
                return $devices[$i];
            }
        }
        
        return 'desktop';
    }

    private function getBrowser(): string
    {
        $browsers = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'];
        $weights = [65, 15, 10, 8, 2]; // Chrome most common
        
        $random = rand(1, 100);
        $cumulative = 0;
        
        for ($i = 0; $i < count($browsers); $i++) {
            $cumulative += $weights[$i];
            if ($random <= $cumulative) {
                return $browsers[$i];
            }
        }
        
        return 'Chrome';
    }

    private function getOperatingSystem(): string
    {
        $os = ['Windows', 'macOS', 'Linux', 'Android', 'iOS'];
        $weights = [50, 25, 10, 10, 5]; // Windows most common
        
        $random = rand(1, 100);
        $cumulative = 0;
        
        for ($i = 0; $i < count($os); $i++) {
            $cumulative += $weights[$i];
            if ($random <= $cumulative) {
                return $os[$i];
            }
        }
        
        return 'Windows';
    }

    private function getLocation(): string
    {
        $locations = [
            'Dar es Salaam, Tanzania',
            'Arusha, Tanzania',
            'Mwanza, Tanzania',
            'Dodoma, Tanzania',
            'Tanga, Tanzania',
            'Morogoro, Tanzania',
            'Moshi, Tanzania',
            'Zanzibar, Tanzania'
        ];
        
        return $locations[array_rand($locations)];
    }
}
