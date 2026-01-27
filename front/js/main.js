let appData = null;

async function fetchData() {
    try {
        // In a real scenario, this would be an API endpoint like /api/landing-page-data
        const response = await fetch('js/data.json');
        appData = await response.json();

        renderDynamicContent();
    } catch (error) {
        console.error('Error loading page data:', error);
        showErrorState();
    }
}

function renderDynamicContent() {
    if (!appData) return;

    updatePricingTable();
    renderFAQs();
    renderTestimonials();

    // Initialize Swiper after content is rendered
    if (typeof initSwiper === 'function') {
        initSwiper();
    }
}

function showErrorState() {
    const tableBody = document.getElementById('priceTableBody');
    if (tableBody) {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Error loading data. Please try again later.</td></tr>';
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
            if (optionValue === 'arabic') {
                document.documentElement.setAttribute('dir', 'rtl');
                document.documentElement.setAttribute('lang', 'ar');
            } else {
                document.documentElement.setAttribute('dir', 'ltr');
                document.documentElement.setAttribute('lang', 'en');
            }
        }

        drop?.querySelector('.drop-down-list')
            ?.classList.remove('active');

        // Update pricing table if brand or country changed
        if (hiddenInput?.name === 'brand' || hiddenInput?.name === 'country' || hiddenInput?.name === 'pack_size') {
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
function updatePricingTable() {
    if (!appData) return;

    const { pricingData, countryNames, exchangeRates } = appData;

    const brand = document.querySelector('input[name="brand"]')?.value;
    const country = document.querySelector('input[name="country"]')?.value;
    const packSize = parseInt(document.querySelector('input[name="pack_size"]')?.value || '100');
    const isDigital = document.getElementById('modeDigitalBtn')?.classList.contains('active');

    const tableBody = document.getElementById('priceTableBody');
    if (!tableBody) return;

    const brandData = pricingData[brand];
    if (!brandData) {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No data available for this brand</td></tr>';
        return;
    }

    const options = brandData.options[country];
    if (!options) {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No data available for this country</td></tr>';
        return;
    }

    tableBody.innerHTML = '';
    options.forEach(opt => {
        const pricePerCard = opt.price;
        const totalPrice = (pricePerCard * packSize).toFixed(2);
        const rate = exchangeRates[opt.currency] || 1;
        const priceInAED = (pricePerCard * rate).toFixed(2);
        const totalInAED = (totalPrice * rate).toFixed(2);

        const row = document.createElement('tr');
        row.innerHTML = `
            <td data-label="Brand" class="text-center">
                <div class="brand-logo m-auto">
                    <img src="${brandData.logo}" alt="">
                </div>
            </td>
            <td data-label="Denomination">
                <span>${opt.denomination}</span><br>
                <span class="color-bright font-size-0-9">${isDigital ? 'Digital' : 'Physical'} · ${opt.currency}</span>
            </td>
            <td data-label="Country">${countryNames[country]}</td>
            <td data-label="Qty">${packSize}</td>
            <td data-label="Price / Card">
                <span>${getCurrencySymbol(opt.currency)}${pricePerCard}</span><br>
                ${opt.currency !== 'AED' ? `<span class="color-bright font-size-0-9">~ ${priceInAED} AED</span>` : ''}
            </td>
            <td data-label="Total Price">
                <span>${getCurrencySymbol(opt.currency)}${totalPrice}</span><br>
                ${opt.currency !== 'AED' ? `<span class="color-bright font-size-0-9">~ ${totalInAED} AED</span>` : ''}
            </td>
            <td class="text-center" data-label="Buy">
                <a href="tel:+9710506565129" class="btn">
                    <span class="icon icon-calling icon-size-18"></span>
                    Call To Order
                </a>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

/* =======================
   FAQ RENDERING
======================== */
function renderFAQs() {
    const container = document.getElementById('faqContainer');
    if (!container || !appData.faqs) return;

    container.innerHTML = appData.faqs.map((faq, index) => `
        <div class="faq-item ${index === appData.faqs.length - 1 ? '' : 'border-b'}">
            <div class="faq-head d-flex align-center gap-10 pd-20 pointer">
                <span class="color-primary">${faq.id}</span>
                <h3 class="color-title">${faq.question}</h3>
                <span class="icon icon-add icon-size-22 lt-auto"></span>
            </div>
            <div class="faq-content border-t pd-20">
                <p>${faq.answer}</p>
            </div>
        </div>
    `).join('');
}

/* =======================
   TESTIMONIALS RENDERING
======================== */
function renderTestimonials() {
    const container = document.getElementById('testimonialsContainer');
    if (!container || !appData.testimonials) return;

    container.innerHTML = appData.testimonials.map(t => `
        <div class="swiper-slide">
            <div class="slide-comment">
                <div class="d-flex align-center just-between gap-20 mb-10">
                    <div class="d-flex align-center ">
                        <div class="user-img"><img src="${t.image}" alt=""></div>
                        <div class="line20">
                            <div class="color-title font-size-0-9">${t.name}</div>
                            <div class="color-bright font-size-0-8">${t.date}</div>
                        </div>
                    </div>

                    <div class="">
                        <div class="stars"><img src="images/stars.svg" alt=""></div>
                        <div class="font-size-0-8 color-green"><span class="icon icon-size-16 icon-color-green"></span> Verified</div>
                    </div>
                </div>
                <p class="font-size-0-9">${t.text}</p>
            </div>
        </div>
    `).join('');
}

function getCurrencySymbol(curr) {
    switch(curr) {
        case 'USD': return '$';
        case 'GBP': return '£';
        case 'TRY': return 'TL';
        case 'AED': return '';
        default: return '';
    }
}

// Initial data fetch
document.addEventListener('DOMContentLoaded', fetchData);
