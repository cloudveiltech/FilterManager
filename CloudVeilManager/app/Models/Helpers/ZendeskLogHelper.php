<?php

namespace App\Models\Helpers;


use App\Models\SystemPlatform;
use App\Models\User;

class ZendeskLogHelper
{
    public static function createTicket(User $user, $platform, $fileName, $fileData)
    {
        $token = Zendesk::postFile($fileName, $fileData, "application/x-zip-compressed");

        $appName = $platform == SystemPlatform::PLATFORM_WIN ? "cv4w" : "cv4m";

        $ticketData = [
            'subject' => 'Log file uploaded - ' . $appName . " - " . $user->email,
            'requester' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'comment' => [
                'public' => false,
                'body' => 'Hi,
                    ' . $user->name . ' with email ' . $user->email . ' has sent ' .  $appName . ' logs to be investigated.',
                "uploads" => [$token]
            ],
            'tags' => [$appName . '-log'],
        ];


        $ticketRequest = Zendesk::createTicket($ticketData);

        return 'success';
    }
}
