<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BrandingAssets;
use App\Services\InstanceSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function settings(string $section, InstanceSettings $settings): View
    {
        $fields = collect(config('admin-settings'))->filter(fn ($field) => $field['group'] === $section)->all();
        abort_unless($fields, 404);
        $values = [];
        foreach ($fields as $key => $field) {
            $values[$key] = $field['type'] === 'secret' ? (bool) $settings->value($key) : $settings->value($key);
        }

        return view('admin.settings', compact('section', 'fields', 'values'));
    }

    public function update(Request $request, string $section, InstanceSettings $settings, BrandingAssets $assets): RedirectResponse
    {
        $fields = collect(config('admin-settings'))->filter(fn ($field) => $field['group'] === $section)->all();
        abort_unless($fields, 404);
        $rules = [];
        foreach ($fields as $key => $field) {
            $rules[$key] = $field['rules'];
            if ($field['type'] === 'checkbox') {
                $request->merge([$key => $request->boolean($key)]);
            }
        }
        if ($section === 'branding') {
            foreach (['logo', 'social_image', 'icon'] as $kind) {
                $rules[$kind] = ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'];
            }
        }
        $data = $request->validate($rules);
        if ($section === 'monitoring') {
            $dsn = $request->boolean('clear_sentry_dsn') ? null : (($data['sentry_dsn'] ?? null) ?: $settings->value('sentry_dsn'));
            if ($data['monitoring_enabled'] && ! $dsn) {
                throw ValidationException::withMessages(['sentry_dsn' => __('Bitte hinterlege eine Sentry DSN oder deaktiviere Sentry.')]);
            }
            if ($data['matomo_enabled'] && (empty($data['matomo_url']) || empty($data['matomo_site_id']))) {
                throw ValidationException::withMessages(['matomo_url' => __('Bitte hinterlege Matomo-URL und Site-ID oder deaktiviere Matomo.')]);
            }
        }
        $uploads = [];
        foreach (['logo', 'social_image', 'icon'] as $kind) {
            if ($request->hasFile($kind) && $section === 'branding') {
                $uploads += $assets->store($request->file($kind), $kind);
            }
        }
        DB::transaction(function () use ($fields, $data, $request, $settings, $uploads, $section): void {
            foreach ($fields as $key => $field) {
                if ($field['type'] === 'secret' && empty($data[$key]) && ! $request->boolean('clear_'.$key)) {
                    continue;
                }
                $value = $data[$key] ?? null;
                if ($field['type'] === 'number' && $value !== null) {
                    $value = (int) $value;
                }
                $settings->put($key, $field['type'] === 'secret' && $request->boolean('clear_'.$key) ? null : $value);
            }
            if ($section === 'branding') {
                foreach (['logo', 'social_image', 'icon'] as $kind) {
                    if ($request->boolean('clear_'.$kind) && ! $request->hasFile($kind)) {
                        foreach ($kind === 'icon' ? ['icon_32', 'icon_180', 'icon_192', 'icon_512', 'favicon'] : [$kind] as $asset) {
                            $settings->put('asset_'.$asset, null);
                        }
                    }
                }
            }
            foreach ($uploads as $key => $value) {
                $settings->put($key, $value);
            }
        });

        return back()->with('status', __('Einstellungen gespeichert.'));
    }

    public function security(Request $request): View
    {
        return view('admin.security', ['user' => $request->user()]);
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'current_password:web'], 'password' => ['required', 'string', 'min:12', 'max:255', 'confirmed']]);
        $request->user()->forceFill(['password' => Hash::make($data['password'])])->save();
        $request->session()->regenerate();
        Auth::logoutOtherDevices($data['password']);

        return back()->with('status', __('Passwort geändert.'));
    }

    public function setup(Request $request, User $user): View
    {
        abort_unless($user->is_admin && $user->password_setup_pending && Password::broker()->tokenExists($user, $request->query('token', '')), 403);

        return view('admin.setup');
    }

    public function completeSetup(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->is_admin && $user->password_setup_pending && Password::broker()->tokenExists($user, $request->query('token', '')), 403);
        $data = $request->validate(['password' => ['required', 'string', 'min:12', 'max:255', 'confirmed']]);
        $user->forceFill(['password' => Hash::make($data['password']), 'password_setup_pending' => false])->save();
        Password::broker()->deleteToken($user);
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('auth.password_confirmed_at', time());

        return redirect('/admin/security');
    }
}
