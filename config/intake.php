<?php

return [
    'api_key' => env('INTAKE_API_KEY', ''),
    'disk' => env('INTAKE_DISK', 'local'),
    'resume_mimes' => ['pdf', 'doc', 'docx', 'rtf', 'odt'],
    'resume_max_kb' => 10240,
];
