<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class NotificationsDropdown extends Component
{
    public DatabaseNotificationCollection $notifications;

    public function mount(): void
    {
        $this->notifications = Auth::user()->unreadNotifications;
    }

    /**
     * Mark notification as read.
     *
     * @param  string  $id
     * @return void
     */
    public function markAsRead(string $id): void
    {
        $notification = Auth::user()?->unreadNotifications()->findOrFail($id);
        if ($notification) {
            $notification->markAsRead();
            $this->notifications = $this->notifications->reject(
                static fn (DatabaseNotification $notificationItem) => $notificationItem->id === $id
            );
        }
    }

    public function render(): View
    {
        return view('livewire.notifications-dropdown');
    }
}
