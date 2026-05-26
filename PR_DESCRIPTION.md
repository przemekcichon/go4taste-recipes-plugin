# PR: Finalize vertical-slices architecture by removing legacy adapters

**Branch:** chore/phase-6-adapter-cleanup -> main

## Motywacja

Domkniecie Fazy 6 planu migracji: usuniecie tymczasowych adapterow kompatybilnosci
po migracji do vertical slices. Celem jest uproszczenie include graph i utrzymanie
jednego, docelowego punktu prawdy w `includes/slices/*`.

Cel tej zmiany:
1. Usunac adaptery w starych sciezkach warstwowych.
2. Podpiac orchestrator i autoload Composer bezposrednio pod entrypointy slice.
3. Utrzymac niezmieniony runtime behavior i SCHEMA_CONTRACT_V2.

## Zakres zmian

### Nowe slices

- `includes/slices/recipe-composition/`
- `includes/slices/recipe-read-model/`
- `includes/slices/recipe-registration/`
- `includes/slices/recipe-storage/`
- `includes/slices/recipe-input-surface/`
- `includes/slices/recipe-output-surface/`
- `includes/slices/recipe-admin-config/`

### Usuniete legacy adaptery i stare warstwy

Usuniete zostaly pliki adapterow oraz puste indeksy ze starych katalogow:

- `includes/view-model/*`
- `includes/registration/*`
- `includes/sync/*`
- `includes/front/*`
- `includes/timber/*`
- `includes/integrations/*`
- `includes/admin/*`
- `includes/acf/*`

### Orchestrator

- `includes/class-go4taste-recipes-plugin.php` laduje teraz entrypointy slices.
- Publiczne hooki, akcje AJAX i sygnatury callbackow pozostaja bez zmian.

### Composer autoload

- `composer.json` (autoload `files`) wskazuje teraz tylko na entrypointy slice,
  bez odniesien do usunietych sciezek legacy.

### Zakres celowo bez zmian funkcjonalnych

- Brak zmian kontraktu view-modelu.
- Brak zmian publicznych hookow i callback signatures.
- Brak zmian semantyki zapisu/odczytu danych recipe.

## Weryfikacja

- `vendor\\bin\\phpunit tests\\unit`
- wynik: `EXIT=0`

## Ryzyko / kompatybilnosc

- Ryzyko funkcjonalne niskie: zmiana ma charakter architektoniczny (cleanup include graph).
- Publiczne API pluginu (hooki/filtry/AJAX) pozostaje stabilne.
- Usuniecie adapterow moze ujawnic ukryte, niestandardowe importy po stronie zewnetrznej.
  W tym PR brak takich odniesien w kodzie pluginu.

## Checklist

- [x] Legacy adaptery usuniete
- [x] Orchestrator laduje bezposrednio slice entrypointy
- [x] Composer autoload zaktualizowany
- [x] Testy unit przechodza
