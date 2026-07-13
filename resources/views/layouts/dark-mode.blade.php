<style>
    /* ══════════════════════════════════════════
       DARK MODE — blue / black / white theme
    ══════════════════════════════════════════ */
    html.dark-mode body {
        background: #0a0e17 !important;
        color: #e8ecf4;
    }

    /* Sidebar */
    html.dark-mode .sidebar {
        background: #0d1220 !important;
        border-right-color: #1c2438 !important;
        box-shadow: 2px 0 12px rgba(0,0,0,0.4) !important;
    }
    html.dark-mode .sidebar h2 { color: #e8ecf4 !important; }
    html.dark-mode .sidebar .nav-item { color: #9aa7c2 !important; }
    html.dark-mode .sidebar .nav-item:hover { background: #161d31 !important; color: #fff !important; }
    html.dark-mode .sidebar .active-nav {
        background: linear-gradient(135deg, #142253, #0f1a3d) !important;
        color: #7ea2ff !important;
        border-left-color: #2f5bff !important;
    }

    /* Generic surfaces */
    html.dark-mode .bg-white,
    html.dark-mode .bg-gray-50 { background-color: #10162a !important; }
    html.dark-mode .bg-gray-100,
    html.dark-mode .bg-gray-200 { background-color: #182036 !important; }
    html.dark-mode .bg-black { background-color: #000 !important; }
    html.dark-mode .bg-transparent { background-color: transparent !important; }

    /* Accent surfaces -> blue-tinted dark */
    html.dark-mode .bg-red-50,   html.dark-mode .bg-amber-50,
    html.dark-mode .bg-blue-50,  html.dark-mode .bg-green-50,
    html.dark-mode .bg-yellow-50 { background-color: #101a33 !important; }
    html.dark-mode .bg-red-100,  html.dark-mode .bg-amber-100,
    html.dark-mode .bg-blue-100, html.dark-mode .bg-green-100,
    html.dark-mode .bg-purple-100 { background-color: #16213d !important; }

    /* Gradient cards (bg-gradient-to-* from-*-50/100 to-*-50/100) -> flat dark surface */
    html.dark-mode [class*="bg-gradient-to-"] {
        background-image: none !important;
        background-color: #10162a !important;
    }
    html.dark-mode [class*="border-red-"],
    html.dark-mode [class*="border-green-"],
    html.dark-mode [class*="border-blue-"],
    html.dark-mode [class*="border-purple-"],
    html.dark-mode [class*="border-amber-"],
    html.dark-mode [class*="border-emerald-"] { border-color: #1f2942 !important; }

    /* Solid brand buttons -> unify on blue/black */
    html.dark-mode .bg-red-500,  html.dark-mode .bg-red-600,  html.dark-mode .bg-red-700,
    html.dark-mode .bg-indigo-600, html.dark-mode .bg-indigo-700,
    html.dark-mode .bg-purple-600, html.dark-mode .bg-purple-700 {
        background-color: #1d4ed8 !important;
    }
    html.dark-mode .hover\:bg-red-700:hover,
    html.dark-mode .hover\:bg-indigo-700:hover { background-color: #1e3a8a !important; }
    html.dark-mode .bg-amber-400, html.dark-mode .bg-amber-500,
    html.dark-mode .bg-amber-600, html.dark-mode .bg-amber-700,
    html.dark-mode .bg-blue-400,  html.dark-mode .bg-blue-600, html.dark-mode .bg-blue-700,
    html.dark-mode .bg-green-400, html.dark-mode .bg-green-500,
    html.dark-mode .bg-teal-400,  html.dark-mode .bg-purple-400 {
        background-color: #2f5bff !important;
    }

    /* Text colors */
    html.dark-mode .text-gray-900, html.dark-mode .text-gray-800,
    html.dark-mode .text-gray-700 { color: #f1f5f9 !important; }
    html.dark-mode .text-gray-600, html.dark-mode .text-gray-500,
    html.dark-mode .text-gray-400, html.dark-mode .text-gray-300 { color: #9aa7c2 !important; }
    html.dark-mode .text-black { color: #fff !important; }
    html.dark-mode .text-red-600, html.dark-mode .text-red-700, html.dark-mode .text-red-800,
    html.dark-mode .text-indigo-600, html.dark-mode .text-purple-600, html.dark-mode .text-purple-800,
    html.dark-mode .text-blue-600, html.dark-mode .text-blue-700, html.dark-mode .text-blue-800, html.dark-mode .text-blue-900 {
        color: #6d94ff !important;
    }
    html.dark-mode .text-amber-600, html.dark-mode .text-amber-700, html.dark-mode .text-amber-800, html.dark-mode .text-amber-900,
    html.dark-mode .text-yellow-700, html.dark-mode .text-yellow-800,
    html.dark-mode .text-orange-400, html.dark-mode .text-orange-600 { color: #f0b34e !important; }
    html.dark-mode .text-green-600, html.dark-mode .text-green-700, html.dark-mode .text-green-800, html.dark-mode .text-green-900,
    html.dark-mode .text-emerald-600, html.dark-mode .text-emerald-700, html.dark-mode .text-emerald-800, html.dark-mode .text-emerald-900 { color: #4ade80 !important; }
    html.dark-mode .text-red-400, html.dark-mode .text-red-500, html.dark-mode .text-red-900 { color: #f87171 !important; }
    html.dark-mode .text-purple-900 { color: #a78bfa !important; }

    /* Borders */
    html.dark-mode .border-gray-100, html.dark-mode .border-gray-200,
    html.dark-mode .border-gray-300, html.dark-mode .border-gray-400 { border-color: #1f2942 !important; }
    html.dark-mode .border-amber-100, html.dark-mode .border-amber-200, html.dark-mode .border-amber-300,
    html.dark-mode .border-blue-200, html.dark-mode .border-green-200, html.dark-mode .border-purple-200,
    html.dark-mode .border-red-200, html.dark-mode .border-yellow-200 { border-color: #1f2942 !important; }
    html.dark-mode .border-blue-500, html.dark-mode .border-blue-600,
    html.dark-mode .border-green-500, html.dark-mode .border-purple-500,
    html.dark-mode .border-red-500, html.dark-mode .border-red-600,
    html.dark-mode .border-orange-500 { border-color: #2f5bff !important; }

    /* Tables */
    html.dark-mode table thead { background-color: #141b30 !important; }
    html.dark-mode table tbody tr { border-color: #1f2942 !important; }
    html.dark-mode table tbody tr:hover { background-color: #131a2e !important; }

    /* Inputs / selects / textareas */
    html.dark-mode input:not([type="checkbox"]):not([type="radio"]),
    html.dark-mode select,
    html.dark-mode textarea {
        background-color: #101627 !important;
        border-color: #26314d !important;
        color: #e8ecf4 !important;
    }
    html.dark-mode input::placeholder,
    html.dark-mode textarea::placeholder { color: #64748b !important; }
    html.dark-mode input:focus, html.dark-mode select:focus, html.dark-mode textarea:focus {
        border-color: #2f5bff !important;
    }

    /* Shadows read poorly on dark bg — soften */
    html.dark-mode .shadow-sm, html.dark-mode .shadow-md, html.dark-mode .shadow-lg {
        box-shadow: 0 4px 16px rgba(0,0,0,0.5) !important;
    }

    /* Low-stock banner */
    html.dark-mode .bg-amber-50.border-amber-300 {
        background-color: #1a1508 !important;
        border-color: #4a3a12 !important;
    }

    /* Navbar shimmer bar -> blue in dark mode */
    html.dark-mode .page-header-bar {
        background: linear-gradient(90deg, #1d4ed8, #2f5bff, #1d4ed8) !important;
        background-size: 200% 100% !important;
    }

    /* Dark-mode toggle switch (navbar) */
    .dm-toggle {
        display: flex; align-items: center; justify-content: center;
        width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.07);
        border: 1px solid rgba(255,255,255,0.1); cursor: pointer; transition: all 0.18s;
        color: #cbd5e1; font-size: 15px;
    }
    .dm-toggle:hover { background: rgba(255,255,255,0.13); border-color: rgba(255,255,255,0.2); }
    html.dark-mode .dm-toggle { color: #ffd166; }
</style>

<script>
    (function () {
        var saved = localStorage.getItem('darkMode');
        if (saved === 'on') {
            document.documentElement.classList.add('dark-mode');
        }
    })();

    function toggleDarkMode() {
        var html = document.documentElement;
        var isDark = html.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', isDark ? 'on' : 'off');
        var icon = document.getElementById('dmToggleIcon');
        if (icon) icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var icon = document.getElementById('dmToggleIcon');
        if (icon) icon.className = document.documentElement.classList.contains('dark-mode') ? 'fas fa-sun' : 'fas fa-moon';
    });
</script>
