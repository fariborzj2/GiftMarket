        </div>
    </div>
    <script>
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (hamburger) {
            hamburger.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }

        // Dropdown Toggle
        document.addEventListener('click', (e) => {
            const dropBtn = e.target.closest('.drop-down-btn');
            if (dropBtn) {
                const drop = dropBtn.closest('.drop-down');
                const list = drop?.querySelector('.drop-down-list');
                if (!list) return;

                document.querySelectorAll('.drop-down-list.active').forEach(l => {
                    if (l !== list) l.classList.remove('active');
                });
                list.classList.toggle('active');
                return;
            }

            const option = e.target.closest('.drop-option');
            if (option) {
                const drop = option.closest('.drop-down');
                drop?.querySelectorAll('.drop-option.active').forEach(opt => opt.classList.remove('active'));
                option.classList.add('active');

                const selectedText = drop?.querySelector('.selected-text');
                const selectedImg = drop?.querySelector('.selected-img');
                const hiddenInput = drop?.querySelector('.selected-option');

                const optionText = option.querySelector('span')?.textContent;
                const optionImg = option.querySelector('img')?.src;
                const optionValue = option.dataset.option || option.querySelector('.drop-option-img')?.dataset.option;

                if (selectedText && optionText) selectedText.textContent = optionText;
                if (selectedImg && optionImg) {
                    selectedImg.src = optionImg;
                    selectedImg.style.display = 'block';
                } else if (selectedImg) {
                    selectedImg.style.display = 'none';
                }
                if (hiddenInput && optionValue) hiddenInput.value = optionValue;

                drop?.querySelector('.drop-down-list')?.classList.remove('active');
                return;
            }

            document.querySelectorAll('.drop-down-list.active').forEach(list => list.classList.remove('active'));
        });
    </script>
</body>
</html>
