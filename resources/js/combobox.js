/**
 * Progressively enhances a normal <select data-combobox> into a searchable
 * dropdown: a text input to filter by, plus a floating list of matches.
 * The original <select> stays in the DOM (visually hidden via .sr-only) and
 * remains the real form field, so server-side validation, `old()` re-fill,
 * and `required` all keep working exactly as before.
 *
 * Markup convention, built by the `x-combobox` component:
 *   <input id="{id}_search"> <select id="{id}" data-combobox class="sr-only">…</select> <div id="{id}_menu">
 *
 * Cascading (main category -> sub-category) is handled by Combobox.setupCascade,
 * which disables/enables <option> elements based on a data-parent attribute
 * rather than rebuilding the DOM, so no AJAX round-trip is needed.
 */
(function () {
    function optionItems(select) {
        return Array.from(select.options)
            .filter((o) => o.value !== '' && !o.disabled)
            .map((o) => ({ value: o.value, label: o.textContent.trim() }));
    }

    function selectedLabel(select) {
        const opt = select.options[select.selectedIndex];
        return opt && opt.value !== '' ? opt.textContent.trim() : '';
    }

    function initCombobox(select) {
        if (select._comboboxInit) return;
        select._comboboxInit = true;

        const id = select.id;
        const search = document.getElementById(id + '_search');
        const menu = document.getElementById(id + '_menu');
        if (!search || !menu) return;

        let highlighted = -1;
        let items = [];

        function renderMenu(filter) {
            const all = optionItems(select);
            const f = (filter || '').toLowerCase();
            items = f ? all.filter((i) => i.label.toLowerCase().includes(f)) : all;

            menu.innerHTML = '';
            if (!items.length) {
                menu.innerHTML = '<div class="px-4 py-2 text-sm text-gray-400">No matches</div>';
            } else {
                items.forEach((item, idx) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'w-full text-left px-4 py-2 text-sm hover:bg-blue-50 combobox-item' +
                        (idx === highlighted ? ' bg-blue-50' : '');
                    btn.textContent = item.label;
                    btn.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        pick(item);
                    });
                    menu.appendChild(btn);
                });
            }
            menu.classList.remove('hidden');
        }

        function pick(item) {
            select.value = item.value;
            search.value = item.label;
            menu.classList.add('hidden');
            highlighted = -1;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function closeMenu() {
            menu.classList.add('hidden');
            search.value = selectedLabel(select);
            highlighted = -1;
        }

        search.addEventListener('focus', () => renderMenu(''));
        search.addEventListener('input', () => {
            highlighted = -1;
            renderMenu(search.value);
        });
        search.addEventListener('keydown', (e) => {
            if (menu.classList.contains('hidden') && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
                renderMenu(search.value);
                return;
            }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                highlighted = Math.min(highlighted + 1, items.length - 1);
                renderMenu(search.value);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                highlighted = Math.max(highlighted - 1, 0);
                renderMenu(search.value);
            } else if (e.key === 'Enter') {
                if (!menu.classList.contains('hidden') && items[highlighted]) {
                    e.preventDefault();
                    pick(items[highlighted]);
                }
            } else if (e.key === 'Escape') {
                closeMenu();
            }
        });
        search.addEventListener('blur', () => setTimeout(closeMenu, 150));

        search.value = selectedLabel(select);

        select._combobox = { close: closeMenu, refresh: () => { search.value = selectedLabel(select); } };
    }

    function initAll(root) {
        (root || document).querySelectorAll('select[data-combobox]').forEach(initCombobox);
    }

    /**
     * Wires a dependent sub-category select to a main-category select: options
     * on the child whose [data-parent] doesn't match the parent's current value
     * get disabled (and excluded from the search menu) rather than removed.
     */
    function setupCascade(childSelectId, parentSelectId, dataAttr) {
        const child = document.getElementById(childSelectId);
        const parent = document.getElementById(parentSelectId);
        if (!child || !parent) return;
        const attr = dataAttr || 'data-parent';

        function apply(preserveIfMatches) {
            const parentVal = parent.value;
            let stillValid = false;

            Array.from(child.options).forEach((opt) => {
                if (opt.value === '') { opt.disabled = false; return; }
                const belongs = !parentVal || opt.getAttribute(attr) === parentVal;
                opt.disabled = !belongs;
                if (belongs && opt.value === child.value) stillValid = true;
            });

            if (!preserveIfMatches || !stillValid) {
                child.value = '';
            }
            if (child._combobox) child._combobox.refresh();
        }

        apply(true); // initial render: keep a pre-selected value (edit forms) if it still matches
        parent.addEventListener('change', () => apply(false));
    }

    window.Combobox = { initAll, init: initCombobox, setupCascade };
    document.addEventListener('DOMContentLoaded', () => initAll());
})();
