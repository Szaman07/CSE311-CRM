<?php

return [
    'session_idle_minutes' => (int) env('SESSION_LIFETIME', 30),
    'session_absolute_minutes' => (int) env('SESSION_ABSOLUTE_LIFETIME', 480),
    'display_timezone' => 'Asia/Dhaka',
    'currency' => 'BDT',
];
