document.addEventListener('DOMContentLoaded', function () {
    const navItems = document.querySelectorAll('.nav-item[data-tab]');
    const panels = document.querySelectorAll('.tab-panel');

    function activateTab(targetTab) {
        navItems.forEach(nav => nav.classList.toggle('active', nav.dataset.tab === targetTab));
        panels.forEach(panel => panel.classList.toggle('active', panel.id === `tab-${targetTab}`));
    }

    navItems.forEach(item => {
        item.addEventListener('click', function () {
            activateTab(this.dataset.tab);
        });
    });

    const params = new URLSearchParams(window.location.search);
    const requestedTab = params.get('tab');
    if (requestedTab && document.getElementById(`tab-${requestedTab}`)) {
        activateTab(requestedTab);
    }
});