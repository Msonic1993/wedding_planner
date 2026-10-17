<?php

namespace App\Application\Notification\Contracts;

use App\Application\Notification\Data\PushNotification;

interface PushNotifier
{
    /**
     * Sends one notification to every token.
     *
     * @param  list<string>  $tokens
     * @return list<string> tokens the provider reported as no longer registered
     */
    public function send(array $tokens, PushNotification $notification): array;
}
