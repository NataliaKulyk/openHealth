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
        $this->notifications = Auth::user()->unreadNotifications->take(4);
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
            $this->notifications = Auth::user()->unreadNotifications->take(4);
        }
    }
    public function getTotalUnreadCountProperty(): int
    {
        return Auth::user()->unreadNotifications->count();
    }

    public function getNotificationIconType($notification): string
    {
        $data = $notification->data;
        $message = mb_strtolower($data['message'] ?? '');

        if (isset($data['action'])) {
            $action = $data['action'] ?? '';

            if ($action === 'completed') {
                return 'success';
            }

            if ($action === 'failed') {
                return 'error';
            }

            if (in_array($action, ['started', 'paused', 'resumed'])) {
                return 'sync';
            }
        }

        if (stripos($message, 'синхронізація') !== false || stripos($message, 'синхронізаці') !== false) {

            if (stripos($message, 'не вдалася') !== false ||
                stripos($message, 'не вдалась') !== false ||
                stripos($message, 'не вдалося') !== false) {
                return 'error';
            }

            if (stripos($message, 'розпочата') !== false ||
                stripos($message, 'розпочато') !== false ||
                stripos($message, 'призупинена') !== false ||
                stripos($message, 'призупинено') !== false ||
                stripos($message, 'відновлена') !== false ||
                stripos($message, 'відновлено') !== false) {
                return 'sync';
            }

            if (stripos($message, 'завершена') !== false ||
                stripos($message, 'завершено') !== false ||
                stripos($message, 'завершена.') !== false) {
                return 'success';
            }
        }

        if (stripos($message, 'помилка') !== false || stripos($message, 'помилк') !== false) {
            return 'error';
        }

        if (stripos($message, 'підписано') !== false) {
            return 'success';
        }

        if (stripos($message, 'зміни') !== false ||
            stripos($message, 'договор') !== false ||
            stripos($message, 'договор') !== false ||
            stripos($message, 'зауваження') !== false) {
            return 'info';
        }

        return 'default';
    }

    public function render(): View
    {
        return view('livewire.notifications-dropdown');
    }
}
