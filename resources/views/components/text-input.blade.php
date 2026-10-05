@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'h-11 w-full rounded-lg border-line-input text-ink focus:border-primary focus:ring-primary']) }}>
