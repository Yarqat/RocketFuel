(function() {
    'use strict';

    var ATTR_SRC = 'data-rfc-src';
    var ATTR_SRCSET = 'data-rfc-srcset';
    var CLASS_LOADED = 'rfc-loaded';
    var CLASS_LOADING = 'rfc-loading';
    var SELECTOR = '[' + ATTR_SRC + '],[' + ATTR_SRCSET + ']';

    function loadElement(el) {
        if (el.classList.contains(CLASS_LOADED)) return;

        el.classList.add(CLASS_LOADING);

        var src = el.getAttribute(ATTR_SRC);
        var srcset = el.getAttribute(ATTR_SRCSET);

        if (el.tagName === 'IMG') {
            if (src) el.src = src;
            if (srcset) el.srcset = srcset;
            el.onload = function() { markLoaded(el); };
            el.onerror = function() { markLoaded(el); };
        } else if (el.tagName === 'IFRAME') {
            if (src) el.src = src;
            el.onload = function() { markLoaded(el); };
        } else if (el.tagName === 'VIDEO') {
            if (src) el.src = src;
            var sources = el.querySelectorAll('source[' + ATTR_SRC + ']');
            for (var i = 0; i < sources.length; i++) {
                sources[i].src = sources[i].getAttribute(ATTR_SRC);
                sources[i].removeAttribute(ATTR_SRC);
            }
            el.load();
            markLoaded(el);
        } else if (el.tagName === 'DIV' || el.tagName === 'SECTION') {
            if (src) el.style.backgroundImage = 'url(' + src + ')';
            markLoaded(el);
        }
    }

    function markLoaded(el) {
        el.classList.remove(CLASS_LOADING);
        el.classList.add(CLASS_LOADED);
        el.removeAttribute(ATTR_SRC);
        el.removeAttribute(ATTR_SRCSET);
    }

    function initObserver() {
        if (!('IntersectionObserver' in window)) {
            fallbackLoad();
            return;
        }

        var observer = new IntersectionObserver(function(entries) {
            for (var i = 0; i < entries.length; i++) {
                if (entries[i].isIntersecting) {
                    loadElement(entries[i].target);
                    observer.unobserve(entries[i].target);
                }
            }
        }, {
            rootMargin: '200px 0px',
            threshold: 0.01
        });

        var elements = document.querySelectorAll(SELECTOR);
        for (var i = 0; i < elements.length; i++) {
            observer.observe(elements[i]);
        }

        initMutationObserver(observer);
    }

    function initMutationObserver(intersectionObserver) {
        if (!('MutationObserver' in window)) return;

        var mutationObserver = new MutationObserver(function(mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var added = mutations[i].addedNodes;
                for (var j = 0; j < added.length; j++) {
                    if (added[j].nodeType !== 1) continue;

                    if (added[j].matches && added[j].matches(SELECTOR)) {
                        intersectionObserver.observe(added[j]);
                    }

                    var children = added[j].querySelectorAll ? added[j].querySelectorAll(SELECTOR) : [];
                    for (var k = 0; k < children.length; k++) {
                        intersectionObserver.observe(children[k]);
                    }
                }
            }
        });

        mutationObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    function fallbackLoad() {
        var elements = document.querySelectorAll(SELECTOR);
        for (var i = 0; i < elements.length; i++) {
            var el = elements[i];
            if (el.tagName === 'IMG') {
                el.setAttribute('loading', 'lazy');
            }
            loadElement(el);
        }
    }

    function initYouTube() {
        document.addEventListener('click', function(e) {
            var wrapper = e.target.closest('.rfc-youtube-wrapper');
            if (!wrapper) return;

            e.preventDefault();

            var videoId = wrapper.getAttribute('data-rfc-youtube');
            if (!videoId) return;

            var iframe = document.createElement('iframe');
            iframe.setAttribute('src', 'https://www.youtube.com/embed/' + videoId + '?autoplay=1');
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allowfullscreen', '');
            iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
            iframe.style.position = 'absolute';
            iframe.style.top = '0';
            iframe.style.left = '0';
            iframe.style.width = '100%';
            iframe.style.height = '100%';

            wrapper.innerHTML = '';
            wrapper.appendChild(iframe);
        });
    }

    function initVimeo() {
        document.addEventListener('click', function(e) {
            var wrapper = e.target.closest('.rfc-vimeo-wrapper');
            if (!wrapper) return;

            e.preventDefault();

            var videoId = wrapper.getAttribute('data-rfc-vimeo');
            if (!videoId) return;

            var iframe = document.createElement('iframe');
            iframe.setAttribute('src', 'https://player.vimeo.com/video/' + videoId + '?autoplay=1');
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allowfullscreen', '');
            iframe.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
            iframe.style.position = 'absolute';
            iframe.style.top = '0';
            iframe.style.left = '0';
            iframe.style.width = '100%';
            iframe.style.height = '100%';

            wrapper.innerHTML = '';
            wrapper.appendChild(iframe);
        });
    }

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    onReady(function() {
        initObserver();
        initYouTube();
        initVimeo();
    });

})();
