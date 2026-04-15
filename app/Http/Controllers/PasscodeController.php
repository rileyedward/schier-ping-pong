<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PasscodeController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Unlock');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:4'],
        ]);

        if ($request->code === env('APP_PASSCODE')) {
            $request->session()->put('passcode_verified', true);
            return redirect()->intended('/');
        }

        return back()->withErrors(['code' => 'Incorrect access code.']);
    }
}
