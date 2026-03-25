<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (!isset($notifiable->fcm_token) || empty($notifiable->fcm_token)) {
            return;
        }

        if (method_exists($notification, 'toFcm')) {
            $fcmMessage = $notification->toFcm($notifiable);
            
            try {
                $serviceAccountPath = base_path('config/firebase_credentials.json');
                if (file_exists($serviceAccountPath)) {
                    $factory = (new \Kreait\Firebase\Factory)->withServiceAccount($serviceAccountPath);
                    $messaging = $factory->createMessaging();
                    
                    $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $notifiable->fcm_token)
                        ->withNotification(\Kreait\Firebase\Messaging\Notification::create(
                            $fcmMessage['title'] ?? '',
                            $fcmMessage['body'] ?? ''
                        ))
                        ->withData($fcmMessage['data'] ?? []);
                        
                    $messaging->send($message);
                }
            } catch (\Exception $e) {
                Log::error('FCM Push Notification Failed: ' . $e->getMessage());
            }
        }
    }
}
