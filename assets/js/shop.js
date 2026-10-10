
document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.getElementById('shopSearch');
    const sortSelect = document.getElementById('shopSort');
    const priceSlider = document.getElementById('shopPrice');
    const priceValue = document.getElementById('shopPriceValue');

    const myMatches = document.getElementById('shopMyMatches');

    const productGrid = document.getElementById('shopProductGrid');
    const productCards = Array.from(
        document.querySelectorAll('.shop-product-card')
    );

    const categoryTabs = document.querySelectorAll('.shop-tab');

    const resultCount = document.getElementById('shopResultCount');
    const emptyState = document.getElementById('shopEmpty');

    const clearButton = document.getElementById('clearFilters');
    const resetEmpty = document.getElementById('shopResetEmpty');

    const categoryCheckboxes = Array.from(
        document.querySelectorAll('input[name="category"]')
    );

    const skinCheckboxes = Array.from(
        document.querySelectorAll('input[name="skin_type"]')
    );

    const concernCheckboxes = Array.from(
        document.querySelectorAll('input[name="concern"]')
    );

    let activeCategory = 'all';

    function getCheckedValues(checkboxes) {
        return checkboxes
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);
    }

    function parseValues(value) {
        return String(value || '')
            .split(',')
            .filter(Boolean);
    }

    function matchesAny(productValues, selectedValues) {
        if (selectedValues.length === 0) {
            return true;
        }

        return selectedValues.some(value =>
            productValues.includes(value)
        );
    }

    function filterProducts() {

        const search = searchInput.value
            .trim()
            .toLowerCase();

        const selectedCategories = getCheckedValues(
            categoryCheckboxes
        ).filter(value => value !== 'all');

        const selectedTypes = getCheckedValues(
            skinCheckboxes
        );

        const selectedConcerns = getCheckedValues(
            concernCheckboxes
        );

        const maxPrice = Number(priceSlider.value);

        const onlyMatches = myMatches.checked;

        let visibleCount = 0;

        productCards.forEach(card => {

            const name = card.dataset.name || '';
            const description = card.dataset.description || '';

            const category = card.dataset.category || '';

            const price = Number(card.dataset.price || 0);

            const types = parseValues(card.dataset.types);
            const concerns = parseValues(card.dataset.concerns);

            const match = card.dataset.match === '1';

            const searchMatch =
                name.includes(search) ||
                description.includes(search);

            const tabMatch =
                activeCategory === 'all' ||
                category === activeCategory;

            const sidebarCategoryMatch =
                selectedCategories.length === 0 ||
                selectedCategories.includes(category);

            const skinMatch = matchesAny(
                types,
                selectedTypes
            );

            const concernMatch = matchesAny(
                concerns,
                selectedConcerns
            );

            const priceMatch = price <= maxPrice;

            const profileMatch =
                !onlyMatches || match;

            const visible =
                searchMatch &&
                tabMatch &&
                sidebarCategoryMatch &&
                skinMatch &&
                concernMatch &&
                priceMatch &&
                profileMatch;

            card.hidden = !visible;

            if (visible) {
                visibleCount++;
            }

        });

        resultCount.textContent =
            visibleCount +
            (visibleCount === 1 ? ' product' : ' products');

        emptyState.hidden = visibleCount > 0;

        sortProducts();
    }

    function sortProducts() {

        const sort = sortSelect.value;

        const sorted = [...productCards];

        sorted.sort((a, b) => {

            const priceA = Number(a.dataset.price || 0);
            const priceB = Number(b.dataset.price || 0);

            const nameA = a.dataset.name || '';
            const nameB = b.dataset.name || '';

            const dateA = a.dataset.date || '';
            const dateB = b.dataset.date || '';

            switch (sort) {

                case 'price-low':
                    return priceA - priceB;

                case 'price-high':
                    return priceB - priceA;

                case 'name':
                    return nameA.localeCompare(nameB);

                case 'newest':
                    return dateB.localeCompare(dateA);

                default:
                    // Preserve database ordering.
                    return productCards.indexOf(a) -
                           productCards.indexOf(b);
            }
        });

        sorted.forEach(card => {
            productGrid.appendChild(card);
        });
    }

    /* SEARCH */

    searchInput.addEventListener('input', filterProducts);

    /* SORT */

    sortSelect.addEventListener('change', sortProducts);

    /* CATEGORY TABS */

    categoryTabs.forEach(tab => {

        tab.addEventListener('click', function () {

            categoryTabs.forEach(item => {
                item.classList.remove('active');
            });

            tab.classList.add('active');

            activeCategory = tab.dataset.category;

            filterProducts();
        });

    });

    /* CATEGORY CHECKBOXES */

    categoryCheckboxes.forEach(checkbox => {

        checkbox.addEventListener('change', function () {

            const allCheckbox = categoryCheckboxes.find(
                item => item.value === 'all'
            );

            if (checkbox.value === 'all' && checkbox.checked) {

                categoryCheckboxes.forEach(item => {
                    if (item.value !== 'all') {
                        item.checked = false;
                    }
                });

            } else if (checkbox.value !== 'all') {

                if (allCheckbox) {
                    allCheckbox.checked = false;
                }

                const selected = categoryCheckboxes.some(
                    item => item.value !== 'all' && item.checked
                );

                if (!selected && allCheckbox) {
                    allCheckbox.checked = true;
                }
            }

            filterProducts();
        });

    });

    /* SKIN TYPE FILTER */

    skinCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', filterProducts);
    });

    /* SKIN CONCERNS */

    concernCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', filterProducts);
    });

    /* SKIN MATCH */

    myMatches.addEventListener('change', filterProducts);

    /* PRICE */

    priceSlider.addEventListener('input', function () {

        const value = Number(priceSlider.value);

        priceValue.textContent =
            '₱' + value.toLocaleString('en-PH');

        filterProducts();
    });

    /* RESET FILTERS */

    function resetFilters() {

        searchInput.value = '';

        sortSelect.value = 'popular';

        priceSlider.value = priceSlider.max;

        priceValue.textContent =
            '₱' + Number(priceSlider.max).toLocaleString('en-PH');

        myMatches.checked = false;

        categoryCheckboxes.forEach(checkbox => {
            checkbox.checked = checkbox.value === 'all';
        });

        skinCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });

        concernCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });

        activeCategory = 'all';

        categoryTabs.forEach(tab => {
            tab.classList.toggle(
                'active',
                tab.dataset.category === 'all'
            );
        });

        filterProducts();
    }

    clearButton.addEventListener('click', resetFilters);
    resetEmpty.addEventListener('click', resetFilters);

    /* INITIAL DISPLAY */

    filterProducts();

});
