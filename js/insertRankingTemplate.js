(function($) {
    'use strict';

    $(document).ready(function() {
        const rankingTabsDiv = document.querySelector('.rankingTabs');

        if (rankingTabsDiv && window.app && window.app.rankingTemplate) {
            moveToConfiguredPosition(rankingTabsDiv);
            rankingTabsDiv.innerHTML = window.app.rankingTemplate;
            rankingTabsDiv.querySelectorAll('.nav-tabs a[data-toggle="tab"]')
            .forEach(function(tabLink){
                tabLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    rankingTabsDiv.querySelectorAll('.nav-tabs li').forEach(li=> li.classList.remove('active'));
                    rankingTabsDiv.querySelectorAll('.tab-pane').forEach(pane=> pane.classList.remove('active'));
                    this.parentElement.classList.add('active');
                    rankingTabsDiv.querySelector(this.getAttribute('href')).classList.add('active');
                });
            });
        }

        $.ajax({
            url: `${window.app.fgvOpenRankApiBaseUrl}/mostRecent`,
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
            url: `${window.app.fgvOpenRankApiBaseUrl}/mostRead`,
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
            url: `${window.app.fgvOpenRankApiBaseUrl}/mostCitedSubmissions`,
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
            url: `${window.app.fgvOpenRankApiBaseUrl}/trendingSubmissions`,
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

        function moveToConfiguredPosition(container) {
        const position = window.app.displayPosition;
        const parent = container.parentElement;

        if (!parent || !position || position === 'additionalContent' || position === 'top') {
            return;
        }

        if (position === 'bottom') {
            parent.appendChild(container);
            return;
        }

        if (position === 'afterSection') {
            const siblings = Array.prototype.slice.call(parent.children)
                .filter(function(element) {
                    return element !== container;
                });
            const section = parseInt(window.app.displayPositionSection, 10) || 1;

            parent.insertBefore(container, siblings[section] || null);
        }
    }

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
                container.fadeOut(150, function() {
                    container.empty();
                    const start = (page - 1) * itemsPerPage;
                    const end = Math.min(start + itemsPerPage, submissions.length);
                    const pageSubmissions = submissions.slice(start, end);

                    pageSubmissions.forEach(function(submission) {
                        const articleItem = $('<div class="article-item"></div>');

                        if (submission.coverImage) {
                            const coverImage = $('<img>')
                                .attr('src', submission.coverImage.coverImageUrl)
                                .attr('alt', submission.altText || '');
                            articleItem.append(
                                $('<div class="article-cover"></div>').append(
                                    $('<div class="item cover_image"></div>').append(
                                        $('<div class="sub_item"></div>').append(coverImage)
                                    )
                                )
                            );
                        }

                        const titleLink = $('<a></a>')
                            .attr('href', submission.submissionUrl)
                            .append(sanitizeTitle(localize(submission.title)));
                        const details = $('<div class="article-details"></div>')
                            .append($('<h3></h3>').append(titleLink))
                            .append(
                                $('<div class="article-authors"></div>').append(
                                    $('<div></div>').text(submission.authorString || '')
                                )
                            )
                            .append(
                                $('<div class="article-date-published"></div>').append(
                                    $('<p></p>').text(localizeDatePublished(submission.datePublished))
                                )
                            );
                        articleItem.append(details);

                        if (tabId === 'trending') {
                            articleItem.append(
                                "<script type='text/javascript' src='https://d1bxh8uas1mnw7.cloudfront.net/assets/embed.js'></script>"
                            );
                            articleItem.append(
                                $('<div class="article-cover"></div>').append(
                                    $('<div class="altmetric-embed" data-badge-type="donut"></div>')
                                        .attr('data-doi', submission.doi || '')
                                )
                            );
                        }

                        container.append(articleItem);
                        container.append('<hr>');
                    });

                    container.fadeIn(150);
                });
            }

            function renderPagination() {
                if (totalPages <= 1) {
                    paginationContainer.empty();
                    return;
                }

                paginationContainer.empty();
                const paginationUl = $('<ul></ul>');

                const prevLi = $('<li class="page-item"></li>');
                const prevLink = $(`<a class="page-link" href="#" aria-label="${window.app.previousPageLabel || 'Previous'}">
                    ${window.app.previousPageLabel || 'Previous'}
                </a>`);

                if (currentPage > 1) {
                    prevLink.on('click', function(e) {
                        e.preventDefault();
                        currentPage--;
                        showPage(currentPage);
                        renderPagination();
                    });
                } else {
                    prevLi.addClass('disabled');
                    prevLink.css('pointer-events', 'none');
                }

                prevLi.append(prevLink);
                paginationUl.append(prevLi);

                for (let i = 1; i <= totalPages; i++) {
                    const pageLi = $(`<li class="page-item ${i === currentPage ? 'active' : ''}"></li>`);
                    const pageLink = $(`<a class="page-link" href="#" aria-label="Page ${i}">${i}</a>`);

                    if (i === currentPage) {
                        pageLink.attr('aria-current', 'page');
                    } else {
                        pageLink.on('click', function(e) {
                            e.preventDefault();
                            currentPage = i;
                            showPage(currentPage);
                            renderPagination();
                        });
                    }

                    pageLi.append(pageLink);
                    paginationUl.append(pageLi);
                }

                const nextLi = $('<li class="page-item"></li>');
                const nextLink = $(`<a class="page-link" href="#" aria-label="${window.app.nextPageLabel || 'Next'}">
                    ${window.app.nextPageLabel || 'Next'}
                </a>`);

                if (currentPage < totalPages) {
                    nextLink.on('click', function(e) {
                        e.preventDefault();
                        currentPage++;
                        showPage(currentPage);
                        renderPagination();
                    });
                } else {
                    nextLi.addClass('disabled');
                    nextLink.css('pointer-events', 'none');
                }

                nextLi.append(nextLink);
                paginationUl.append(nextLi);

                paginationContainer.append(paginationUl);
            }

            showPage(currentPage);
            renderPagination();
        }

        // Titles may carry the inline formatting the core allows in titles
        // (allowed_title_html); everything else is kept as plain text.
        // DOMParser builds an inert document, so nothing in the title runs
        // or loads while it is parsed.
        function sanitizeTitle(title) {
            const allowedTags = ['B', 'STRONG', 'I', 'EM', 'U', 'SUP', 'SUB'];
            const parsed = new DOMParser().parseFromString(
                '<body>' + String(title || '') + '</body>',
                'text/html'
            );
            const fragment = document.createDocumentFragment();

            function copyAllowed(source, target) {
                source.childNodes.forEach(function(node) {
                    if (node.nodeType === Node.TEXT_NODE) {
                        target.appendChild(document.createTextNode(node.nodeValue));
                    } else if (node.nodeType === Node.ELEMENT_NODE) {
                        if (allowedTags.indexOf(node.tagName) !== -1) {
                            const element = document.createElement(node.tagName.toLowerCase());
                            copyAllowed(node, element);
                            target.appendChild(element);
                        } else if (node.tagName !== 'SCRIPT' && node.tagName !== 'STYLE') {
                            copyAllowed(node, target);
                        }
                    }
                });
            }

            copyAllowed(parsed.body, fragment);
            return fragment;
        }

        function localize(multilingualData) {
			if (!multilingualData) {
				return '';
			} else if (
				multilingualData.hasOwnProperty(window.app.currentLocale) &&
				multilingualData[window.app.currentLocale]
			) {
				return multilingualData[window.app.currentLocale];
			} else if (
				multilingualData.hasOwnProperty(window.app.primaryLocale) &&
				multilingualData[window.app.primaryLocale]
			) {
				return multilingualData[window.app.primaryLocale];
			}

			for (var key in multilingualData) {
				if (multilingualData[key]) {
					return multilingualData[key];
				}
			}

			return '';
		}

        function localizeDatePublished(dateString) {
            const date = moment.utc(dateString).toDate();
            let dateLocale = window.app.currentLocale !== undefined
                ? window.app.currentLocale.replace('_', '-')
                : window.app.currentLocale.replace('_', '-');
            let localizedDate = date.toLocaleDateString(dateLocale, {
				year: 'numeric',
				month: 'short',
				day: 'numeric',
                timeZone: 'UTC'
			});
            return window.app.publishedDateLocaleMessage.replace('{$datePublished}', localizedDate);
        }
    });
})(jQuery);