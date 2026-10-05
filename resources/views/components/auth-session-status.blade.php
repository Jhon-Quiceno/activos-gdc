@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'text-[14px] font-medium text-success-text']) }}>
        {{ $status }}
    </div>
@endif
