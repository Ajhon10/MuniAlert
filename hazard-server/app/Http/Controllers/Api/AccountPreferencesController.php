<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountPreferencesController extends Controller
{
    public function reading(Request $request): array
    {
        $data = $request->validate([
            'account_id' => 'required|integer',
            'preferences' => 'required|array:textSize,boldText,highContrast,readingSpace,reduceMotion,keepMessages',
            'preferences.textSize' => 'required|integer|min:100|max:200|multiple_of:5',
            'preferences.boldText' => 'required|boolean', 'preferences.highContrast' => 'required|boolean',
            'preferences.readingSpace' => 'required|boolean', 'preferences.reduceMotion' => 'required|boolean', 'preferences.keepMessages' => 'required|boolean',
        ]);
        // Ownership always comes from authentication. The ID prevents a delayed
        // request from another signed-in account overwriting the current one.
        abort_unless($data['account_id'] === $request->user()->id, 403);
        $values = $data['preferences'];
        foreach (array_keys($values) as $name) {
            $values[$name] = $name === 'textSize' ? (int) $values[$name] : (bool) $values[$name];
        }
        $request->user()->forceFill(['reading_preferences' => $values])->save();

        return ['preferences' => $values];
    }

    public function email(Request $request): array
    {
        $data = $request->validate(['account_id' => 'required|integer', 'email_report_updates' => 'required|boolean']);
        abort_unless($data['account_id'] === $request->user()->id, 403);
        $request->user()->forceFill(['email_report_updates' => (bool) $data['email_report_updates']])->save();

        return ['email_report_updates' => (bool) $data['email_report_updates']];
    }
}
