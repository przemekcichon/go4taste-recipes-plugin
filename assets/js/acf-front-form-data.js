// Preferujemy dane taksonomii z WordPressa (recipe_ingredient).
// Statyczna lista zostaje jako fallback dla środowisk bez lokalizacji danych.
const G4T_STATIC_INGREDIENT_TAXONOMY = [
  { id: 'brokul',         name: 'Brokuł',                category: 'warzywa' },
  { id: 'por',            name: 'Por',                   category: 'warzywa' },
  { id: 'ziemniak',       name: 'Ziemniak',              category: 'warzywa' },
  { id: 'marchew',        name: 'Marchew',               category: 'warzywa' },
  { id: 'cebula',         name: 'Cebula',                category: 'warzywa' },
  { id: 'czosnek',        name: 'Czosnek',               category: 'warzywa' },
  { id: 'platki-migdal',  name: 'Płatki migdałowe',      category: 'bakalie' },
  { id: 'maslo',          name: 'Masło',                 category: 'nabiał' },
  { id: 'smietana-30',    name: 'Śmietana 30%',          category: 'nabiał' },
  { id: 'mleko',          name: 'Mleko',                 category: 'nabiał' },
  { id: 'jajko',          name: 'Jajko',                 category: 'nabiał' },
  { id: 'oliwa',          name: 'Oliwa z oliwek',        category: 'tłuszcze' },
  { id: 'olej-rzepak',    name: 'Olej rzepakowy',        category: 'tłuszcze' },
  { id: 'bulion-warz',    name: 'Bulion warzywny',       category: 'płyny' },
  { id: 'woda',           name: 'Woda',                  category: 'płyny' },
  { id: 'sol',            name: 'Sól',                   category: 'przyprawy' },
  { id: 'pieprz',         name: 'Pieprz czarny',         category: 'przyprawy' },
  { id: 'galka-musz',     name: 'Gałka muszkatołowa',    category: 'przyprawy' },
  { id: 'tymianek',       name: 'Tymianek',              category: 'przyprawy' },
  { id: 'natka-piet',     name: 'Natka pietruszki',      category: 'zioła' },
  { id: 'koperek',        name: 'Koperek',               category: 'zioła' },
  { id: 'maka-pszenna',   name: 'Mąka pszenna',          category: 'sypkie' },
  { id: 'cukier',         name: 'Cukier',                category: 'sypkie' },
  { id: 'drozdze',        name: 'Drożdże świeże',        category: 'sypkie' },
  { id: 'kasza-gryczana', name: 'Kasza gryczana',        category: 'sypkie' },
  { id: 'soczewica-cz',   name: 'Soczewica czerwona',    category: 'sypkie' },
];

window.INGREDIENT_TAXONOMY = Array.isArray(window.G4T_RECIPE_CREATOR_DATA?.ingredientTaxonomy)
  ? window.G4T_RECIPE_CREATOR_DATA.ingredientTaxonomy
  : G4T_STATIC_INGREDIENT_TAXONOMY;

// Sugestie jednostek — pole jest wolnym tekstem, te wartości pojawią się tylko jako podpowiedzi w datalist.
window.UNIT_SUGGESTIONS    = ['g', 'kg', 'ml', 'l', 'szt.', 'łyżka', 'łyżeczka', 'szklanka', 'szczypta', 'do smaku'];
window.PORTION_UNIT_SUGGESTIONS = ['osób', 'porcji', 'sztuk', 'kawałków', 'słoików'];

window.PRODUCTS = [
  { id: 'p1', title: 'Garnek żeliwny 4L — Hutting Classic', price: '349,00 zł', cat: 'Naczynia', sku: 'HUT-GAR-4L' },
  { id: 'p2', title: 'Blender ręczny 800W',                price: '189,00 zł', cat: 'AGD',       sku: 'BLM-800' },
  { id: 'p3', title: 'Nóż szefa kuchni 20cm',              price: '249,00 zł', cat: 'Noże',      sku: 'NSK-20' },
  { id: 'p4', title: 'Deska bambusowa XL',                 price: '79,00 zł',  cat: 'Akcesoria', sku: 'DB-XL' },
  { id: 'p5', title: 'Płatki migdałowe 500g',              price: '34,90 zł',  cat: 'Bakalie',   sku: 'PM-500' },
  { id: 'p6', title: 'Oliwa z oliwek extra virgin 750ml',  price: '59,00 zł',  cat: 'Oleje',     sku: 'OL-750' },
  { id: 'p7', title: 'Bulion warzywny BIO 1L',             price: '14,90 zł',  cat: 'Spiżarnia', sku: 'BW-1L' },
  { id: 'p8', title: 'Sól morska gruba 1kg',               price: '19,00 zł',  cat: 'Przyprawy', sku: 'SM-1K' },
  { id: 'p9', title: 'Pieprz czarny ziarnisty 100g',       price: '17,00 zł',  cat: 'Przyprawy', sku: 'PC-100' },
  { id: 'p10',title: 'Fartuch lniany — Hutting',           price: '129,00 zł', cat: 'Tekstylia', sku: 'FL-NAT' },
];

// Pastelowe tinty do striped-placeholderów (te same co w wersji React).
window.PLACEHOLDER_TINTS = [
  ['oklch(0.94 0.04 80)', 'oklch(0.88 0.05 80)'],
  ['oklch(0.93 0.05 130)','oklch(0.86 0.06 130)'],
  ['oklch(0.94 0.04 40)', 'oklch(0.88 0.06 40)'],
  ['oklch(0.93 0.04 200)','oklch(0.86 0.05 200)'],
  ['oklch(0.94 0.04 340)','oklch(0.88 0.05 340)'],
  ['oklch(0.93 0.04 280)','oklch(0.86 0.05 280)'],
];
