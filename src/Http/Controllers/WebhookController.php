<?php

declare(strict_types=1);

namespace SendKit\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SendKit\Laravel\Events\ContactCreated;
use SendKit\Laravel\Events\ContactDeleted;
use SendKit\Laravel\Events\ContactUpdated;
use SendKit\Laravel\Events\EmailBounced;
use SendKit\Laravel\Events\EmailClicked;
use SendKit\Laravel\Events\EmailComplained;
use SendKit\Laravel\Events\EmailDelivered;
use SendKit\Laravel\Events\EmailDeliveryDelayed;
use SendKit\Laravel\Events\EmailFailed;
use SendKit\Laravel\Events\EmailOpened;
use SendKit\Laravel\Events\EmailRejected;
use SendKit\Laravel\Events\EmailSent;

class WebhookController extends Controller
{
    /** @var array<string, class-string> */
    private const EVENT_MAP = [
        'email.sent' => EmailSent::class,
        'email.delivered' => EmailDelivered::class,
        'email.bounced' => EmailBounced::class,
        'email.complained' => EmailComplained::class,
        'email.opened' => EmailOpened::class,
        'email.clicked' => EmailClicked::class,
        'email.failed' => EmailFailed::class,
        'email.delivery_delayed' => EmailDeliveryDelayed::class,
        'email.rejected' => EmailRejected::class,
        'contact.created' => ContactCreated::class,
        'contact.updated' => ContactUpdated::class,
        'contact.deleted' => ContactDeleted::class,
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $type = $request->input('type');
        $data = $request->input('data', []);

        $eventClass = self::EVENT_MAP[$type] ?? null;

        if ($eventClass) {
            event(new $eventClass($data));
        }

        return response()->json([], 200);
    }
}
