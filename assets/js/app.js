document.addEventListener('DOMContentLoaded', function () {
    var body = document.body;
    var toggle = document.querySelector('[data-sidebar-toggle]');
    var backdrop = document.querySelector('[data-sidebar-close]');

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

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            body.classList.remove('sidebar-open');
        }
    });
});
