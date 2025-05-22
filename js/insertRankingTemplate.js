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
                renderSubmissions(data['mostRecentSubmissions'], $('#mostRecentSubmissionsContainer'));
            },
            error: function(xhr, status, error) {
                console.error(window.app.mostCitedFailedMessage, error);
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
                renderSubmissions(data['mostReadSubmissions'], $('#mostReadSubmissionsContainer'));
            },
            error: function(xhr, status, error) {
                console.error(window.app.mostCitedFailedMessage, error);
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
                renderSubmissions(data['mostCitedSubmissions'], $('#mostCitedSubmissionsContainer'));
            },
            error: function(xhr, status, error) {
                console.error(window.app.mostCitedFailedMessage, error);
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
                renderTrendingSubmissions(data['trendingSubmissions']);
            },
            error: function(xhr, status, error) {
                console.error(window.app.trendingFailedMessage, error);
                $('#trendingSubmissionsContainer').html(
                    `<div class="alert alert-danger">${window.app.trendingFailedMessage}</div>`
                );
            }
        });
        
        function renderSubmissions(submissions, container) {
            container.empty();
            if (!submissions || !Array.isArray(submissions) || submissions.length === 0) {
                container.html(`<p>${window.app.noPublicationsFoundMessage}</p>`);
                return;
            }
            
            submissions.forEach(function(submission) {
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
                
                container.append(articleItem);
                container.append('<hr>');
            });
        }

        function renderTrendingSubmissions(submissions) {
            const container = $('#trendingSubmissionsContainer');
            container.empty();
            if (!submissions || !Array.isArray(submissions) || submissions.length === 0) {
                container.html(`<p>${window.app.noPublicationsFoundMessage}</p>`);
                return;
            }
            
            submissions.forEach(function(submission) {
                const articleItem = $('<div class="article-item"></div>');
                
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

                if (submission.altmetricsScore) {
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
    });
})(jQuery);