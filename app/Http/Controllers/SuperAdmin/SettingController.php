<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;

class SettingsController extends Controller
{
    public function index() { return view('superadmin.settings.index'); }
}