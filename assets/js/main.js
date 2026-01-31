let appData = null;

async function fetchData() {
    try {
        // Use global API_URL if defined, fallback to static json
        const url = typeof API_URL !== 'undefined' ? API_URL : 'assets/js/data.json';
        const response = await fetch(url);
        appData = await response.json();

        // Update pack sizes based on initial brand/country
        updatePackSizeDropdown();

        // Initial render to sync table with current filter state (handles case where user clicked before data loaded)
        updatePricingTable();
    } catch (error) {
        console.error('Error loading page data:', error);
    }
}

document.addEventListener('click', (e) => {

    /* =======================
    THEME TOGGLE
    ======================== */
    const themeBtn = e.target.closest('.toggle-theme');
    if (themeBtn) {
        document.body.classList.toggle('dark-theme');
        return;
    }

    /* =======================
    MODE BUTTONS
    ======================== */
    const modeBtn = e.target.closest('.mode-btn');
    if (modeBtn) {
        document.querySelectorAll('.mode-btn.active')
            .forEach(b => b.classList.remove('active'));

        modeBtn.classList.add('active');
        updatePricingTable();
        return;
    }

    /* =======================
    DROPDOWN TOGGLE (ONLY ONE OPEN)
    ======================== */
    const dropBtn = e.target.closest('.drop-down-btn');
    if (dropBtn) {
        const drop = dropBtn.closest('.drop-down');
        const list = drop?.querySelector('.drop-down-list');

        if (!list) return;

        // close all other dropdowns
        document.querySelectorAll('.drop-down-list.active').forEach(l => {
            if (l !== list) l.classList.remove('active');
        });

        list.classList.toggle('active');
        return;
    }

    /* =======================
    DROPDOWN OPTION SELECT
    ======================== */
    const option = e.target.closest('.drop-option');
    if (option) {
        const drop = option.closest('.drop-down');

        drop?.querySelectorAll('.drop-option.active')
            .forEach(opt => opt.classList.remove('active'));

        option.classList.add('active');

        // Update selected text and image
        const selectedText = drop?.querySelector('.selected-text');
        const selectedImg = drop?.querySelector('.selected-img');
        const hiddenInput = drop?.querySelector('.selected-option');

        const optionText = option.querySelector('span')?.textContent;
        const optionImg = option.querySelector('img')?.src;
        const optionValue = option.querySelector('.drop-option-img')?.dataset.option || option.dataset.option;

        if (selectedText && optionText) selectedText.textContent = optionText;
        if (selectedImg && optionImg) selectedImg.src = optionImg;
        if (hiddenInput && optionValue) hiddenInput.value = optionValue;

        // Special case: Language toggle RTL/LTR
        if (hiddenInput?.name === 'lang') {
            const url = option.dataset.url;
            if (url) {
                window.location.href = url;
                return;
            }

            if (optionValue === 'ar') {
                document.documentElement.setAttribute('dir', 'rtl');
                document.documentElement.setAttribute('lang', 'ar');
            } else {
                document.documentElement.setAttribute('dir', 'ltr');
                document.documentElement.setAttribute('lang', 'en');
            }

            // Re-initialize Swiper for RTL/LTR change
            initSwiper();
        }

        drop?.querySelector('.drop-down-list')
            ?.classList.remove('active');

        // Update pricing table if brand or country changed
        if (hiddenInput?.name === 'brand' || hiddenInput?.name === 'country') {
            updatePackSizeDropdown();
            updatePricingTable();
        } else if (hiddenInput?.name === 'pack_size') {
            updatePricingTable();
        }

        return;
    }

    /* =======================
    FAQ ACCORDION
    ======================== */
    const faqHead = e.target.closest('.faq-head');
    if (faqHead) {
        faqHead.closest('.faq-item')
            ?.classList.toggle('active');
        return;
    }

    /* =======================
    CLICK OUTSIDE → CLOSE DROPDOWNS
    ======================== */
    document.querySelectorAll('.drop-down-list.active')
        .forEach(list => list.classList.remove('active'));
});

/* =======================
   PRICING TABLE LOGIC
======================== */
let USD_TO_AED = 3.673;

function updatePackSizeDropdown() {
    if (!appData) return;

    const brand = document.querySelector('input[name="brand"]')?.value;
    const country = document.querySelector('input[name="country"]')?.value;
    const packSizeInput = document.querySelector('input[name="pack_size"]');
    const packSizeDropdown = packSizeInput?.closest('.drop-down');
    const packSizeList = packSizeDropdown?.querySelector('.drop-down-list');

    if (!brand || !country || !packSizeList) return;

    const brandData = appData.pricingData[brand];
    if (!brandData) return;

    const options = brandData.options[country] || [];

    // Get unique pack sizes
    const availablePackSizes = [...new Set(options.map(opt => parseInt(opt.pack_size)))].sort((a, b) => a - b);

    if (availablePackSizes.length === 0) {
        packSizeList.innerHTML = `<div class="drop-option pd-10 text-center color-bright">${appData.translations?.no_packs || 'No packs available'}</div>`;
        return;
    }

    // Current selected pack size
    let currentSize = parseInt(packSizeInput.value);
    let sizeFound = availablePackSizes.includes(currentSize);

    // If current size not available, pick the first one
    if (!sizeFound) {
        currentSize = availablePackSizes[0];
        packSizeInput.value = currentSize;

        // Update selected text in button
        const selectedText = packSizeDropdown.querySelector('.selected-text');
        if (selectedText) selectedText.textContent = `${appData.translations?.pack_of || 'Pack Of'} ${currentSize}`;
    }

    // Re-populate list
    packSizeList.innerHTML = '';
    availablePackSizes.forEach(size => {
        const item = document.createElement('div');
        item.className = `drop-option d-flex gap-10 align-center ${size === currentSize ? 'active' : ''}`;
        item.dataset.option = size;
        item.innerHTML = `<span>${appData.translations?.pack_of || 'Pack Of'} ${size}</span>`;
        packSizeList.appendChild(item);
    });
}

