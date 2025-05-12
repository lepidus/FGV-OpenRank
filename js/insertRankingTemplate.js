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
            url: `${window.app.rankingPluginApiBaseUrl}/mostCitedSubmissions`,
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                renderMostCitedSubmissions(data['mostCitedSubmissions']);
            },
            error: function(xhr, status, error) {
                console.error('Erro ao buscar submissões mais citadas:', error);
                $('#mostCitedSubmissionsContainer').html(
                    '<div class="alert alert-danger">Erro ao carregar submissões mais citadas.</div>'
                );
            }
        });
        
        function renderMostCitedSubmissions(submissions) {
            const container = $('#mostCitedSubmissionsContainer');
            container.empty();
            if (!submissions || !Array.isArray(submissions) || submissions.length === 0) {
                container.html('<p>Nenhuma submissão citada encontrada.</p>');
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
    });
})(jQuery);