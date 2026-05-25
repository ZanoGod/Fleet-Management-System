document.addEventListener('DOMContentLoaded', function () {

    const body = document.body;

    /* =========================================================
       ELEMENTS
    ========================================================= */

    const mobileToggle = document.querySelector(
        '[data-sidebar-toggle]'
    );

    const backdrop = document.querySelector(
        '[data-sidebar-close]'
    );

    const desktopSidebarToggles = document.querySelectorAll(
        '[data-sidebar-desktop-toggle]'
    );

    const fullscreenButtons = document.querySelectorAll(
        '[data-fullscreen-target]'
    );

    const selectOrTypeInputs = document.querySelectorAll(
        '[data-select-or-type-input]'
    );

    const sidebarStorageKey = 'fleet_sidebar_collapsed';

    /* =========================================================
       HELPERS
    ========================================================= */

    const isDesktopViewport = () => {
        return window.innerWidth >= 992;
    };

    /* =========================================================
       SIDEBAR ICON SYNC
    ========================================================= */

    const syncDesktopSidebarButtons = () => {

        const collapsed = body.classList.contains(
            'sidebar-collapsed'
        );

        desktopSidebarToggles.forEach((button) => {

            const expandedIcon = button.querySelector(
                '.icon-expanded'
            );

            const collapsedIcon = button.querySelector(
                '.icon-collapsed'
            );

            if (expandedIcon) {
                expandedIcon.style.display = collapsed
                    ? 'none'
                    : 'inline-flex';
            }

            if (collapsedIcon) {
                collapsedIcon.style.display = collapsed
                    ? 'inline-flex'
                    : 'none';
            }

            button.setAttribute(
                'aria-label',
                collapsed
                    ? 'Expand sidebar'
                    : 'Collapse sidebar'
            );

            button.setAttribute(
                'aria-pressed',
                collapsed
            );
        });
    };

    /* =========================================================
       SET COLLAPSED STATE
    ========================================================= */

    const setDesktopSidebarCollapsed = (
        collapsed,
        persistState = true
    ) => {

        body.classList.toggle(
            'sidebar-collapsed',
            collapsed
        );

        syncDesktopSidebarButtons();

        if (persistState) {
            localStorage.setItem(
                sidebarStorageKey,
                collapsed ? '1' : '0'
            );
        }
    };

    /* =========================================================
       INITIALIZE SIDEBAR STATE
    ========================================================= */

    const savedState = localStorage.getItem(
        sidebarStorageKey
    );

    if (savedState === '1') {
        setDesktopSidebarCollapsed(true, false);
    } else {
        setDesktopSidebarCollapsed(false, false);
    }

    /* =========================================================
       MOBILE SIDEBAR
    ========================================================= */

    if (mobileToggle) {

        mobileToggle.addEventListener('click', () => {

            body.classList.toggle('sidebar-open');

        });

    }

    if (backdrop) {

        backdrop.addEventListener('click', () => {

            body.classList.remove('sidebar-open');

        });

    }

    /* =========================================================
       DESKTOP COLLAPSE
    ========================================================= */

    desktopSidebarToggles.forEach((button) => {

        button.addEventListener('click', () => {

            if (!isDesktopViewport()) {
                return;
            }

            const collapsed = body.classList.contains(
                'sidebar-collapsed'
            );

            setDesktopSidebarCollapsed(
                !collapsed,
                true
            );

        });

    });

    /* =========================================================
       WINDOW RESIZE
    ========================================================= */

    window.addEventListener('resize', () => {

        if (isDesktopViewport()) {

            body.classList.remove('sidebar-open');

        }

    });

    /* =========================================================
       FULLSCREEN
    ========================================================= */

    fullscreenButtons.forEach((button) => {

        button.addEventListener('click', async () => {

            const targetId = button.getAttribute(
                'data-fullscreen-target'
            );

            const target = targetId
                ? document.getElementById(targetId)
                : null;

            if (
                !target ||
                typeof target.requestFullscreen !== 'function'
            ) {
                return;
            }

            try {

                if (document.fullscreenElement) {

                    await document.exitFullscreen();

                } else {

                    await target.requestFullscreen();

                }

            } catch (error) {

                console.error(
                    'Fullscreen error:',
                    error
                );

            }

        });

    });

    /* =========================================================
       SELECT OR TYPE INPUTS
    ========================================================= */

    selectOrTypeInputs.forEach((input) => {

        const hiddenFieldId = input.getAttribute(
            'data-select-or-type-hidden'
        );

        const hiddenField = hiddenFieldId
            ? document.getElementById(hiddenFieldId)
            : null;

        const dataListId = input.getAttribute('list');

        const dataList = dataListId
            ? document.getElementById(dataListId)
            : null;

        if (!hiddenField || !dataList) {
            return;
        }

        const syncHiddenField = () => {

            const matchingOption = Array.from(
                dataList.options
            ).find((option) => {

                return option.value === input.value;

            });

            hiddenField.value = matchingOption
                ? matchingOption.getAttribute('data-id') || ''
                : '';

        };

        input.addEventListener(
            'input',
            syncHiddenField
        );

        input.addEventListener(
            'change',
            syncHiddenField
        );

        input.addEventListener(
            'blur',
            syncHiddenField
        );

        syncHiddenField();

    });

});