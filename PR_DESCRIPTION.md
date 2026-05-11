# PR Title
Recipes: structured ACF fields, ingredient taxonomy sync, and archive taxonomy support

## Cel
Ujednolicenie modelu danych przepisu pod ACF/meta, powiazanie skladnikow z taksonomia oraz poprawa obslugi archiwow i UX edytora blokowego.

## Co zostalo zrobione
- Dodano structured recipe fields z fallbackiem block -> ACF/meta.
- Dodano synchronizacje danych z blokow do kluczy meta kompatybilnych z ACF.
- Dodano i uszczelniono sync repeaterow dla skladnikow i krokow.
- Wprowadzono taksonomie skladnikow oraz automatyczne mapowanie wierszy skladnikow do terminow.
- Dodano dodatkowe plaskie taksonomie klasyfikacyjne (typ dania, czas przygotowania, cechy).
- Rozszerzono obsluge archiwow w MODE_POST o archiwa nowych taksonomii recipe.
- Ulepszono edytor blokowy: podpowiedzi skladnikow (datalist), pobieranie terminow przez REST, tworzenie brakujacych terminow.
- Zaktualizowano kontrakt schematu i dokumentacje blueprint.

## Zakres techniczny
- 6 plikow zmienionych w merge do main
- 1848 insertions, 34 deletions
- 3 nowe pliki dokumentacyjne

## Ryzyko i kompatybilnosc
- Niskie ryzyko runtime, ale zmiany sa szerokie funkcjonalnie (edytor + backend sync + taxonomie).
- Brak deklarowanych breaking changes API, jednak zalecana szybka regresja na edycji przepisu i archiwach taksonomii.

## Checklist QA
- Zapis przepisu aktualizuje klucze meta ACF.
- Skladniki bez term_id tworza lub wiaza termin taksonomii.
- Archiwa recipe taxonomy dzialaja poprawnie w MODE_POST.
- Edytor skladnikow pokazuje podpowiedzi i pozwala utworzyc brakujacy termin.

## Included commits
- Merge commit na main: 91008ad
- Zakres feature: 5d4438a..9a520fc
