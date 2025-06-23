/**
 * LifeCoachHub Admin JavaScript
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // If we have a clean iframe (connected state), no need for complex logic
        if ($('.lifecoachhub-clean-iframe-container').length) {
            return; // Exit early - clean iframe handles everything
        }
        
        // Check if we should load full page iframe
        if (lifecoachhubData.apiKey && window.location.hash === '#fullscreen') {
            loadFullPageIframe();
            return;
        }
        
        // Tab functionality
        $('.lifecoachhub-tab').on('click', function() {
            const tabId = $(this).data('tab');
            
            // Update active tab
            $('.lifecoachhub-tab').removeClass('active');
            $(this).addClass('active');
            
            // Show active content
            $('.lifecoachhub-tab-content').removeClass('active');
            $('#tab-' + tabId).addClass('active');
            
            // If switching to app tab, load the iframe
            if (tabId === 'app') {
                loadIframe();
            }
        });
        
        // Show embedded app button
        $('.show-embedded-app').on('click', function() {
            $('.lifecoachhub-tab[data-tab="app"]').trigger('click');
        });
        
        // Back to options button
        $('.back-to-options').on('click', function() {
            $('.lifecoachhub-tab[data-tab="options"]').trigger('click');
        });
        
        // Full screen button
        $('.load-fullscreen').on('click', function() {
            window.location.hash = '#fullscreen';
            location.reload();
        });
        
        // Function to load full page iframe
        function loadFullPageIframe() {
            const body = $('body');
            body.html(`
                <div class="lifecoachhub-fullscreen">
                    <div class="lifecoachhub-fullscreen-header">
                        <div class="lifecoachhub-header-left">
                            <h1>Life Coach Hub</h1>
                        </div>
                        <div class="lifecoachhub-header-right">
                            <button class="button exit-fullscreen">Exit Full Screen</button>
                        </div>
                    </div>
                    <iframe 
                        src="${lifecoachhubData.appUrl}" 
                        id="lifecoachhub-fullscreen-iframe"
                        class="lifecoachhub-fullscreen-iframe"
                        sandbox="allow-forms allow-scripts allow-same-origin allow-popups allow-top-navigation"
                    ></iframe>
                </div>
            `);
            
            // Exit fullscreen functionality
            $('.exit-fullscreen').on('click', function() {
                window.location.hash = '';
                location.reload();
            });
        }
        
        // Function to load the iframe
        function loadIframe() {
            const iframe = $('#lifecoachhub-iframe');
            const src = iframe.data('src');
            
            // Only load if not already loaded
            if (iframe.attr('src') === 'about:blank') {
                $('#loading-indicator').show();
                $('#iframe-error').hide();
                iframe.show();
                
                // Set a longer timeout for better detection
                const timeoutId = setTimeout(function() {
                    handleIframeError();
                }, 15000); // 15 seconds timeout
                
                // Try loading the iframe
                iframe.attr('src', src);
                
                // Handle iframe load event
                iframe.on('load', function() {
                    clearTimeout(timeoutId);
                    
                    // Wait a bit to ensure iframe content is loaded
                    setTimeout(function() {
                        $('#loading-indicator').hide();
                        
                        // Check if iframe loaded successfully by checking its height
                        const iframeDoc = iframe[0].contentDocument || iframe[0].contentWindow.document;
                        
                        // If we can access the iframe document, it means it loaded successfully
                        // If we can't access it due to CORS but the iframe is visible, it's also successful
                        if (iframe.is(':visible')) {
                            console.log('iframe loaded successfully');
                            // Add success button for full screen if connected
                            if (lifecoachhubData.apiKey) {
                                addFullScreenOption();
                            }
                        }
                    }, 1000); // Wait 1 second after load event
                });
                
                // Handle iframe error event
                iframe.on('error', function() {
                    clearTimeout(timeoutId);
                    handleIframeError();
                });
                
                // Additional check for iframe loading
                setTimeout(function() {
                    // If loading indicator is still showing after 5 seconds, consider it loaded
                    if ($('#loading-indicator').is(':visible')) {
                        $('#loading-indicator').hide();
                        if (lifecoachhubData.apiKey) {
                            addFullScreenOption();
                        }
                    }
                }, 5000);
            }
        }
        
        // Function to add full screen option
        function addFullScreenOption() {
            if (!$('.load-fullscreen').length) {
                const fullScreenButton = `
                    <div class="lifecoachhub-fullscreen-option">
                        <h3>Full Screen Mode</h3>
                        <p>For the best experience, open the application in full screen mode.</p>
                        <button class="button button-primary load-fullscreen">
                            Open in Full Screen
                        </button>
                    </div>
                `;
                $('#tab-app').append(fullScreenButton);
                
                $('.load-fullscreen').on('click', function() {
                    window.location.hash = '#fullscreen';
                    location.reload();
                });
            }
        }
        
        // Function to handle iframe errors
        function handleIframeError() {
            $('#loading-indicator').hide();
            $('#lifecoachhub-iframe').hide();
            $('#iframe-error').show();
        }
        
        // Handle URL synchronization for non-clean iframe scenarios
        if (window.location.search.includes('iframe_path=')) {
            const urlParams = new URLSearchParams(window.location.search);
            const iframePath = urlParams.get('iframe_path');
            if (iframePath) {
                // Switch to app tab and load specific path
                $('.lifecoachhub-tab[data-tab="app"]').trigger('click');
                setTimeout(function() {
                    const iframe = $('#lifecoachhub-iframe');
                    const baseUrl = iframe.data('src').split('?')[0];
                    const newSrc = baseUrl + iframePath + '?' + new URLSearchParams({
                        api_key: lifecoachhubData.apiKey,
                        source: 'external_connection',
                        embedded: '1'
                    }).toString();
                    iframe.attr('src', newSrc);
                }, 1000);
            }
        }
    });
})(jQuery);
