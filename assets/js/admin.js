/* WPBlogTree Mikroinlägg – Admin JS */
(function () {
    'use strict';

    // ── Character counter ────────────────────────────────────────────────────
    var textarea  = document.getElementById('mikro-content');
    var countEl   = document.getElementById('mikro-count');
    var countWrap = document.getElementById('mikro-char-count');
    var MAX_CHARS = 500;

    function updateCount() {
        var len = textarea.value.length;
        countEl.textContent = len;
        if ( len > MAX_CHARS * 0.9 ) {
            countWrap.classList.add('over-limit');
        } else {
            countWrap.classList.remove('over-limit');
        }
    }

    if ( textarea && countEl ) {
        textarea.addEventListener('input', updateCount);
        updateCount();
    }

    // ── Tag input ────────────────────────────────────────────────────────────
    var tagInput     = document.getElementById('mikro-tag-input');
    var tagsContainer = document.getElementById('mikro-tags-container');
    var tagsHidden   = document.getElementById('mikro-taggar');
    var tags         = [];

    function renderTags() {
        if ( ! tagsContainer ) return;
        tagsContainer.innerHTML = '';
        tags.forEach( function( tag, idx ) {
            var chip = document.createElement('span');
            chip.className = 'mikro-tag-chip';
            chip.textContent = tag;

            var removeBtn = document.createElement('button');
            removeBtn.type      = 'button';
            removeBtn.className = 'mikro-tag-remove';
            removeBtn.textContent = '×';
            removeBtn.setAttribute('aria-label', 'Ta bort tagg: ' + tag);
            removeBtn.addEventListener('click', function() {
                tags.splice(idx, 1);
                renderTags();
            });

            chip.appendChild(removeBtn);
            tagsContainer.appendChild(chip);
        });
        if ( tagsHidden ) {
            tagsHidden.value = tags.join(',');
        }
    }

    function addTag( value ) {
        var tag = value.trim().replace(/,+$/, '');
        if ( tag && ! tags.includes(tag) && tags.length < 20 ) {
            tags.push(tag);
            renderTags();
        }
    }

    if ( tagInput ) {
        tagInput.addEventListener('keydown', function(e) {
            if ( e.key === 'Enter' || e.key === ',' ) {
                e.preventDefault();
                addTag( tagInput.value );
                tagInput.value = '';
            }
            if ( e.key === 'Backspace' && tagInput.value === '' && tags.length > 0 ) {
                tags.pop();
                renderTags();
            }
        });

        tagInput.addEventListener('blur', function() {
            if ( tagInput.value.trim() ) {
                addTag( tagInput.value );
                tagInput.value = '';
            }
        });
    }

    // ── Toolbar formatting ───────────────────────────────────────────────────
    var toolBtns = document.querySelectorAll('.mikro-tool');
    toolBtns.forEach( function( btn ) {
        btn.addEventListener('click', function() {
            if ( ! textarea ) return;
            var tag  = btn.getAttribute('data-tag');
            var start = textarea.selectionStart;
            var end   = textarea.selectionEnd;
            var sel   = textarea.value.substring(start, end);
            var before = textarea.value.substring(0, start);
            var after  = textarea.value.substring(end);

            var wrapped = sel;
            if      ( tag === 'b'  ) wrapped = '**' + sel + '**';
            else if ( tag === 'i'  ) wrapped = '_' + sel + '_';
            else if ( tag === 'u'  ) wrapped = '__' + sel + '__';
            else if ( tag === 's'  ) wrapped = '~~' + sel + '~~';
            else if ( tag === 'bq' ) wrapped = '\n> ' + sel;
            else if ( tag === 'ul' ) wrapped = '\n- ' + sel;

            textarea.value = before + wrapped + after;
            textarea.focus();
            textarea.selectionStart = start + wrapped.length;
            textarea.selectionEnd   = start + wrapped.length;
            updateCount();
        });
    });

    // ── Form validation ──────────────────────────────────────────────────────
    var form = document.getElementById('mikro-form');
    if ( form ) {
        form.addEventListener('submit', function(e) {
            var content = textarea ? textarea.value.trim() : '';
            if ( ! content ) {
                e.preventDefault();
                textarea.style.borderColor = '#c0392b';
                textarea.focus();
                alert('Skriv ett inlägg innan du publicerar.');
                return;
            }
            if ( content.length > MAX_CHARS ) {
                e.preventDefault();
                textarea.focus();
                alert('Inlägget är för långt. Max ' + MAX_CHARS + ' tecken.');
            }
        });
    }

})();
