/* WPBlogTree Mikroinlägg – Compose modal */
(function () {
    'use strict';

    var overlay  = document.getElementById('mikro-modal');
    var trigger  = document.getElementById('mikro-compose-trigger');
    var cancelBtn = document.getElementById('mikro-modal-cancel');
    var submitBtn = document.getElementById('mikro-modal-submit');
    var draftBtn  = document.getElementById('mikro-modal-draft');
    var textarea  = document.getElementById('mikro-modal-content');
    var countEl   = document.getElementById('mikro-modal-count');
    var statusEl  = document.getElementById('mikro-modal-status');

    if ( ! overlay || ! trigger ) return;

    // ── Open ────────────────────────────────────────────────────────────────
    function openModal() {
        overlay.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(function() {
            overlay.classList.add('is-open');
            if (textarea) textarea.focus();
        }, 10);
    }

    trigger.addEventListener('click', openModal);
    trigger.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openModal(); }
    });

    // ── Close ───────────────────────────────────────────────────────────────
    function closeModal() {
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
        setTimeout(function() { overlay.setAttribute('hidden', ''); }, 200);
    }

    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closeModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !overlay.hasAttribute('hidden')) closeModal();
    });

    // ── Char counter ────────────────────────────────────────────────────────
    if (textarea && countEl) {
        textarea.addEventListener('input', function() {
            var len = textarea.value.length;
            countEl.textContent = len;
            countEl.parentElement.style.color = len > 450 ? '#c0392b' : '';
        });
    }

    // ── Submit ───────────────────────────────────────────────────────────────
    function submit(action) {
        var content = textarea ? textarea.value.trim() : '';
        if (!content) {
            textarea.focus();
            setStatus('Skriv något först.', 'error');
            return;
        }

        var amneEl    = document.getElementById('mikro-modal-amne');
        var linkEl    = document.getElementById('mikro-modal-link');
        var exklEl    = document.getElementById('mikro-modal-exclusive');
        var platforms = document.querySelectorAll('.mikro-modal-platforms input[type="checkbox"]:checked');

        var body = new URLSearchParams();
        body.append('action',         'mikro_compose');
        body.append('nonce',          mikroCompose.nonce);
        body.append('content',        content);
        body.append('publish_action', action);
        if (amneEl && amneEl.value)   body.append('amne', amneEl.value);
        if (linkEl && linkEl.value)   body.append('originallank', linkEl.value);
        if (exklEl && exklEl.checked) body.append('exklusivt', '1');
        platforms.forEach(function(cb) { body.append('plattform[]', cb.value); });

        setStatus('Sparar…', 'loading');
        setLoading(true);

        fetch(mikroCompose.ajaxurl, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body.toString(),
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                setStatus(data.data.message, 'success');
                setTimeout(function() {
                    closeModal();
                    window.location.reload();
                }, 800);
            } else {
                setStatus(data.data || 'Något gick fel.', 'error');
                setLoading(false);
            }
        })
        .catch(function() {
            setStatus('Nätverksfel – försök igen.', 'error');
            setLoading(false);
        });
    }

    if (submitBtn) submitBtn.addEventListener('click', function() { submit('publish'); });
    if (draftBtn)  draftBtn.addEventListener('click',  function() { submit('draft'); });

    function setStatus(msg, type) {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.className = 'mikro-modal-status mikro-status-' + type;
    }

    function setLoading(on) {
        if (submitBtn) submitBtn.disabled = on;
        if (draftBtn)  draftBtn.disabled  = on;
    }

})();
