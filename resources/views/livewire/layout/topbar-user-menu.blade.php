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

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button
        type="button"
        @click="open = !open"
        class="flex items-center gap-3 rounded-lg px-2 py-1.5 transition hover:bg-app-bg"
    >
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-info-bg text-[15px] font-bold text-info-text">
            {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div class="hidden min-w-0 text-left sm:block">
            <p class="truncate text-[15px] font-semibold text-ink">{{ auth()->user()->name }}</p>
            <p class="truncate text-[13px] text-ink-muted">{{ ucfirst(auth()->user()->perfil) }}</p>
        </div>
        <svg class="hidden h-4 w-4 text-ink-muted sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.origin.top.right
        class="absolute right-0 z-50 mt-2 w-48 overflow-hidden rounded-lg border border-line bg-white py-1 shadow-md"
    >
        <a
            href="{{ route('profile') }}"
            wire:navigate
            class="block px-4 py-2 text-[14px] text-ink transition hover:bg-app-bg"
        >
            {{ __('Perfil') }}
        </a>
        <button
            wire:click="logout"
            type="button"
            class="block w-full px-4 py-2 text-left text-[14px] text-ink transition hover:bg-app-bg"
        >
            {{ __('Salir') }}
        </button>
    </div>
</div>
