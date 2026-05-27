# PR: Dynamiczny pasek postepu po podkrokach + usuniecie etykiet substep

**Branch:** feature/dynamic-progress-substeps -> main

## Motywacja

W kreatorze przepisu aktywny segment paska postepu byl zawsze wypelniany do stalej wartosci (~55%),
niezaleznie od liczby podkrokow i aktualnej pozycji uzytkownika w kroku.

Dodatkowo widok formularza pokazywal osobne etykiety typu `x z y · ...` (`step__substep`),
co dublowalo informacje wizualne po stronie paska postepu.

Cel tej zmiany:
1. Urealnic wypelnienie aktywnego segmentu na podstawie rzeczywistego podkroku.
2. Zachowac dotychczasowa semantyke `is-active` i `is-done`.
3. Usunac etykiety `step__substep` ze wszystkich krokow formularza.

## Zakres zmian

### 1) Dynamiczne wypelnienie segmentu progress

Plik: `assets/js/acf-front-form.js`

- Dodane funkcje pomocnicze:
  - `clamp01(value)`
  - `getFormSubstepTotal(step)`
  - `getCurrentFormSubstepPosition(screenIdx)`
  - `getActiveStepFillScale(screenIdx)`
- W `showScreen(idx)`:
  - dla kazdego segmentu czyszczone jest poprzednie `--progress-fill-scale`,
  - aktywny segment dostaje nowa wartosc `--progress-fill-scale` wyliczana z podkroku.
- Intro nie liczy sie do substepu (`0%` wypelnienia aktywnego segmentu).
- Dla ekranow innych niz `intro/form` (review/done) semantyka pozostaje bez zmian (wszystkie `is-done`).

### 2) CSS progress oparty o zmienne

Plik: `assets/css/acf-front-form.css`

- `grid-template-columns` dla `.progress` korzysta z `--progress-steps` (fallback `6`).
- `@keyframes fillSeg` konczy animacje na `scaleX(var(--progress-fill-scale, 0.5))`
  zamiast stalego `scaleX(0.55)`.

### 3) Usuniecie etykiet substep z UI

Pliki:
- `includes/slices/recipe-input-surface/recipe-creator-template.php`
- `assets/css/acf-front-form.css`

- Usuniete wszystkie elementy `<div class="step__substep">...</div>` w krokach formularza.
- Usuniete nieuzywane style `.step__substep` i `.step--center > .step__substep`.

## Weryfikacja

- Walidacja edytowanych plikow: brak bledow (JS/CSS/PHP) w Problems.
- Sprawdzenie wyszukiwania: brak wystapien `step__substep` po zmianie.
- Kontrola diff: zmiany ograniczone do 3 plikow.

## Ryzyko / kompatybilnosc

- Ryzyko niskie/srednie: zmiana dotyczy warstwy UI kreatora (JS + CSS + template).
- Brak zmian w modelu danych, ACF schema, hookach backendowych i zapisie recipe meta.
- Potencjalny obszar uwagi: wizualny odbior animacji na roznych urzadzeniach i przy szybkim przechodzeniu Wstecz/Dalej.

## Checklist

- [x] Aktywny segment progress zalezy od aktualnego podkroku
- [x] Intro nie liczy sie do podkroku (0%)
- [x] Zachowane `is-active` i `is-done`
- [x] Usuniete etykiety `step__substep` z formularza
- [x] Usuniete martwe style CSS zwiazane z `step__substep`
