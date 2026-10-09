<?php

use Illuminate\Support\Facades\Artisan;

it('rejects daily cron requests without the configured bearer token', function () {
    config(['services.vercel.cron_secret' => 'test-cron-secret']);

    $this->getJson('/internal/cron/daily')->assertUnauthorized();
    $this->withToken('wrong-secret')->getJson('/internal/cron/daily')->assertUnauthorized();
});

it('runs daily maintenance for an authorized cron request', function () {
    config(['services.vercel.cron_secret' => 'test-cron-secret']);

    Artisan::shouldReceive('call')->times(5)->andReturn(0);

    $this->withToken('test-cron-secret')
        ->getJson('/internal/cron/daily')
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonCount(5, 'commands');
});
