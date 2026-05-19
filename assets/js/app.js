document.addEventListener('DOMContentLoaded', function () {
    var body = document.body;
    var toggle = document.querySelector('[data-sidebar-toggle]');
    var backdrop = document.querySelector('[data-sidebar-close]');
    var desktopSidebarToggles = document.querySelectorAll('[data-sidebar-desktop-toggle]');
    var fullscreenButtons = document.querySelectorAll('[data-fullscreen-target]');
    var selectOrTypeInputs = document.querySelectorAll('[data-select-or-type-input]');
    var sidebarStorageKey = 'fleet_sidebar_collapsed';

    var isDesktopViewport = function () {
        return window.innerWidth >= 992;
    };

    var syncDesktopSidebarButtons = function () {
        var collapsed = body.classList.contains('sidebar-collapsed');

        desktopSidebarToggles.forEach(function (button) {
            var textTarget = button.querySelector('[data-sidebar-toggle-text]');

            if (textTarget) {
                textTarget.textContent = collapsed ? 'Expand Sidebar' : 'Hide Sidebar';
            }

            button.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Hide sidebar');
            button.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
        });
    };

    var setDesktopSidebarCollapsed = function (collapsed, persistState) {
        body.classList.toggle('sidebar-collapsed', collapsed);
        syncDesktopSidebarButtons();

        if (persistState) {
            window.localStorage.setItem(sidebarStorageKey, collapsed ? '1' : '0');
        }
    };

    if (window.localStorage.getItem(sidebarStorageKey) === '1') {
        setDesktopSidebarCollapsed(true, false);
    } else {
        syncDesktopSidebarButtons();
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            body.classList.toggle('sidebar-open');
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            body.classList.remove('sidebar-open');
        });
    }

    desktopSidebarToggles.forEach(function (button) {
        button.addEventListener('click', function () {
            if (!isDesktopViewport()) {
                return;
            }

            setDesktopSidebarCollapsed(!body.classList.contains('sidebar-collapsed'), true);
        });
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            body.classList.remove('sidebar-open');
        }

        syncDesktopSidebarButtons();
    });

    fullscreenButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-fullscreen-target');
            var target = targetId ? document.getElementById(targetId) : null;

            if (!target || typeof target.requestFullscreen !== 'function') {
                return;
            }

            if (document.fullscreenElement === target) {
                document.exitFullscreen();
                return;
            }

            target.requestFullscreen();
        });
    });

    selectOrTypeInputs.forEach(function (input) {
        var hiddenFieldId = input.getAttribute('data-select-or-type-hidden');
        var hiddenField = hiddenFieldId ? document.getElementById(hiddenFieldId) : null;
        var dataListId = input.getAttribute('list');
        var dataList = dataListId ? document.getElementById(dataListId) : null;

        if (!hiddenField || !dataList) {
            return;
        }

        var syncHiddenField = function () {
            var matchingOption = Array.prototype.find.call(dataList.options, function (option) {
                return option.value === input.value;
            });

            hiddenField.value = matchingOption ? matchingOption.getAttribute('data-id') || '' : '';
        };

        input.addEventListener('input', syncHiddenField);
        input.addEventListener('change', syncHiddenField);
        input.addEventListener('blur', syncHiddenField);
        syncHiddenField();
    });
});
