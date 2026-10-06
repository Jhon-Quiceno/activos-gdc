## Que hace este PR

<!-- Una o dos lineas: que tarea resuelve y por que. -->

## Checklist (ver docs/normas-de-trabajo.md, seccion 7 "Que significa terminado")

- [ ] Funciona desde cero con `migrate:fresh --seed`, sin pasos manuales adicionales.
- [ ] Usa los componentes Blade compartidos (`x-ui.*`) y el layout general, no una pantalla improvisada.
- [ ] Si registra un evento sobre un equipo, pasa por `HistorialService::registrar()`.
- [ ] Tiene al menos una prueba Pest que cubra la regla principal de la tarea.
- [ ] `./vendor/bin/pest` corre en verde en mi maquina.

## Notas para quien revisa

<!-- Algo puntual que el revisor deba saber (decision no obvia, algo a probar a mano, etc). Opcional. -->
