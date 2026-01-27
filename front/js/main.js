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