<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Devices extends Component
{
    public function render()
    {
        return view('livewire.dashboard.devices', [
            'devices' => Auth::user()->devices()->orderBy('name')->get(),
        ])->layout('components.layouts.app')->title(__('sintoniza.general.devices'));
    }
}
