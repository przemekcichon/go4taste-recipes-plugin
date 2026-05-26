/**
 * GO4TASTE Recipes Plugin — Algolia Product Search for Recipe Creator (Step 6)
 *
 * Integrates instantsearch.js with Algolia to search recipes products.
 * Handles product selection and saves via AJAX.
 *
 * @requires algoliasearch, instantsearch
 */

(function(window, document) {
    'use strict';

    const algoliaModule = {
        config: {
            containerId: 'g4t-product-search',
            selectedContainerId: 'selected-products',
        },

        state: {
            credentials: {},
            selectedProducts: [],
        },

        /**
         * Initialize Algolia search module
         */
        init() {
            // Get credentials from localized data
            this.state.credentials = window.G4T_ALGOLIA_DATA?.credentials || {};

            // Skip if no credentials
            if (!this.state.credentials.appId || !this.state.credentials.apiKey) {
                this.renderError('Algolia configuration not available');
                return;
            }

            this.setupInstantSearch();
        },

        /**
         * Setup instantsearch instance
         */
        setupInstantSearch() {
            const { algoliasearch, instantsearch } = window;

            if (!algoliasearch || !instantsearch) {
                console.error('Algolia libraries not loaded');
                return;
            }

            const searchClient = algoliasearch(
                this.state.credentials.appId,
                this.state.credentials.apiKey
            );

            const search = instantsearch({
                indexName: this.state.credentials.indexName || 'go4taste_product_list',
                searchClient,
            });

            // Search box
            search.addWidgets([
                instantsearch.widgets.searchBox({
                    container: `#${this.config.containerId}`,
                    placeholder: 'Search products...',
                }),

                // Search results as custom hits widget
                instantsearch.widgets.hits({
                    container: `#${this.config.containerId}`,
                    templates: {
                        item: (hit) => this.renderProductHit(hit),
                        empty: () => '<div class="ais-Hits-empty">No products found</div>',
                    },
                }),
            ]);

            search.start();

            // Bind product selection
            this.setupProductSelection();
        },

        /**
         * Render a single product hit
         */
        renderProductHit(hit) {
            const isSelected = this.state.selectedProducts.includes(hit.objectID);
            return `
                <div class="g4t-product-hit" data-product-id="${window.encodeURIComponent(hit.objectID)}">
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                        <input 
                            type="checkbox" 
                            class="g4t-product-checkbox"
                            value="${window.encodeURIComponent(hit.objectID)}"
                            ${isSelected ? 'checked' : ''}
                        />
                        <div style="flex: 1;">
                            <div style="font-weight: 500;">${window._.escape(hit.title || hit.name)}</div>
                            <div style="font-size: 12px; color: #999;">${window._.escape(hit.sku || '')}</div>
                            ${hit.price ? `<div style="font-weight: 600; margin-top: 4px;">${window._.escape(hit.price)}</div>` : ''}
                        </div>
                    </label>
                </div>
            `;
        },

        /**
         * Setup product selection listeners
         */
        setupProductSelection() {
            const container = document.getElementById(this.config.containerId);
            if (!container) return;

            container.addEventListener('change', (e) => {
                if (e.target.classList.contains('g4t-product-checkbox')) {
                    const productId = window.decodeURIComponent(e.target.value);
                    
                    if (e.target.checked) {
                        if (!this.state.selectedProducts.includes(productId)) {
                            this.state.selectedProducts.push(productId);
                        }
                    } else {
                        const index = this.state.selectedProducts.indexOf(productId);
                        if (index > -1) {
                            this.state.selectedProducts.splice(index, 1);
                        }
                    }

                    this.renderSelectedProducts();
                }
            });
        },

        /**
         * Render selected products display
         */
        renderSelectedProducts() {
            const container = document.getElementById(this.config.selectedContainerId);
            if (!container) return;

            if (this.state.selectedProducts.length === 0) {
                container.innerHTML = '<p style="color: #999;">No products selected</p>';
                return;
            }

            container.innerHTML = `
                <div style="padding: 16px; background: #f5f5f5; border-radius: 8px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600;">
                        Selected: ${this.state.selectedProducts.length} product(s)
                    </h3>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 8px;">
                        ${this.state.selectedProducts.map(id => `
                            <li style="display: inline-flex; align-items: center; gap: 8px; background: white; padding: 8px 12px; border-radius: 4px; font-size: 13px;">
                                <code>${window._.escape(id)}</code>
                                <button 
                                    type="button" 
                                    class="g4t-remove-product" 
                                    data-product-id="${window.encodeURIComponent(id)}"
                                    style="background: none; border: none; color: #999; cursor: pointer;"
                                >
                                    ✕
                                </button>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;

            // Bind remove buttons
            container.querySelectorAll('.g4t-remove-product').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const productId = window.decodeURIComponent(btn.getAttribute('data-product-id'));
                    const index = this.state.selectedProducts.indexOf(productId);
                    if (index > -1) {
                        this.state.selectedProducts.splice(index, 1);
                    }

                    // Update checkbox if visible
                    const checkbox = document.querySelector('.g4t-product-checkbox[value="' + window.encodeURIComponent(productId) + '"]');
                    if (checkbox) {
                        checkbox.checked = false;
                    }

                    this.renderSelectedProducts();
                });
            });
        },

        /**
         * Save selected products via AJAX
         */
        saveProducts(postId) {
            if (this.state.selectedProducts.length === 0) {
                return Promise.resolve();
            }

            return fetch(window.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'g4t_save_recipe_ads_products',
                    post_id: postId,
                    product_ids: JSON.stringify(this.state.selectedProducts),
                    nonce: window.G4T_ALGOLIA_DATA?.nonce || '',
                }).toString(),
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.data?.message || 'Failed to save products');
                }
                return data;
            });
        },

        /**
         * Render error message
         */
        renderError(message) {
            const container = document.getElementById(this.config.containerId);
            if (container) {
                container.innerHTML = `<div style="padding: 16px; background: #fee; color: #c00; border-radius: 4px;">${window._.escape(message)}</div>`;
            }
        },
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => algoliaModule.init());
    } else {
        algoliaModule.init();
    }

    // Expose to window
    window.G4TAlgoliaModule = algoliaModule;

})(window, document);
