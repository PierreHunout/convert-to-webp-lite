const container = document.querySelector('.convert-to-webp__compare');
const range = document.querySelector('.convert-to-webp__range');

range.addEventListener('input', function (e) {
    container.style.setProperty('--position', e.target.value + '%');
});

document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('select-image-button');

    let frame;
    if (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            if (frame) {
                frame.open();
                return;
            }
            frame = wp.media({
                multiple: false
            });
            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();

                if (attachment.type === 'image') {
                    // Update comparison UI
                    const original = document.getElementById('comparison-original');
                    const webp = document.getElementById('comparison-webp');
                    const originalSize = document.getElementById('comparison-original-size');
                    const webpSize = document.getElementById('comparison-webp-size');
                    if (original && webp) {
                        const url = attachment.url;
                        const width = attachment.width;
                        const height = attachment.height;
                        const originalFilesize = attachment.filesizeHumanReadable;
                        const webpUrl = url.replace(/\.(jpe?g|png|gif)$/i, '.webp');

                        checkWebP(webpUrl, function () {
                            container.style.display = 'block';
                            container.style.aspectRatio = width / height;
                            original.src = url;
                            webp.src = webpUrl;

                            // Update size information
                            originalSize.textContent = originalFilesize;
                            webpSize.textContent = 'Loading...';

                            // Check WebP file size
                            fetch(webpUrl).then(response => {
                                if (response.ok) {
                                    const webpBytes = response.headers.get('Content-Length');
                                    const webpFilesize = (webpBytes / 1024).toFixed(2) + ' Ko';
                                    webpSize.textContent = webpFilesize;
                                } else {
                                    webpSize.textContent = 'Not available';
                                }
                            });
                        }, function () {
                            container.style.display = 'none';
                            window.alert("WebP version does not exist for this image. Please convert all previously uploaded images.");
                        });
                    }
                }
            });
            frame.open();
        });
    }

    /*
     * This function checks if the WebP image format is available.
     * It creates a new Image object, sets its onload and onerror handlers.
     * If the image loads successfully, it calls the is_true callback, otherwise it calls the is_false callback.
     */
    function checkWebP(src, is_true, is_false) {
        const image = new Image();
        image.onload = function () {
            is_true();
        };
        image.onerror = function () {
            is_false();
        };
        image.src = src;
    }
});
