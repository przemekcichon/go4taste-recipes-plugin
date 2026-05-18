# PR: Fix stale ACF overwrite after Gutenberg REST save

**Branch:** `feature/archive-description-markup-fix` -> `main`

## Motywacja

Przy zapisie przepisu z edytora blokowego nowa tresc `post_content` potrafila zostac nadpisana starsza trescia z momentu otwarcia wpisu.

Scenariusz problemowy:
1. Gutenberg zapisuje nowe bloki przez REST (`REST PUT`) -> `post_content` jest poprawnie aktualizowany.
2. Nastepnie Gutenberg wysyla formularz meta boxow (z `$_POST['acf']`) ze starymi wartosciami.
3. `g4t_sync_acf_to_blocks()` traktowal ten drugi request jako zapis ACF i przebudowywal bloki z nieaktualnej mety.
4. Efekt: nowe bloki byly nadpisywane starym stanem.

## Zakres zmian

### `includes/sync/meta-sync.php`
- Dodano transient `g4t_rest_save_{post_id}` ustawiany podczas zapisu REST (`REST_REQUEST`).
- Transient jest krotkotrwaly (30s) i sluzy jako znacznik, ze post zostal wlasnie zapisany przez Gutenberg.

### `includes/sync/acf-to-blocks.php`
- Zmieniono sygnature `g4t_sync_acf_to_blocks(...)`, aby przyjmowala instancje pluginu i mogla poprawnie operowac na proxy hookach.
- Dodano guard: natychmiastowy return dla requestow REST.
- Dodano guard na transient `g4t_rest_save_{post_id}`: follow-up POST z meta boxow jest ignorowany, aby nie nadpisac nowo zapisanych blokow.
- Poprawiono `remove_action/add_action` dla `save_post`: teraz uzywa proxy metody klasy (`array($plugin, 'proxy_sync_meta')`), zgodnie z rzeczywista rejestracja hooka.
- Usunieto nieuzywany tracking edit source z tej sciezki.

### `includes/class-go4taste-recipes-plugin.php`
- `proxy_sync_acf_to_blocks()` przekazuje instancje pluginu do `g4t_sync_acf_to_blocks()`.
- Usunieto hook i proxy metody zwiazane z UI switch notice (nieuzywana sciezka po cleanupie).

### Cleanup

#### `includes/sync/detection.php`
- Usunieto nieuzywane funkcje trackingowe i notice (`g4t_track_edit_source`, `g4t_maybe_show_ui_switch_notice`).

#### `includes/view-model/normalizers.php`
- Usunieto nieuzywane helpery normalizacji obrazow.

## Weryfikacja

- `php -l` dla wszystkich zmienionych plikow: OK (brak bledow skladni).
- Reka (scenariusz reprodukcji): zapis w Gutenberg nie jest juz cofany przez nastepujacy POST meta boxow.
- Hook `save_post` jest tymczasowo odlaczany i ponownie dolaczany poprawnym callbackiem proxy.

## Ryzyko / kompatybilnosc

- Brak zmian w publicznym API pluginu.
- Zmiany dotycza tylko orkiestracji zapisu i usuniecia martwego kodu.
- Zachowanie po stronie ACF-only pozostaje bez zmian; guardy dotyczace REST zapobiegaja jedynie niechcianemu overwrite po zapisie Gutenberg.
