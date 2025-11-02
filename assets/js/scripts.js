/**
 * Handles the UI logic for the WebP comparison tool in the WordPress admin.
 *
 * @package PoetryConvertToWebp
 * @since 1.0.0
 */

// Get the comparison container and range slider elements
const container = document.getElementById('comparison-container');
const range = document.getElementById('comparison-range');

/**
 * Updates the position of the comparison slider.
 *
 * @param {Event} e - The input event from the range slider.
 * @return {void}
 */
range.addEventListener('input', function (e) {
    container.style.setProperty('--position', e.target.value + '%');
});

/**
 * Initializes the media frame and handles image selection for comparison.
 *
 * @since 1.0.0
 */
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('comparison-button');

    let frame;

    /**
     * Handles the click event for the image selection button.
     * Opens the WordPress media frame and processes the selected image.
     *
     * @param {Event} e - The click event.
     * @return {void}
     */
    button.addEventListener('click', function (e) {
        e.preventDefault();
        removeNotice();

        // Reuse the frame if already created
        if (frame) {
            frame.open();
            return;
        }

        // Create the WordPress media frame
        frame = wp.media();
        frame.on('select', function () {
            try {
                // Get the selected attachment object
                const attachment = frame.state().get('selection').first().toJSON();

                // Validate the selected attachment
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

                // Destructure attachment properties
                const {
                    filesizeHumanReadable: originalFilesize,
                    height,
                    url,
                    width,
                } = attachment;

                // Build the WebP image URL
                const webpUrl = url.replace(/\.(jpe?g|png|gif)$/i, '.webp');

                // Check if the WebP image exists and update the UI
                checkWebPCreated(webpUrl, function () {
                    container.style.display = 'block';
                    container.style.aspectRatio = width / height;
                    original.src = url;
                    webp.src = webpUrl;

                    // Update size information
                    originalSize.textContent = originalFilesize;
                    webpSize.textContent = 'Loading...';

                    // Fetch the WebP file size
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
                    // Hide comparison UI if WebP does not exist
                    container.style.display = 'none';

                    original.src = '';
                    webp.src = '';
                    originalSize.textContent = '';
                    webpSize.textContent = '';

                    const message = 'WebP version does not exist for this image. Please convert your old images.';
                    displayNotice(message);
                });
            } catch ({ message }) {
                // Handle errors and display notice
                container.style.display = 'none';
                removeNotice();
                displayNotice(message);
                return;
            }
        });
        frame.open();
    });
});

/**
 * Displays a notice with the provided message.
 * Creates a new div element, sets its class and content, and appends it to the parent element of "poetry-convert-to-webp".
 *
 * @param {string} message - The message to display in the notice.
 * @return {void}
 */
const displayNotice = (message) => {
    const notice = document.createElement('div');
    notice.className = `notice is-dismissible error poetry-convert-to-webp__notice`;

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

    const grid = document.getElementById('poetry-convert-to-webp-grid');

    if (grid && grid.parentNode) {
        grid.parentNode.insertBefore(notice, grid);
    }
}

/**
 * Removes the notice element from the DOM.
 * Selects the notice element with the class 'poetry-convert-to-webp__notice' and removes it if it exists.
 *
 * @returns {void}
 */
const removeNotice = () => {
    const notice = document.querySelector('.poetry-convert-to-webp__notice');
    if (notice) {
        notice.remove();
    }
};

/**
 * Checks if the WebP image format is available.
 * Creates a new Image object, sets its onload and onerror handlers.
 * If the image loads successfully, it calls the success callback, otherwise it calls the error callback.
 *
 * @param {string} src - The source URL of the image to check.
 * @param {function} success - The callback function to call if the image loads successfully.
 * @param {function} error - The callback function to call if the image fails to load.
 * @returns {void}
 * @example
 * checkWebPCreated('path/to/image.webp', () => {
 *     console.log('WebP image is available.');
 * }, () => {
 *     console.error('WebP image is not available.');
 * });
 */
const checkWebPCreated = (src, success, error) => {
    const image = new Image();
    image.onload = () => success();
    image.onerror = () => error();
    image.src = src;
};
