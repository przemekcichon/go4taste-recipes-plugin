# PR: Admin Access Guard — blokada wp-admin dla wybranych ról

**Branch:** `feature/options-block-admin-by-role` → `main`

**Affects repos:** `go4taste-recipes-plugin` only

---

## Goal

Dodać do panelu ustawień wtyczki możliwość wskazania ról (np. Editor), które po zalogowaniu mają być automatycznie przekierowywane z wp-admin na stronę główną. Cel: redaktorzy nie mogą przypadkowo tworzyć ani edytować przepisów przez wp-admin zamiast przez kreator frontendowy.

---

## Design decisions

| Kwestia | Decyzja | Uzasadnienie |
|---|---|---|
| Gdzie przechowywać ustawienie | Nowy klucz `admin_block_roles` w istniejącej opcji `go4taste_recipes_options` | Zero dodatkowych opcji w bazie — ta sama sanityzacja co `creator_heartbeat_roles` |
| Cel przekierowania | `home_url('/')` | Zawsze istnieje, niezależny od konfiguracji kreatora; nie ujawnia URL panelu |
| Admini (`manage_options`) | Zawsze przepuszczani, nie można ich zablokować | Zapobiega przypadkowemu zablokowaniu sobie dostępu przez administratora |
| Wyjątek dla `/wp-admin/profile.php` | Tak — przepuszczać | Profil nie pozwala tworzyć przepisów; redaktor powinien móc zmienić hasło |
| Wyjątek dla AJAX | Tak — `wp_doing_ajax()` | Wp-admin wysyła AJAX w tle; blokada requestów XHR psuje inne wtyczki |
| Wyjątek dla REST API | Tak — `defined('REST_REQUEST') && REST_REQUEST` | REST nie jest stroną wp-admin; Gutenberg i inne używają REST |
| Wyjątek dla WP Cron | Tak — `wp_doing_cron()` | Cron odpala się w kontekście wp-admin; blokada psuje zaplanowane zadania |
| UI | Multi-checkbox — wzorzec identyczny z `creator_heartbeat_roles` | Spójność z istniejącym UI, zero nowych wzorców |
| Nowa sekcja w ustawieniach | Tak — "Kontrola dostępu do panelu" | Semantycznie odrębne od konfiguracji kreatora i źródła danych |

---

## Nowy klucz opcji

```
admin_block_roles    = []   (domyślnie puste — guard nieaktywny)
                     = ['editor', 'author']   (przykład aktywny)
```

Przechowywane w `go4taste_recipes_options` (istniejąca opcja WP) jako kolejny klucz tablicy.

---

## Logika guardu

```php
// Uruchamiany na: add_action('admin_init', 'g4t_admin_access_guard_check')
function g4t_admin_access_guard(): void {
    if ( wp_doing_ajax() )                              return; // AJAX z wp-admin
    if ( defined('REST_REQUEST') && REST_REQUEST )      return; // REST API
    if ( wp_doing_cron() )                              return; // WP Cron
    if ( ! is_user_logged_in() )                        return; // niezalogowany — WP sobie obsłuży
    if ( current_user_can('manage_options') )           return; // administrator — zawsze przepuść

    global $pagenow;
    if ( 'profile.php' === $pagenow )                   return; // własny profil — przepuść

    $blocked_roles = g4t_get_option('admin_block_roles', []);
    if ( empty($blocked_roles) )                        return; // guard nieaktywny

    $user_roles = (array) wp_get_current_user()->roles;
    if ( ! empty( array_intersect($user_roles, $blocked_roles) ) ) {
        wp_safe_redirect( home_url('/') );
        exit;
    }
}
```

---

## Pliki do zmiany

### `features/admin-settings/settings-page.php`

**1. Rozszerzenie `$defaults` w `register_setting()`**

```php
// Dodać do tablicy defaults:
'admin_block_roles' => array(),
```

**2. Nowa sekcja + nowe pole w `g4t_register_settings()`**

