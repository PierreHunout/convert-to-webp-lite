const container = document.getElementById('comparison-container');
const range = document.getElementById('comparison-range');

range.addEventListener('input', function (e) {
    container.style.setProperty('--position', e.target.value + '%');
});

document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('comparison-button');

    let frame;

    button.addEventListener('click', function (e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media();
        frame.on('select', function () {

            try {
                const attachment = frame.state().get('selection').first().toJSON();

                if (!attachment || !attachment.url) {
                    throw new Error('No image selected or image URL is missing.');
                }

                if (attachment.type !== 'image') {
                    throw new Error('Selected file is not an image.');
                }

                if (attachment.mime === 'image/webp') {
                    throw new Error('Selected image is already in WebP format.');
                }

                if (attachment.mime !== 'image/jpeg' && attachment.mime !== 'image/png' && attachment.mime !== 'image/gif') {
                    throw new Error('Selected image is not in a supported format (JPEG, PNG, GIF).');
                }

                // Update comparison UI
                const original = document.getElementById('comparison-original');
                const webp = document.getElementById('comparison-webp');
                const originalSize = document.getElementById('comparison-original-size');
                const webpSize = document.getElementById('comparison-webp-size');

                if (!original || !webp) {
                    throw new Error('Comparison images not found.');
                }

                const {
                    filesizeHumanReadable: originalFilesize,
                    height,
                    url,
                    width,
                } = attachment;

                const webpUrl = url.replace(/\.(jpe?g|png|gif)$/i, '.webp');

                checkWebPCreated(webpUrl, function () {
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

                    original.src = '';
                    webp.src = '';
                    originalSize.textContent = '';
                    webpSize.textContent = '';

                    const message = 'WebP version does not exist for this image. Please convert all previously uploaded images.';
                    displayNotice(message);

                });
            } catch ({ message }) {
                container.style.display = 'none';
                displayNotice(message);
                return;
            }
        });
        frame.open();
    });

    /*
    * This function displays a notice with the provided message.
    * It creates a new div element, sets its class and content and appends it to the parent element of the "convert-to-webp" element.
    * @param {string} message - The message to display in the notice.
    * @return {void}
    */
    const displayNotice = (message) => {
        const notice = document.createElement('div');
        notice.className = `notice is-dismissible error convert-to-webp__notice`;

        const child = document.createElement('p');
        child.textContent = message;

        const dismiss = document.createElement('button');
        dismiss.className = 'notice-dismiss';
        dismiss.setAttribute('type', 'button');
        dismiss.addEventListener('click', function () {
            notice.remove();
        });
        dismiss.innerHTML = '<span class="screen-reader-text">Dismiss this notice.</span>';

        notice.appendChild(dismiss);
        notice.appendChild(child);

        const parent = document.getElementById("convert-to-webp").parentNode;
        parent.insertBefore(notice, parent.firstChild);
    }

    /*
     * This function checks if the WebP image format is available.
     * It creates a new Image object, sets its onload and onerror handlers.
     * If the image loads successfully, it calls the success callback, otherwise it calls the error callback.
     * @param {string} src - The source URL of the image to check.
     * @param {function} success - The callback function to call if the image loads successfully
     * @param {function} error - The callback function to call if the image fails to load
     * @returns {void}
    */
    const checkWebPCreated = (src, success, error) => {
        const image = new Image();
        image.onload = () => success();
        image.onerror = () => error();
        image.src = src;
    };
});
