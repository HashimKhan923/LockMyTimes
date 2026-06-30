<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Main\SubscriptionPlan;

class HomeController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::active()->ordered()->get();

        return view('public.home', compact('plans'));
    }
}