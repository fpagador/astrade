<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Contract\Messaging;
use App\Http\Controllers\Api\Traits\HandlesApiErrors;
use App\Http\Requests\PushNotification\SendToDeviceRequest;
use App\Http\Requests\PushNotification\SendToMultipleRequest;
use App\Http\Requests\PushNotification\SendToTopicRequest;

class PushNotificationController extends ApiController
{
    use HandlesApiErrors;

    protected $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Send push notification to a single device by FCM token.
     *
     * @param SendToDeviceRequest $request
     * @return JsonResponse
     */
    public function sendToDevice(SendToDeviceRequest $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $message = CloudMessage::withTarget('token', $request->token)
                ->withNotification(FirebaseNotification::create($request->title, $request->body));
            $this->messaging->send($message);
            return ['message' => 'Notification sent to device'];
        }, 'Failed to send notification to device', $request);
    }

    /**
     * Send push notification to multiple devices.
     *
     * @param SendToMultipleRequest $request
     * @return JsonResponse
     */
    public function sendToMultiple(SendToMultipleRequest $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $message = CloudMessage::new()
                ->withNotification(FirebaseNotification::create($request->title, $request->body));
            $report = $this->messaging->sendMulticast($message, $request->tokens);
            return [
                'message' => 'Notification sent to multiple devices',
                'success_count' => $report->successes()->count(),
                'failure_count' => $report->failures()->count(),
            ];
        }, 'Failed to send notification to multiple devices', $request);
    }

    /**
     * Send push notification to a topic.
     *
     * @param SendToTopicRequest $request
     * @return JsonResponse
     */
    public function sendToTopic(SendToTopicRequest $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $message = CloudMessage::withTarget('topic', $request->topic)
                ->withNotification(FirebaseNotification::create($request->title, $request->body));
            $this->messaging->send($message);
            return ['message' => "Notification sent to topic '{$request->topic}'"];
        }, 'Failed to send notification to topic', $request);
    }
}
