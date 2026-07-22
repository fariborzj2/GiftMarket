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

        // Topbar dropdowns (user menu + language)
        (function () {
            const menus = [
                { btn: 'userMenuBtn', dd: 'userMenuDropdown' },
                { btn: 'langMenuBtn', dd: 'langMenuDropdown' },
            ].map(m => ({ btn: document.getElementById(m.btn), dd: document.getElementById(m.dd) }))
             .filter(m => m.btn && m.dd);
            if (!menus.length) return;

            const closeAll = (except) => menus.forEach(m => { if (m.dd !== except) m.dd.classList.add('hidden'); });

            menus.forEach(m => {
                m.btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const willOpen = m.dd.classList.contains('hidden');
                    closeAll(m.dd);
                    m.dd.classList.toggle('hidden', !willOpen);
                });
            });
            document.addEventListener('click', (e) => {
                menus.forEach(m => {
                    if (!m.dd.classList.contains('hidden') && !m.dd.contains(e.target) && !m.btn.contains(e.target)) m.dd.classList.add('hidden');
                });
            });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAll(null); });
        })();
    </script>
</body>
</html>
