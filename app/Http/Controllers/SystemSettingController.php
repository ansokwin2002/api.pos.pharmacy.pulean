<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    /**
     * GET /manageapi/systemset/get
     * Returns the system settings for the system settings page.
     */
    public function getSystemSet(Request $request)
    {
        $setting = SystemSetting::row();

        return response()->json([
            'code' => '1',
            'data' => [
                'loginLogo' => $setting->login_logo,
                'systemLogo' => $setting->system_logo,
                'rechargeAudit' => (bool) $setting->recharge_audit,
            ],
        ]);
    }

    /**
     * POST /manageapi/systemset/edit
     * Updates the non-tax system settings.
     */
    public function editSystemSet(Request $request)
    {
        $data = $request->validate([
            'loginlogo' => ['nullable', 'string', 'max:255'],
            'systemlogo' => ['nullable', 'string', 'max:255'],
            'rechargeAudit' => ['nullable', 'boolean'],
        ]);

        $setting = SystemSetting::row();
        $setting->login_logo = $data['loginlogo'] ?? $setting->login_logo;
        $setting->system_logo = $data['systemlogo'] ?? $setting->system_logo;
        $setting->recharge_audit = array_key_exists('rechargeAudit', $data)
            ? (bool) $data['rechargeAudit']
            : $setting->recharge_audit;
        $setting->save();

        return response()->json([
            'code' => '1',
            'message' => 'System settings updated successfully.',
            'data' => [
                'loginLogo' => $setting->login_logo,
                'systemLogo' => $setting->system_logo,
                'rechargeAudit' => (bool) $setting->recharge_audit,
            ],
        ]);
    }

    /**
     * GET /api/settings
     * Returns the tax rate setting (used by POS / system settings page).
     */
    public function getSettings(Request $request)
    {
        $setting = SystemSetting::row();

        return response()->json([
            'data' => [
                'tax_rate' => (float) $setting->tax_rate,
            ],
        ]);
    }

    /**
     * POST /api/settings
     * Updates the tax rate setting.
     */
    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $setting = SystemSetting::row();
        $setting->tax_rate = $data['tax_rate'];
        $setting->save();

        return response()->json([
            'message' => 'Settings updated successfully.',
            'data' => [
                'tax_rate' => (float) $setting->tax_rate,
            ],
        ]);
    }
}
