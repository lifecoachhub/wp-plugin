document.addEventListener('DOMContentLoaded', function () {
    const iframe = document.getElementById('lifecoachhub-clean-iframe');
    const loading = document.getElementById('iframe-loading');
    const fallback = document.getElementById('iframe-fallback');
    
    // Get data passed from PHP
    const config = window.lifecoachHubConfig || {};
    const baseUrl = config.baseUrl || '';
    const apiKey = config.apiKey || '';
    const adminPageUrl = config.adminPageUrl || '';
    const connectorScriptUrl = config.connectorScriptUrl || '';
    
    let loadTimeout;

    // Function to inject our external connector script into the iframe
    function injectExternalConnector() {
        try {
            const iframeWindow = iframe.contentWindow;
            const iframeDocument = iframeWindow.document;

            // Check if document is accessible (same-origin)
            if (iframeDocument) {
                // Create script element
                const script = iframeDocument.createElement('script');
                script.src = connectorScriptUrl + '?v=' + (new Date()).getTime();
                script.async = true;

                // Append to head or body
                (iframeDocument.head || iframeDocument.body).appendChild(script);

                // Also inject configuration directly
                setTimeout(function () {
                    iframeWindow.postMessage({
                        type: 'config',
                        apiKey: apiKey,
                        embedded: true,
                        source: 'external_connection',
                        wordpressOrigin: window.location.origin
                    }, '*');
                }, 500);
            }
        } catch (error) {
            console.warn('Could not inject script directly due to CORS, using postMessage instead', error);

            // Fall back to postMessage for cross-origin iframes
            setTimeout(function () {
                iframe.contentWindow.postMessage({
                    type: 'config',
                    apiKey: apiKey,
                    embedded: true,
                    source: 'external_connection',
                    wordpressOrigin: window.location.origin
                }, '*');
            }, 1000);
        }
    }

    // Function to update iframe URL with API key parameters
    function updateIframeUrl(path) {
        // First, determine if this is a full URL or just a path
        let url;
        if (path.startsWith('http')) {
            // It's a full URL
            url = new URL(path);
        } else {
            // It's just a path, prepend base URL
            url = new URL(path.startsWith('/') ? path : '/' + path, baseUrl);
        }

        // Always ensure our required parameters are set
        url.searchParams.set('api_key', apiKey);
        url.searchParams.set('source', 'external_connection');
        url.searchParams.set('embedded', '1');

        iframe.src = url.toString();

        // Update browser URL bar to reflect iframe navigation
        updateBrowserUrl(path);
    }

    // Function to update browser URL bar
    function updateBrowserUrl(path) {
        // Extract just the path without domain
        let pathOnly;
        if (path.startsWith('http')) {
            try {
                const pathUrl = new URL(path);
                pathOnly = pathUrl.pathname + pathUrl.search + pathUrl.hash;
            } catch (e) {
                pathOnly = path.startsWith('/') ? path : '/' + path;
            }
        } else {
            pathOnly = path.startsWith('/') ? path : '/' + path;
        }

        const newUrl = adminPageUrl + '&iframe_path=' + encodeURIComponent(pathOnly);

        // Update URL without page reload
        if (window.history && window.history.pushState) {
            window.history.pushState({ iframePath: pathOnly }, '', newUrl);
        }
    }

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function (event) {
        if (event.state && event.state.iframePath) {
            updateIframeUrl(event.state.iframePath);
        }
    });

    // Check if we have an iframe path in URL on page load
    const urlParams = new URLSearchParams(window.location.search);
    const initialPath = urlParams.get('iframe_path');
    if (initialPath) {
        setTimeout(function () {
            updateIframeUrl(initialPath);
        }, 1000);
    }

    // Set a timeout to show fallback if iframe doesn't load
    loadTimeout = setTimeout(function () {
        loading.style.display = 'none';
        fallback.style.display = 'block';
    }, 15000); // 15 second timeout

    iframe.addEventListener('load', function () {
        clearTimeout(loadTimeout);
        loading.style.display = 'none';
        iframe.style.display = 'block';

        // Inject our external connector script
        injectExternalConnector();
    });

    iframe.addEventListener('error', function () {
        clearTimeout(loadTimeout);
        loading.style.display = 'none';
        fallback.style.display = 'block';
    });

    // Listen for navigation messages from iframe
    window.addEventListener('message', function (event) {
        // Be more lenient with origin checking (if running on different ports/domains)
        if (event.origin.startsWith(baseUrl) || event.origin.includes('localhost')) {
            // Handle navigation requests from iframe
            if (event.data && event.data.type === 'navigate') {
                updateIframeUrl(event.data.path);
            }

            // Handle URL changes from iframe
            if (event.data && event.data.type === 'url_changed' || event.data.type === 'location_change') {
                updateBrowserUrl(event.data.path || event.data.url);
            }

            // Handle other iframe communications
            if (event.data && event.data.type === 'ready') {
                // Inject our external connector script again to ensure it's there
                setTimeout(injectExternalConnector, 500);
            }

            // Handle auth refresh requests
            if (event.data && event.data.type === 'refresh_auth') {
                // Reload iframe with fresh auth parameters
                updateIframeUrl(iframe.src);
            }
        }
    });
});