```php
add_settings_section(
    'go4taste_recipes_section_access',
    __( 'Kontrola dostępu do panelu', 'go4taste-recipes-plugin' ),
    'g4t_render_settings_section_access',
    'go4taste-recipes-settings'
);

add_settings_field(
    'go4taste_recipes_admin_block_roles',
    __( 'Zablokuj wp-admin dla ról', 'go4taste-recipes-plugin' ),
    'g4t_settings_field_admin_block_roles',
    'go4taste-recipes-settings',
    'go4taste_recipes_section_access'
);
```

**3. Nowe named wrapper callback**

```php
function g4t_settings_field_admin_block_roles(): void {
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        g4t_render_admin_block_roles_field( $g4t_recipes_plugin );
    }
}
```

**4. Nowa funkcja renderująca** — multi-checkbox wzorowany 1:1 na `g4t_render_creator_heartbeat_roles_field()`, z wyjątkiem że `administrator` jest zawsze wyłączony (disabled + unchecked) z opisem dlaczego.

**5. Rozszerzenie `g4t_sanitize_options()`** — ta sama logika co `creator_heartbeat_roles`:

```php
$admin_block_roles = array();
if ( isset( $input['admin_block_roles'] ) && is_array( $input['admin_block_roles'] ) ) {
    foreach ( $input['admin_block_roles'] as $role ) {
        $role = sanitize_key( (string) $role );
        // administrator nigdy nie może być na liście blokowanych
        if ( in_array( $role, $allowed_roles, true ) && 'administrator' !== $role ) {
            $admin_block_roles[] = $role;
        }
    }
    $admin_block_roles = array_values( array_unique( $admin_block_roles ) );
}
```

Klucz `admin_block_roles` dodany do zwracanej tablicy.

---

### `features/admin-settings/settings-logic.php`

Nowa funkcja `g4t_admin_access_guard()` — pełna logika guardu jak powyżej w sekcji "Logika guardu". Plik `settings-logic.php` jest dedykowany logice runtime działającej na każdym requeście — dokładnie to miejsce.

---

### `features/admin-settings/index.php`

Dodanie hooka w `g4t_bootstrap_admin_settings_feature()`:

```php
add_action( 'admin_init', 'g4t_admin_access_guard_check' );
```

Nowy wrapper (zgodnie z konwencją pliku — żadnych anonimowych callbacków bezpośrednio w `add_action`):

```php
function g4t_admin_access_guard_check(): void {
    g4t_admin_access_guard();
}
```

---

## Stages

| Stage | Status | Opis |
|---|---|---|
| Settings 1: nowy klucz + sekcja + pole | ✅ Done | `admin_block_roles` w defaults, nowa sekcja ACL, `add_settings_field` |
| Settings 2: render callback | ✅ Done | `g4t_render_admin_block_roles_field()` — multi-checkbox, admin disabled |
| Settings 3: sanityzacja | ✅ Done | Rozszerzenie `g4t_sanitize_options()` o `admin_block_roles`, blokada dodania `administrator` |
| Logic: guard function | ✅ Done | `g4t_admin_access_guard()` w `settings-logic.php` — wszystkie wyjątki + redirect |
| Bootstrap: hook | ✅ Done | `add_action('admin_init', ...)` + wrapper w `index.php` |

---

## Zakres i ryzyko

**Zakres:** 3 pliki, ~70 linii nowego kodu — mała zmiana.

**Ryzyko: niskie**

- Admini (`manage_options`) nie mogą być zablokowani — zero ryzyka utraty dostępu
- Domyślna wartość `admin_block_roles = []` — guard jest nieaktywny do momentu świadomej konfiguracji
- AJAX, REST, Cron wyjęte spod guardu — żadne requesty w tle nie są blokowane
- Wyjątek dla `profile.php` — redaktorzy zachowują dostęp do zmiany hasła
- Odwracalne: wyczyszczenie checkboxów = guard wyłączony

---

## Merge readiness

✅ Ready to merge — wszystkie 5 stage'ów zaimplementowanych, brak nowych diagnostyk IDE.
