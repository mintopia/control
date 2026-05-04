<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApiKeyStoreRequest;
use App\Http\Requests\Admin\ApiKeyUpdateRequest;
use App\Http\Requests\Admin\DeleteRequest;
use App\Models\ApiKey;

class ApiKeyController extends Controller
{
    public function index()
    {
        $apikeys = ApiKey::orderBy('name')->paginate(20);

        return view('admin.apikeys.index', [
            'apikeys' => $apikeys,
        ]);
    }

    public function create()
    {
        return view('admin.apikeys.create', [
            'apikey' => new ApiKey,
        ]);
    }

    public function store(ApiKeyStoreRequest $request)
    {
        [$apiKey, $plaintext] = ApiKey::generate($request->input('name'));

        $request->session()->flash('apiKeyPlaintext', $plaintext);

        return response()
            ->redirectToRoute('admin.settings.apikeys.created', $apiKey->id)
            ->with('successMessage', 'The API key has been created');
    }

    public function created(ApiKey $apikey)
    {
        return view('admin.apikeys.created', [
            'apikey' => $apikey,
        ]);
    }

    public function edit(ApiKey $apikey)
    {
        return view('admin.apikeys.edit', [
            'apikey' => $apikey,
        ]);
    }

    public function update(ApiKeyUpdateRequest $request, ApiKey $apikey)
    {
        $apikey->name = $request->input('name');
        $apikey->enabled = (bool) $request->input('enabled', false);
        $apikey->save();

        return response()
            ->redirectToRoute('admin.settings.apikeys.index')
            ->with('successMessage', 'The API key has been updated');
    }

    public function delete(ApiKey $apikey)
    {
        return view('admin.apikeys.delete', [
            'apikey' => $apikey,
        ]);
    }

    public function destroy(DeleteRequest $request, ApiKey $apikey)
    {
        $apikey->delete();

        return response()
            ->redirectToRoute('admin.settings.apikeys.index')
            ->with('successMessage', 'The API key has been deleted');
    }
}
