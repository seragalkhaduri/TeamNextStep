<?php

return [
    'auth' => [
        'failed' => 'These credentials do not match our records.',
        'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
        'locked' => 'Account is locked due to too many failed login attempts. Try again later.',
        'logged_out' => 'Logged out successfully.',
    ],
    'validation' => [
        'required' => 'The :attribute field is required.',
        'email' => 'The :attribute must be a valid email address.',
        'unique' => 'The :attribute has already been taken.',
    ],
    'errors' => [
        'duplicate_student' => 'A student with this national ID and institutional ID already exists (FR-STU-002).',
        'confirm_delete' => 'X-Confirm-Delete header required.',
        'forbidden' => 'You do not have permission to access this resource.',
    ],
];
