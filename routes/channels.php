<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Broadcast channel for contacts.
 * Any client can listen to contact events.
 */
Broadcast::channel('contacts', function () {
    return true;
});

/**
 * Broadcast channel for messages.
 * Any client can listen to message events.
 */
Broadcast::channel('messages', function () {
    return true;
});
