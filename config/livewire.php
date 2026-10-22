<?php
return [
    'temporary_file_upload' => [
        'disk' => 'local',
        'directory' => 'livewire-tmp',
        'rules' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        'middleware' => 'throttle:30,1',
        'preview_mimes' => ['png', 'jpg', 'jpeg'],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],
];
