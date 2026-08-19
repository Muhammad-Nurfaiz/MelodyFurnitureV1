<?php

namespace App\Http\Controllers\Admin\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Tandai satu notification sebagai telah dibaca.
     */
    public function markAsRead(
        DatabaseNotification $notification
    ): RedirectResponse {

        if (
            $notification->notifiable_type !== Auth::user()->getMorphClass()
            || $notification->notifiable_id !== Auth::id()
        ) {
            abort(403);
        }

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $url = $notification->data['url'] ?? route('admin.dashboard');

        return redirect()->to($url);
    }

    /**
     * Tandai seluruh notification sebagai telah dibaca.
     */
    public function markAllAsRead(): RedirectResponse
    {
        Auth::user()
            ->unreadNotifications()
            ->update([
                'read_at' => now(),
            ]);

        return back();
    }

    /**
     * Hapus satu notification.
     */
    public function destroy(
        DatabaseNotification $notification
    ): RedirectResponse {

        if (
            $notification->notifiable_type !== Auth::user()->getMorphClass()
            || $notification->notifiable_id !== Auth::id()
        ) {
            abort(403);
        }

        $notification->delete();

        return back();
    }
}