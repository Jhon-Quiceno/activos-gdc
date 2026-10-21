<?php

use App\Support\Identificadores;

it('normaliza el serial a mayúsculas y sin espacios', function (string $entra, string $sale) {
    expect(Identificadores::serial($entra))->toBe($sale);
})->with([
    ['  cn-0001 ', 'CN-0001'],
    ['sn mon 0001', 'SNMON0001'],
    ['m27527-161', 'M27527-161'],
]);

it('lleva el código de activo al formato I1-###### o devuelve null', function (string $entra, ?string $sale) {
    expect(Identificadores::codigoActivo($entra))->toBe($sale);
})->with([
    'con prefijo y espacio' => ['I1 24147', 'I1-24147'],
    'sin guion'             => ['i124147', 'I1-24147'],
    'L1 por I1'             => ['L1-24147', 'I1-24147'],
    'con espacios alrededor'=> [' i1-024147 ', 'I1-024147'],
    'solo dígitos'          => ['020410', 'I1-020410'],
    'embebido en texto'     => ['I1-003788 Pc todo en Uno', 'I1-003788'],
    'basura sin forma'      => ['no se ve', null],
]);

it('quita los separadores del documento y lo deja en mayúsculas', function (string $entra, string $sale) {
    expect(Identificadores::documento($entra))->toBe($sale);
})->with([
    ['1.067.888.999', '1067888999'],
    ['1067 888 999', '1067888999'],
    ['ac-123.456', 'AC123456'],
]);

it('el patrón del código de activo acepta 5 y 6 dígitos y rechaza el resto', function () {
    expect(preg_match(Identificadores::PATRON_CODIGO_ACTIVO, 'I1-24147'))->toBe(1)
        ->and(preg_match(Identificadores::PATRON_CODIGO_ACTIVO, 'I1-024147'))->toBe(1)
        ->and(preg_match(Identificadores::PATRON_CODIGO_ACTIVO, 'I1-2414'))->toBe(0)
        ->and(preg_match(Identificadores::PATRON_CODIGO_ACTIVO, '24147'))->toBe(0);
});