function updatePricingTable() {
    if (!appData) return;

    if (appData.exchangeRates && appData.exchangeRates.USD) {
        USD_TO_AED = appData.exchangeRates.USD;
    }

    const { pricingData, countryNames } = appData;

    const brand = document.querySelector('input[name="brand"]')?.value;
    const country = document.querySelector('input[name="country"]')?.value;
    const packSize = parseInt(document.querySelector('input[name="pack_size"]')?.value || '100');
    const isDigital = document.getElementById('modeDigitalBtn')?.classList.contains('active');

    const tableBody = document.getElementById('priceTableBody');
    if (!tableBody) return;

    const t = appData.translations || {};

    const brandData = pricingData[brand];
    if (!brandData) {
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center">${t.no_packs || 'No data available'}</td></tr>`;
        return;
    }

    const options = brandData.options[country];
    if (!options) {
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center">${t.no_packs || 'No data available'}</td></tr>`;
        return;
    }

    const filteredOptions = options.filter(opt => parseInt(opt.pack_size) === packSize);

    if (filteredOptions.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center">${t.no_packs || 'No data available'}</td></tr>`;
        return;
    }

    tableBody.innerHTML = '';
    filteredOptions.forEach(opt => {
        const pricePerCard = parseFloat(isDigital ? opt.price_digital : opt.price_physical);
        const totalPrice = (pricePerCard * packSize).toFixed(2);

        const priceInAED = (pricePerCard * USD_TO_AED).toFixed(2);
        const totalInAED = (parseFloat(totalPrice) * USD_TO_AED).toFixed(2);

        const cardSymbol = getCurrencySymbol(opt.currency);

        const row = document.createElement('tr');
        row.innerHTML = `
            <td data-label="${t.brand || 'Brand'}" class="text-center">
                <div class="brand-logo m-auto">
                    <img src="${BASE_URL}${brandData.logo}" width="100" height="100" alt="Brand logo" loading="lazy">
                </div>
            </td>
            <td data-label="${t.denomination || 'Denomination'}">
                <span>${opt.denomination} ${cardSymbol}</span><br>
                <span class="color-bright font-size-0-9">${isDigital ? (t.digital || 'Digital') : (t.physical || 'Physical')} · ${opt.currency}</span>
            </td>
            <td data-label="${t.country || 'Country'}">${countryNames[country]}</td>
            <td data-label="${t.qty || 'Qty'}">${packSize}</td>
            <td data-label="${t.price_card || 'Price / Card'}">
                <span>$${pricePerCard.toFixed(2)}</span><br>
                <span class="color-bright font-size-0-9">~ ${priceInAED} AED</span>
            </td>
            <td data-label="${t.total_price || 'Total Price'}">
                <span>$${totalPrice}</span><br>
                <span class="color-bright font-size-0-9">~ ${totalInAED} AED</span>
            </td>
            <td class="text-center" data-label="${t.buy || 'Buy'}">
                <a href="tel:+9710506565129" class="btn">
                    <span class="icon icon-calling icon-size-18"></span>
                    ${t.call_to_order || 'Call To Order'}
                </a>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

function getCurrencySymbol(curr) {
    switch(curr) {
        case 'USD': return '$';
        case 'GBP': return '£';
        case 'TRY': return 'TL';
        case 'AED': return 'AED';
        case 'EUR': return '€';
        default: return curr || '';
    }
}

// Initial data fetch
document.addEventListener('DOMContentLoaded', () => {
    fetchData();
    setupSwiperLazyLoad();
});

/* =======================
   SWIPER LAZY LOAD
======================== */
let commentsSlider = null;
let swiperLoaded = false;

function initSwiper() {
    if (!swiperLoaded || !window.Swiper) return;

    if (commentsSlider) {
        commentsSlider.destroy(true, true);
    }

    const sliderEl = document.getElementById('comments-slider');
    if (!sliderEl) return;

    const isRtl = document.documentElement.getAttribute('dir') === 'rtl';

    commentsSlider = new Swiper('#comments-slider', {
        loop: true,
        rtl: isRtl,
        spaceBetween: 20,
        navigation: {
            nextEl: '.com-slide-next',
            prevEl: '.com-slide-prev'
        },
        breakpointsBase: 'container',
        breakpoints: {
            0: { slidesPerView: 1 },
            500: { slidesPerView: 2 }
        }
    });
}

function setupSwiperLazyLoad() {
    const sliderEl = document.getElementById('comments-slider');
    if (!sliderEl) return;

    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) {
            loadSwiperAssets();
            observer.disconnect();
        }
    }, { rootMargin: '200px' });

    observer.observe(sliderEl);
}

async function loadSwiperAssets() {
    if (swiperLoaded) return;

    try {
        // Load CSS
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = typeof SWIPER_CSS_URL !== 'undefined' ? SWIPER_CSS_URL : 'assets/css/swiper-bundle.min.css';
        document.head.appendChild(link);

        // Load JS
        await new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = typeof SWIPER_JS_URL !== 'undefined' ? SWIPER_JS_URL : 'assets/js/swiper-bundle.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.body.appendChild(script);
        });

        swiperLoaded = true;
        initSwiper();
    } catch (error) {
        console.error('Error loading Swiper assets:', error);
    }
}
