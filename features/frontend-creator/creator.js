// hutting · Kreator przepisu — wersja vanilla.
// Cały stan w jednym obiekcie, ręczny rendering, delegacja eventów. Bez build stepu, bez frameworków.

(function () {
  'use strict';

  // ============================================================
  // SCREENS  —  linear flow: intro 1 → form 1 (podstrony) → intro 2 → form 2 → ... → review → done
  // Każdy screen typu 'form' może mieć własną funkcję validate(data) → null | error message.
  // ============================================================
  const SCREENS = [
    { kind:'intro', step:0 },  // 0
    // Krok 1 podzielony na 7 podstron: tytuł, wstęp, featured image, trudność, porcje, czas, termy
    { kind:'form',  step:0, validate: d => (!d.title || d.title.trim().length < 3) ? 'Tytuł musi mieć co najmniej 3 znaki.' : null }, // 1
    { kind:'form',  step:0, validate: d => (!d.lead || d.lead.trim().length < 20) ? 'Wstęp musi mieć co najmniej 20 znaków.' : null }, // 2
    { kind:'form',  step:0 }, // 3  (featured image — opcjonalnie)
    { kind:'form',  step:0, validate: d => !d.difficulty ? 'Wybierz poziom trudności.' : null },                               // 4
    { kind:'form',  step:0, validate: d => (!d.portions || d.portions < 1) ? 'Podaj liczbę porcji.' : null },                   // 5
    { kind:'form',  step:0, validate: d => !(d.timePrep > 0 || d.timeCook > 0) ? 'Podaj przynajmniej czas przygotowania lub gotowania.' : null }, // 6
    { kind:'form',  step:0, validate: d => {
      if (!Array.isArray(window.MEAL_TYPE_TAXONOMY) || window.MEAL_TYPE_TAXONOMY.length === 0) {
        return 'Brak termów w taksonomii Typy dania. Dodaj termy i spróbuj ponownie.';
      }
      if (!Array.isArray(window.PREP_TIME_TAXONOMY) || window.PREP_TIME_TAXONOMY.length === 0) {
        return 'Brak termów w taksonomii Czas przygotowania. Dodaj termy i spróbuj ponownie.';
      }
      if (!Array.isArray(d.mealTypeTermIds) || d.mealTypeTermIds.length !== 1) {
        return 'Wybierz dokładnie jeden typ dania.';
      }
      if (!Array.isArray(d.prepTimeTermIds) || d.prepTimeTermIds.length !== 1) {
        return 'Wybierz dokładnie jeden zakres czasu przygotowania.';
      }
      return null;
    } }, // 7
    { kind:'intro', step:1 },  // 8
    // Krok 2 podzielony na 2 podstrony: zdjęcie (opcjonalne), lista
    { kind:'form',  step:1 },  // 9  (zdjęcie składników — opcjonalne)
    { kind:'form',  step:1, validate: d => d.ingredients.some(r => r.name && r.amount) ? null : 'Dodaj co najmniej jeden składnik z ilością.' },  // 10
    { kind:'intro', step:2 },  // 11
    { kind:'form',  step:2, validate: d => d.steps.some(r => (r.heading||'').trim() && (r.body||'').trim()) ? null : 'Dodaj co najmniej jeden krok przygotowania (nagłówek + opis).' }, // 12
    { kind:'intro', step:3 },  // 13
    { kind:'form',  step:3 },  // 14  (opcjonalne)
    { kind:'intro', step:4 },  // 15
    { kind:'form',  step:4 },  // 16  (opcjonalne)
    { kind:'intro', step:5 },  // 17
    { kind:'form',  step:5 },  // 18  (opcjonalne)
    { kind:'review'         }, // 19
    { kind:'done'           }, // 20
  ];
  const TOTAL_FORM_STEPS = 6;
  const STEP_TITLES = ['Podstawy', 'Składniki', 'Przygotowanie', 'Wskazówki', 'Źródło', 'Promowane produkty'];
  const formScreenForStep = step => SCREENS.findIndex(s => s.kind === 'form' && s.step === step);

  function clamp01(value) {
    if (value < 0) return 0;
    if (value > 1) return 1;
    return value;
  }

  function getFormSubstepTotal(step) {
    return SCREENS.filter(screen => screen.kind === 'form' && screen.step === step).length;
  }

  function getCurrentFormSubstepPosition(screenIdx) {
    const currentScreen = SCREENS[screenIdx];
    if (!currentScreen || currentScreen.kind !== 'form') return 0;

    let position = 0;
    for (let i = 0; i <= screenIdx; i += 1) {
      const screen = SCREENS[i];
      if (screen.kind === 'form' && screen.step === currentScreen.step) {
        position += 1;
      }
    }

    return position;
  }

  function getActiveStepFillScale(screenIdx) {
    const screen = SCREENS[screenIdx];
    if (!screen) return 0;

    if (screen.kind !== 'intro' && screen.kind !== 'form') {
      return 1;
    }

    const step = Number(screen.step);
    if (!Number.isInteger(step) || step < 0) {
      return 0;
    }

    if (screen.kind === 'intro') {
      return 0;
    }

    const totalSubsteps = getFormSubstepTotal(step);
    if (totalSubsteps <= 0) {
      return 0;
    }

    const currentPosition = getCurrentFormSubstepPosition(screenIdx);
    if (currentPosition <= 0) {
      return 0;
    }

    return clamp01(currentPosition / totalSubsteps);
  }

  // ============================================================
  // STATE
  // ============================================================
  const state = {
    screenIdx: 0,
    isSubmitting: false,
    activeUploads: 0,
    data: {
      title: '',
      lead: '',
      image: null,
      difficulty: '',
      portions: 4,
      portionUnit: 'osób',
      timePrep: 0, timeCook: 0, timeSoak: 0,
      mealTypeTermIds: [],
      prepTimeTermIds: [],
      featureTermIds: [],
      ingredientsPhoto: null,
      ingredients: [mkIng()],
      steps: [mkPrep()],
      tips: '',
      sourceName: '', sourceUrl: '',
      products: [],
    },
    productQuery: '',
  };

  const creatorData = getCreatorData();
  const isHeartbeatEnabled = !!creatorData.heartbeatEnabled;
  let autosavePostId = Number(creatorData.editPostId || 0) || 0;
  let productSourceMode = 'local';
  let algoliaIndex = null;
  let productSearchTimer = null;
  let productRequestSeq = 0;
  let visibleProducts = [];

  function mkIng()  { return { id: rid('ing'),  name:'', termId:null, amount:'', unit:'', hint:'' }; }
  function mkPrep() { return { id: rid('prep'), heading:'', body:'', hint:'', photo:null }; }
  function rid(p)   { return p + '-' + Math.random().toString(36).slice(2, 9); }

  function getCreatorData() {
    return window.G4T_RECIPE_CREATOR_DATA || {};
  }

  function setPreviewButtonState(postId) {
    const previewBtn = document.getElementById('preview-btn');
    const frontLink  = document.getElementById('preview-front');
    if (!previewBtn) return;

    const id = Number(postId || 0);
    if (id > 0) {
      previewBtn.removeAttribute('aria-disabled');
      previewBtn.style.pointerEvents = '';
      previewBtn.style.opacity = '';
      if (frontLink) frontLink.href = `/?post_type=recipe&p=${id}&preview=true`;
      return;
    }

    previewBtn.setAttribute('aria-disabled', 'true');
    previewBtn.style.pointerEvents = 'none';
    previewBtn.style.opacity = '0.4';
    if (frontLink) frontLink.href = '#';
  }

  function initPreviewDropdown() {
    const btn  = document.getElementById('preview-btn');
    const menu = document.getElementById('preview-dropdown-menu');
    const formLink = document.getElementById('preview-form');
    if (!btn || !menu) return;

    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = !menu.hidden;
      menu.hidden = isOpen;
      btn.setAttribute('aria-expanded', String(!isOpen));
    });

    if (formLink) {
      formLink.addEventListener('click', (e) => {
        e.preventDefault();
        menu.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
        const reviewIdx = SCREENS.findIndex(s => s.kind === 'review');
        if (reviewIdx >= 0) showScreen(reviewIdx);
      });
    }

    document.addEventListener('click', (e) => {
      if (!menu.hidden && !document.getElementById('preview-dropdown').contains(e.target)) {
        menu.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !menu.hidden) {
        menu.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
        btn.focus();
      }
    });
  }

  function normalizeDifficultyKey(value) {
    const raw = String(value || '').trim().toLowerCase();
    if (!raw) return '';
    const ascii = raw.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

    if (raw === 'easy' || ascii.includes('latw') || ascii.includes('easy')) return 'easy';
    if (raw === 'medium' || ascii.includes('sred') || ascii.includes('medium')) return 'medium';
    if (raw === 'hard' || ascii.includes('trud') || ascii.includes('hard')) return 'hard';

    return raw;
  }

  function getPhotoUrl(photo) {
    if (!photo) return '';
    if (typeof photo === 'string') return photo;
    if (typeof photo === 'object' && typeof photo.url === 'string') return photo.url;
    return '';
  }

  function getPhotoId(photo) {
    if (!photo || typeof photo !== 'object') return '';
    const id = Number(photo.id || 0);
    if (id > 0) return String(id);
    return typeof photo.url === 'string' ? photo.url : '';
  }

  function isUploadingPhoto(photo) {
    return !!(photo && typeof photo === 'object' && photo.isUploading);
  }

  function isPhotoBusy() {
    return state.activeUploads > 0;
  }

  function createImagePreview(url, alt) {
    const img = document.createElement('img');
    img.src = url;
    img.alt = alt;
    img.style.width = '100%';
    img.style.height = '100%';
    img.style.objectFit = 'cover';
    img.style.display = 'block';
    return img;
  }

  function createHiddenImageInput(onSelect) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/png,image/jpeg,image/webp,image/avif';
    input.hidden = true;
    input.addEventListener('change', () => {
      const file = input.files && input.files[0];
      if (file) onSelect(file);
      input.remove();
    });
    document.body.appendChild(input);
    input.click();
  }

  async function uploadImageFile(file) {
    const creatorData = getCreatorData();
    if (!creatorData.ajaxUrl || !creatorData.uploadNonce) {
      throw new Error('Brakuje konfiguracji uploadu zdjęć.');
    }

    const formData = new FormData();
    formData.append('action', 'g4t_upload_image');
    formData.append('nonce', creatorData.uploadNonce);
    formData.append('file', file);

    const response = await fetch(creatorData.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    });

    const data = await response.json();
    if (!response.ok || !data.success || !data.data || !data.data.url) {
      throw new Error(data?.data?.message || 'Nie udało się wysłać zdjęcia.');
    }

    return {
      id: Number(data.data.id || 0),
      url: String(data.data.url || ''),
    };
  }

  function bindUploadSurface(element, onFile) {
    element.addEventListener('click', () => createHiddenImageInput(onFile));
    element.addEventListener('dragover', (event) => {
      event.preventDefault();
      element.classList.add('is-dragover');
    });
    element.addEventListener('dragleave', () => {
      element.classList.remove('is-dragover');
    });
    element.addEventListener('drop', (event) => {
      event.preventDefault();
      element.classList.remove('is-dragover');
      const file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];
      if (file) onFile(file);
    });
  }

  async function uploadIngredientsPhoto(file) {
    state.activeUploads += 1;
    state.data.ingredientsPhoto = { isUploading: true };
    renderIngredientsPhotoSlot();

    try {
      const uploaded = await uploadImageFile(file);
      state.data.ingredientsPhoto = uploaded;
      afterChange();
    } catch (error) {
      state.data.ingredientsPhoto = null;
      showError(error.message || 'Nie udało się wysłać zdjęcia składników.');
    } finally {
      state.activeUploads = Math.max(0, state.activeUploads - 1);
      renderIngredientsPhotoSlot();
    }
  }

  async function uploadFeaturedImage(file) {
    state.activeUploads += 1;
    state.data.image = { isUploading: true };
    renderFeaturedImageSlot();

    try {
      const uploaded = await uploadImageFile(file);
      state.data.image = uploaded;
      afterChange();
    } catch (error) {
      state.data.image = null;
      showError(error.message || 'Nie udało się wysłać głównego zdjęcia przepisu.');
    } finally {
      state.activeUploads = Math.max(0, state.activeUploads - 1);
      renderFeaturedImageSlot();
    }
  }

  async function uploadStepPhoto(row, slot, idx, file) {
    state.activeUploads += 1;
    row.photo = { isUploading: true };
    renderPrepPhoto(slot, row, idx);

    try {
      const uploaded = await uploadImageFile(file);
      row.photo = uploaded;
      afterChange();
    } catch (error) {
      row.photo = null;
      showError(error.message || 'Nie udało się wysłać zdjęcia kroku.');
    } finally {
      state.activeUploads = Math.max(0, state.activeUploads - 1);
      renderPrepPhoto(slot, row, idx);
    }
  }

  // ============================================================
  // INIT
  // ============================================================
  document.addEventListener('DOMContentLoaded', init);

  function init() {
    applyInitialStateFromServer();

    // Datalists
    fillDatalist('#portion-unit-suggestions', window.PORTION_UNIT_SUGGESTIONS);
    fillDatalist('#unit-suggestions', window.UNIT_SUGGESTIONS);

    // Progress bar segments
    const progress = document.getElementById('progress');
    progress.style.setProperty('--progress-steps', String(TOTAL_FORM_STEPS));
    for (let i = 0; i < TOTAL_FORM_STEPS; i++) {
      const seg = document.createElement('div');
      seg.className = 'progress__seg';
      progress.appendChild(seg);
    }

    // Initial DOM values
    document.getElementById('portions-val').textContent = state.data.portions;

    paintIntroImages();
    bindAllBindings();
    setupCounters();
    setupTimeInputs();
    setupTaxonomySelectors();
    setupDifficultyChoices();
    setupPortionStepper();
    setupIngredientsPhotoSlot();
    setupFeaturedImageSlot();
    setupProductSearch();
    renderIngredients();
    renderPrepSteps();
    renderProducts();

    // Navigation
    document.getElementById('btn-back').addEventListener('click', () => showScreen(state.screenIdx - 1));
    document.getElementById('btn-next').addEventListener('click', onNext);

    // Modals + global delegated actions
    document.body.addEventListener('click', onDelegatedClick);

    setPreviewButtonState(autosavePostId);
    initPreviewDropdown();
    showScreen(0);
    maybeShowPublishedDoneScreen();
    setupHeartbeatAutosave();

    // ----------------------------------------------------------
    // Permission gate
    //   window.showPermissionGate(true [, { email }])  — zablokuj kreator
    //   window.showPermissionGate(false)               — odblokuj
    //   ?gate=1 w URL                                  — natychmiastowy podgląd
    // ----------------------------------------------------------
    window.showPermissionGate = function(show = true, opts = {}) {
      const gate = document.getElementById('permission-gate');
      const topbar = document.querySelector('.topbar');
      const footer = document.getElementById('footer');
      const indicator = document.getElementById('step-indicator');

      if (!gate) {
        return;
      }

      if (opts.email) {
        const emailEl = document.getElementById('permission-gate__email');
        if (emailEl) {
          emailEl.textContent = opts.email;
        }
      }

      if (show) {
        // Schowaj wszystkie ekrany formularza i nawigację
        document.querySelectorAll('[data-screen-idx]').forEach(el => el.hidden = true);
        if (footer) footer.style.display = 'none';
        if (indicator) indicator.hidden = true;
        // Ukryj też przyciski formularza w topbarze (Masz pytania, Zapisz i wyjdź)
        topbar?.querySelectorAll('[data-modal]').forEach(b => b.hidden = true);
        gate.hidden = false;
      } else {
        gate.hidden = true;
        if (footer) footer.style.display = '';
        if (indicator) indicator.hidden = false;
        topbar?.querySelectorAll('[data-modal]').forEach(b => b.hidden = false);
        showScreen(state.screenIdx);
      }
    };

    const wrapper = document.querySelector('.g4t-recipe-creator-wrapper');
    const forceGate = wrapper && wrapper.dataset && wrapper.dataset.g4tForceGate === '1';
    const gateEmail = wrapper && wrapper.dataset ? wrapper.dataset.g4tUserEmail || '' : '';
    const hasGateParam = new URLSearchParams(location.search).has('gate') || location.hash === '#gate';
    if (forceGate || hasGateParam) {
      const opts = gateEmail ? { email: gateEmail } : {};
      window.showPermissionGate(true, opts);
    }
  }

  function maybeShowPublishedDoneScreen() {
    const params = new URLSearchParams(window.location.search);
    const saved = params.get('saved');
    const status = params.get('status');
    if (saved !== '1' || status !== 'published') {
      return;
    }

    bindDoneViewPostLink();
    applyDoneScreenCopy();
    showScreen(20);

    function applyDoneScreenCopy() {
      if (!isEditMode) return;
      const titleEl = document.querySelector('[data-screen-idx="20"] .step__title');
      const ledeEl  = document.querySelector('[data-screen-idx="20"] .step__lede');
      if (titleEl) titleEl.textContent = 'Przepis został zaktualizowany';
      if (ledeEl)  ledeEl.textContent  = 'Zmiany zostały zapisane. Możesz wrócić do edycji lub zobaczyć przepis.';
    }
  }

  function bindDoneViewPostLink() {
    const doneLink = document.getElementById('done-view-post');
    if (!doneLink) {
      return;
    }

    const contextNode = document.querySelector('.g4t-recipe-creator-context');
    const viewUrl = contextNode && contextNode.dataset ? (contextNode.dataset.g4tPublishedPostUrl || '') : '';
    if (!viewUrl) {
      doneLink.hidden = true;
      doneLink.setAttribute('href', '#');
      return;
    }

    doneLink.setAttribute('href', viewUrl);
    doneLink.hidden = false;
  }

  // Maluje gradient (striped) w obrazkach na stronach intro
  function paintIntroImages() {
    document.querySelectorAll('[data-tint]').forEach(div => {
      const i = +div.dataset.tint;
      const t = window.PLACEHOLDER_TINTS[i % window.PLACEHOLDER_TINTS.length];
      div.style.background = `repeating-linear-gradient(135deg, ${t[0]} 0 24px, ${t[1]} 24px 48px)`;
    });
  }

  // ============================================================
  // BINDINGS  —  każdy [data-bind="key"] aktualizuje state.data[key]
  // ============================================================
  function bindAllBindings() {
    document.querySelectorAll('[data-bind]').forEach(el => {
      const key = el.dataset.bind;
      el.value = state.data[key] ?? '';
      el.addEventListener('input', () => {
        state.data[key] = el.value;
        afterChange();
      });
    });
  }

  function applyInitialStateFromServer() {
    const creatorData = getCreatorData();
    if (!creatorData || !creatorData.initialState || typeof creatorData.initialState !== 'object') {
      return;
    }

    const initial = creatorData.initialState;
    const asText = (value, fallback = '') => (typeof value === 'string' ? value : fallback);
    const asNumber = (value, fallback = 0) => {
      const num = Number(value);
      return Number.isFinite(num) ? num : fallback;
    };
    const asIntArray = (value) => {
      if (!Array.isArray(value)) return [];
      return [...new Set(value
        .map((item) => Number(item))
        .filter((num) => Number.isInteger(num) && num > 0))];
    };

    const normalizedProducts = Array.isArray(initial.products)
      ? initial.products
          .map((item) => {
            const id = asNumber(item && item.id, 0);
            if (!id) return null;
            const match = Array.isArray(window.PRODUCTS) ? window.PRODUCTS.find((p) => Number(p.id) === id) : null;
            return match || { id, title: `Produkt #${id}`, cat: '', sku: '', price: '' };
          })
          .filter(Boolean)
      : [];

    state.data = {
      ...state.data,
      title: asText(initial.title, state.data.title),
      lead: asText(initial.lead, state.data.lead),
      image: initial.image || null,
      difficulty: normalizeDifficultyKey(asText(initial.difficulty, state.data.difficulty)),
      portions: Math.max(1, asNumber(initial.portions, state.data.portions)),
      portionUnit: asText(initial.portionUnit, state.data.portionUnit),
      timePrep: Math.max(0, asNumber(initial.timePrep, state.data.timePrep)),
      timeCook: Math.max(0, asNumber(initial.timeCook, state.data.timeCook)),
      timeSoak: Math.max(0, asNumber(initial.timeSoak, state.data.timeSoak)),
      mealTypeTermIds: asIntArray(initial.mealTypeTermIds),
      prepTimeTermIds: asIntArray(initial.prepTimeTermIds),
      featureTermIds: asIntArray(initial.featureTermIds),
      ingredientsPhoto: initial.ingredientsPhoto || null,
      ingredients: Array.isArray(initial.ingredients) && initial.ingredients.length > 0
        ? initial.ingredients.map((row, idx) => ({
            id: `ing-${idx}-${Math.random().toString(36).slice(2, 6)}`,
            name: asText(row && row.name),
            termId: (row && row.termId !== undefined && row.termId !== null && String(row.termId) !== '') ? Number(row.termId) : null,
            amount: asText(row && row.amount),
            unit: asText(row && row.unit),
            hint: asText(row && row.hint),
          }))
        : [mkIng()],
      steps: Array.isArray(initial.steps) && initial.steps.length > 0
        ? initial.steps.map((row, idx) => ({
            id: `prep-${idx}-${Math.random().toString(36).slice(2, 6)}`,
            heading: asText(row && row.heading),
            body: asText(row && row.body),
            hint: asText(row && row.hint),
            photo: row && row.photo ? row.photo : null,
          }))
        : [mkPrep()],
      tips: asText(initial.tips, state.data.tips),
      sourceName: asText(initial.sourceName, state.data.sourceName),
      sourceUrl: asText(initial.sourceUrl, state.data.sourceUrl),
      products: normalizedProducts,
    };
  }

  function setupCounters() {
    bindCounter('title', 'title-count');
    bindCounter('lead', 'lead-count');
    bindCounter(document.querySelector('[data-bind="tips"]'), 'tips-count');
  }
  function bindCounter(srcOrId, targetId) {
    const src = typeof srcOrId === 'string' ? document.getElementById(srcOrId) : srcOrId;
    const tgt = document.getElementById(targetId);
    const upd = () => tgt.textContent = src.value.length;
    src.addEventListener('input', upd);
    upd();
  }

  // ============================================================
  // DIFFICULTY  —  3 buttons, single-choice
  // ============================================================
  function setupDifficultyChoices() {
    const buttons = document.querySelectorAll('#difficulty-row .choice');
    buttons.forEach(btn => {
      btn.addEventListener('click', () => {
        state.data.difficulty = btn.dataset.difficulty;
        buttons.forEach(b => b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'));
        afterChange();
      });
    });

    buttons.forEach((b) => b.setAttribute('aria-pressed', b.dataset.difficulty === state.data.difficulty ? 'true' : 'false'));
  }

  // ============================================================
  // PORTIONS STEPPER
  // ============================================================
  function setupPortionStepper() {
    document.querySelectorAll('[data-portion-delta]').forEach(btn => {
      btn.addEventListener('click', () => {
        const delta = parseInt(btn.dataset.portionDelta, 10);
        state.data.portions = Math.max(1, Math.min(99, state.data.portions + delta));
        document.getElementById('portions-val').textContent = state.data.portions;
        afterChange();
      });
    });
  }

  // ============================================================
  // TIME INPUTS  —  trzy karty + automatyczna suma
  // ============================================================
  function setupTimeInputs() {
    ['timePrep', 'timeCook', 'timeSoak'].forEach(key => {
      const hInput = document.querySelector(`[data-time-h="${key}"]`);
      const mInput = document.querySelector(`[data-time-m="${key}"]`);
      if (!hInput || !mInput) return;
      const total = Number(state.data[key] || 0);
      hInput.value = Math.floor(total / 60);
      mInput.value = total % 60;
    });

    document.querySelectorAll('[data-time-h], [data-time-m]').forEach(input => {
      input.addEventListener('input', () => {
        ['timePrep', 'timeCook', 'timeSoak'].forEach(key => {
          const h = clamp(+document.querySelector(`[data-time-h="${key}"]`).value || 0, 0, 48);
          const m = clamp(+document.querySelector(`[data-time-m="${key}"]`).value || 0, 0, 59);
          state.data[key] = h * 60 + m;
        });
        updateTotalTime();
        afterChange();
      });
    });
    updateTotalTime();
  }
  function updateTotalTime() {
    const t = state.data.timePrep + state.data.timeCook + state.data.timeSoak;
    document.getElementById('total-h').value = Math.floor(t / 60);
    document.getElementById('total-m').value = t % 60;
  }
  function clamp(v, lo, hi) { return Math.max(lo, Math.min(hi, v)); }

  // ============================================================
  // TAXONOMY SELECTORS  —  Step 1.7
  // ============================================================
  function setupTaxonomySelectors() {
    renderTaxonomySelectors();
  }

  function renderTaxonomySelectors() {
    renderTaxonomyGroup({
      terms: Array.isArray(window.MEAL_TYPE_TAXONOMY) ? window.MEAL_TYPE_TAXONOMY : [],
      selectedIds: state.data.mealTypeTermIds,
      emptyId: 'taxonomy-mealtype-empty',
      gridId: 'taxonomy-mealtype-grid',
      single: true,
      iconKind: 'meal',
      onChange: (next) => {
        state.data.mealTypeTermIds = next;
        afterChange();
      },
    });

    renderTaxonomyGroup({
      terms: Array.isArray(window.PREP_TIME_TAXONOMY) ? window.PREP_TIME_TAXONOMY : [],
      selectedIds: state.data.prepTimeTermIds,
      emptyId: 'taxonomy-preptime-empty',
      gridId: 'taxonomy-preptime-grid',
      single: true,
      iconKind: 'time',
      onChange: (next) => {
        state.data.prepTimeTermIds = next;
        afterChange();
      },
    });

    renderTaxonomyGroup({
      terms: Array.isArray(window.FEATURE_TAXONOMY) ? window.FEATURE_TAXONOMY : [],
      selectedIds: state.data.featureTermIds,
      emptyId: 'taxonomy-feature-empty',
      gridId: 'taxonomy-feature-grid',
      single: false,
      iconKind: 'feature',
      onChange: (next) => {
        state.data.featureTermIds = next;
        afterChange();
      },
    });
  }

  function renderTaxonomyGroup({ terms, selectedIds, emptyId, gridId, single, iconKind, onChange }) {
    const emptyNode = document.getElementById(emptyId);
    const gridNode = document.getElementById(gridId);
    if (!emptyNode || !gridNode) return;

    const normalizedSelected = Array.isArray(selectedIds)
      ? selectedIds
          .map((id) => Number(id))
          .filter((id) => Number.isInteger(id) && id > 0)
      : [];

    if (!Array.isArray(terms) || terms.length === 0) {
      emptyNode.hidden = false;
      gridNode.hidden = true;
      gridNode.innerHTML = '';
      return;
    }

    emptyNode.hidden = true;
    gridNode.hidden = false;
    gridNode.innerHTML = '';

    terms.forEach((term) => {
      const termId = Number(term && term.termId);
      if (!Number.isInteger(termId) || termId <= 0) return;

      const selected = normalizedSelected.includes(termId);
      const button = document.createElement('button');
      button.type = 'button';
      button.className = `taxonomy-choice${selected ? ' is-selected' : ''}`;
      button.setAttribute('aria-pressed', selected ? 'true' : 'false');

      const label = term && term.name ? String(term.name) : `Term #${termId}`;
      const iconHtml = getTaxonomyIconHtml(term && term.heroIcon ? term.heroIcon : null, iconKind, label);
      button.innerHTML = `<span class="taxonomy-choice__icon">${iconHtml}</span><span class="taxonomy-choice__label">${escapeHtml(label)}</span>`;

      button.addEventListener('click', () => {
        let next = [];
        if (single) {
          next = selected ? [] : [termId];
        } else {
          next = selected
            ? normalizedSelected.filter((id) => id !== termId)
            : [...normalizedSelected, termId];
        }
        onChange(next);
        renderTaxonomySelectors();
      });

      gridNode.appendChild(button);
    });
  }

  function getTaxonomyIconHtml(heroIcon, iconKind, fallbackAlt) {
    if (heroIcon && typeof heroIcon === 'object' && typeof heroIcon.url === 'string' && heroIcon.url.trim() !== '') {
      const alt = typeof heroIcon.alt === 'string' && heroIcon.alt.trim() !== '' ? heroIcon.alt : fallbackAlt;
      return `<img src="${escapeHtml(heroIcon.url)}" alt="${escapeHtml(alt)}" loading="lazy"/>`;
    }

    if (iconKind === 'meal') {
      return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 5v8M7 5v8M4 9h3M11 5v14M16 7h4v12M16 12h4"/></svg>';
    }
    if (iconKind === 'time') {
      return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l3 2M8 3h8"/></svg>';
    }

    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.2 6.4 20.2l1.1-6.2L3 9.6l6.2-.9Z"/></svg>';
  }

  // ============================================================
  // INGREDIENTS PHOTO  —  toggleable placeholder dropzone
  // ============================================================
  function setupIngredientsPhotoSlot() {
    renderIngredientsPhotoSlot();
  }
  function renderIngredientsPhotoSlot() {
    const slot = document.getElementById('ingredients-photo-slot');
    slot.innerHTML = '';
    if (isUploadingPhoto(state.data.ingredientsPhoto)) {
      const loading = emptyDropzone(null, 'Wysyłanie zdjęcia...', 'To może potrwać kilka sekund.');
      loading.classList.add('is-busy');
      slot.appendChild(loading);
      return;
    }

    const currentUrl = getPhotoUrl(state.data.ingredientsPhoto);
    if (!currentUrl) {
      const empty = emptyDropzone((file) => uploadIngredientsPhoto(file));
      slot.appendChild(empty);
    } else {
      slot.appendChild(filledDropzone(currentUrl, 'Zdjęcie składników', () => {
        state.data.ingredientsPhoto = null;
        renderIngredientsPhotoSlot();
        afterChange();
      }, (file) => uploadIngredientsPhoto(file)));
    }
  }

  // ============================================================
  // FEATURED IMAGE  —  toggleable placeholder dropzone
  // ============================================================
  function setupFeaturedImageSlot() {
    renderFeaturedImageSlot();
  }
  function renderFeaturedImageSlot() {
    const slot = document.getElementById('featured-image-slot');
    slot.innerHTML = '';
    if (isUploadingPhoto(state.data.image)) {
      const loading = emptyDropzone(null, 'Wysyłanie zdjęcia...', 'To może potrwać kilka sekund.');
      loading.classList.add('is-busy');
      slot.appendChild(loading);
      return;
    }

    const currentUrl = getPhotoUrl(state.data.image);
    if (!currentUrl) {
      const empty = emptyDropzone((file) => uploadFeaturedImage(file));
      slot.appendChild(empty);
    } else {
      slot.appendChild(filledDropzone(currentUrl, 'Główne zdjęcie przepisu', () => {
        state.data.image = null;
        renderFeaturedImageSlot();
        afterChange();
      }, (file) => uploadFeaturedImage(file)));
    }
  }

  function emptyDropzone(onSelect, title = 'Przeciągnij zdjęcie lub kliknij, aby wybrać', subtitle = 'JPG / PNG / WEBP / AVIF, do 8 MB') {
    const dz = document.createElement('div');
    dz.className = 'dropzone';
    if (onSelect) {
      dz.setAttribute('role', 'button');
      dz.tabIndex = 0;
      bindUploadSurface(dz, onSelect);
    }
    dz.innerHTML = `
      <div class="dropzone__art">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
          <path d="M4 8h3l2-3h6l2 3h3v11H4z"/>
          <circle cx="12" cy="13" r="3.5"/>
        </svg>
      </div>
      <div>
        <div class="dropzone__title">${escapeHtml(title)}</div>
        <div class="dropzone__sub">${escapeHtml(subtitle)}</div>
      </div>
      ${onSelect ? '<button type="button" class="dropzone__cta">Wybierz plik</button>' : ''}
    `;
    return dz;
  }

  function filledDropzone(url, label, onRemove, onReplace) {
    const dz = document.createElement('div');
    dz.className = 'dropzone dropzone--filled';
    dz.appendChild(createImagePreview(url, label));
    const x = document.createElement('button');
    x.type = 'button';
    x.className = 'dropzone__remove';
    x.textContent = 'Usuń';
    x.addEventListener('click', (e) => { e.stopPropagation(); onRemove(); });
    dz.appendChild(x);
    if (onReplace) {
      bindUploadSurface(dz, onReplace);
      x.addEventListener('click', (e) => e.stopPropagation());
    }
    return dz;
  }

  function placeholderImg(tintIdx, label) {
    const tints = window.PLACEHOLDER_TINTS;
    const tint = tints[tintIdx % tints.length];
    const d = document.createElement('div');
    d.className = 'placeholder-img';
    d.style.background = `repeating-linear-gradient(135deg, ${tint[0]} 0 12px, ${tint[1]} 12px 24px)`;
    if (label) {
      const span = document.createElement('span');
      span.textContent = label;
      d.appendChild(span);
    }
    return d;
  }

  // ============================================================
  // INGREDIENTS REPEATER
  // ============================================================
  function renderIngredients() {
    const list = document.getElementById('ingredients-list');
    list.innerHTML = '';
    const tpl = document.getElementById('tpl-ingredient');

    state.data.ingredients.forEach((row, i) => {
      const node = tpl.content.firstElementChild.cloneNode(true);
      node.dataset.rowId = row.id;
      node.querySelector('.rep-card__num').textContent = i + 1;

      // Fields
      node.querySelectorAll('[data-field]').forEach(input => {
        const key = input.dataset.field;
        input.value = row[key] ?? '';
        input.addEventListener('input', () => {
          row[key] = input.value;
          if (key === 'name') {
            row.termId = null;
            showAutocomplete(node, row);
          }
          afterChange();
        });
      });

      // Autocomplete
      const nameInput = node.querySelector('[data-field="name"]');
      nameInput.addEventListener('focus', () => showAutocomplete(node, row));
      nameInput.addEventListener('blur',  () => setTimeout(() => closeAutocomplete(node), 150));

      // Tools
      node.querySelector('[data-action="up"]').addEventListener('click',     () => moveRow('ingredients', i, -1));
      node.querySelector('[data-action="down"]').addEventListener('click',   () => moveRow('ingredients', i, +1));
      node.querySelector('[data-action="remove"]').addEventListener('click', () => removeRow('ingredients', i));

      list.appendChild(node);
    });

    refreshRowToolStates(list);
  }

  function showAutocomplete(card, row) {
    const dropdown = card.querySelector('.ac__list');
    const q = (row.name || '').trim().toLowerCase();
    const matches = !q
      ? window.INGREDIENT_TAXONOMY.slice(0, 8)
      : window.INGREDIENT_TAXONOMY.filter(t => t.name.toLowerCase().includes(q)).slice(0, 8);
    const exact = matches.find(t => t.name.toLowerCase() === q);
    const showCreate = q.length > 1 && !exact;

    dropdown.innerHTML = '';
    matches.forEach(t => {
      const it = document.createElement('div');
      it.className = 'ac__item';
      it.innerHTML = `<span>${escapeHtml(t.name)}</span><span class="ac__tag">${escapeHtml(t.category)}</span>`;
      it.addEventListener('mousedown', () => {
        row.name = t.name;
        row.termId = Number.isFinite(Number(t.termId)) ? Number(t.termId) : null;
        card.querySelector('[data-field="name"]').value = t.name;
        closeAutocomplete(card);
        afterChange();
      });
      dropdown.appendChild(it);
    });
    if (showCreate) {
      const it = document.createElement('div');
      it.className = 'ac__item ac__item--new';
      it.textContent = `+ Utwórz nowy: „${row.name}"`;
      it.addEventListener('mousedown', () => {
        row.termId = null;
        closeAutocomplete(card);
        afterChange();
      });
      dropdown.appendChild(it);
    }
    dropdown.hidden = (matches.length === 0 && !showCreate);
  }
  function closeAutocomplete(card) {
    card.querySelector('.ac__list').hidden = true;
  }

  // ============================================================
  // PREP STEPS REPEATER
  // ============================================================
  function renderPrepSteps() {
    const list = document.getElementById('prep-list');
    list.innerHTML = '';
    const tpl = document.getElementById('tpl-prep');

    state.data.steps.forEach((row, i) => {
      const node = tpl.content.firstElementChild.cloneNode(true);
      node.dataset.rowId = row.id;
      node.querySelector('.rep-card__num').textContent = i + 1;

      node.querySelectorAll('[data-field]').forEach(input => {
        const key = input.dataset.field;
        if (key === 'photo-slot') return;
        input.value = row[key] ?? '';
        input.addEventListener('input', () => { row[key] = input.value; afterChange(); });
      });

      const slot = node.querySelector('[data-field="photo-slot"]');
      renderPrepPhoto(slot, row, i);

      node.querySelector('[data-action="up"]').addEventListener('click',     () => moveRow('steps', i, -1));
      node.querySelector('[data-action="down"]').addEventListener('click',   () => moveRow('steps', i, +1));
      node.querySelector('[data-action="remove"]').addEventListener('click', () => removeRow('steps', i));

      list.appendChild(node);
    });

    refreshRowToolStates(list);
  }

  function renderPrepPhoto(slot, row, idx) {
    slot.innerHTML = '';
    if (isUploadingPhoto(row.photo)) {
      const loading = document.createElement('div');
      loading.className = 'prep-photo is-busy';
      loading.innerHTML = `
        <div class="prep-photo__hint">
          <div class="prep-photo__icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>
            </svg>
          </div>
          <div>Wysyłanie zdjęcia...</div>
        </div>
      `;
      slot.appendChild(loading);
      return;
    }

    const currentUrl = getPhotoUrl(row.photo);
    if (!currentUrl) {
      const empty = document.createElement('div');
      empty.className = 'prep-photo';
      empty.innerHTML = `
        <div class="prep-photo__hint">
          <div class="prep-photo__icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>
            </svg>
          </div>
          <div>Dodaj zdjęcie</div>
          <div style="font-size:11px; color:var(--ink-3);">4:3 zalecane</div>
        </div>
      `;
      bindUploadSurface(empty, (file) => uploadStepPhoto(row, slot, idx, file));
      slot.appendChild(empty);
    } else {
      const filled = document.createElement('div');
      filled.className = 'prep-photo prep-photo--filled';
      filled.style.position = 'relative';
      filled.appendChild(createImagePreview(currentUrl, `Krok ${idx + 1}`));
      bindUploadSurface(filled, (file) => uploadStepPhoto(row, slot, idx, file));
      const x = document.createElement('button');
      x.type = 'button';
      x.className = 'dropzone__remove';
      x.textContent = 'Usuń';
      x.addEventListener('click', (e) => {
        e.stopPropagation();
        row.photo = null;
        renderPrepPhoto(slot, row, idx);
        afterChange();
      });
      filled.appendChild(x);
      slot.appendChild(filled);
    }
  }

  // ============================================================
  // Common repeater operations
  // ============================================================
  function moveRow(key, i, dir) {
    const arr = state.data[key];
    const j = i + dir;
    if (j < 0 || j >= arr.length) return;
    [arr[i], arr[j]] = [arr[j], arr[i]];
    (key === 'ingredients') ? renderIngredients() : renderPrepSteps();
    afterChange();
  }
  function removeRow(key, i) {
    if (state.data[key].length <= 1) return;
    state.data[key].splice(i, 1);
    (key === 'ingredients') ? renderIngredients() : renderPrepSteps();
    afterChange();
  }
  function refreshRowToolStates(list) {
    const cards = list.querySelectorAll('.rep-card');
    cards.forEach((c, i) => {
      c.querySelector('[data-action="up"]').disabled     = (i === 0);
      c.querySelector('[data-action="down"]').disabled   = (i === cards.length - 1);
      c.querySelector('[data-action="remove"]').disabled = (cards.length <= 1);
    });
  }

  // ============================================================
  // PRODUCTS  —  search + multi-select
  // ============================================================
  function normalizePickerToken(value, fallbackValue) {
    const normalized = String(value || '')
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9_-]+/g, '-');

    return normalized || fallbackValue;
  }

  function getAdsPickerRuntimeContract() {
    const baseConfig = creatorData && creatorData.adsPicker && typeof creatorData.adsPicker === 'object'
      ? creatorData.adsPicker
      : {};
    const rawMetadata = baseConfig.metadata && typeof baseConfig.metadata === 'object'
      ? baseConfig.metadata
      : {};
    const currentPostId = Number(autosavePostId || creatorData.editPostId || 0);

    return {
      ...baseConfig,
      enabled: !!baseConfig.enabled,
      context: normalizePickerToken(baseConfig.context, 'recipe_creator'),
      variant: normalizePickerToken(baseConfig.variant, 'creator'),
      metadata: {
        ...rawMetadata,
        surface: 'frontend_creator',
        step: 'promoted_products',
        editMode: !!creatorData.editMode,
        postId: Number.isFinite(currentPostId) && currentPostId > 0 ? currentPostId : 0,
      },
    };
  }

  function applyAdsPickerAdapterContract(adapter, pickerConfig) {
    if (!adapter || !pickerConfig) {
      return;
    }

    const variantClassPrefix = 'g4t-recipe-ads-picker--variant-';
    const metadata = pickerConfig.metadata && typeof pickerConfig.metadata === 'object'
      ? pickerConfig.metadata
      : {};

    Array.from(adapter.classList).forEach((className) => {
      if (className.indexOf(variantClassPrefix) === 0) {
        adapter.classList.remove(className);
      }
    });

    adapter.dataset.g4tPickerContext = String(pickerConfig.context || '');
    adapter.dataset.g4tPickerVariant = String(pickerConfig.variant || '');
    adapter.dataset.g4tPickerMetadata = JSON.stringify(metadata);
    adapter.dataset.g4tPickerSurfaceLabel = String(pickerConfig.surfaceLabel || '');
    adapter.dataset.g4tPickerFallbackMessage = String(pickerConfig.fallbackMessage || '');

    if (pickerConfig.variant) {
      adapter.classList.add(`${variantClassPrefix}${pickerConfig.variant}`);
    }
  }

  function updateAdsPickerStatusUI(pickerConfig, isRuntimeEnabled) {
    const title = document.getElementById('g4t-recipe-ads-picker-title');
    const meta = document.getElementById('g4t-recipe-ads-picker-meta');
    const fallbackNote = document.getElementById('g4t-recipe-ads-picker-fallback-note');

    if (title && pickerConfig.surfaceLabel) {
      title.textContent = String(pickerConfig.surfaceLabel);
    }

    if (meta) {
      const contextLabel = pickerConfig.context ? `Kontrakt: ${pickerConfig.context}` : 'Kontrakt: recipe_creator';
      const variantLabel = pickerConfig.variant ? `wariant: ${pickerConfig.variant}` : 'wariant: creator';
      meta.textContent = `Ten krok używa ${contextLabel}, ${variantLabel}. Lista produktów jest renderowana natywnie w kreatorze.`;
    }

    if (fallbackNote) {
      if (isRuntimeEnabled) {
        fallbackNote.hidden = true;
        fallbackNote.textContent = '';
      } else {
        fallbackNote.hidden = false;
        fallbackNote.textContent = pickerConfig.fallbackMessage || 'Wyszukiwarka produktów jest chwilowo niedostępna. Możesz korzystać z lokalnej listy produktów poniżej.';
      }
    }
  }

  function normalizeProductRecord(raw) {
    if (!raw || typeof raw !== 'object') {
      return null;
    }

    const id = String(raw.id || raw.objectID || '').trim();
    if (!id) {
      return null;
    }

    const title = String(raw.title || raw.name || `Produkt #${id}`);
    const category = String(raw.cat || raw.category || '');
    const sku = String(raw.sku || raw.reference || id);
    const priceValue = raw.price ?? raw.formatted_price ?? raw.formattedPrice ?? '';

    return {
      id,
      title,
      cat: category,
      sku,
      price: String(priceValue || ''),
    };
  }

  function getLocalProductsCatalog() {
    if (!Array.isArray(window.PRODUCTS)) {
      return [];
    }

    return window.PRODUCTS
      .map((item) => normalizeProductRecord(item))
      .filter(Boolean);
  }

  async function fetchAlgoliaProducts(query, pickerConfig) {
    if (!algoliaIndex || !pickerConfig) {
      return [];
    }

    const response = await algoliaIndex.search(String(query || ''), {
      hitsPerPage: Number(pickerConfig.hitsPerPage || 12),
    });

    if (!response || !Array.isArray(response.hits)) {
      return [];
    }

    return response.hits
      .map((hit) => normalizeProductRecord(hit))
      .filter(Boolean);
  }

  function renderProductLoadingState() {
    const list = document.getElementById('product-list');
    if (!list) {
      return;
    }

    list.innerHTML = '';
    const loading = document.createElement('div');
    loading.style.cssText = 'padding:20px; text-align:center; color:var(--ink-2); border:1px dashed var(--line); border-radius:var(--r-md);';
    loading.textContent = 'Ładowanie produktów...';
    list.appendChild(loading);
  }

  async function refreshProductResults(query, pickerConfig) {
    const requestId = ++productRequestSeq;

    if (productSourceMode === 'algolia') {
      try {
        renderProductLoadingState();
        const algoliaResults = await fetchAlgoliaProducts(query, pickerConfig);
        if (requestId !== productRequestSeq) {
          return;
        }
        visibleProducts = algoliaResults;
        showError(null);
      } catch (error) {
        if (requestId !== productRequestSeq) {
          return;
        }
        productSourceMode = 'local';
        visibleProducts = getLocalProductsCatalog();
        showError('Nie udało się pobrać produktów z Algolii. Pokazujemy listę lokalną.');
      }

      renderProducts();
      return;
    }

    const q = String(query || '').trim().toLowerCase();
    const localCatalog = getLocalProductsCatalog();
    visibleProducts = !q
      ? localCatalog
      : localCatalog.filter((p) =>
          String(p.title || '').toLowerCase().includes(q)
          || String(p.cat || '').toLowerCase().includes(q)
          || String(p.sku || '').toLowerCase().includes(q)
        );
    renderProducts();
  }

  function syncAdsPickerAdapterContract() {
    const adapter = document.getElementById('g4t-recipe-ads-picker-adapter');
    if (!adapter) {
      return;
    }

    applyAdsPickerAdapterContract(adapter, getAdsPickerRuntimeContract());
  }

  function setupProductSearch() {
    const pickerConfig = getAdsPickerRuntimeContract();
    const runtimeEnabled = !!(
      pickerConfig.enabled
      && pickerConfig.appId
      && pickerConfig.apiKey
      && window.algoliasearch
    );
    updateAdsPickerStatusUI(pickerConfig, runtimeEnabled);

    productSourceMode = runtimeEnabled ? 'algolia' : 'local';

    if (runtimeEnabled) {
      const searchClient = window.algoliasearch(pickerConfig.appId, pickerConfig.apiKey);
      algoliaIndex = searchClient.initIndex(pickerConfig.indexName || 'go4taste_product_list');
    } else {
      algoliaIndex = null;
    }

    const searchInput = document.getElementById('product-search');
    if (!searchInput) {
      return;
    }

    const adapter = document.getElementById('g4t-recipe-ads-picker-adapter');
    if (adapter) {
      applyAdsPickerAdapterContract(adapter, pickerConfig);
      adapter.hidden = true;
    }
    const fallbackSearch = document.querySelector('.search-box');
    if (fallbackSearch) {
      fallbackSearch.hidden = false;
    }

    searchInput.value = '';

    searchInput.addEventListener('input', (e) => {
      state.productQuery = String(e.target.value || '');

      if (productSearchTimer) {
        clearTimeout(productSearchTimer);
      }

      productSearchTimer = setTimeout(() => {
        refreshProductResults(state.productQuery, pickerConfig);
      }, Number(pickerConfig.searchDebounceMs || 260));
    });

    refreshProductResults('', pickerConfig);
  }

  function renderProducts() {
    const list = document.getElementById('product-list');
    list.innerHTML = '';
    const filtered = Array.isArray(visibleProducts) ? visibleProducts : [];

    if (filtered.length === 0) {
      const empty = document.createElement('div');
      empty.style.cssText = 'padding:28px; text-align:center; color:var(--ink-2); border:1px dashed var(--line); border-radius:var(--r-md);';
      empty.textContent = state.productQuery.trim()
        ? `Brak wyników dla „${state.productQuery}". Spróbuj inaczej.`
        : 'Brak produktów do wyświetlenia.';
      list.appendChild(empty);
    } else {
      filtered.forEach((p, i) => list.appendChild(productRow(p, i)));
    }
    renderSelectedStrip();
  }

  function productRow(p, i) {
    const selected = state.data.products.some(s => String(s.id) === String(p.id));
    const row = document.createElement('div');
    row.className = 'product-row' + (selected ? ' is-selected' : '');
    row.setAttribute('role', 'button');
    row.innerHTML = `
      <div class="product-row__thumb" aria-hidden="true"></div>
      <div class="product-row__body">
        <div class="product-row__title">${escapeHtml(p.title)}</div>
        <div class="product-row__meta"><span>${escapeHtml(p.cat)}</span><span>· SKU ${escapeHtml(p.sku)}</span></div>
      </div>
      <div class="product-row__price">${escapeHtml(p.price)}</div>
      <div class="product-row__check"></div>
    `;
    const thumb = row.querySelector('.product-row__thumb');
    thumb.appendChild(placeholderImg(i, ''));
    const cam = document.createElement('span');
    cam.className = 'product-row__thumb-icon';
    cam.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg>`;
    thumb.appendChild(cam);
    if (selected) {
      row.querySelector('.product-row__check').innerHTML =
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4 10-10"/></svg>';
    }
    row.addEventListener('click', () => toggleProduct(p));
    return row;
  }

  function toggleProduct(p) {
    const idx = state.data.products.findIndex(s => String(s.id) === String(p.id));
    if (idx >= 0) state.data.products.splice(idx, 1);
    else state.data.products.push(p);
    renderProducts();
    afterChange();
  }

  function renderSelectedStrip() {
    const strip = document.getElementById('selected-strip');
    strip.innerHTML = '';
    if (state.data.products.length === 0) { strip.hidden = true; return; }
    strip.hidden = false;
    const label = document.createElement('span');
    label.className = 'selected-strip__label';
    label.textContent = `Wybrane (${state.data.products.length}):`;
    strip.appendChild(label);
    state.data.products.forEach((p, index) => {
      const title = String((p && p.title) || '').trim() || `Produkt #${String((p && p.id) || '').trim()}`;
      const chip = document.createElement('span');
      chip.className = 'chip';
      chip.innerHTML = `${escapeHtml(`${index + 1}. ${title}`)}<button type="button" class="chip__x" aria-label="Usuń">×</button>`;
      chip.querySelector('.chip__x').addEventListener('click', (e) => { e.stopPropagation(); toggleProduct(p); });
      strip.appendChild(chip);
    });
  }

  // ============================================================
  // GLOBAL DELEGATED CLICK  —  modale + add buttons + edit-step
  // ============================================================
  function onDelegatedClick(e) {
    // Modal openers
    const modalBtn = e.target.closest('[data-modal]');
    if (modalBtn) { openModal(modalBtn.dataset.modal); return; }

    const t = e.target.closest('[data-action]');
    if (!t) return;
    const action = t.dataset.action;
    if (action === 'add-ingredient') {
      state.data.ingredients.push(mkIng());
      renderIngredients();
      afterChange();
    } else if (action === 'add-prep') {
      state.data.steps.push(mkPrep());
      renderPrepSteps();
      afterChange();
    } else if (action === 'edit-step') {
      showScreen(formScreenForStep(+t.dataset.target));
    } else if (action === 'restart') {
      restart();
    }
  }

  // ============================================================
  // NAVIGATION
  // ============================================================
  function showScreen(idx) {
    idx = Math.max(0, Math.min(SCREENS.length - 1, idx));
    const screen = SCREENS[idx];
    const activeFillScale = getActiveStepFillScale(idx);

    // Visibility
    document.querySelectorAll('[data-screen-idx]').forEach(el => {
      el.hidden = +el.dataset.screenIdx !== idx;
    });

    // Progress bar
    document.querySelectorAll('.progress__seg').forEach((seg, i) => {
      seg.classList.remove('is-done', 'is-active');
      seg.style.removeProperty('--progress-fill-scale');
      if (screen.kind === 'intro' || screen.kind === 'form') {
        if (i < screen.step) seg.classList.add('is-done');
        if (i === screen.step) {
          seg.classList.add('is-active');
          seg.style.setProperty('--progress-fill-scale', activeFillScale.toFixed(4));
        }
      } else {
        // review / done  —  wszystkie zakończone
        seg.classList.add('is-done');
      }
    });

    // Step indicator (zawsze widoczny w topbarze)
    const indicator = document.getElementById('step-indicator');
    if (screen.kind === 'intro' || screen.kind === 'form') {
      indicator.textContent = `Krok ${screen.step + 1} z ${TOTAL_FORM_STEPS} · ${STEP_TITLES[screen.step]}`;
      indicator.hidden = false;
    } else if (screen.kind === 'review') {
      indicator.textContent = 'Podgląd · ostatni krok';
      indicator.hidden = false;
    } else {
      indicator.textContent = 'Opublikowano';
      indicator.hidden = false;
    }

    // Footer (ukrywamy na ekranie Done)
    const footer = document.getElementById('footer');
    footer.style.display = (screen.kind === 'done') ? 'none' : '';
    document.getElementById('btn-back').disabled = (idx === 0);
    const isReview = (screen.kind === 'review');
    const btnNext = document.getElementById('btn-next');
    if (isReview) {
      if (isEditMode) {
        btnNext.textContent = 'Aktualizuj przepis';
        btnNext.disabled = !hasChanges;
      } else {
        btnNext.textContent = 'Opublikuj przepis';
        btnNext.disabled = false;
      }
    } else {
      btnNext.textContent = 'Dalej';
      btnNext.disabled = false;
    }
    btnNext.classList.toggle('btn-next--accent', isReview);

    showError(null);
    state.screenIdx = idx;
    if (screen.kind === 'review') renderSummary();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function onNext() {
    if (state.isSubmitting) return;
    if (isPhotoBusy()) {
      showError('Poczekaj, aż wszystkie zdjęcia zakończą wysyłanie.');
      return;
    }

    const screen = SCREENS[state.screenIdx];
    // Walidacja: każdy ekran formularza może mieć własną funkcję validate(data)
    if (screen.kind === 'form' && screen.validate) {
      const err = screen.validate(state.data);
      if (err) { showError(err); return; }
    }
    if (screen.kind === 'review') {
      submitToAcf();
      return;
    }
    showScreen(state.screenIdx + 1);
  }

  function submitToAcf() {
    const form = document.querySelector('.g4t-acf-form-bridge form.acf-form');
    if (!form) {
      showError('Nie znaleziono formularza zapisu. Odśwież stronę i spróbuj ponownie.');
      return;
    }

    state.isSubmitting = true;
    const btnNext = document.getElementById('btn-next');
    if (btnNext) {
      btnNext.disabled = true;
      btnNext.textContent = isEditMode ? 'Aktualizowanie...' : 'Publikowanie...';
    }

    clearAcfBridgeInputs(form);
    const payload = buildAcfPayload(state.data);
    appendField(form, 'g4t_recipe_creator_action', isEditMode ? 'update' : 'publish');
    if (isEditMode && String(creatorData.editPostStatus || '') === 'publish') {
      appendField(form, 'post_status', 'publish');
    }
    appendField(form, 'acf[_post_title]', (state.data.title || '').trim());
    appendField(form, 'post_title', (state.data.title || '').trim());
    appendAcfPayload(form, payload);
    appendField(form, 'g4t_meal_type_term_ids_present', '1');
    appendField(form, 'g4t_meal_type_term_ids', Array.isArray(state.data.mealTypeTermIds) ? state.data.mealTypeTermIds : []);
    appendField(form, 'g4t_prep_time_term_ids_present', '1');
    appendField(form, 'g4t_prep_time_term_ids', Array.isArray(state.data.prepTimeTermIds) ? state.data.prepTimeTermIds : []);
    appendField(form, 'g4t_feature_term_ids_present', '1');
    appendField(form, 'g4t_feature_term_ids', Array.isArray(state.data.featureTermIds) ? state.data.featureTermIds : []);

    form.submit();
  }

  function buildAcfPayload(d) {
    const ingredients = d.ingredients
      .filter(r => (r.name || '').trim() !== '' || (r.amount || '').trim() !== '')
      .map(r => ({
        field_ingredient_term_id: Number.isFinite(Number(r.termId)) ? String(Number(r.termId)) : '',
        field_ingredient_name: (r.name || '').trim(),
        field_ingredient_amount: (r.amount || '').trim(),
        field_ingredient_unit: (r.unit || '').trim(),
        field_ingredient_note: (r.hint || '').trim(),
      }));

    const steps = d.steps
      .filter(r => (r.heading || '').trim() !== '' || (r.body || '').trim() !== '')
      .map(r => ({
        field_step_header: (r.heading || '').trim(),
        field_step_description: (r.body || '').trim(),
        field_step_tip: (r.hint || '').trim(),
        field_step_image_url: getPhotoId(r.photo),
      }));

    const adsProducts = d.products.map(p => ({
      field_recipe_ads_product_id: p.id,
    }));

    return {
      field_recipe_lead: (d.lead || '').trim(),
      field_recipe_featured_image: getPhotoId(d.image),
      field_recipe_difficulty: d.difficulty || '',
      field_recipe_servings_value: String(d.portions || ''),
      field_recipe_servings_unit: (d.portionUnit || '').trim(),
      field_recipe_time_prep: formatMinutes(d.timePrep),
      field_recipe_time_cook: formatMinutes(d.timeCook),
      field_recipe_time_soak: formatMinutes(d.timeSoak),
      field_recipe_time_total: formatMinutes((d.timePrep || 0) + (d.timeCook || 0) + (d.timeSoak || 0)),
      field_recipe_ingredients_photo_url: getPhotoId(d.ingredientsPhoto),
      field_recipe_ingredients: ingredients,
      field_recipe_steps: steps,
      field_recipe_tips: (d.tips || '').trim(),
      field_recipe_source_label: (d.sourceName || '').trim(),
      field_recipe_source_url: (d.sourceUrl || '').trim(),
      field_recipe_ads_products: adsProducts,
    };
  }

  function formatMinutes(totalMinutes) {
    const mins = Number(totalMinutes || 0);
    if (!mins) return '';
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    if (h && m) return `${h} h ${m} min`;
    if (h) return `${h} h`;
    return `${m} min`;
  }

  function clearAcfBridgeInputs(form) {
    form.querySelectorAll('input[data-g4t-acf-bridge="1"]').forEach(el => el.remove());
    // Remove ACF-rendered repeater rows to prevent duplication when bridge re-appends them.
    // ACF Pro 6.x may render rows with hashed row IDs (e.g. row_xxxx) instead of
    // sequential integers; submitting both sets causes PHP to treat them as separate
    // entries, doubling every ingredient and step on each update.
    const bridgeRepeaters = [
      'field_recipe_ingredients',
      'field_recipe_steps',
      'field_recipe_ads_products',
    ];
    bridgeRepeaters.forEach(fieldKey => {
      form.querySelectorAll(`input[name*="[${fieldKey}]"], textarea[name*="[${fieldKey}]"], select[name*="[${fieldKey}]"]`).forEach(el => el.remove());
    });
  }

  function appendAcfPayload(form, payload) {
    Object.keys(payload).forEach(key => {
      appendField(form, `acf[${key}]`, payload[key]);
    });
  }

  function appendField(form, name, value) {
    if (Array.isArray(value)) {
      value.forEach((item, index) => {
        appendField(form, `${name}[${index}]`, item);
      });
      return;
    }

    if (value && typeof value === 'object') {
      Object.keys(value).forEach(key => {
        appendField(form, `${name}[${key}]`, value[key]);
      });
      return;
    }

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value == null ? '' : String(value);
    input.setAttribute('data-g4t-acf-bridge', '1');
    form.appendChild(input);
  }

  function showError(msg) {
    const el = document.getElementById('error-msg');
    if (msg) { el.textContent = msg; el.hidden = false; }
    else el.hidden = true;
  }

  // ============================================================
  // SUMMARY
  // ============================================================
  function renderSummary() {
    const d = state.data;
    const body = document.getElementById('summary-body');
    body.innerHTML = '';

    const fmt = m => {
      const h = Math.floor(m / 60), r = m % 60;
      if (!m) return '—';
      if (h && r) return `${h} h ${r} min`;
      if (h) return `${h} h`;
      return `${r} min`;
    };
    const total = d.timePrep + d.timeCook + d.timeSoak;
    const diffLabel = { easy: 'Łatwy', medium: 'Średni', hard: 'Trudny' }[d.difficulty] || '—';
    const mealTypeNames = mapSelectedTermNames(d.mealTypeTermIds, window.MEAL_TYPE_TAXONOMY);
    const prepTimeNames = mapSelectedTermNames(d.prepTimeTermIds, window.PREP_TIME_TAXONOMY);
    const featureNames = mapSelectedTermNames(d.featureTermIds, window.FEATURE_TAXONOMY);

    // Hero
    const hero = document.createElement('div');
    hero.className = 'summary-card';
    hero.innerHTML = `
      <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:18px; align-items:stretch;">
        <div>
          <div class="step__eyebrow" style="color:var(--ink-3);">Tytuł przepisu</div>
          <h2 style="font-family:var(--serif); font-size:28px; margin:6px 0 10px; letter-spacing:-0.01em; line-height:1.1;">${d.title ? escapeHtml(d.title) : 'Twój przepis · podgląd'}</h2>
          <p style="margin:0; color:var(--ink-2); line-height:1.55;">
            ${d.lead ? escapeHtml(d.lead) : '<em style="color:var(--ink-3);">Brak wstępu — wróć do kroku 1, aby go uzupełnić.</em>'}
          </p>
          <div class="summary-stats" style="display:flex; gap:20px; margin-top:18px; flex-wrap:wrap;"></div>
        </div>
        <div id="hero-thumb" style="border-radius:var(--r-md); overflow:hidden; aspect-ratio:4/3;"></div>
      </div>
    `;
    body.appendChild(hero);
    hero.querySelector('#hero-thumb').appendChild(placeholderImg(1, 'zdjęcie główne (dodawane osobno)'));
    const stats = hero.querySelector('.summary-stats');
    [
      ['Trudność',   diffLabel],
      ['Porcje',     `${d.portions} ${d.portionUnit || ''}`],
      ['Czas razem', fmt(total)],
      ['Składników', d.ingredients.filter(r => r.name).length],
      ['Kroków',     d.steps.filter(r => r.heading || r.body).length],
    ].forEach(([k, v]) => {
      const s = document.createElement('div');
      s.innerHTML = `
        <div style="font:500 11px var(--sans); letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">${escapeHtml(k)}</div>
        <div style="font:600 17px var(--sans); margin-top:2px;">${escapeHtml(String(v))}</div>
      `;
      stats.appendChild(s);
    });

    // 1. Podstawy
    summarySection(body, '1 · Podstawy', 0, `
      ${row('Tytuł',         d.title ? escapeHtml(d.title) : '<em class="muted">—</em>')}
      ${row('Lead',          d.lead ? escapeHtml(d.lead) : '<em class="muted">—</em>')}
      ${row('Główne zdjęcie', d.image ? `<img src="${escapeHtml(d.image.url)}" style="max-width:120px; height:auto; border-radius:var(--r-sm);" alt="Główne zdjęcie" />` : '<em class="muted">—</em>')}
      ${row('Trudność',      escapeHtml(diffLabel))}
      ${row('Porcje',        `${d.portions} ${escapeHtml(d.portionUnit || '')}`)}
      ${row('Przygotowanie', fmt(d.timePrep))}
      ${row('Gotowanie',     fmt(d.timeCook))}
      ${row('Namoczenie',    fmt(d.timeSoak))}
      ${row('Razem',         `<strong>${fmt(total)}</strong>`)}
      ${row('Typ dania',     mealTypeNames.length ? escapeHtml(mealTypeNames.join(', ')) : '<em class="muted">—</em>')}
      ${row('Czas przygotowania', prepTimeNames.length ? escapeHtml(prepTimeNames.join(', ')) : '<em class="muted">—</em>')}
      ${row('Cechy',         featureNames.length ? escapeHtml(featureNames.join(', ')) : '<em class="muted">—</em>')}
    `);

    // 2. Składniki
    const ings = d.ingredients.filter(r => r.name);
    summarySection(body, `2 · Składniki (${ings.length})`, 1,
      ings.length === 0
        ? `<div style="padding:10px;"><em class="muted">Brak składników</em></div>`
        : `<ul style="margin:0; padding-left:18px; line-height:1.7;">
            ${ings.map(r => `<li>
              <strong>${escapeHtml(r.amount || '')} ${escapeHtml(r.unit || '')}</strong>
              ${escapeHtml(r.name)}
              ${r.hint ? `<span class="muted"> — ${escapeHtml(r.hint)}</span>` : ''}
            </li>`).join('')}
          </ul>`
    );

    // 3. Kroki
    const prep = d.steps.filter(r => r.heading || r.body);
    summarySection(body, `3 · Kroki przygotowania (${prep.length})`, 2,
      prep.length === 0
        ? `<div style="padding:10px;"><em class="muted">Brak kroków</em></div>`
        : `<ol style="margin:0; padding-left:18px; line-height:1.6; display:flex; flex-direction:column; gap:14px;">
            ${prep.map((r, i) => `<li>
              <strong style="font-family:var(--serif); font-size:17px;">${escapeHtml(r.heading || `Krok ${i + 1}`)}</strong>
              ${r.body ? `<div style="color:var(--ink-2); margin-top:4px;">${escapeHtml(r.body)}</div>` : ''}
              ${r.hint ? `<div style="margin-top:4px; font-size:13px; color:var(--accent);">${escapeHtml(r.hint)}</div>` : ''}
            </li>`).join('')}
          </ol>`
    );

    summarySection(body, '4 · Wskazówki', 3,
      d.tips
        ? `<div style="white-space:pre-wrap; line-height:1.6;">${escapeHtml(d.tips)}</div>`
        : `<em class="muted">Brak wskazówek</em>`
    );

    summarySection(body, '5 · Źródło', 4,
      (d.sourceName || d.sourceUrl)
        ? `${d.sourceName ? `<div>${escapeHtml(d.sourceName)}</div>` : ''}
           ${d.sourceUrl  ? `<div class="muted" style="font-size:13px; margin-top:4px;">${escapeHtml(d.sourceUrl)}</div>` : ''}`
        : `<em class="muted">Bez źródła</em>`
    );

    summarySection(body, `6 · Promowane produkty (${d.products.length})`, 5,
      d.products.length === 0
        ? `<em class="muted">Brak produktów</em>`
        : `<div style="display:flex; flex-wrap:wrap; gap:8px;">
            ${d.products.map(p => `<span class="chip">${escapeHtml(p.title)}</span>`).join('')}
          </div>`
    );
  }

  function summarySection(parent, title, target, contentHtml) {
    const sec = document.createElement('section');
    sec.className = 'summary-card';
    sec.innerHTML = `
      <header style="display:flex; justify-content:space-between; align-items:baseline; border-bottom:1px solid var(--line); padding-bottom:8px;">
        <h3 style="margin:0; font-family:var(--serif); font-size:22px; font-weight:600;">${escapeHtml(title)}</h3>
        <button type="button" class="btn-pill" data-action="edit-step" data-target="${target}" style="padding:6px 14px; font-size:13px;">Edytuj</button>
      </header>
      <div>${contentHtml}</div>
    `;
    parent.appendChild(sec);
  }
  function row(label, val) {
    return `<div class="summary-row"><span class="summary-row__label">${escapeHtml(label)}</span><span class="summary-row__val">${val}</span></div>`;
  }

  // ============================================================
  // AUTOSAVE indicator
  // ============================================================
  let dirty = true;
  const isEditMode = !!(creatorData.editMode);
  let hasChanges = !isEditMode; // w trybie edycji startujemy bez zmian
  function afterChange() {
    dirty = true;
    if (!hasChanges) {
      hasChanges = true;
      // zaktualizuj przycisk jeśli aktualnie jesteśmy na ekranie review
      const screen = SCREENS[state.screenIdx];
      if (screen && screen.kind === 'review') {
        const btnNext = document.getElementById('btn-next');
        if (btnNext) {
          btnNext.disabled = false;
          btnNext.textContent = 'Aktualizuj przepis';
        }
      }
    }
  }
  function renderAutosaveBadge(message, isActive) {
    const pill = document.getElementById('autosave-pill');
    if (!pill) return;

    if (!isActive) {
      pill.innerHTML = '';
      return;
    }

    pill.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;"><path d="m5 12 4 4 10-10"></path></svg> ${escapeHtml(message)}`;
  }

  function setupHeartbeatAutosave() {
    if (!isHeartbeatEnabled) {
      return;
    }
    if (!window.jQuery || !window.wp || !wp.heartbeat) {
      return;
    }

    const interval = Number(creatorData.heartbeatInterval || 30);
    if (Number.isFinite(interval) && interval >= 15) {
      wp.heartbeat.interval(interval);
    }

    window.jQuery(document).on('heartbeat-send.g4tCreator', (event, data) => {
      if (state.isSubmitting || !dirty) {
        return;
      }

      data.g4t_recipe_creator_autosave = {
        nonce: creatorData.heartbeatNonce || '',
        postId: autosavePostId || 0,
        state: state.data,
      };

      renderAutosaveBadge('Autozapis…', true);
    });

    window.jQuery(document).on('heartbeat-tick.g4tCreator', (event, data) => {
      const result = data && data.g4t_recipe_creator_autosave ? data.g4t_recipe_creator_autosave : null;
      if (!result) {
        return;
      }

      if (result.ok) {
        const nextPostId = Number(result.postId || 0);
        if (nextPostId > 0) {
          autosavePostId = nextPostId;
          setPreviewButtonState(autosavePostId);
          syncAdsPickerAdapterContract();
        }
        const savedAt = String(result.savedAt || '');
        renderAutosaveBadge(`Wersja robocza zapisana${savedAt ? ` · ${savedAt}` : ''}`, true);
        dirty = false;
        return;
      }

      if (result.message) {
        renderAutosaveBadge(String(result.message), true);
      }
    });
  }

  async function saveDraftNow() {
    const creator = getCreatorData();
    if (!creator.ajaxUrl || !creator.heartbeatNonce) {
      throw new Error('Brakuje konfiguracji zapisu szkicu.');
    }

    const body = new FormData();
    body.append('action', 'g4t_save_recipe_draft');
    body.append('nonce', creator.heartbeatNonce || '');
    body.append('postId', String(autosavePostId || 0));
    body.append('state', JSON.stringify(state.data || {}));

    renderAutosaveBadge('Autozapis…', true);

    const response = await fetch(creator.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body,
    });

    const payload = await response.json();
    const result = payload && payload.data ? payload.data : {};
    if (!response.ok || !payload.success || !result.ok) {
      const message = result && result.message ? String(result.message) : 'Nie udało się zapisać wersji roboczej.';
      throw new Error(message);
    }

    const nextPostId = Number(result.postId || 0);
    if (nextPostId > 0) {
      autosavePostId = nextPostId;
      setPreviewButtonState(autosavePostId);
      syncAdsPickerAdapterContract();
    }

    const savedAt = String(result.savedAt || '');
    renderAutosaveBadge(`Wersja robocza zapisana${savedAt ? ` · ${savedAt}` : ''}`, true);
    dirty = false;
  }

  // ============================================================
  // MODALS  —  help + exit
  // ============================================================
  function openModal(kind) {
    const root = document.getElementById('modal-root');
    root.hidden = false;

    let title = '', body = '', actions = '<button type="button" class="btn-pill" data-modal-close>Zamknij</button>';
    if (kind === 'help') {
      title = 'Masz pytania?';
      body = `
        <p>Wypełniaj formularz krok po kroku — możesz wrócić do każdego etapu, klikając „Wstecz". Wersja robocza zapisuje się automatycznie co kilka sekund.</p>
        <ul style="line-height:1.7; padding-left:18px;">
          <li>Pola oznaczone „opcjonalnie" możesz pominąć.</li>
          <li>Składniki synchronizujemy z taksonomią — jeśli nie znajdziesz nazwy, utwórz nową prosto z pola.</li>
          <li>Pierwsze zdjęcie (główne) dodasz po opublikowaniu, w widoku przepisu.</li>
        </ul>
        <p>Wsparcie: <a href="mailto:redakcja@hutting.pl" style="color:var(--accent);">redakcja@hutting.pl</a></p>
      `;
    } else if (kind === 'exit') {
      title = 'Zapisać i wyjść?';
      body = `<p>Twój przepis zostanie zapisany jako wersja robocza. Możesz wrócić do niego w każdej chwili.</p>`;
      actions = `
        <button type="button" class="btn-pill" data-modal-save-stay>Zapisz i wróc do edycji</button>
        <button type="button" class="btn-next" data-modal-save-exit>Zapisz i wyjdź</button>
      `;
    }

    root.innerHTML = `
      <div role="dialog" style="position:fixed; inset:0; background:oklch(0.2 0.02 60 / 0.45); display:grid; place-items:center; z-index:100;">
        <div style="background:var(--bg-elev); border-radius:16px; padding:28px; max-width:480px; width:calc(100% - 40px); box-shadow:var(--shadow-lg);">
          <h3 style="margin:0 0 12px; font-family:var(--serif); font-size:26px; letter-spacing:-0.01em;">${escapeHtml(title)}</h3>
          <div style="color:var(--ink-2); line-height:1.55;">${body}</div>
          <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:16px;">${actions}</div>
        </div>
      </div>
    `;

    root.querySelector('[role="dialog"]').addEventListener('click', (e) => {
      if (e.target === e.currentTarget) closeModal();
    });
    root.querySelectorAll('[data-modal-close]').forEach(b => b.addEventListener('click', closeModal));

    const disableModalButtons = (disabled) => {
      root.querySelectorAll('button').forEach((btn) => {
        btn.disabled = !!disabled;
      });
    };

    const saveStayBtn = root.querySelector('[data-modal-save-stay]');
    if (saveStayBtn) {
      saveStayBtn.addEventListener('click', async () => {
        disableModalButtons(true);
        try {
          await saveDraftNow();
          closeModal();
        } catch (error) {
          showError(error && error.message ? error.message : 'Nie udało się zapisać wersji roboczej.');
        } finally {
          disableModalButtons(false);
        }
      });
    }

    const saveExitBtn = root.querySelector('[data-modal-save-exit]');
    if (saveExitBtn) {
      saveExitBtn.addEventListener('click', async () => {
        disableModalButtons(true);
        try {
          await saveDraftNow();
          const creator = getCreatorData();
          const homeUrl = creator && creator.homeUrl ? String(creator.homeUrl) : '/';
          window.location.href = homeUrl;
        } catch (error) {
          showError(error && error.message ? error.message : 'Nie udało się zapisać wersji roboczej.');
          disableModalButtons(false);
        }
      });
    }
  }
  function closeModal() {
    const root = document.getElementById('modal-root');
    root.hidden = true;
    root.innerHTML = '';
  }

  // ============================================================
  // RESTART
  // ============================================================
  function restart() {
    clearPublishedQueryParams();

    state.data = {
      title: '', lead: '', image: null, difficulty: '', portions: 4, portionUnit: 'osób',
      timePrep: 0, timeCook: 0, timeSoak: 0,
      mealTypeTermIds: [], prepTimeTermIds: [], featureTermIds: [],
      ingredientsPhoto: null,
      ingredients: [mkIng()], steps: [mkPrep()],
      tips: '', sourceName: '', sourceUrl: '', products: [],
    };
    state.productQuery = '';

    document.querySelectorAll('[data-bind]').forEach(el => el.value = state.data[el.dataset.bind] ?? '');
    document.getElementById('portions-val').textContent = state.data.portions;
    document.querySelectorAll('[data-time-h], [data-time-m]').forEach(el => el.value = 0);
    document.getElementById('total-h').value = 0;
    document.getElementById('total-m').value = 0;
    document.getElementById('lead-count').textContent = 0;
    document.getElementById('tips-count').textContent = 0;
    document.querySelectorAll('.choice').forEach(c => c.setAttribute('aria-pressed', 'false'));
    document.getElementById('product-search').value = '';
    renderIngredientsPhotoSlot();
    renderTaxonomySelectors();
    renderIngredients();
    renderPrepSteps();
    renderProducts();
    showScreen(0);
  }

  function clearPublishedQueryParams() {
    const url = new URL(window.location.href);
    let changed = false;

    ['saved', 'status', 'post_id'].forEach((key) => {
      if (url.searchParams.has(key)) {
        url.searchParams.delete(key);
        changed = true;
      }
    });

    if (!changed) {
      return;
    }

    const nextUrl = `${url.pathname}${url.search}${url.hash}`;
    window.history.replaceState({}, '', nextUrl);
  }

  // ============================================================
  // UTILS
  // ============================================================
  function mapSelectedTermNames(selectedIds, taxonomyTerms) {
    if (!Array.isArray(selectedIds) || !Array.isArray(taxonomyTerms)) {
      return [];
    }

    return selectedIds
      .map((termId) => Number(termId))
      .filter((termId) => Number.isInteger(termId) && termId > 0)
      .map((termId) => {
        const term = taxonomyTerms.find((item) => Number(item && item.termId) === termId);
        return term && term.name ? String(term.name) : '';
      })
      .filter((name) => name.trim() !== '');
  }

  function fillDatalist(selector, items) {
    const dl = document.querySelector(selector);
    items.forEach(v => {
      const opt = document.createElement('option');
      opt.value = v;
      dl.appendChild(opt);
    });
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }
})();
