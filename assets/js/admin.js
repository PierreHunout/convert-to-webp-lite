document.addEventListener('DOMContentLoaded', function () {
    // Quality sync
    const qualityInput = document.getElementById('webp_quality');
    const qualitySlider = document.getElementById('webp_quality_slider');
    if (qualityInput && qualitySlider) {
        qualityInput.addEventListener('input', function () {
            qualitySlider.value = qualityInput.value;
        });
        qualitySlider.addEventListener('input', function () {
            qualityInput.value = qualitySlider.value;
        });
    }
    // Image comparison
    let ajaxurl = typeof window.ajaxurl !== 'undefined' ? window.ajaxurl : '/wp-admin/admin-ajax.php';
    fetch(ajaxurl + '?action=wpctw_get_sample_image')
        .then(r => r.json())
        .then(data => {
            if (data.original && data.webp) {
                document.getElementById('original-img').src = data.original;
                document.getElementById('webp-img').src = data.webp;
            }
        });
    const slider = document.getElementById('compare-slider');
    const webpImg = document.getElementById('webp-img');
    if (slider && webpImg) {
        slider.addEventListener('input', function () {
            webpImg.style.clipPath = 'inset(0 ' + (100 - slider.value) + '% 0 0)';
        });
    }
});