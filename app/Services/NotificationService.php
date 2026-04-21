<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NotificationService
{
    public static function send(
        $userId,
        $title,
        $message,
        $type = null,
        $referenceId = null,
        $referenceType = null
    ) {
        DB::table('notifications')->insert([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'status' => 'UNREAD',
            'type' => $type,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}