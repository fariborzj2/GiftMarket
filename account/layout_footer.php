            </div>
        </main>
    </div>

    <script>
        // Mobile sidebar
        (function () {
            const hamburger = document.getElementById('hamburger');
            const sidebar   = document.getElementById('sidebar');
            const overlay   = document.getElementById('sidebarOverlay');
            const HIDDEN    = '<?php echo $sideHidden; ?>';
            if (!hamburger || !sidebar || !overlay) return;
            const open  = () => { sidebar.classList.remove(HIDDEN); overlay.classList.remove('hidden'); };
            const close = () => { sidebar.classList.add(HIDDEN); overlay.classList.add('hidden'); };
            hamburger.addEventListener('click', open);
            overlay.addEventListener('click', close);
        })();

        // Theme toggle
        (function () {
            const toggle = document.getElementById('themeToggle');
            if (!toggle) return;
            toggle.addEventListener('click', () => {
                const isDark = document.documentElement.classList.toggle('dark');
                try { localStorage.setItem('account-theme', isDark ? 'dark' : 'light'); } catch (e) {}
            });
        })();

        // User dropdown
        (function () {
            const btn = document.getElementById('userMenuBtn');
            const dd  = document.getElementById('userMenuDropdown');
            if (!btn || !dd) return;
            btn.addEventListener('click', (e) => { e.stopPropagation(); dd.classList.toggle('hidden'); });
            document.addEventListener('click', (e) => {
                if (!dd.classList.contains('hidden') && !dd.contains(e.target) && !btn.contains(e.target)) dd.classList.add('hidden');
            });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') dd.classList.add('hidden'); });
        })();
    </script>
</body>
</html>
