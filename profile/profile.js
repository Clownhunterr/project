document.addEventListener('DOMContentLoaded', function () {
    const navItems = document.querySelectorAll('.nav-item[data-tab]');
    const panels = document.querySelectorAll('.tab-panel');

    navItems.forEach(item => {
        item.addEventListener('click', function () {
            const targetTab = this.dataset.tab;

            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');

            panels.forEach(panel => {
                panel.classList.remove('active');
                if (panel.id === `tab-${targetTab}`) {
                    panel.classList.add('active');
                }
            });
        });
    });
});