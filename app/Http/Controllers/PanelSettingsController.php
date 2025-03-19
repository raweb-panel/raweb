<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class PanelSettingsController extends Controller
{
    public function index()
    {
        // Retrieve all settings and convert to key-value pairs
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        return response()->json([
            'settings' => $settings
        ]);
    }

    public function update(Request $request)
    {
        // Define the settings to be updated
        $settingsData = $request->only([
            'panel_title',
            'panel_description'
        ]);

        // Validate the inputs
        $request->validate([
            'panel_title' => 'nullable|string|max:255',
            'panel_description' => 'nullable|string|max:255'
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
