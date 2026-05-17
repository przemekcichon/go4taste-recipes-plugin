# PR: Zachowanie atrybutow bloku ads w ACF sync i odczyt product IDs z tresci bloku

**Branch:** `feature/ads-block-sync-and-product-ids` -> `main`

## Motywacja

Po wybraniu produktow w bloku `go4taste-ads/inline-banner` i zapisaniu przez ACF, produkty znikaly po odswiezeniu. Przyczyną bylo to, ze sync ACF->bloki w `acf-to-blocks.php` zawsze nadpisywal blok ads pustym komentarzem `<!-- wp:go4taste-ads/inline-banner /-->`, kasujac atrybut `products`. Niezaleznie od tego, view model pluginu odczytywal ID produktow wylacznie z ACF meta (`recipe_ads_products`), a nie z atrybutow bloku.

Dodatkowo przy parsowaniu atrybutu `products` (tablica obiektow `{id, name, ...}`) wykonywany byl rzut `(string)` na cala tablice, generujac `Warning: Array to string conversion` x N.

## Zakres zmian

### `includes/sync/acf-to-blocks.php`
- Zastapiono hardcodowane `<!-- wp:go4taste-ads/inline-banner /-->` wywolaniem `g4t_build_inline_banner_block_from_existing_content()`.
- Helper parsuje aktualna tresc posta i serializuje istniejacy blok ads z jego atrybutami (produkty zostaja zachowane).
- Dodano `g4t_find_inline_banner_block()` — rekurencyjne wyszukiwanie bloku w drzewie blokow.

### `includes/sync/meta-sync.php`
- W `g4t_get_recipe_blocks_data()` dodano obsluge bloku `go4taste-ads/inline-banner`: ekstrahuje ID produktow z atrybutu `products` do klucza `ads_product_ids`.
- Dodano `g4t_extract_ads_product_id()` — bezpieczne wyciaganie ID z formatow: scalar, `['id' => ...]`, `['product_id' => ...]`. Eliminuje `Array to string conversion`.

### `includes/view-model/builder.php`
- `g4t_build_single_recipe_view_model()` — ID produktow pobierane najpierw z `block_data['ads_product_ids']`, dopiero potem fallback do ACF meta.
- Dodano pomocniki `g4t_get_ads_product_ids_from_block_data()`, `g4t_get_ads_product_ids_from_acf()`, `g4t_normalize_ads_product_ids()`.

## Weryfikacja

- Produkty wybrane w edytorze nie znikaja po zapisie przez ACF.
- Brak `Warning: Array to string conversion` w logach PHP.
- Posty bez bloku ads lub bez wybranych produktow renderuja sie bez zmian.
- Importowane posty bez bloku nadal korzystaja z fallbacku ACF meta.

## Brak breaking changes

- Kolejnosc priorytetow: block data > ACF meta (bez zmian w stosunku do pozostalych pol).
- `g4t_fetch_ads_products()` wywoluje sie tylko gdy sa ID do pobrania.
