/**
 * Handles the UI logic for the legacy WebP conversion process in the WordPress admin.
 * Manages popup display, progress donut chart, success counter, and disables the delete button during conversion.
 * 
 * @since 1.0.0
 * @package PoetryConvertToWebp
 * @author Pierre Hunout
 */

document.addEventListener('DOMContentLoaded', function () {
    // Get DOM elements for conversion and UI
    const openButton = document.getElementById('poetry-convert-to-webp-legacy');
    const popup = document.getElementById('poetry-convert-to-webp-progress-popup');
    const progressMessages = document.getElementById('poetry-convert-to-webp-progress-messages');
    const closeButton = document.getElementById('poetry-convert-to-webp-progress-close');
    const deleteButton = document.getElementById('poetry-convert-to-webp-delete-all'); // Add your button's ID here

    // Exit if any required element is missing
    if (!openButton || !popup || !progressMessages || !closeButton || !deleteButton) {
        return;
    }

    // State variables to track conversion process
    let isProcessing = false;
    let processStarted = false; // Track if the process has started
    const originalConvertLabel = openButton.textContent;

    /**
      * Handles click on the convert button.
      * Starts the conversion process or shows the popup if already running.
      */
    openButton.addEventListener('click', function (e) {
        e.preventDefault();
        removeNotice();

        if (isProcessing || processStarted) {
            // If process is ongoing, show popup and update button label
            openButton.textContent = 'Show conversion progress';
            popup.classList.add('is-active');
            return;
        }

        // Start new conversion process
        popup.classList.add('is-active');
        progressMessages.innerHTML = '';
        isProcessing = true;
        processStarted = true;
        openButton.textContent = 'Show conversion progress';

        // Disable the delete button
        if (deleteButton) {
            deleteButton.disabled = true;
        }

        startLegacyConversion();
    });

    /**
     * Handles click on the close button.
     * Hides the popup and restores button label if process is finished.
     */
    closeButton.addEventListener('click', function () {
        popup.classList.remove('is-active');

        // Restore original label if process is finished
        if (!isProcessing && !processStarted) {
            openButton.textContent = originalConvertLabel;
        }
    });

    /**
     * Starts the legacy conversion process.
     * Fetches attachments, updates progress, and processes each image via AJAX.
     */
    const startLegacyConversion = () => {
        fetchAttachments(function (attachments) {
            if (!attachments || attachments.length === 0) {
                // No images found, reset state and UI
                appendMessage('<li class="poetry-convert-to-webp__message poetry-convert-to-webp__message--nofiles">No images found.</li>', true);

                isProcessing = false;
                processStarted = false;

                // Re-enable the delete button
                if (deleteButton) {
                    deleteButton.disabled = false;
                }

                openButton.textContent = originalConvertLabel;
                return;
            }

            // Get donut chart canvas and create counter element below it
            const donut = document.getElementById('poetry-convert-to-webp-progress-donut');
            let counter = document.getElementById('poetry-convert-to-webp-progress-counter');

            if (!counter) {
                counter = document.createElement('div');
                counter.id = 'poetry-convert-to-webp-progress-counter';
                counter.className = 'poetry-convert-to-webp__counter';
                donut.parentNode.insertBefore(counter, donut.nextSibling);
            }

            let current = 0;
            let successCount = 0;

            /**
             * Updates the donut chart and success counter.
             *
             * @param {number} current - Number of processed images.
             * @param {number} total - Total number of images.
             */
            const updateProgress = (current, total) => {
                const percent = total > 0 ? current / total : 0;
                drawDonutChart(donut, percent);
                counter.textContent = `${successCount} / ${total}`;
            }

            /**
             * Processes the next image in the attachments list via AJAX.
             * Updates messages and progress after each conversion.
             */
            const processNext = () => {
                if (current >= attachments.length) {
                    // All images processed, reset state and UI
                    updateProgress(attachments.length, attachments.length);
                    appendMessage('<li class="poetry-convert-to-webp__message poetry-convert-to-webp__message--finished">Conversion finished!</li>', true);
                    isProcessing = false;
                    processStarted = false;

                    // Disable the delete button
                    if (deleteButton) {
                        deleteButton.disabled = false;
                    }

                    openButton.textContent = originalConvertLabel;
                    return;
                }

                const id = attachments[current];

                jQuery.post(ajaxurl, {
                    action: 'convert',
                    attachment_id: id,
                    _ajax_nonce: PoetryConvertToWebp.nonce
                }, function (response) {
                    // Build message classes from PHP response
                    let classes = 'poetry-convert-to-webp__message';

                    if (response.data.classes && Array.isArray(response.data.classes)) {
                        classes += ' poetry-convert-to-webp__message--' + response.data.classes.join(' ');
                    }

                    appendMessage(`<li class="${classes}">${response.data.message}</li>`);

                    // Increment success counter if conversion was successful
                    if (response.data.classes && response.data.classes.includes('success')) {
                        successCount++;
                    }

                    current++;
                    updateProgress(current, attachments.length);
                    processNext();
                }).fail(function () {
                    appendMessage('<li class="poetry-convert-to-webp__message poetry-convert-to-webp__message--error">An error occurred during conversion.</li>', true);
                    isProcessing = false;
                    processStarted = false;

                    if (deleteButton) {
                        deleteButton.disabled = false;
                    }

                    openButton.textContent = originalConvertLabel;
                });
            }

            // Initial display
            updateProgress(current, attachments.length);
            processNext();
        });
    }

    // Fragment for batching messages
    let messageFragment = document.createDocumentFragment();

    /**
     * Appends a message to the fragment and flushes to DOM every 10 items or at the end.
     *
     * @param {string} html - Message HTML to append.
     * @param {boolean} flush - Whether to flush the fragment to DOM.
     */
    const appendMessage = (html, flush = false) => {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        messageFragment.appendChild(temp.firstChild);

        // Flush every 10 messages or when requested
        if (flush || messageFragment.childNodes.length >= 10) {
            progressMessages.appendChild(messageFragment);
            messageFragment = document.createDocumentFragment();
        }
    };

    /**
    * Fetches the list of image attachments to convert via AJAX.
    *
    * @param {function} callback - Function to call with the attachments array.
    */
    const fetchAttachments = (callback) => {
        jQuery.post(ajaxurl, {
            action: 'get_attachments',
            _ajax_nonce: PoetryConvertToWebp.nonce
        }, function (response) {
            callback(response.data.attachments);
        }).fail(function () {
            // Handle AJAX failure
            appendMessage('<li class="poetry-convert-to-webp__message poetry-convert-to-webp__message--error">Failed to fetch attachments. Please try again.</li>', true);
            isProcessing = false;
            processStarted = false;

            if (deleteButton) {
                deleteButton.disabled = false;
            }

            openButton.textContent = originalConvertLabel;
        });
    }

    /**
     * Draws the donut chart showing conversion progress.
     *
     * @param {HTMLCanvasElement} canvas - The canvas element.
     * @param {number} percent - Progress percentage (0 to 1).
     */
    const drawDonutChart = (canvas, percent) => {
        const context = canvas.getContext('2d');
        const size = canvas.width;
        const lineWidth = 18;
        const radius = (size - lineWidth) / 2;
        context.clearRect(0, 0, size, size);

        // Draw background circle
        context.beginPath();
        context.arc(size / 2, size / 2, radius, 0, 2 * Math.PI);
        context.strokeStyle = '#eee';
        context.lineWidth = lineWidth;
        context.stroke();

        // Draw progress arc
        context.beginPath();
        context.arc(size / 2, size / 2, radius, -0.5 * Math.PI, (2 * Math.PI * percent) - 0.5 * Math.PI);
        context.strokeStyle = '#007cba';
        context.lineWidth = lineWidth;
        context.stroke();

        // Draw percentage text
        context.font = 'bold 22px Arial';
        context.fillStyle = '#333';
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.fillText(Math.round(percent * 100) + '%', size / 2, size / 2);
    }
});