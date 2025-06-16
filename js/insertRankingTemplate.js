(function($) {
    'use strict';

    $(document).ready(function() {
        const rankingTabsDiv = document.querySelector('.rankingTabs');
        
        if (rankingTabsDiv && window.app && window.app.rankingTemplate) {
            rankingTabsDiv.innerHTML = window.app.rankingTemplate;
            document.querySelectorAll('.nav-tabs a[data-toggle="tab"]')
            .forEach(function(tabLink){
                tabLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.nav-tabs li').forEach(li=> li.classList.remove('active'));
                    document.querySelectorAll('.tab-pane').forEach(pane=> pane.classList.remove('active'));
                    this.parentElement.classList.add('active');
                    document.querySelector(this.getAttribute('href')).classList.add('active');
                });
            });
        }

        $.ajax({
            url: `${window.app.rankingPluginApiBaseUrl}/mostRecent`,
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                renderSubmissions(
                    data['mostRecentSubmissions'], 
                    $('#mostRecentSubmissionsContainer'), 
                    'mostRecent'
                );
            },
            error: function(xhr, status, error) {
                $('#mostRecentSubmissionsContainer').html(
                    `<div class="alert alert-danger">${window.app.mostRecentFailedMessage}</div>`
                );
            }
        });

        $.ajax({
            url: `${window.app.rankingPluginApiBaseUrl}/mostRead`,
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                renderSubmissions(
                    data['mostReadSubmissions'], 
                    $('#mostReadSubmissionsContainer'), 
                    'mostRead'
                );
            },
            error: function(xhr, status, error) {
                $('#mostReadSubmissionsContainer').html(
                    `<div class="alert alert-danger">${window.app.mostReadFailedMessage}</div>`
                );
            }
        });
        
        $.ajax({
            url: `${window.app.rankingPluginApiBaseUrl}/mostCitedSubmissions`,
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                renderSubmissions(
                    data['mostCitedSubmissions'], 
                    $('#mostCitedSubmissionsContainer'), 
                    'mostCited'
                );
            },
            error: function(xhr, status, error) {
                $('#mostCitedSubmissionsContainer').html(
                    `<div class="alert alert-danger">${window.app.mostCitedFailedMessage}</div>`
                );
            }
        });

        $.ajax({
            url: `${window.app.rankingPluginApiBaseUrl}/trendingSubmissions`,
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                renderSubmissions(
                    data['trendingSubmissions'], 
                    $('#trendingSubmissionsContainer'), 
                    'trending'
                );
            },
            error: function(xhr, status, error) {
                $('#trendingSubmissionsContainer').html(
                    `<div class="alert alert-danger">${window.app.trendingFailedMessage}</div>`
                );
            }
        });
        
        function renderSubmissions(submissions, container, tabId = null) {
            container.empty();
            if (!submissions || !Array.isArray(submissions) || submissions.length === 0) {
                container.html(`<p>${window.app.noPublicationsFoundMessage}</p>`);
                return;
            }
            
            let itemsPerPage = window.app.itemsPerPage || 4;
            
            if (tabId && window.app.tabSettings && window.app.tabSettings[tabId]) {
                const tabSettings = window.app.tabSettings[tabId];
                itemsPerPage = tabSettings.itemsPerPage || itemsPerPage;
            }
            
            const totalPages = Math.ceil(submissions.length / itemsPerPage);
            const containerId = container.attr('id');
            const baseId      = containerId.replace(/Container$/, '');
            const paginationContainer = $(`#${baseId}Pagination`);
            
            let currentPage = 1;
            
            function showPage(page) {
                container.empty();
                const start = (page - 1) * itemsPerPage;
                const end = Math.min(start + itemsPerPage, submissions.length);
                const pageSubmissions = submissions.slice(start, end);
                
                pageSubmissions.forEach(function(submission) {
                    const articleItem = $('<div class="article-item"></div>');
                    
                    if (submission.coverImage) {
                        const coverHtml = `
                            <div class="article-cover">
                                <div class="item cover_image">
                                    <div class="sub_item">
                                        <img src="${submission.coverImage.coverImageUrl}" alt="${submission.altText || ''}">
                                    </div>
                                </div>
                            </div>
                        `;
                        articleItem.append(coverHtml);
                    }
                    
                    const detailsHtml = `
                        <div class="article-details">
                            <h3><a href="${submission.submissionUrl}">${submission.title}</a></h3>
                            <div class="article-authors">
                                <div>${submission.authorString}</div>
                            </div>
                            <div class="article-date-published">
                                <p>${submission.datePublishedLabel}</p>
                            </div>
                        </div>
                    `;
                    articleItem.append(detailsHtml);

                    if (tabId === 'trending' && submission.altmetricsScore) {
                        const altmetricsBadgeHtml = `
                            <script type='text/javascript' src='https://d1bxh8uas1mnw7.cloudfront.net/assets/embed.js'></script>
                            <div class="article-cover">
                                <div class='altmetric-embed' data-badge-type='donut' data-doi="${submission.doi}"></div>
                            </div>
                        `;
                        articleItem.append(altmetricsBadgeHtml);
                    }
                    
                    container.append(articleItem);
                    container.append('<hr>');
                });
            }
            
            function renderPagination() {
                if (totalPages <= 1) {
                    paginationContainer.empty();
                    return;
                }
                
                paginationContainer.empty();
                const paginationUl = $('<ul class="pagination"></ul>');
                
                if (currentPage > 1) {
                    const prevLi = $(`<li class="page-item">
                        <a class="page-link" href="#" aria-label="Previous">‹</a>
                    </li>`);
                    prevLi.find('a').on('click', function(e) {
                        e.preventDefault();
                        currentPage--;
                        showPage(currentPage);
                        renderPagination();
                    });
                    paginationUl.append(prevLi);
                }
                
                for (let i = 1; i <= totalPages; i++) {
                    const pageLi = $(`<li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#">${i}</a>
                    </li>`);
                    
                    pageLi.find('a').on('click', function(e) {
                        e.preventDefault();
                        currentPage = i;
                        showPage(currentPage);
                        renderPagination();
                    });
                    
                    paginationUl.append(pageLi);
                }
                
                if (currentPage < totalPages) {
                    const nextLi = $(`<li class="page-item">
                        <a class="page-link" href="#" aria-label="Next">›</a>
                    </li>`);
                    nextLi.find('a').on('click', function(e) {
                        e.preventDefault();
                        currentPage++;
                        showPage(currentPage);
                        renderPagination();
                    });
                    paginationUl.append(nextLi);
                }
                
                paginationContainer.append(paginationUl);
            }
            
            showPage(currentPage);
            renderPagination();
        }
    });
})(jQuery);