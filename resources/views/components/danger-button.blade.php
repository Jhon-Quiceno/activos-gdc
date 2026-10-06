<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex h-11 items-center justify-center rounded-lg bg-danger-hover px-5 text-[15px] font-semibold text-white transition hover:bg-danger-hover/90 focus:outline-none focus:ring-2 focus:ring-danger-hover focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
