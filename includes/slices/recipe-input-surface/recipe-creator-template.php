<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$creator_brand_mark_url = isset( $creator_brand_mark_url ) ? (string) $creator_brand_mark_url : '';
$creator_brand_name     = isset( $creator_brand_name ) ? (string) $creator_brand_name : '';
?>

<div class="shell">
    <!-- ============ TOP BAR ============ -->
    <header class="topbar">
      <div class="topbar__left">
        <?php if ( ! empty( $creator_brand_mark_url ) || ! empty( $creator_brand_name ) ) : ?>
        <a href="#" class="topbar__brand" onclick="event.preventDefault()">
          <?php if ( ! empty( $creator_brand_mark_url ) ) : ?>
          <span class="topbar__mark"><img src="<?php echo esc_url( $creator_brand_mark_url ); ?>" alt="<?php echo esc_attr( $creator_brand_name ); ?>" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:inherit;"/></span>
          <?php endif; ?>
          <?php if ( ! empty( $creator_brand_name ) ) : ?>
          <span><?php echo esc_html( $creator_brand_name ); ?></span>
          <?php endif; ?>
        </a>
        <?php endif; ?>
        <div class="step-indicator" id="step-indicator">Krok 1 z 6 · Podstawy</div>
      </div>
      <div class="topbar__actions">
        <span id="autosave-pill" style="font:500 13px var(--sans); color:var(--ink-3); margin-right:8px;"></span>
        <div id="preview-dropdown" class="g4t-preview-dropdown" style="position:relative; display:inline-flex;">
          <button type="button" id="preview-btn" class="btn-pill" aria-expanded="false" aria-haspopup="true" aria-disabled="true" style="pointer-events:none; opacity:0.4;">
            <span style="display:inline-flex; align-items:center; gap:8px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
              Zobacz podgląd
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
          </button>
          <div id="preview-dropdown-menu" role="menu" hidden style="position:absolute; top:calc(100% + 6px); right:0; min-width:200px; background:var(--surface, #fff); border:1px solid var(--border, #e5e7eb); border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,.12); z-index:1000; overflow:hidden;">
            <a id="preview-front" href="#" target="_blank" role="menuitem" style="display:flex; align-items:center; gap:10px; padding:10px 16px; font:500 14px var(--sans); color:var(--ink-1,#111); text-decoration:none; white-space:nowrap; transition:background .15s;" onmouseover="this.style.background='var(--bg-hover,#f5f5f5)'" onmouseout="this.style.background=''">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
              Front strony
            </a>
            <a id="preview-form" href="#" role="menuitem" style="display:flex; align-items:center; gap:10px; padding:10px 16px; font:500 14px var(--sans); color:var(--ink-1,#111); text-decoration:none; white-space:nowrap; border-top:1px solid var(--border,#e5e7eb); transition:background .15s;" onmouseover="this.style.background='var(--bg-hover,#f5f5f5)'" onmouseout="this.style.background=''"> 
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="18" rx="2"/><path d="M8 10h8M8 14h5"/></svg>
              Podgląd formularza
            </a>
          </div>
        </div>
        <button type="button" class="btn-pill" data-modal="exit">
          <span style="display:inline-flex; align-items:center; gap:8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 5h10l4 4v10H5z"/><path d="M8 5v4h7"/><rect x="8" y="13" width="8" height="6"/></svg>
            Zapisz i wyjdź
          </span>
        </button>
      </div>
    </header>

    <!-- ============ STAGE ============ -->
    <main class="stage">

      <!-- ============== KROK 1: PODSTAWY ============== -->
      <section class="step-intro" data-screen-idx="0">
        <div class="step-intro__text">
          <div class="step__eyebrow">Krok 1 z 6 · Podstawy</div>
          <h1 class="step__title">Powiedz nam, co dziś gotujemy</h1>
          <p class="step__lede">Wprowadź wstęp do przepisu, jego trudność, liczbę porcji oraz czasy. Wszystkie pola oprócz „główne zdjęcie" i „czas namoczenia" są wymagane.</p>
          <p class="step-intro__hint">Sześciostronicowy krok — po jednej decyzji na ekran. Kliknij „Dalej".</p>
        </div>
        <div class="step-intro__image" data-tint="0">
          <svg class="step-intro__illustration" viewBox="0 0 100 100" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <rect x="22" y="14" width="56" height="72" rx="3"/>
      <path d="M30 28h28M30 38h40M30 48h36M30 58h40M30 68h24M30 78h14"/>
      <path d="M62 22l2-4 2 4 4 2-4 2-2 4-2-4-4-2z" stroke-linejoin="round"/>
          </svg>
          <span class="step-intro__image-num">01</span>
          <image-slot id="intro-1" placeholder="Wrzuć zdjęcie do tego kroku (przeciągnij lub kliknij)" shape="rect"></image-slot>
        </div>
      </section>

      <!-- 1.1 · TYTUŁ -->
      <section class="step step--center" data-screen-idx="1" hidden>
        <div class="step__substep">1 z 6 · Tytuł</div>
        <div class="field">
          <label class="field__label" for="title">Tytuł przepisu</label>
          <p class="field__hint">Krótki, konkretny — taki, jaki pojawi się na liście przepisów. Np. „Kremowa zupa z brokułów i porów".</p>
          <input id="title" class="input" type="text" maxlength="120" data-bind="title"
            style="font-family:var(--serif); font-size:24px; font-weight:600; padding:18px 18px;"
            placeholder="np. Kremowa zupa z brokułów i porów"/>
          <div style="display:flex; justify-content:space-between;">
            <span class="field__hint">Sugerowana długość: 20–80 znaków.</span>
            <span class="field__hint"><span id="title-count">0</span>/120</span>
          </div>
        </div>
      </section>

      <!-- 1.2 · LEAD -->
      <section class="step step--center" data-screen-idx="2" hidden>
        <div class="step__substep">2 z 6 · Krótki opis</div>
        <div class="field">
          <label class="field__label" for="lead">Wstęp / opis (lead)</label>
          <p class="field__hint">2–4 zdania, które pojawią się pod tytułem przepisu. Krótko opisz danie i kontekst.</p>
          <textarea id="lead" class="textarea" maxlength="500" data-bind="lead"
            placeholder="Kremowa zupa z brokułów i porów, posypana prażonymi płatkami migdałów. Sycąca, a jednocześnie lekka — idealna na chłodny wieczór."></textarea>
          <div style="display:flex; justify-content:space-between;">
            <span class="field__hint">Sugerowana długość: 180–400 znaków.</span>
            <span class="field__hint"><span id="lead-count">0</span>/500</span>
          </div>
        </div>
      </section>

      <!-- 1.3 · GŁÓWNE ZDJĘCIE -->
      <section class="step step--center" data-screen-idx="3" hidden>
        <div class="step__substep">3 z 6 · Główne zdjęcie</div>
        <div class="field">
          <label class="field__label">Główne zdjęcie przepisu <span class="field__opt">opcjonalnie</span></label>
          <p class="field__hint">Najpiękniejsze zdjęcie Twojego dania. Ten obraz pojawi się w hero obszarze, na liście przepisów i w wynikach wyszukiwania.</p>
          <div id="featured-image-slot"></div>
        </div>
      </section>

      <!-- 1.4 · TRUDNOŚĆ -->
      <section class="step step--center" data-screen-idx="4" hidden>
        <div class="step__substep">4 z 6 · Dla kogo</div>
        <div class="field">
          <label class="field__label">Poziom trudności</label>
          <p class="field__hint">Pomóż czytelnikowi zdecydować, czy przepis jest dla niego.</p>
          <div class="choice-row" id="difficulty-row">
            <button type="button" class="choice" data-difficulty="easy">
              <div class="choice__glyph"><i style="height:7px"></i><i style="height:7px; opacity:.3"></i><i style="height:7px; opacity:.3"></i></div>
              <div class="choice__title">Łatwy</div>
              <div class="choice__desc">Dla każdego</div>
            </button>
            <button type="button" class="choice" data-difficulty="medium">
              <div class="choice__glyph"><i style="height:7px"></i><i style="height:12px"></i><i style="height:7px; opacity:.3"></i></div>
              <div class="choice__title">Średni</div>
              <div class="choice__desc">Trochę wprawy</div>
            </button>
            <button type="button" class="choice" data-difficulty="hard">
              <div class="choice__glyph"><i style="height:7px"></i><i style="height:12px"></i><i style="height:17px"></i></div>
              <div class="choice__title">Trudny</div>
              <div class="choice__desc">Wymaga techniki</div>
            </button>
          </div>
        </div>
      </section>

      <!-- 1.5 · PORCJE -->
      <section class="step step--center" data-screen-idx="5" hidden>
        <div class="step__substep">5 z 6 · Wielkość przepisu</div>
        <div class="field">
          <label class="field__label">Porcje</label>
          <p class="field__hint">Ile porcji wychodzi z tego przepisu i w jakiej jednostce.</p>
          <div class="row">
            <div class="stepper" style="justify-self:start;">
              <button type="button" class="stepper__btn" data-portion-delta="-1" aria-label="Mniej">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 12h14"/></svg>
              </button>
              <span class="stepper__val" id="portions-val">4</span>
              <button type="button" class="stepper__btn" data-portion-delta="1" aria-label="Więcej">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 5v14M5 12h14"/></svg>
              </button>
            </div>
            <input class="input" type="text" data-bind="portionUnit" list="portion-unit-suggestions"
              placeholder="np. osób, porcji, kawałków…"/>
            <datalist id="portion-unit-suggestions"></datalist>
          </div>
          <p class="field__hint" style="margin-top:6px;">Wpisz dowolną jednostkę. Podpowiedzi pojawią się automatycznie — to tylko sugestie.</p>
        </div>
      </section>

      <!-- 1.6 · CZAS -->
      <section class="step step--center" data-screen-idx="6" hidden>
        <div class="step__substep">6 z 6 · Ile to zajmie</div>
        <div class="field">
          <label class="field__label">Czas</label>
          <p class="field__hint">Czas całkowity policzymy automatycznie. Podaj wartości w godzinach i minutach.</p>

          <div class="time-grid">
            <div class="time-card" data-time-card="timePrep">
              <div class="time-card__head"><span class="time-card__label">Przygotowanie</span></div>
              <div class="time-card__inputs">
                <div class="time-card__group">
                  <span class="time-card__sub">godz.</span>
                  <input class="input" type="number" min="0" max="48" data-time-h="timePrep" value="0"/>
                </div>
                <div class="time-card__group">
                  <span class="time-card__sub">min.</span>
                  <input class="input" type="number" min="0" max="59" data-time-m="timePrep" value="0"/>
                </div>
              </div>
            </div>

            <div class="time-card">
              <div class="time-card__head"><span class="time-card__label">Gotowanie</span></div>
              <div class="time-card__inputs">
                <div class="time-card__group">
                  <span class="time-card__sub">godz.</span>
                  <input class="input" type="number" min="0" max="48" data-time-h="timeCook" value="0"/>
                </div>
                <div class="time-card__group">
                  <span class="time-card__sub">min.</span>
                  <input class="input" type="number" min="0" max="59" data-time-m="timeCook" value="0"/>
                </div>
              </div>
            </div>

            <div class="time-card">
              <div class="time-card__head">
                <span class="time-card__label">Namoczenie</span>
                <span class="time-card__optional">opcjonalnie</span>
              </div>
              <div class="time-card__inputs">
                <div class="time-card__group">
                  <span class="time-card__sub">godz.</span>
                  <input class="input" type="number" min="0" max="48" data-time-h="timeSoak" value="0"/>
                </div>
                <div class="time-card__group">
                  <span class="time-card__sub">min.</span>
                  <input class="input" type="number" min="0" max="59" data-time-m="timeSoak" value="0"/>
                </div>
              </div>
            </div>

            <div class="time-card time-card__total">
              <div class="time-card__head">
                <span class="time-card__label">Razem</span>
                <span class="time-card__optional">automatycznie</span>
              </div>
              <div class="time-card__inputs">
                <div class="time-card__group">
                  <span class="time-card__sub">godz.</span>
                  <input class="input" type="number" id="total-h" disabled value="0" style="border-style:dashed; background:transparent;"/>
                </div>
                <div class="time-card__group">
                  <span class="time-card__sub">min.</span>
                  <input class="input" type="number" id="total-m" disabled value="0" style="border-style:dashed; background:transparent;"/>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ============== KROK 2: SKŁADNIKI ============== -->
      <section class="step-intro" data-screen-idx="7" hidden>
        <div class="step-intro__text">
          <div class="step__eyebrow">Krok 2 z 6 · Składniki</div>
          <h1 class="step__title">Co znajdzie się na liście zakupów?</h1>
          <p class="step__lede">Dodaj wszystkie składniki potrzebne do przepisu. Nazwy synchronizujemy z taksonomią — wybierz z listy lub stwórz nowy składnik.</p>
          <p class="step-intro__hint">Możesz dorzucić zdjęcie wszystkich składników w jednym kadrze.</p>
        </div>
        <div class="step-intro__image" data-tint="1">
          <svg class="step-intro__illustration" viewBox="0 0 100 100" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <circle cx="36" cy="62" r="14"/>
      <path d="M36 48v-8M32 42l4-2 4 2"/>
      <circle cx="64" cy="62" r="11"/>
      <path d="M60 53l4-2 4 2"/>
      <path d="M50 26v14M44 30c2 1 4 2 6 3M56 30c-2 1-4 2-6 3M44 36c2 1 4 2 6 3M56 36c-2 1-4 2-6 3"/>
          </svg>
          <span class="step-intro__image-num">02</span>
          <image-slot id="intro-2" placeholder="Wrzuć zdjęcie do tego kroku (przeciągnij lub kliknij)" shape="rect"></image-slot>
        </div>
      </section>

      <section class="step step--wide step--center" data-screen-idx="8" hidden>
        <div class="step__substep">1 z 2 · Zdjęcie</div>
        <!-- Zdjęcie składników -->
        <div class="field">
          <div class="field__label">Zdjęcie składników <span class="field__opt">opcjonalnie</span></div>
          <p class="field__hint">Wszystkie składniki na jednym, dobrze oświetlonym kadrze. Zalecane proporcje 16:9.</p>
          <div id="ingredients-photo-slot"></div>
        </div>

      </section>

      <!-- 2.2 · LISTA SKŁADNIKÓW -->
      <section class="step step--wide" data-screen-idx="9" hidden>
        <div class="step__substep">2 z 2 · Lista</div>
        <!-- Lista składników -->
        <div class="field">
          <div class="field__label">Lista składników</div>
          <p class="field__hint">Minimum jeden składnik. Możesz dodać wskazówkę (np. „świeży, drobno posiekany").</p>

          <div class="repeater" id="ingredients-list"></div>

          <button type="button" class="rep-add" data-action="add-ingredient">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 5v14M5 12h14"/></svg>
            Dodaj składnik
          </button>
        </div>

        <datalist id="unit-suggestions"></datalist>
      </section>

      <!-- ============== KROK 3: PRZYGOTOWANIE ============== -->
      <section class="step-intro" data-screen-idx="10" hidden>
        <div class="step-intro__text">
          <div class="step__eyebrow">Krok 3 z 6 · Przygotowanie</div>
          <h1 class="step__title">Rozpisz przepis krok po kroku</h1>
          <p class="step__lede">Każdy krok to jedna instrukcja. Możesz dodać wskazówkę i zdjęcie ilustrujące moment przygotowania.</p>
          <p class="step-intro__hint">Im krótsze i bardziej konkretne kroki, tym łatwiej się gotuje.</p>
        </div>
        <div class="step-intro__image" data-tint="2">
          <svg class="step-intro__illustration" viewBox="0 0 100 100" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <rect x="18" y="58" width="64" height="10" rx="2"/>
      <path d="M28 48l44 0 4-8H32z" stroke-linejoin="round"/>
      <path d="M72 48l10 0"/>
      <rect x="26" y="72" width="7" height="7"/>
      <rect x="38" y="72" width="7" height="7"/>
      <rect x="50" y="72" width="7" height="7"/>
      <rect x="62" y="72" width="7" height="7"/>
          </svg>
          <span class="step-intro__image-num">03</span>
          <image-slot id="intro-3" placeholder="Wrzuć zdjęcie do tego kroku (przeciągnij lub kliknij)" shape="rect"></image-slot>
        </div>
      </section>

      <section class="step step--wide" data-screen-idx="11" hidden>
        <div class="repeater" id="prep-list"></div>

        <button type="button" class="rep-add" data-action="add-prep">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 5v14M5 12h14"/></svg>
          Dodaj kolejny krok
        </button>
      </section>

      <!-- ============== KROK 4: WSKAZÓWKI ============== -->
      <section class="step-intro" data-screen-idx="12" hidden>
        <div class="step-intro__text">
          <div class="step__eyebrow">Krok 4 z 6 · Wskazówki</div>
          <h1 class="step__title">Twoje sztuczki i triki</h1>
          <p class="step__lede">Co warto wiedzieć przed gotowaniem? Czym zastąpić składniki? Jak przechowywać resztki? Krótka sekcja, która zostanie wyświetlona pod listą kroków.</p>
          <p class="step-intro__hint">Pole opcjonalne, ale dobre wskazówki podnoszą jakość przepisu.</p>
        </div>
        <div class="step-intro__image" data-tint="3">
          <svg class="step-intro__illustration" viewBox="0 0 100 100" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M50 20c-12 0-18 9-14 20l6 12h16l6-12c4-11-2-20-14-20z" stroke-linejoin="round"/>
      <path d="M42 58h16M44 64h12M46 70h8"/>
      <path d="M50 6v8M26 18l5 5M74 18l-5 5M12 38h8M80 38h8"/>
          </svg>
          <span class="step-intro__image-num">04</span>
          <image-slot id="intro-4" placeholder="Wrzuć zdjęcie do tego kroku (przeciągnij lub kliknij)" shape="rect"></image-slot>
        </div>
      </section>

      <section class="step step--center" data-screen-idx="13" hidden>
        <div class="field">
          <label class="field__label">Ogólne wskazówki do przepisu</label>
          <p class="field__hint">Możesz zostawić to pole puste, ale dobre wskazówki znacząco podnoszą jakość przepisu.</p>
          <textarea class="textarea" data-bind="tips" maxlength="1500" style="min-height:200px;"
            placeholder="Np.&#10;· Brokuł najlepiej blanszować nie dłużej niż 3 minuty — zachowa kolor i sprężystość.&#10;· Płatki migdałowe pražcie na suchej patelni bez tłuszczu, ciągle mieszając.&#10;· Zupę można zamrozić, ale dodajcie śmietanę dopiero po rozmrożeniu."></textarea>
          <div style="display:flex; justify-content:space-between;">
            <span class="field__hint">Wskazówka — punktory zaczynaj od „·" lub „—"</span>
            <span class="field__hint"><span id="tips-count">0</span>/1500</span>
          </div>
        </div>
      </section>

      <!-- ============== KROK 5: ŹRÓDŁO ============== -->
      <section class="step-intro" data-screen-idx="14" hidden>
        <div class="step-intro__text">
          <div class="step__eyebrow">Krok 5 z 6 · Źródło</div>
          <h1 class="step__title">Skąd pochodzi przepis?</h1>
          <p class="step__lede">Jeśli inspirowałeś się książką, blogiem lub innym źródłem — podaj jego nazwę i ewentualnie link. Pola opcjonalne.</p>
          <p class="step-intro__hint">Doceniamy autorów i źródła. Możesz pominąć ten krok.</p>
        </div>
        <div class="step-intro__image" data-tint="4">
          <svg class="step-intro__illustration" viewBox="0 0 100 100" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M50 28L24 23v50l26 5z" stroke-linejoin="round"/>
      <path d="M50 28l26-5v50l-26 5z" stroke-linejoin="round"/>
      <path d="M50 28v50"/>
      <path d="M30 36l16 2M30 46l16 2M30 56l14 2"/>
      <path d="M54 38l16-2M54 48l16-2M54 58l14-2"/>
          </svg>
          <span class="step-intro__image-num">05</span>
          <image-slot id="intro-5" placeholder="Wrzuć zdjęcie do tego kroku (przeciągnij lub kliknij)" shape="rect"></image-slot>
        </div>
      </section>

      <section class="step step--center" data-screen-idx="15" hidden>
        <div class="source-card">
          <div class="field">
            <label class="field__label">Źródło · nazwa <span class="field__opt">opcjonalnie</span></label>
            <p class="field__hint">Np. „Magda Gessler — Smak życia" lub „przepisy babci".</p>
            <div style="position:relative;">
              <input class="input" data-bind="sourceName" style="padding-left:46px;" placeholder="Nazwa książki, autora lub serwisu"/>
              <span style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--ink-3);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 5a2 2 0 0 1 2-2h12v18H6a2 2 0 0 1-2-2zM6 17h12"/></svg>
              </span>
            </div>
          </div>

          <div class="field">
            <label class="field__label">Źródło · URL <span class="field__opt">opcjonalnie</span></label>
            <p class="field__hint">Link otworzy się w nowej karcie.</p>
            <div style="position:relative;">
              <input class="input" type="url" data-bind="sourceUrl" style="padding-left:46px;" placeholder="https://"/>
              <span style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--ink-3);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7L11.5 7"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7L12.5 17"/></svg>
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- ============== KROK 6: PRODUKTY ============== -->
      <section class="step-intro" data-screen-idx="16" hidden>
        <div class="step-intro__text">
          <div class="step__eyebrow">Krok 6 z 6 · Promowane produkty</div>
          <h1 class="step__title">Co polecasz do tego przepisu?</h1>
          <p class="step__lede">Wybierz produkty z naszego sklepu, które pasują do dania — pojawią się jako rekomendacje pod przepisem. Pole opcjonalne.</p>
          <p class="step-intro__hint">Możesz wybrać dowolną liczbę produktów albo pominąć ten krok.</p>
        </div>
        <div class="step-intro__image" data-tint="5">
          <svg class="step-intro__illustration" viewBox="0 0 100 100" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M28 38h44l-4 44H32z" stroke-linejoin="round"/>
      <path d="M38 38v-10c0-5 4-10 12-10s12 5 12 10v10" fill="none"/>
      <circle cx="42" cy="50" r="2.5" fill="currentColor" stroke="none"/>
      <circle cx="58" cy="50" r="2.5" fill="currentColor" stroke="none"/>
          </svg>
          <span class="step-intro__image-num">06</span>
          <image-slot id="intro-6" placeholder="Wrzuć zdjęcie do tego kroku (przeciągnij lub kliknij)" shape="rect"></image-slot>
        </div>
      </section>

      <section class="step step--wide" data-screen-idx="17" hidden>
        <div id="selected-strip" class="selected-strip" hidden></div>

        <div class="search-box">
          <span class="search-box__icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
          </span>
          <input class="input" id="product-search" placeholder="Wyszukaj po nazwie, kategorii lub SKU…"/>
        </div>

        <div class="product-list" id="product-list"></div>
      </section>

      <!-- ============== PODGLĄD ============== -->
      <section class="step step--wide" data-screen-idx="18" hidden id="summary">
        <header>
          <div class="step__eyebrow">Podgląd · ostatni krok</div>
          <h1 class="step__title">Wszystko gotowe?</h1>
          <p class="step__lede">Sprawdź podsumowanie i kliknij „Opublikuj", aby zapisać przepis. Każdą sekcję możesz jeszcze edytować — kliknij „Edytuj".</p>
        </header>

        <div id="summary-body"></div>
      </section>

      <!-- ============== DONE ============== -->
      <section class="step step--center center" data-screen-idx="19" hidden>
        <div class="done-art">
          <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.4">
            <path d="M5 12c3 4 4 6 5 8 2-7 5-12 10-15"/>
          </svg>
        </div>
        <div class="step__eyebrow" style="text-align:center;">Gotowe</div>
        <h1 class="step__title" style="text-align:center;">Przepis został opublikowany</h1>
        <p class="step__lede" style="text-align:center; margin-inline:auto;">
          Twój przepis pojawi się na hutting.pl w ciągu kilku minut. Możesz go edytować w panelu w każdej chwili.
        </p>
        <div style="display:flex; justify-content:center; gap:10px; margin-top:8px;">
          <a href="#" class="btn-next" id="done-view-post" hidden>Zobacz wpis</a>
          <button type="button" class="btn-pill" data-action="restart">Dodaj kolejny przepis</button>
        </div>
      </section>

      <!-- ============== BRAK UPRAWNIEŃ ==============
           Pokazywany przez window.showPermissionGate(true) lub URL ?gate=1.
           Zastępuje cały formularz — chowamy stepy, postęp, indicator. -->
      <section id="permission-gate" class="permission-gate" hidden>
        <div class="permission-gate__icon" aria-hidden="true">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <rect x="5" y="11" width="14" height="9" rx="1.5"/>
            <path d="M8 11V8a4 4 0 0 1 8 0v3"/>
            <circle cx="12" cy="15.5" r="1.2" fill="currentColor" stroke="none"/>
          </svg>
        </div>
        <div class="step__eyebrow">Brak uprawnień</div>
        <h1 class="step__title step__title--sm">Nie masz dostępu do dodawania przepisów</h1>
        <p class="step__lede">
          Ten formularz jest dostępny tylko dla redaktorów i wybranych autorów hutting.pl.
          Jeśli uważasz, że powinieneś mieć tu dostęp — napisz do redakcji, a nadamy Ci odpowiednią rolę.
        </p>
        <ul class="permission-gate__meta">
          <li>
            <span class="permission-gate__meta-label">Wymagana rola</span>
            <span class="permission-gate__meta-val"><code>editor</code> lub <code>author</code></span>
          </li>
          <li>
            <span class="permission-gate__meta-label">Twoje konto</span>
            <span class="permission-gate__meta-val" id="permission-gate__email">—</span>
          </li>
        </ul>
        <div class="permission-gate__actions">
          <a class="btn-next" href="https://hutting.pl">Wróć na stronę główną</a>
          <a class="btn-pill" href="mailto:redakcja@hutting.pl?subject=Pro%C5%9Bba%20o%20dost%C4%99p%20do%20kreatora%20przepis%C3%B3w">Napisz do redakcji</a>
        </div>
      </section>
    </main>

    <!-- ============ FOOTER ============ -->
    <footer class="footer" id="footer">
      <div class="progress" id="progress" aria-label="Postęp"></div>
      <div class="footer__nav">
        <button type="button" class="btn-back" id="btn-back" disabled>← Wstecz</button>
        <div style="display:flex; align-items:center; gap:16px;">
          <span id="error-msg" hidden style="font:500 13px var(--sans); color:var(--danger); background:oklch(0.96 0.04 25); padding:8px 12px; border-radius:10px; border:1px solid oklch(0.85 0.08 25);"></span>
          <button type="button" class="btn-next" id="btn-next">Dalej</button>
        </div>
      </div>
    </footer>
  </div>

  <!-- ============ TEMPLATES ============ -->
  <template id="tpl-ingredient">
    <div class="rep-card">
      <div class="rep-card__head">
        <div class="rep-card__idx"><span class="rep-card__num"></span>Składnik</div>
        <div class="rep-card__tools">
          <button type="button" class="icon-btn" data-action="up" title="Wyżej" aria-label="Wyżej">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 14 6-6 6 6"/></svg>
          </button>
          <button type="button" class="icon-btn" data-action="down" title="Niżej" aria-label="Niżej">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 10 6 6 6-6"/></svg>
          </button>
          <button type="button" class="icon-btn icon-btn--danger" data-action="remove" title="Usuń" aria-label="Usuń">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></svg>
          </button>
        </div>
      </div>
      <div class="ing-grid">
        <div class="ac">
          <input class="input" data-field="name" placeholder="Nazwa składnika (np. brokuł)" autocomplete="off"/>
          <div class="ac__list" hidden></div>
        </div>
        <input class="input" inputmode="decimal" data-field="amount" placeholder="Ilość"/>
        <input class="input" type="text" data-field="unit" list="unit-suggestions" placeholder="Jednostka"/>
      </div>
      <div class="ing-hint">
        <input class="input" data-field="hint" placeholder='Wskazówka (opcjonalnie) — np. „drobno posiekany", „świeży", „bez skórki"'/>
      </div>
    </div>
  </template>

  <template id="tpl-prep">
    <div class="rep-card" style="padding:20px;">
      <div class="rep-card__head">
        <div class="rep-card__idx"><span class="rep-card__num"></span>Krok przygotowania</div>
        <div class="rep-card__tools">
          <button type="button" class="icon-btn" data-action="up" title="Wyżej" aria-label="Wyżej">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 14 6-6 6 6"/></svg>
          </button>
          <button type="button" class="icon-btn" data-action="down" title="Niżej" aria-label="Niżej">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 10 6 6 6-6"/></svg>
          </button>
          <button type="button" class="icon-btn icon-btn--danger" data-action="remove" title="Usuń" aria-label="Usuń">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></svg>
          </button>
        </div>
      </div>
      <div style="display:grid; grid-template-columns:1.6fr 1fr; gap:16px;">
        <div class="stack" style="display:flex; flex-direction:column; gap:14px;">
          <input class="input" data-field="heading" placeholder='Nagłówek kroku — np. „Podsmaż por i czosnek"' style="font-family:var(--serif); font-size:18px; font-weight:600; padding:14px 16px;"/>
          <textarea class="textarea" data-field="body" placeholder='Opis kroku — co dokładnie należy zrobić. Pełne zdania, w 2. os. l. mn. (np. „Pokrójcie por w cienkie krążki…")'></textarea>
          <input class="input" data-field="hint" placeholder='Wskazówka (opcjonalnie) — np. „Nie rumień zbyt mocno, ma być szklisty"'/>
        </div>
        <div>
          <div class="time-card__optional" style="margin-bottom:6px;">Zdjęcie kroku · opcjonalnie</div>
          <div data-field="photo-slot"></div>
        </div>
      </div>
    </div>
  </template>

  <!-- Modal helper -->
  <div id="modal-root" hidden></div>

  <!-- App scripts -->
