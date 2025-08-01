<?php

namespace App\Http\Controllers\Api;

use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Http\Requests\Notification\SendNotificationRequest;
use App\Http\Controllers\Api\Traits\HandlesApiErrors;
use App\Notifications\FcmNotification;

class NotificationApiController extends ApiController
{
    use HandlesApiErrors;

    /**
     * Get the authenticated user's notification configuration.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function config(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $userId = $request->user()->id;

            // Get the notification preference associated with the user
            $config = NotificationPreference::where('user_id', $userId)->first();

            if (!$config) {
                return $this->render(null, 'No notification configuration found',404);
            }

            return $this->render($config, 'Notification configuration retrieved successfully');
        }, 'Failed to load notification configuration', $request);
    }

    /**
     * List all scheduled notifications for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $notifications = Notification::where('user_id', $request->user()->id)
                ->latest()
                ->get();

            return $this->render($notifications, 'Notifications retrieved successfully');
        }, 'Failed to fetch notifications',$request);
    }

    /**
     * Send a notification immediately.
     * Note: Delivery method (push/audio/visual) to be integrated.
     *
     * @param SendNotificationRequest $request
     * @return JsonResponse
     */
    public function sendNow(SendNotificationRequest $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $data = $request->validated();
            $user = $request->user();

            Notification::create([
                'user_id' => $user->id,
                'task_id' => $data['task_id'],
                'title' => $data['title'],
                'body' => $data['body'],
                'scheduled_at' => now(),
                'delivered' => true,
            ]);

            if ($user->fcm_token) {
                $user->notify(new FcmNotification($data['title'], $data['body']));
            }

            return $this->render(null, 'Notification sent successfully');
        }, 'Failed to send notification', $request);
    }
}
