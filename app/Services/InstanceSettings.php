<?php

namespace App\Services;

use App\Models\InstanceSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstanceSettings
{
    public function apply(): void
    {
        if (config('database.default') === 'sqlite' && config('database.connections.sqlite.database') !== ':memory:' && ! is_file(config('database.connections.sqlite.database'))) {
            return;
        }
        try {
            if (! Schema::hasTable('instance_settings')) {
                return;
            }
            $settings = DB::table('instance_settings')->get();
        } catch (QueryException $exception) {
            if (app()->runningInConsole()) {
                return;
            }
            throw $exception;
        }
        foreach ($settings as $setting) {
            $target = config('admin-settings.'.$setting->key.'.config');
            if (! $target && str_starts_with($setting->key, 'asset_')) {
                $target = 'branding.'.substr($setting->key, 6);
            }
            if ($target) {
                config([$target => json_decode(Crypt::decryptString($setting->payload), true, flags: JSON_THROW_ON_ERROR)['value']]);
            }
        }
        config(['logging.channels.daily.level' => config('logging.channels.single.level')]);
        if (! config('monitoring.enabled') || ! config('sentry.dsn')) {
            config(['sentry.dsn' => null, 'monitoring.enabled' => false]);
        }
        if (! config('monitoring.matomo_url') || ! config('monitoring.matomo_site_id')) {
            config(['monitoring.matomo_enabled' => false]);
        }
    }

    public function put(string $key, mixed $value): void
    {
        InstanceSetting::updateOrCreate(['key' => $key], ['payload' => ['value' => $value]]);
    }

    public function value(string $key): mixed
    {
        $setting = InstanceSetting::find($key);

        return $setting ? $setting->payload['value'] : config(config('admin-settings.'.$key.'.config'));
    }
}
