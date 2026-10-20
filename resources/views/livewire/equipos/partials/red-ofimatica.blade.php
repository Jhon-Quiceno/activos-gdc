{{--
    Ofimática y red de un equipo con configuración de cómputo (docs/base-de-datos.md
    §4.5), trait DatosDeAdquisicion. Lo usan el registro y la edición, dentro de
    la tarjeta de cómputo / software.
--}}
<div class="mt-5">
    <p class="text-[13px] font-semibold text-ink-label">{{ __('Ofimática y red') }}</p>
    <div class="mt-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.input name="ofimatica" :label="__('Ofimática')" wire:model="ofimatica" maxlength="80" placeholder="Microsoft 365, LibreOffice…" />
        <x-ui.input name="licenciaOfimatica" :label="__('Licencia de ofimática')" wire:model="licenciaOfimatica" maxlength="80" />
        <x-ui.input name="dominio" :label="__('Dominio')" wire:model="dominio" maxlength="60" placeholder="cordoba.gov.co" />
        <x-ui.input name="direccionIp" :label="__('Dirección IP')" wire:model="direccionIp" maxlength="45" placeholder="10.0.0.25" />
        <x-ui.input name="direccionMac" :label="__('Dirección MAC')" wire:model.blur="direccionMac" maxlength="17" placeholder="3C:52:82:1A:2B:4C" />
    </div>
</div>
