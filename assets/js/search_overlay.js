
/* =========================================================
   PUREVIA SEARCH OVERLAY
========================================================= */

document.addEventListener('DOMContentLoaded', () => {

    const openButton =
        document.getElementById('openSearchOverlay');

    const overlay =
        document.getElementById('pureviaSearchOverlay');

    const closeButton =
        document.getElementById('closeSearchOverlay');

    const backdrop =
        document.getElementById('searchBackdrop');

    const form =
        document.getElementById('pureviaSearchForm');

    const input =
        document.getElementById('pureviaSearchInput');

    const clearButton =
        document.getElementById('clearSearchInput');

    const results =
        document.getElementById('pureviaSearchSuggestions');

    const suggestionList =
        document.getElementById('searchSuggestionList');

    const productList =
        document.getElementById('searchProductList');

    const searchAllLink =
        document.getElementById('searchAllLink');

    const searchAllText =
        document.getElementById('searchAllText');


    if (
        !openButton || !overlay || !closeButton ||
        !backdrop || !form || !input || !results
    ) {
        return;
    }


    /* =====================================
       WEBSITE ROOT
    ====================================== */

    const siteRoot = new URL(
        form.getAttribute('action'),
        window.location.href
    );

    const basePath = siteRoot.pathname.replace(
        /\/shop\.php$/,
        ''
    );

    const searchEndpoint =
        basePath + '/actions/search/suggest.php';

    const shopUrl =
        basePath + '/shop.php';

    const detailUrl =
        basePath + '/productdetails.php';


    let searchTimer = null;
    let requestController = null;
    let searchSequence = 0;
    let previousFocus = null;


    /* =====================================
       OPEN SEARCH
    ====================================== */

    function openSearch() {

        previousFocus = document.activeElement;

        const announcementBar = document.querySelector(
            '.announcement-bar'
        );

        const announcementHeight = announcementBar
            ? announcementBar.getBoundingClientRect().bottom
            : 0;

        overlay.style.setProperty(
            '--pv-announcement-height',
            `${Math.max(0, announcementHeight)}px`
        );

        overlay.hidden = false;

        document.body.classList.add('pv-search-open');

        openButton.setAttribute('aria-expanded', 'true');

        input.focus();

        if (input.value.trim() !== '') {
            updateSearch();
        }
    }


    /* =====================================
       CLOSE SEARCH
    ====================================== */

    function closeSearch() {

        searchSequence++;

        clearTimeout(searchTimer);

        if (requestController) {
            requestController.abort();
            requestController = null;
        }

        overlay.hidden = true;
        results.hidden = true;

        input.setAttribute('aria-expanded', 'false');

        document.body.classList.remove('pv-search-open');

        openButton.setAttribute('aria-expanded', 'false');

        if (previousFocus &&
            typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
    }


    /* =====================================
       UPDATE SEARCH ALL LINK
    ====================================== */

    function updateAllLink(query) {

        searchAllLink.href =
            shopUrl + '?q=' + encodeURIComponent(query);

        searchAllText.textContent =
            `Search for “${query}”`;
    }


    /* =====================================
       CLEAR RESULT ELEMENTS
    ====================================== */

    function clearResults() {

        suggestionList.replaceChildren();
        productList.replaceChildren();
    }


    /* =====================================
       EMPTY RESULT MESSAGE
    ====================================== */

    function emptyMessage(text) {

        const message = document.createElement('p');

        message.className = 'pv-search-empty';

        message.textContent = text;

        return message;
    }


    /* =====================================
       HIGHLIGHT MATCHING TEXT SAFELY
    ====================================== */

    function appendHighlightedText(element, value, query) {

        const lowerValue = value.toLocaleLowerCase();

        const lowerQuery = query.toLocaleLowerCase();

        const index = lowerValue.indexOf(lowerQuery);

        if (index < 0 || !query) {
            element.textContent = value;
            return;
        }

        element.append(
            document.createTextNode(value.slice(0, index))
        );

        const strong = document.createElement('strong');

        strong.textContent = value.slice(
            index,
            index + query.length
        );

        element.append(strong);

        element.append(
            document.createTextNode(
                value.slice(index + query.length)
            )
        );
    }


    /* =====================================
       RENDER SUGGESTIONS
    ====================================== */

    function renderSuggestions(items, query) {

        suggestionList.replaceChildren();

        if (!items.length) {

            suggestionList.append(
                emptyMessage('No suggestions found.')
            );

            return;
        }

        items.forEach(item => {

            const link = document.createElement('a');

            link.className = 'pv-suggestion-item';

            link.href = shopUrl + '?q=' +
                encodeURIComponent(item);

            appendHighlightedText(link, item, query);

            suggestionList.append(link);
        });
    }


    /* =====================================
       RENDER PRODUCT RESULTS
    ====================================== */

    function renderProducts(products) {

        productList.replaceChildren();

        if (!products.length) {

            productList.append(
                emptyMessage('No matching products found.')
            );

            return;
        }

        products.forEach(product => {

            const link = document.createElement('a');

            link.className = 'pv-search-product';

            link.href =
                detailUrl + '?id=' + encodeURIComponent(product.id);


            /* PRODUCT IMAGE */

            const imageBox = document.createElement('span');

            imageBox.className = 'pv-search-product-image';

            if (product.image) {

                const image = document.createElement('img');

                image.src = product.image;
                image.alt = product.name;
                image.loading = 'lazy';

                image.onerror = () => {
                    image.remove();

                    const icon = document.createElement('i');

                    icon.className = 'fa-solid fa-pump-soap';

                    imageBox.append(icon);
                };

                imageBox.append(image);

            } else {

                const icon = document.createElement('i');

                icon.className = 'fa-solid fa-pump-soap';

                imageBox.append(icon);
            }


            /* PRODUCT NAME AND PRICE */

            const details = document.createElement('span');

            details.className = 'pv-search-product-details';

            const name = document.createElement('span');

            name.className = 'pv-search-product-name';

            name.textContent = product.name;

            const price = document.createElement('span');

            price.className = 'pv-search-product-price';

            price.textContent = new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP'
            }).format(Number(product.price));

            details.append(name, price);

            link.append(imageBox, details);

            productList.append(link);

        });
    }


    /* =====================================
       FETCH RESULTS
    ====================================== */

    async function fetchResults(query) {

        if (requestController) {
            requestController.abort();
        }

        const controller = new AbortController();

        requestController = controller;

        const sequence = ++searchSequence;

        try {

            const response = await fetch(
                searchEndpoint + '?q=' + encodeURIComponent(query),
                {
                    method: 'GET',
                    signal: controller.signal,
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (!response.ok) {
                throw new Error('Search request failed');
            }

            const data = await response.json();

            if (
                overlay.hidden ||
                sequence !== searchSequence ||
                input.value.trim() !== query
            ) {
                return;
            }

            renderSuggestions(
                data.suggestions || [],
                query
            );

            renderProducts(
                data.products || []
            );

        } catch (error) {

            if (error.name === 'AbortError') {
                return;
            }

            if (sequence !== searchSequence) {
                return;
            }

            suggestionList.replaceChildren();

            productList.replaceChildren(
                emptyMessage(
                    'Search is temporarily unavailable. Please try again.'
                )
            );
        }
    }


    /* =====================================
       INPUT CHANGE
    ====================================== */

    function updateSearch() {

        clearTimeout(searchTimer);

        if (requestController) {
            requestController.abort();
            requestController = null;
        }

        searchSequence++;

        const query = input.value.trim();

        clearButton.hidden = query.length === 0;

        if (!query) {

            results.hidden = true;

            input.setAttribute('aria-expanded', 'false');

            clearResults();

            return;
        }

        results.hidden = false;

        input.setAttribute('aria-expanded', 'true');

        updateAllLink(query);

        clearResults();

        productList.append(
            emptyMessage('Searching...')
        );

        searchTimer = setTimeout(() => {

            fetchResults(query);

        }, 250);
    }


    /* =====================================
       EVENT LISTENERS
    ====================================== */

    openButton.addEventListener('click', openSearch);

    closeButton.addEventListener('click', closeSearch);

    backdrop.addEventListener('click', closeSearch);

    input.addEventListener('input', updateSearch);

    clearButton.addEventListener('click', () => {

        input.value = '';

        updateSearch();

        input.focus();
    });


    /* =====================================
       FORM SUBMIT
    ====================================== */

    form.addEventListener('submit', event => {

        const query = input.value.trim();

        if (!query) {
            event.preventDefault();
            input.focus();
            return;
        }

        input.value = query;
    });


    /* =====================================
       ESCAPE KEY AND FOCUS CONTROL
    ====================================== */

    document.addEventListener('keydown', event => {

        if (overlay.hidden) return;

        if (event.key === 'Escape') {

            closeSearch();
            return;
        }

        if (event.key === 'Tab') {

            const focusables = [
                ...overlay.querySelectorAll(
                    'button:not([disabled]):not([hidden]), ' +
                    'input:not([disabled]), a[href]'
                )
            ].filter(element =>
                element.getClientRects().length > 0
            );

            if (!focusables.length) return;

            const first = focusables[0];
            const last = focusables[focusables.length - 1];

            if (event.shiftKey &&
                document.activeElement === first) {

                event.preventDefault();
                last.focus();

            } else if (
                !event.shiftKey &&
                document.activeElement === last
            ) {

                event.preventDefault();
                first.focus();
            }
        }
    });

});
