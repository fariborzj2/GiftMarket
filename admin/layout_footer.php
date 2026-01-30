        </div>
    </main>
</div>

<script>
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (hamburger) {
        hamburger.addEventListener('click', () => {
            sidebar.classList.remove('translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.add('translate-x-full');
            sidebar.classList.remove('translate-x-0');
            overlay.classList.add('hidden');
        });
    }

    // Dropdown Logic (for custom selects if needed)
    document.addEventListener('click', (e) => {
        const dropBtn = e.target.closest('.drop-down-btn');
        if (dropBtn) {
            const drop = dropBtn.closest('.drop-down');
            const list = drop?.querySelector('.drop-down-list');
            if (!list) return;

            // Close other dropdowns
            document.querySelectorAll('.drop-down-list').forEach(l => {
                if (l !== list) l.classList.add('hidden');
            });

            list.classList.toggle('hidden');
            return;
        }

        const option = e.target.closest('.drop-option');
        if (option) {
            const drop = option.closest('.drop-down');
            const hiddenInput = drop?.querySelector('.selected-option');
            const selectedText = drop?.querySelector('.selected-text');
            const selectedImg = drop?.querySelector('.selected-img');

            const val = option.dataset.option;
            const text = option.querySelector('span')?.textContent;
            const img = option.querySelector('img')?.src;

            if (hiddenInput) hiddenInput.value = val;
            if (selectedText) selectedText.textContent = text;
            if (selectedImg && img) {
                selectedImg.src = img;
                selectedImg.style.display = 'block';
            } else if (selectedImg) {
                selectedImg.style.display = 'none';
            }

            // Mark active
            drop?.querySelectorAll('.drop-option').forEach(opt => opt.classList.remove('bg-primary/10', 'text-primary'));
            option.classList.add('bg-primary/10', 'text-primary');

            drop?.querySelector('.drop-down-list')?.classList.add('hidden');
            return;
        }

        // Close all on outside click
        document.querySelectorAll('.drop-down-list').forEach(list => list.classList.add('hidden'));
    });
</script>
</body>
</html>
