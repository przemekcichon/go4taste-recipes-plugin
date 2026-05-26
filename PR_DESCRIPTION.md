# PR: Finalize meta-only recipe architecture and remove block-sync legacy

**Branch:** feat/meta-only-architecture-cleanup -> main

## Motywacja

Domkniecie migracji do modelu meta-only dla recipes pluginu.

Wczesniejsza architektura block/sync zostawiala martwe moduly i niespny UX w adminie
(Import Tools / Reverse Sync), mimo ze runtime juz dzialal w modelu creator + ACF/meta.

Celem tej zmiany jest:
1. Usuniecie legacy kodu block-era i fallbackow runtime.
2. Ujednolicenie zapisu/odczytu recipe do jednego modelu danych (meta-only).
3. Dostosowanie testow i dokumentacji do aktualnej architektury.

## Zakres zmian

### Runtime i zapis danych

- Front creator zapisuje bezposrednio do post_meta (bez budowania block markup).
- Featured image jest ustawiane bezposrednio przez post thumbnail.
- View model builder dziala wylacznie na danych ACF/meta.
- Gutenberg jest wylaczony dla skonfigurowanego recipe post type.

### Usuniete moduly legacy

- includes/registration/blocks.php
- includes/sync/acf-to-blocks.php
- includes/sync/converters.php
- includes/sync/detection.php
- includes/admin/reverse-sync.php

### Admin i UX

- Usuniety UI reverse sync z settings page (sekcja Import Tools i notice results).

### Dokumentacja i instrukcje

- Zaktualizowane: README.md, CLAUDE.md, .github/copilot-instructions.md.
- SCHEMA_CONTRACT_V2.md dopiete do aktualnego runtime (bez usunietych modulow).
- SCHEMA_CONTRACT_V1.md oznaczony jako archival legacy reference.
- Zaktualizowane prompty/komendy pomocnicze, aby nie odwolywaly sie do usunietego flow.

### Testy

- tests/unit/test-builder.php dostosowany do usuniecia merge-helperow.
- Dodatkowe pokrycie dla ladowania repeaterow z meta-only.

## Weryfikacja

- Testy jednostkowe:
  - .\\vendor\\bin\\phpunit tests/unit/test-builder.php tests/unit/test-normalizers.php
  - wynik: EXIT=0

## Ryzyko / kompatybilnosc

- Zmiana celowo usuwa backward compatibility dla block-era workflow.
- Srodowiska zalezne od starego block sync/reverse sync musza przejsc na model creator + ACF/meta.
- Publiczny runtime recipe opiera sie teraz jednoznacznie na meta-only contract.

## Checklist

- [x] Legacy block/sync modules usuniete
- [x] Settings page oczyszczony z reverse sync UI
- [x] Builder i runtime przejscie na meta-only
- [x] Testy builder/normalizers przechodza
- [x] Branch wypchniety i gotowy do PR
