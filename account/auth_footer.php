            </div>
        </div>
    </div>

    <script>
        (function () {
            const toggle = document.getElementById('themeToggle');
            if (!toggle) return;
            toggle.addEventListener('click', () => {
                const isDark = document.documentElement.classList.toggle('dark');
                try { localStorage.setItem('account-theme', isDark ? 'dark' : 'light'); } catch (e) {}
            });
        })();
    </script>
</body>
</html>
