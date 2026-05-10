(function (blocks, element, blockEditor, components, i18n) {
    "use strict";

    var el = element.createElement;
    var useState = element.useState;
    var useEffect = element.useEffect;
    var registerBlockType = blocks.registerBlockType;
    var useBlockProps = blockEditor.useBlockProps;
    var MediaUpload = blockEditor.MediaUpload;
    var MediaUploadCheck = blockEditor.MediaUploadCheck;
    var TextControl = components.TextControl;
    var TextareaControl = components.TextareaControl;
    var Notice = components.Notice;
    var Spinner = components.Spinner;
    var Button = components.Button;
    var __ = i18n.__;
    var apiFetch = window.wp && window.wp.apiFetch ? window.wp.apiFetch : null;

    function createRowControls(rows, onChange, fields, addLabel) {
        var controls = [];

        rows.forEach(function (row, index) {
            controls.push(
                el(
                    "div",
                    { key: "row-" + index, className: "go4taste-recipes-row" },
                    fields.map(function (field) {
                        return el(TextControl, {
                            key: field.key + "-" + index,
                            label: field.label,
                            value: row[field.key] || "",
                            onChange: function (value) {
                                var next = rows.slice();
                                var updated = Object.assign({}, next[index]);
                                updated[field.key] = value;
                                next[index] = updated;
                                onChange(next);
                            }
                        });
                    }),
                    el(Button, {
                        isDestructive: true,
                        variant: "secondary",
                        onClick: function () {
                            var next = rows.slice();
                            next.splice(index, 1);
                            onChange(next);
                        }
                    }, __("Usuń", "go4taste-recipes-plugin"))
                )
            );
        });

        controls.push(
            el(Button, {
                key: "add-row",
                variant: "primary",
                onClick: function () {
                    var next = rows.slice();
                    var emptyRow = {};
                    fields.forEach(function (field) {
                        emptyRow[field.key] = "";
                    });
                    next.push(emptyRow);
                    onChange(next);
                }
            }, addLabel)
        );

        return controls;
    }

    registerBlockType("go4taste-recipes/hero", {
        title: __("Recipe Hero", "go4taste-recipes-plugin"),
        icon: "cover-image",
        category: "widgets",
        attributes: {
            lead: { type: "string", default: "" },
            difficulty: { type: "string", default: "" },
            servingsValue: { type: "string", default: "" },
            servingsUnit: { type: "string", default: "" },
            timePrep: { type: "string", default: "" },
            timeCook: { type: "string", default: "" },
            timeTotal: { type: "string", default: "" }
        },
        edit: function (props) {
            var attrs = props.attributes;
            var setAttrs = props.setAttributes;
            var blockProps = useBlockProps({ className: "go4taste-recipes-block go4taste-recipes-block--hero" });

            return el(
                "section",
                blockProps,
                el("div", { className: "go4taste-block-header" },
                    el("span", { className: "go4taste-block-icon" }, "01"),
                    el("h3", null, __("Informacje podstawowe", "go4taste-recipes-plugin"))
                ),
                el("p", { className: "description" }, __("Dodaj główne informacje o przepisie", "go4taste-recipes-plugin")),
                el("div", { className: "go4taste-recipes-row" },
                    el(TextareaControl, {
                        label: __("Wstęp/Opis (lead)", "go4taste-recipes-plugin"),
                        value: attrs.lead,
                        onChange: function (value) { setAttrs({ lead: value }); },
                        help: __("Krótki opis przepisu, max 200 znaków", "go4taste-recipes-plugin")
                    }),
                    el(TextControl, {
                        label: __("Poziom trudności", "go4taste-recipes-plugin"),
                        value: attrs.difficulty,
                        placeholder: __("np. łatwy, średni, trudny", "go4taste-recipes-plugin"),
                        onChange: function (value) { setAttrs({ difficulty: value }); }
                    })
                ),
                el(
                    "div",
                    { className: "go4taste-recipes-row", style: { display: "grid", gridTemplateColumns: "1fr 1fr", gap: "16px" } },
                    el(TextControl, {
                        label: __("Porcje (liczba)", "go4taste-recipes-plugin"),
                        value: attrs.servingsValue,
                        type: "number",
                        onChange: function (value) { setAttrs({ servingsValue: value }); }
                    }),
                    el(TextControl, {
                        label: __("Porcje (jednostka)", "go4taste-recipes-plugin"),
                        value: attrs.servingsUnit,
                        placeholder: __("np. osoby", "go4taste-recipes-plugin"),
                        onChange: function (value) { setAttrs({ servingsUnit: value }); }
                    })
                ),
                el(
                    "div",
                    { className: "go4taste-recipes-row", style: { display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: "16px" } },
                    el(TextControl, {
                        label: __("Czas przygotowania", "go4taste-recipes-plugin"),
                        value: attrs.timePrep,
                        placeholder: __("np. 15 min", "go4taste-recipes-plugin"),
                        onChange: function (value) { setAttrs({ timePrep: value }); }
                    }),
                    el(TextControl, {
                        label: __("Czas gotowania", "go4taste-recipes-plugin"),
                        value: attrs.timeCook,
                        placeholder: __("np. 30 min", "go4taste-recipes-plugin"),
                        onChange: function (value) { setAttrs({ timeCook: value }); }
                    }),
                    el(TextControl, {
                        label: __("Czas całkowity", "go4taste-recipes-plugin"),
                        value: attrs.timeTotal,
                        placeholder: __("np. 45 min", "go4taste-recipes-plugin"),
                        onChange: function (value) { setAttrs({ timeTotal: value }); }
                    })
                )
            );
        },
        save: function () {
            return null;
        }
    });

    registerBlockType("go4taste-recipes/ingredients", {
        title: __("Recipe Ingredients", "go4taste-recipes-plugin"),
        icon: "carrot",
        category: "widgets",
        attributes: {
            itemsList: {
                type: "array",
                default: [{ ingredient_term_id: "", name: "", amount: "", unit: "" }]
            },
            items: { type: "string", default: "" },
            photoUrl: { type: "string", default: "" }
        },
        edit: function (props) {
            var attrs = props.attributes;
            var setAttrs = props.setAttributes;
            var blockProps = useBlockProps({ className: "go4taste-recipes-block go4taste-recipes-block--ingredients" });
            var rows = Array.isArray(attrs.itemsList) ? attrs.itemsList : [];
            var _useState = useState([]), ingredientTerms = _useState[0], setIngredientTerms = _useState[1];
            var _useState2 = useState(false), isLoadingTerms = _useState2[0], setIsLoadingTerms = _useState2[1];
            var _useState3 = useState(""), termsError = _useState3[0], setTermsError = _useState3[1];

            function refreshIngredientTerms() {
                if (!apiFetch) {
                    return;
                }

                setIsLoadingTerms(true);
                setTermsError("");

                apiFetch({ path: "/wp/v2/recipe_ingredient?per_page=100&orderby=name&order=asc" })
                    .then(function (terms) {
                        setIngredientTerms(Array.isArray(terms) ? terms : []);
                    })
                    .catch(function () {
                        setTermsError(__("Nie udalo sie pobrac listy skladnikow.", "go4taste-recipes-plugin"));
                    })
                    .finally(function () {
                        setIsLoadingTerms(false);
                    });
            }

            useEffect(function () {
                refreshIngredientTerms();
            }, []);

            function updateIngredientRow(index, patch) {
                var next = rows.slice();
                var row = Object.assign({}, next[index] || {});
                next[index] = Object.assign(row, patch);
                setAttrs({ itemsList: next });
            }

            function normalizeName(value) {
                return String(value || "").trim().toLowerCase();
            }

            function findTermByName(name) {
                var normalized = normalizeName(name);
                if (!normalized) {
                    return null;
                }

                for (var i = 0; i < ingredientTerms.length; i++) {
                    var term = ingredientTerms[i];
                    if (!term || typeof term.name !== "string") {
                        continue;
                    }
                    if (normalizeName(term.name) === normalized) {
                        return term;
                    }
                }

                return null;
            }

            function createIngredientTerm(index) {
                if (!apiFetch) {
                    return;
                }

                var row = rows[index] || {};
                var name = String(row.name || "").trim();
                if (!name) {
                    return;
                }

                var existing = findTermByName(name);
                if (existing) {
                    updateIngredientRow(index, { ingredient_term_id: String(existing.id), name: existing.name });
                    return;
                }

                setTermsError("");
                apiFetch({
                    path: "/wp/v2/recipe_ingredient",
                    method: "POST",
                    data: { name: name }
                })
                    .then(function (created) {
                        if (created && created.id) {
                            updateIngredientRow(index, {
                                ingredient_term_id: String(created.id),
                                name: created.name || name
                            });
                            refreshIngredientTerms();
                        }
                    })
                    .catch(function () {
                        setTermsError(__("Nie udalo sie utworzyc skladnika. Sprawdz uprawnienia i REST API.", "go4taste-recipes-plugin"));
                    });
            }

            var datalistId = "go4taste-recipe-ingredient-terms";

            return el(
                "section",
                blockProps,
                el("div", { className: "go4taste-block-header" },
                    el("span", { className: "go4taste-block-icon" }, "02"),
                    el("h3", null, __("Składniki", "go4taste-recipes-plugin"))
                ),
                el("p", { className: "description" }, __("Dodaj zdjęcie oraz listę składników", "go4taste-recipes-plugin")),
                el("div", { className: "go4taste-photo-upload" },
                    el("label", null, __("Zdjęcie składników (opcjonalnie)", "go4taste-recipes-plugin")),
                    el(MediaUploadCheck, null,
                        el(MediaUpload, {
                            onSelect: function (media) {
                                setAttrs({ photoUrl: media.url });
                            },
                            allowedTypes: ["image"],
                            render: function (renderProps) {
                                return el(Button, {
                                    onClick: renderProps.open,
                                    variant: "secondary"
                                    }, attrs.photoUrl ? __("Zmień zdjęcie", "go4taste-recipes-plugin") : __("Dodaj zdjęcie", "go4taste-recipes-plugin"));
                            }
                        })
                    ),
                    attrs.photoUrl && el(
                        "div",
                        { className: "go4taste-photo-preview" },
                        el("img", { src: attrs.photoUrl, alt: "Podgląd" }),
                        el(Button, {
                            isDestructive: true,
                            variant: "secondary",
                            size: "small",
                            onClick: function () {
                                setAttrs({ photoUrl: "" });
                            }
                        }, __("Usuń zdjęcie", "go4taste-recipes-plugin"))
                    )
                ),
                el("h4", { style: { marginTop: "24px", marginBottom: "16px" } }, __("Lista składników", "go4taste-recipes-plugin")),
                isLoadingTerms && el(Spinner, null),
                termsError && el(Notice, { status: "warning", isDismissible: false }, termsError),
                el(
                    "datalist",
                    { id: datalistId },
                    ingredientTerms.map(function (term) {
                        return el("option", { key: "term-opt-" + term.id, value: term.name });
                    })
                )
                ,
                rows.map(function (row, index) {
                    var ingredientInputId = "go4taste-ingredient-name-" + index;
                    var ingredientHelpId = "go4taste-ingredient-help-" + index;
                    return el(
                        "div",
                        { key: "row-" + index, className: "go4taste-recipes-row" },
                        el("div", { className: "components-base-control" },
                            el("label", {
                                className: "components-base-control__label",
                                htmlFor: ingredientInputId
                            }, __("Nazwa", "go4taste-recipes-plugin")),
                            el("div", { className: "components-base-control__field" },
                                el("input", {
                                    id: ingredientInputId,
                                    className: "components-text-control__input",
                                    type: "text",
                                    value: row.name || "",
                                    list: datalistId,
                                    onChange: function (event) {
                                        var value = event && event.target ? event.target.value : "";
                                        var matched = findTermByName(value);
                                        updateIngredientRow(index, {
                                            name: value,
                                            ingredient_term_id: matched ? String(matched.id) : ""
                                        });
                                    },
                                    "aria-describedby": ingredientHelpId
                                })
                            ),
                            el("p", {
                                id: ingredientHelpId,
                                className: "components-base-control__help"
                            }, __("Podpowiedzi pochodza z taksonomii skladnik. Zostaw nazwe, aby utworzyc nowy skladnik.", "go4taste-recipes-plugin"))
                        ),
                        el(TextControl, {
                            label: __("Ilość", "go4taste-recipes-plugin"),
                            value: row.amount || "",
                            onChange: function (value) {
                                updateIngredientRow(index, { amount: value });
                            }
                        }),
                        el(TextControl, {
                            label: __("Jednostka", "go4taste-recipes-plugin"),
                            value: row.unit || "",
                            onChange: function (value) {
                                updateIngredientRow(index, { unit: value });
                            }
                        }),
                        el(
                            "div",
                            { style: { display: "flex", gap: "8px", marginTop: "8px" } },
                            el(Button, {
                                variant: "secondary",
                                onClick: function () {
                                    createIngredientTerm(index);
                                }
                            }, __("Utwórz brakujący składnik", "go4taste-recipes-plugin")),
                            el(Button, {
                                isDestructive: true,
                                variant: "secondary",
                                onClick: function () {
                                    var next = rows.slice();
                                    next.splice(index, 1);
                                    setAttrs({ itemsList: next });
                                }
                            }, __("Usuń", "go4taste-recipes-plugin"))
                        )
                    );
                }),
                el(Button, {
                    variant: "primary",
                    onClick: function () {
                        var next = rows.slice();
                        next.push({ ingredient_term_id: "", name: "", amount: "", unit: "" });
                        setAttrs({ itemsList: next });
                    }
                }, __("+ Dodaj składnik", "go4taste-recipes-plugin"))
            );
        },
        save: function () {
            return null;
        }
    });

    registerBlockType("go4taste-recipes/steps", {
        title: __("Recipe Steps", "go4taste-recipes-plugin"),
        icon: "editor-ol",
        category: "widgets",
        attributes: {
            stepsList: {
                type: "array",
                default: [{ heading: "", description: "", images: [], tip: "" }]
            },
            steps: { type: "string", default: "" }
        },
        edit: function (props) {
            var attrs = props.attributes;
            var setAttrs = props.setAttributes;
            var blockProps = useBlockProps({ className: "go4taste-recipes-block go4taste-recipes-block--steps" });
            var rows = Array.isArray(attrs.stepsList) ? attrs.stepsList : [];

            return el(
                "section",
                blockProps,
                el("div", { className: "go4taste-block-header" },
                    el("span", { className: "go4taste-block-icon" }, "03"),
                    el("h3", null, __("Kroki przygotowania", "go4taste-recipes-plugin"))
                ),
                el("p", { className: "description" }, __("Opisz krok po kroku jak przygotować przepis", "go4taste-recipes-plugin")),
                rows.map(function (row, index) {
                    return el(
                        "div",
                        { key: "step-row-" + index, className: "go4taste-recipes-row" },
                        el(TextControl, {
                            label: __("Nagłówek kroku", "go4taste-recipes-plugin"),
                            value: row.heading || "",
                            placeholder: __("np. Przygotuj składniki", "go4taste-recipes-plugin"),
                            onChange: function (value) {
                                var next = rows.slice();
                                next[index] = Object.assign({}, next[index], { heading: value });
                                setAttrs({ stepsList: next });
                            }
                        }),
                        el(TextareaControl, {
                            label: __("Opis kroku", "go4taste-recipes-plugin"),
                            value: row.description || "",
                            onChange: function (value) {
                                var next = rows.slice();
                                next[index] = Object.assign({}, next[index], { description: value });
                                setAttrs({ stepsList: next });
                            }
                        }),
                        el(TextareaControl, {
                            label: __("Wskazówka (opcjonalnie)", "go4taste-recipes-plugin"),
                            value: row.tip || "",
                            onChange: function (value) {
                                var next = rows.slice();
                                next[index] = Object.assign({}, next[index], { tip: value });
                                setAttrs({ stepsList: next });
                            }
                        }),
                        el("div", { className: "go4taste-step-image" },
                            el("label", { className: "components-base-control__label" }, __("Zdjęcie kroku (opcjonalnie)", "go4taste-recipes-plugin")),
                            el(MediaUploadCheck, null,
                                el(MediaUpload, {
                                    onSelect: function (media) {
                                        var next = rows.slice();
                                        next[index] = Object.assign({}, next[index], { imageUrl: media.url });
                                        setAttrs({ stepsList: next });
                                    },
                                    allowedTypes: ["image"],
                                    render: function (renderProps) {
                                        return el(Button, {
                                            onClick: renderProps.open,
                                            variant: "secondary"
                                        }, row.imageUrl ? __("Zmień zdjęcie", "go4taste-recipes-plugin") : __("Dodaj zdjęcie", "go4taste-recipes-plugin"));
                                    }
                                })
                            ),
                            row.imageUrl && el(
                                "div",
                                { className: "go4taste-step-image-preview" },
                                el("img", { src: row.imageUrl, alt: __("Podgląd kroku", "go4taste-recipes-plugin") }),
                                el(Button, {
                                    isDestructive: true,
                                    variant: "secondary",
                                    size: "small",
                                    onClick: function () {
                                        var next = rows.slice();
                                        next[index] = Object.assign({}, next[index], { imageUrl: "" });
                                        setAttrs({ stepsList: next });
                                    }
                                }, __("Usuń zdjęcie", "go4taste-recipes-plugin"))
                            )
                        ),
                        el(Button, {
                            isDestructive: true,
                            variant: "secondary",
                            onClick: function () {
                                var next = rows.slice();
                                next.splice(index, 1);
                                setAttrs({ stepsList: next });
                            }
                            }, __("Usuń krok", "go4taste-recipes-plugin"))
                    );
                }),
                el(Button, {
                    variant: "primary",
                    onClick: function () {
                        var next = rows.slice();
                        next.push({ heading: "", description: "", images: [], tip: "" });
                        setAttrs({ stepsList: next });
                    },
                    style: { marginTop: "16px" }
                }, __("+ Dodaj krok", "go4taste-recipes-plugin"))
            );
        },
        save: function () {
            return null;
        }
    });

    registerBlockType("go4taste-recipes/tips", {
        title: __("Recipe Tips", "go4taste-recipes-plugin"),
        icon: "lightbulb",
        category: "widgets",
        supports: {
            html: false,
            color: {},
            spacing: {}
        },
        attributes: {
            tips: { type: "string", default: "" }
        },
        edit: function (props) {
            var attrs = props.attributes;
            var setAttrs = props.setAttributes;
            var blockProps = useBlockProps({ className: "go4taste-recipes-block go4taste-recipes-block--tips" });

            return el(
                "section",
                blockProps,
                el("div", { className: "go4taste-block-header" },
                    el("span", { className: "go4taste-block-icon" }, "04"),
                    el("h3", null, __("Wskazówki", "go4taste-recipes-plugin"))
                ),
                el("p", { className: "description" }, __("Dodaj ogólne wskazówki do przepisu", "go4taste-recipes-plugin")),
                el("div", { className: "go4taste-recipes-row" },
                    el(TextareaControl, {
                        label: __("Ogólne wskazówki do przepisu", "go4taste-recipes-plugin"),
                        value: attrs.tips,
                        onChange: function (value) { setAttrs({ tips: value }); }
                    })
                )
            );
        },
        save: function () {
            return null;
        }
    });

    registerBlockType("go4taste-recipes/source", {
        title: __("Recipe Source", "go4taste-recipes-plugin"),
        icon: "book",
        category: "widgets",
        attributes: {
            sourceLabel: { type: "string", default: "" },
            sourceUrl: { type: "string", default: "" }
        },
        edit: function (props) {
            var attrs = props.attributes;
            var setAttrs = props.setAttributes;
            var blockProps = useBlockProps({ className: "go4taste-recipes-block go4taste-recipes-block--source" });

            return el(
                "section",
                blockProps,
                el("div", { className: "go4taste-block-header" },
                    el("span", { className: "go4taste-block-icon" }, "05"),
                    el("h3", null, __("Źródło", "go4taste-recipes-plugin"))
                ),
                el("p", { className: "description" }, __("Podaj źródło przepisu", "go4taste-recipes-plugin")),
                el("div", { className: "go4taste-recipes-row" },
                    el(TextControl, {
                        label: __("Źródło (nazwa)", "go4taste-recipes-plugin"),
                        value: attrs.sourceLabel,
                        placeholder: __("np. Jamie Oliver", "go4taste-recipes-plugin"),
                        onChange: function (value) { setAttrs({ sourceLabel: value }); }
                    }),
                    el(TextControl, {
                        label: __("Źródło (URL)", "go4taste-recipes-plugin"),
                        value: attrs.sourceUrl,
                        placeholder: __("https://...", "go4taste-recipes-plugin"),
                        type: "url",
                        onChange: function (value) { setAttrs({ sourceUrl: value }); }
                    })
                )
            );
        },
        save: function () {
            return null;
        }
    });

})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.i18n
);
