(function($) {
    'use strict';

    $(document).ready(function() {
        const rankingTabsDiv = document.querySelector('.rankingTabs');
        
        if (rankingTabsDiv && window.app && window.app.rankingTemplate) {
            rankingTabsDiv.innerHTML = window.app.rankingTemplate;
            document.querySelectorAll('.nav-tabs a[data-toggle="tab"]')
            .forEach(function(tabLink){
                tabLink.addEventListener('click', function(e){
                e.preventDefault();
                document.querySelectorAll('.nav-tabs li').forEach(li=> li.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(pane=> pane.classList.remove('active'));
                this.parentElement.classList.add('active');
                document.querySelector(this.getAttribute('href')).classList.add('active');
                });
            });
        }
    });
})(jQuery);