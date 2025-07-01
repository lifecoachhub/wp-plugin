/**
 * External Connector Script
 *
 * This script ensures that all URLs within the iframe application
 * maintain the required authentication parameters.
 */
(function() {
    'use strict';
    
    // Only run in iframe context
    if (window.self === window.top) {
        return;
    }
    
    console.log('Life Coach Hub External Connector initialized');
    
    // Configuration
    let config = {
        apiKey: '',
        source: 'external_connection',
        embedded: '1',
        wordpressOrigin: '*'
    };

    // Function to extract URL parameters
    function getUrlParams() {
        const params = {};
        window.location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(str, key, value) {
            params[key] = decodeURIComponent(value);
        });
        return params;
    }

    // Extract API key from current URL if available
    const urlParams = getUrlParams();
    if (urlParams.api_key) {
        config.apiKey = urlParams.api_key;
    }

    // Store original navigation functions
    const originalPushState = history.pushState;
    const originalReplaceState = history.replaceState;
    const originalOpen = XMLHttpRequest.prototype.open;
    const originalFetch = window.fetch;

    // Function to ensure URL has required parameters
    function ensureUrlHasParams(url) {
        if (!config.apiKey) {
            return url; // If no API key, don't modify
        }

        try {
            const urlObj = new URL(url, window.location.origin);
            
            // Only add params for same-origin URLs
            if (urlObj.origin === window.location.origin) {
                // Add parameters if not already present
                if (!urlObj.searchParams.has('api_key')) {
                    urlObj.searchParams.set('api_key', config.apiKey);
                }
                if (!urlObj.searchParams.has('source')) {
                    urlObj.searchParams.set('source', config.source);
                }
                if (!urlObj.searchParams.has('embedded')) {
                    urlObj.searchParams.set('embedded', config.embedded);
                }
                return urlObj.toString();
            }
        } catch (e) {
            // For relative URLs, create a full URL and then process
            if (url.startsWith('/') || !url.includes('://')) {
                return ensureUrlHasParams(new URL(url, window.location.origin).toString());
            }
        }

        return url;
    }

    // Override history.pushState
    history.pushState = function(state, title, url) {
        if (url) {
            url = ensureUrlHasParams(url);
        }
        const result = originalPushState.call(this, state, title, url);
        notifyParentOfUrlChange();
        return result;
    };

    // Override history.replaceState
    history.replaceState = function(state, title, url) {
        if (url) {
            url = ensureUrlHasParams(url);
        }
        const result = originalReplaceState.call(this, state, title, url);
        notifyParentOfUrlChange();
        return result;
    };

    // Override XMLHttpRequest.open to add auth headers
    XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
        const modifiedUrl = ensureUrlHasParams(url);
        return originalOpen.call(this, method, modifiedUrl, async === undefined ? true : async, user, password);
    };

    // Override fetch API
    window.fetch = function(resource, options) {
        if (typeof resource === 'string') {
            resource = ensureUrlHasParams(resource);
        } else if (resource instanceof Request) {
            resource = new Request(
                ensureUrlHasParams(resource.url),
                resource
            );
        }
        return originalFetch.call(this, resource, options);
    };

    // Listen for URL changes via popstate event
    window.addEventListener('popstate', function() {
        notifyParentOfUrlChange();

        // If the URL doesn't have our parameters, add them
        if (!window.location.search.includes('api_key=') && config.apiKey) {
            const newUrl = ensureUrlHasParams(window.location.href);
            history.replaceState(null, '', newUrl);
        }
    });

    // Notify parent window that we're ready
    function sendReadyMessage() {
        window.parent.postMessage({
            type: 'ready',
            url: window.location.href,
            path: window.location.pathname + window.location.search + window.location.hash
        }, config.wordpressOrigin);
    }

    // Notify parent window of URL changes
    function notifyParentOfUrlChange() {
        window.parent.postMessage({
            type: 'location_change',
            url: window.location.href,
            path: window.location.pathname + window.location.search + window.location.hash
        }, config.wordpressOrigin);
    }

    // Intercept all link clicks
    document.addEventListener('click', function(event) {
        // Find closest anchor tag if the clicked element is not an anchor
        let anchor = event.target.closest('a');

        if (anchor) {
            const href = anchor.getAttribute('href');
            
            // Skip special links
            if (!href || href === '#' || href.startsWith('javascript:') ||
                href.startsWith('mailto:') || href.startsWith('tel:')) {
                return;
            }

            // Determine if this is an internal link
            let isInternal = false;

            // Check if it's a relative URL or on the same domain
            if (href.startsWith('/') || href.startsWith('./') || href.startsWith('../') ||
                !href.includes('://')) {
                isInternal = true;
            } else {
                try {
                    const url = new URL(href);
                    isInternal = url.hostname === window.location.hostname;
                } catch(e) {
                    // If URL parsing fails, assume it's internal
                    isInternal = true;
                }
            }

            if (isInternal) {
                // For internal links, update href with authentication parameters
                const newHref = ensureUrlHasParams(href);

                // Update the anchor's href attribute
                if (newHref !== href) {
                    anchor.setAttribute('href', newHref);
                }

                // Notify parent of navigation intent
                window.parent.postMessage({
                    type: 'navigate',
                    path: newHref
                }, config.wordpressOrigin);
            }
        }
    }, true);

    // Process all existing links on the page
    function processExistingLinks() {
        const links = document.querySelectorAll('a');
        links.forEach(link => {
            const href = link.getAttribute('href');

            // Skip special links
            if (!href || href === '#' || href.startsWith('javascript:') ||
                href.startsWith('mailto:') || href.startsWith('tel:')) {
                return;
            }

            // Determine if this is an internal link
            let isInternal = false;

            // Check if it's a relative URL or on the same domain
            if (href.startsWith('/') || href.startsWith('./') || href.startsWith('../') ||
                !href.includes('://')) {
                isInternal = true;
            } else {
                try {
                    const url = new URL(href);
                    isInternal = url.hostname === window.location.hostname;
                } catch(e) {
                    isInternal = true;
                }
            }

            if (isInternal) {
                // For internal links, update href with authentication parameters
                const newHref = ensureUrlHasParams(href);

                // Update the anchor's href attribute
                if (newHref !== href) {
                    link.setAttribute('href', newHref);
                }
            }
        });
    }

    // Process links on page load and on DOM changes
    function setupLinkProcessor() {
        // Process links on page load
        processExistingLinks();

        // Set up MutationObserver to process links on DOM changes
        const observer = new MutationObserver(mutations => {
            processExistingLinks();
        });

        // Start observing the document
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Handle form submissions to ensure parameters are included
    function setupFormProcessor() {
        document.addEventListener('submit', function(event) {
            const form = event.target;

            // Only process GET forms (POST forms handle differently)
            if (form.method.toLowerCase() === 'get') {
                // Add our parameters if they don't exist
                if (!form.querySelector('input[name="api_key"]') && config.apiKey) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'api_key';
                    input.value = config.apiKey;
                    form.appendChild(input);
                }

                if (!form.querySelector('input[name="source"]')) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'source';
                    input.value = config.source;
                    form.appendChild(input);
                }

                if (!form.querySelector('input[name="embedded"]')) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'embedded';
                    input.value = config.embedded;
                    form.appendChild(input);
                }
            }
        }, true);
    }

    // Listen for messages from parent window
    window.addEventListener('message', function(event) {
        // Be more flexible with origin for development
        if (event.data && event.data.type === 'config') {
            console.log('Received config from WordPress:', event.data);

            // Store configuration
            if (event.data.apiKey) {
                config.apiKey = event.data.apiKey;
            }
            if (event.data.source) {
                config.source = event.data.source;
            }
            if (event.data.embedded) {
                config.embedded = event.data.embedded;
            }
            if (event.data.wordpressOrigin) {
                config.wordpressOrigin = event.data.wordpressOrigin;
            }

            // Save configuration in window for other scripts
            window.wpConfig = config;

            // Trigger an event that the app can listen for
            const configEvent = new CustomEvent('wp_external_config', {
                detail: config
            });
            window.dispatchEvent(configEvent);

            // Now that we have the config, ensure current URL has parameters
            if (!window.location.search.includes('api_key=') && config.apiKey) {
                const currentUrl = window.location.href;
                const newUrl = ensureUrlHasParams(currentUrl);

                if (newUrl !== currentUrl) {
                    console.log('Updating URL with authentication parameters');
                    history.replaceState(null, '', newUrl);
                }
            }

            // Process all links on the page
            setupLinkProcessor();

            // Set up form processing
            setupFormProcessor();
        }
    });

    // Helper functions exposed globally
    window.wpHelper = {
        // Navigate back to WordPress
        navigateToWordPress: function(path) {
            window.parent.postMessage({
                type: 'wp_navigate',
                path: path
            }, config.wordpressOrigin);
        },

        // Refresh authentication
        refreshAuth: function() {
            window.parent.postMessage({
                type: 'refresh_auth'
            }, config.wordpressOrigin);
        },

        // Function to manually add parameters to a URL
        addAuthParams: function(url) {
            return ensureUrlHasParams(url);
        }
    };

    // Send ready message once DOM is fully loaded
    if (document.readyState === 'complete') {
        sendReadyMessage();
    } else {
        window.addEventListener('load', sendReadyMessage);
    }

    // Also send notification of initial URL
    notifyParentOfUrlChange();

    // Extract API key from URL and use immediately
    if (config.apiKey) {
        console.log('Found API key in URL, processing page immediately');
        setupLinkProcessor();
        setupFormProcessor();
    }
})();
