(function() {
    const pages = {};

    document.addEventListener('DOMContentLoaded', function () {
        const wrappers = document.querySelectorAll('.single-posts-screen.ajax_load');
        for(const parentPostWrapper of wrappers) {
            attachNavigationClickListeners(parentPostWrapper);
        }
    });

    function setWrapperId(wrapper){
        const id = Math.round(Math.random() * 1000) + 1;
        wrapper.setAttribute('data-id', id);
        return id;
    }

    function attachNavigationClickListeners(wrapper){
        const paginationLinks = wrapper.querySelectorAll('.single-posts-paginate a.page-numbers');
        for (const link of paginationLinks) {
            link.addEventListener('click', handlePaginationLinkClick);
        }
    }

    function handlePaginationLinkClick(e){
        e.preventDefault();
        const link = e.target;
        const url = link.getAttribute('href');
        const parent = link.parentElement.parentElement.parentElement;
        loadPosts(url, parent);
    }

    function loadPosts(url, parentScreen){
        showPreloader(parentScreen);
        let currentPostWrapper = parentScreen.querySelector('.single-posts-block-wrapper.current');
        const parentScreenId = parentScreen.getAttribute('id');
        if(isPageLoaded(parentScreenId, url)){
            setTimeout(function(){
                // Hide previous page
                currentPostWrapper.classList.add('hidden');
                currentPostWrapper.classList.remove('current');
                /*
                 * Assign and show the new page.
                 */
                const pageId = pages[parentScreenId][url];
                const selector = ".single-posts-block-wrapper[data-id='" + pageId + "']";
                const nextParentPostWrapper = parentScreen.querySelector(selector);
                nextParentPostWrapper.classList.remove('hidden');
                nextParentPostWrapper.classList.add('current');
                hidePreloader(parentScreen);
            }, 200);
        } else {
            getPageDoc(url).then(function (pageDoc) {
                const postWrapperSelector = '#' + parentScreenId + ' > .single-posts-block-wrapper';
                const wrapper = pageDoc.querySelector(postWrapperSelector);
                if(!pages[parentScreenId]){
                    pages[parentScreenId] = {};
                }
                pages[parentScreenId][url] = setWrapperId(wrapper);

                currentPostWrapper.classList.add('hidden');
                currentPostWrapper.classList.remove('current');

                const preloader = parentScreen.querySelector('.single-posts-preloader');
                parentScreen.insertBefore(wrapper, preloader);
                attachNavigationClickListeners(wrapper);
                hidePreloader(parentScreen);
            });
        }
        //window.history.pushState({}, document.title, url);
    }

    function showPreloader(parent){
        const preloader = parent.querySelector('.single-posts-preloader');
        preloader.classList.remove('hidden');
    }

    function hidePreloader(parent){
        const preloader = parent.querySelector('.single-posts-preloader');
        preloader.classList.add('hidden');
    }

    function isPageLoaded(screenId, url){
        return pages.hasOwnProperty(screenId) && pages[screenId].hasOwnProperty(url);
    }

    function getPageDoc(url){
        return loadPage(url).then(pageContent => {
            const loadedDoc = document.implementation.createHTMLDocument();
            loadedDoc.documentElement.innerHTML = pageContent;
            return loadedDoc;
        });
    }

    function loadPage(url){
        return fetch(url).then(response => response.text());
    }
})();