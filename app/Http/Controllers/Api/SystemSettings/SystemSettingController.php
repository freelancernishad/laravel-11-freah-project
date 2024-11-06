<?php

namespace App\Http\Controllers\Api\SystemSettings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\SystemSetting;

class SystemSettingController extends Controller
{
    public function storeOrUpdate(Request $request)
    {
        // Validate the input to ensure it's an array of key-value pairs
        $rules = [
            '*' => 'required|array', // Each item must be an array with key-value pairs
            '*.key' => 'required|string', // Each key must be a string
            '*.value' => 'required|string', // Each value must be a string
        ];

        // Create the validator instance
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        // Loop through the settings array and update or create each setting
        $settingsData = $request->all();

        foreach ($settingsData as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']], // Search by 'key'
                ['value' => $setting['value']] // If found, update 'value'; if not, create a new setting
            );
        }

        // Return success response
        return response()->json([
            'message' => 'System settings saved successfully!'
        ], 200); // OK
    }
}
