<script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>
<script src="https://unpkg.com/lucide@1.7.0/dist/umd/lucide.min.js"></script>
<script>
    lucide.createIcons();

    // Theme toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('theme-toggle');
        const knob = document.getElementById('theme-toggle-knob');
        const iconMoon = document.getElementById('toggle-icon-moon');
        const iconSun = document.getElementById('toggle-icon-sun');

        if (!toggle || !knob || !iconMoon || !iconSun) return;

        function applyThemeState() {
            const isDark = document.documentElement.classList.contains('dark');
            knob.style.transform = isDark ? 'translateX(20px)' : 'translateX(0)';
            toggle.style.backgroundColor = isDark ? '#272a2f' : '#eff0f3';
			toggle.style.borderColor = isDark ? '#3c3f44' : '#c2c2c4';
            iconMoon.style.opacity = isDark ? '1' : '0';
            iconSun.style.opacity = isDark ? '0' : '1';
            toggle.setAttribute('aria-checked', isDark);
        }

        // Apply initial state
        applyThemeState();

        // Toggle on click
        toggle.addEventListener('click', async function() {
            const isDark = document.documentElement.classList.toggle('dark');
            const theme = isDark ? 'dark' : 'light';
            applyThemeState();
            lucide.createIcons();
            
            // Save to database
            try {
                await fetch('/api/theme_toggle.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ theme: theme })
                });
            } catch (error) {
                console.error('Failed to save theme:', error);
            }
        });
    });
</script>
