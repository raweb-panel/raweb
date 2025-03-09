<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function index()
    {
        // Retrieve all settings and convert to key-value pairs
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        // Read the contents of the nginx.conf file
        $nginxConfigPath = '/nginx/nginx.conf';
        $nginxConfig = File::exists($nginxConfigPath) ? File::get($nginxConfigPath) : '';

        return response()->json([
            'settings' => $settings,
            'nginxConfig' => $nginxConfig
        ]);
    }

    public function update(Request $request)
    {
        // Define the settings to be updated
        $settingsData = $request->only([
            'panel_title',
            'panel_description',
            'nginx_stub_url'
        ]);

        // Validate the inputs
        $request->validate([
            'panel_title' => 'nullable|string|max:255',
            'panel_description' => 'nullable|string|max:255',
            'nginx_stub_url' => 'required|url'
        ]);

        // Loop through each setting and update or create it
        foreach ($settingsData as $key => $value) {
            if ($value !== null) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        }

        // Update the nginx.conf file if provided
        if ($request->has('nginxConfig')) {
            $nginxConfigPath = '/nginx/nginx.conf';
            File::put($nginxConfigPath, $request->input('nginxConfig'));
        }

        return response()->json(['status' => 'success']);
    }

    public function getPanelTitle()
    {
        $panelTitle = Setting::where('key', 'panel_title')->value('value');
        return response()->json(['panel_title' => $panelTitle]);
    }

    public function app()
    {
        $panelTitle = Setting::where('key', 'panel_title')->value('value') ?? 'Nginx Manager';
        return view('app', compact('panelTitle'));
    }
}
