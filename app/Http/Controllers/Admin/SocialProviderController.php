<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SocialProviderUpdateRequest;
use App\Models\SocialProvider;

class SocialProviderController extends Controller
{
    public function edit(SocialProvider $provider)
    {
        $config = $provider->configMapping();
        return view('admin.socialproviders.edit', [
            'provider' => $provider,
            'config' => $config,
        ]);
    }

    public function update(SocialProviderUpdateRequest $request, SocialProvider $provider)
    {
        $provider->client_id = $request->input('client_id');
        $provider->client_secret = $request->input('client_secret');
        $provider->enabled = (bool)$request->input('enabled');
        if ($provider->supports_auth) {
            $provider->auth_enabled = (bool)$request->input('auth_enabled');
        }
        $provider->save();
        return response()->redirectToRoute('admin.settings.index')->with('successMessage', "{$provider->name} has been updated");
    }
}
