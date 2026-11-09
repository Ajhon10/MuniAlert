<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $r)
    {
        return $r->user()->notifications()->latest()->limit(50)->get();
    }

    public function read(Request $r)
    {
        $r->user()->unreadNotifications()->update(['read_at' => now()]);

        return ['ok' => true];
    }

    public function device(Request $r)
    {
        $data = $r->validate(['token' => 'required|string|min:20|max:4096', 'platform' => 'required|in:web,android,ios']);
        DB::table('device_tokens')->updateOrInsert(['token_hash' => hash('sha256', $data['token'])], ['user_id' => $r->user()->id, ...$data, 'created_at' => now(), 'updated_at' => now()]);

        return ['registered' => true];
    }

    public function removeDevice(Request $r)
    {
        $data = $r->validate(['token' => 'required|string']);
        DB::table('device_tokens')->where('user_id', $r->user()->id)->where('token_hash', hash('sha256', $data['token']))->delete();

        return ['ok' => true];
    }
}
