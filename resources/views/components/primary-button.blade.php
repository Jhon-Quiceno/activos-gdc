<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex h-11 items-center justify-center rounded-lg bg-primary px-5 font-semibold text-sm text-white transition hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
