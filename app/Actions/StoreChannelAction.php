<?php

namespace App\Actions;

class StoreChannelAction
{
    /**
     * Handle storing or updating a channel based on provided data.
     *
     * @param array $data
     * @param bool $exists
     * @return string
     */
    public function handle(array $data, ?bool $exists = false): string
    {
        $uuid = $data['uuid'] ?? null;

        // Skip if UUID is not provided or if the channel already exists
        if (!$uuid || $exists) {
            return 'skipped';
        }

        $channel = (new \App\Models\Channel)->updateOrCreate(
            ['channel_uuid' => $uuid],
            [
                'friendly_name' => $data['friendly_name'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'iso_country_code' => $data['country_code'] ?? null,
                'pushname' => $data['pushname'] ?? null,
                'server' => $data['server'] ?? null,
                'platform' => $data['platform'] ?? null,
                'connection_status' => $data['connection_status'] ?? null,
                'enabled' => $data['enabled'] ?? null,
                'is_business_profile' => $data['is_business_profile'] ?? null,
                'sync_contacts' => $data['sync_contacts'] ?? null,
            ]
        );

        return $channel->wasRecentlyCreated ? 'created' : 'updated';
    }
}
