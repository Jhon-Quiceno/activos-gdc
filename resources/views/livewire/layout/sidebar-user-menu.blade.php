<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="border-t border-white/10 px-4 py-4">
    <div class="flex items-center gap-3">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm font-semibold text-white">
            {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-white/60">{{ auth()->user()->email }}</p>
        </div>
    </div>

    <div class="mt-3 flex items-center gap-2">
        <a
            href="{{ route('profile') }}"
            wire:navigate
            class="flex-1 rounded-lg px-3 py-2 text-center text-xs font-semibold text-white/80 transition hover:bg-white/10 hover:text-white"
        >
            {{ __('Perfil') }}
        </a>
        <button
            wire:click="logout"
            type="button"
            class="flex-1 rounded-lg px-3 py-2 text-center text-xs font-semibold text-white/80 transition hover:bg-white/10 hover:text-white"
        >
            {{ __('Salir') }}
        </button>
    </div>
</div>
