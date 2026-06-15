<?php

return [
    'api_key' => env('INTAKE_API_KEY', ''),
    'disk' => env('INTAKE_DISK', 'local'),
    // Все входящие отклики от бота привязываются к этому филиалу (по businessUnit
    // code). По умолчанию — ҶСК «Тоҷиктелеком» (BU51). Если филиал не найден,
    // филиал определяется из совпавшей вакансии (запасное поведение).
    'default_branch_code' => env('INTAKE_DEFAULT_BRANCH_CODE', 'BU51'),
    'resume_mimes' => ['pdf', 'doc', 'docx', 'rtf', 'odt'],
    'resume_max_kb' => 10240,
];
