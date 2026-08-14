document.addEventListener('DOMContentLoaded', function () {
  const selectAll = document.getElementById('sins-select-all');
  const checks = Array.from(document.querySelectorAll('.sins-row-check'));
  const sequenceButton = document.getElementById('sins-apply-sequence');
  const aiButton = document.getElementById('sins-ai-suggest');
  const aiAnotherButton = document.getElementById('sins-ai-another');
  const aiClearButton = document.getElementById('sins-ai-clear');
  const aiStatus = document.getElementById('sins-ai-status');
  const baseEl = document.getElementById('sins-sequence-base');
  const startEl = document.getElementById('sins-sequence-start');
  const padEl = document.getElementById('sins-sequence-padding');
  const preview = document.getElementById('sins-sequence-preview');
  const renameForm = document.getElementById('sins-rename-form');
  const renameButton = renameForm ? renameForm.querySelector('.sins-rename-submit') : null;
  const returnScroll = document.getElementById('sins-return-scroll');
  const scrollKey = 'scoutAssetStudioScroll:' + window.location.pathname + window.location.search;

  if (selectAll) {
    selectAll.addEventListener('change', function () {
      checks.forEach(function (check) {
        check.checked = selectAll.checked;
      });
    });
  }

  function slug(value) {
    return value
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'scout-trails';
  }

  function updatePreview() {
    if (!preview) {
      return;
    }

    const base = slug(baseEl ? baseEl.value : 'Scout Trails');
    const start = parseInt(startEl ? startEl.value : '1', 10) || 1;
    const padding = parseInt(padEl ? padEl.value : '2', 10) || 2;
    const codes = preview.querySelectorAll('code');

    codes.forEach(function (code, index) {
      code.textContent = base + '-' + String(start + index).padStart(padding, '0') + '.jpg';
    });
  }

  [baseEl, startEl, padEl].forEach(function (element) {
    if (element) {
      element.addEventListener('input', updatePreview);
      element.addEventListener('change', updatePreview);
    }
  });
  updatePreview();

  if (sequenceButton) {
    sequenceButton.addEventListener('click', function () {
      const base = (baseEl.value || '').trim();
      const start = parseInt(startEl.value || '1', 10);
      const padding = parseInt(padEl.value || '2', 10);

      if (!base) {
        alert('Enter a base name, such as Scout Trails.');
        return;
      }

      let index = Number.isFinite(start) ? start : 1;
      let count = 0;

      checks.forEach(function (check) {
        if (!check.checked) {
          return;
        }

        const input = document.querySelector('.sins-new-name[data-attachment-id="' + check.value + '"]');
        if (!input) {
          return;
        }

        const current = input.dataset.suggestion || '';
        const match = current.match(/(\.[a-z0-9]+)$/i);
        const extension = match ? match[1].toLowerCase() : '';
        input.value = base + ' ' + String(index).padStart(padding, '0') + extension;
        index++;
        count++;
      });

      if (!count) {
        alert('Select at least one image first.');
      }
    });
  }

  if (renameForm && renameButton) {
    renameForm.addEventListener('submit', function () {
      const scrollPosition = String(Math.max(0, Math.round(window.scrollY)));
      if (returnScroll) {
        returnScroll.value = scrollPosition;
      }
      sessionStorage.setItem(scrollKey, scrollPosition);

      window.setTimeout(function () {
        renameButton.disabled = true;
        renameButton.classList.add('is-loading');
        renameButton.setAttribute('aria-busy', 'true');
        const label = renameButton.querySelector('.sins-button-label');
        if (label) {
          label.textContent = renameButton.dataset.loadingLabel || 'Renaming Assets…';
        }
      }, 0);
    });
  }

  const savedScroll = sessionStorage.getItem(scrollKey);
  if (savedScroll !== null) {
    sessionStorage.removeItem(scrollKey);
    requestAnimationFrame(function () {
      window.scrollTo({
        top: parseInt(savedScroll, 10) || 0,
        left: 0,
        behavior: 'auto'
      });
    });
  }

  function renderAiStatus(message, errors) {
    if (!aiStatus) {
      return;
    }

    aiStatus.innerHTML = '';
    const summary = document.createElement('span');
    summary.textContent = message || '';
    aiStatus.appendChild(summary);

    const entries = errors ? Object.entries(errors) : [];
    if (entries.length) {
      const details = document.createElement('details');
      details.className = 'sins-ai-details';
      const label = document.createElement('summary');
      label.textContent = 'Show AI response details';
      details.appendChild(label);

      entries.forEach(function (entry) {
        const id = entry[0];
        const item = entry[1];
        const block = document.createElement('div');
        block.className = 'sins-ai-detail-item';
        const heading = document.createElement('strong');
        heading.textContent = '#' + id + ': ' + ((item && item.message) || 'AI naming failed.');
        block.appendChild(heading);

        if (item && item.raw) {
          const pre = document.createElement('pre');
          pre.textContent = item.raw;
          block.appendChild(pre);
        }
        details.appendChild(block);
      });

      aiStatus.appendChild(details);
    }
  }

  function setAiReady(ready) {
    if (!aiButton) {
      return;
    }

    const aiLabel = aiButton.querySelector('.sins-ai-button-label');
    const aiIcon = aiButton.querySelector('.sins-ai-button-icon');

    if (ready) {
      if (aiLabel) {
        aiLabel.textContent = 'Suggestions Ready';
      }
      if (aiIcon) {
        aiIcon.textContent = '✓';
      }
      aiButton.classList.add('is-ready');
      aiButton.disabled = true;
      if (aiAnotherButton) {
        aiAnotherButton.hidden = false;
      }
      if (aiClearButton) {
        aiClearButton.hidden = false;
      }
    } else {
      if (aiLabel) {
        aiLabel.textContent = 'Select Name with AI';
      }
      if (aiIcon) {
        aiIcon.textContent = '✨';
      }
      aiButton.classList.remove('is-ready');
      aiButton.disabled = false;
      if (aiAnotherButton) {
        aiAnotherButton.hidden = true;
      }
      if (aiClearButton) {
        aiClearButton.hidden = true;
      }
    }
  }

  function selectedIds() {
    return checks
      .filter(function (check) {
        return check.checked;
      })
      .map(function (check) {
        return check.value;
      });
  }

  async function requestAiSuggestions(isAnotherIdea) {
    const ids = selectedIds();
    if (!ids.length) {
      alert(SINS_DATA.strings.selectImages);
      return;
    }
    if (!SINS_DATA.aiConfigured) {
      alert('Add and save an API key in the AI naming panel first.');
      return;
    }

    const activeButton = isAnotherIdea ? aiAnotherButton : aiButton;
    if (activeButton) {
      activeButton.disabled = true;
      activeButton.setAttribute('aria-busy', 'true');
    }

    renderAiStatus(
      isAnotherIdea
        ? (SINS_DATA.strings.aiAnotherWorking || 'Scout AI is creating another set of ideas…')
        : SINS_DATA.strings.aiWorking
    );

    try {
      const body = new URLSearchParams();
      body.append('action', 'sins_ai_suggest');
      body.append('nonce', SINS_DATA.nonce);
      ids.forEach(function (id) {
        body.append('ids[]', id);
      });

      const seoPhrase = document.getElementById('sins-seo-phrase');
      const seoContext = document.getElementById('sins-seo-context');
      const seoMax = document.getElementById('sins-seo-max-length');
      if (seoPhrase) {
        body.append('seo_phrase', seoPhrase.value || '');
      }
      if (seoContext) {
        body.append('seo_context', seoContext.value || '');
      }
      if (seoMax) {
        body.append('seo_max_length', seoMax.value || '70');
      }

      const response = await fetch(SINS_DATA.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: body.toString()
      });
      const json = await response.json();

      if (!json.success) {
        const error = new Error((json.data && json.data.message) || SINS_DATA.strings.aiError);
        error.details = (json.data && json.data.errors) || {};
        throw error;
      }

      Object.entries(json.data.names || {}).forEach(function (entry) {
        const id = entry[0];
        const name = entry[1];
        const input = document.querySelector('.sins-new-name[data-attachment-id="' + id + '"]');
        if (input) {
          input.value = name;
        }
      });

      const successMessage = isAnotherIdea
        ? (SINS_DATA.strings.aiAnotherDone || 'Scout AI has successfully created another set of names for your selected images.')
        : ((json.data && json.data.message) || SINS_DATA.strings.aiDone);

      renderAiStatus(successMessage, (json.data && json.data.errors) || {});
      setAiReady(true);
    } catch (error) {
      renderAiStatus(error.message || SINS_DATA.strings.aiError, error.details || {});
      if (!isAnotherIdea) {
        setAiReady(false);
      }
    } finally {
      if (activeButton) {
        activeButton.disabled = false;
        activeButton.removeAttribute('aria-busy');
      }
      if (aiButton && aiButton.classList.contains('is-ready')) {
        aiButton.disabled = true;
      }
    }
  }

  if (aiButton) {
    aiButton.addEventListener('click', function () {
      requestAiSuggestions(false);
    });
  }

  if (aiAnotherButton) {
    aiAnotherButton.addEventListener('click', function () {
      requestAiSuggestions(true);
    });
  }

  if (aiClearButton) {
    aiClearButton.addEventListener('click', function () {
      selectedIds().forEach(function (id) {
        const input = document.querySelector('.sins-new-name[data-attachment-id="' + id + '"]');
        if (input) {
          input.value = '';
        }
      });
      renderAiStatus(SINS_DATA.strings.aiCleared || 'AI suggestions have been cleared. Your images have not been renamed.');
      setAiReady(false);
    });
  }
});

document.addEventListener('DOMContentLoaded', function () {
  const button = document.getElementById('sins-ai-test');
  const status = document.getElementById('sins-ai-test-status');

  if (!button) {
    return;
  }

  button.addEventListener('click', async function () {
    button.disabled = true;
    status.textContent = SINS_DATA.strings.testing;

    try {
      const body = new URLSearchParams({
        action: 'sins_ai_test_connection',
        nonce: SINS_DATA.nonce
      });
      const response = await fetch(SINS_DATA.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: body.toString()
      });
      const json = await response.json();

      if (!json.success) {
        throw new Error((json.data && json.data.message) || SINS_DATA.strings.testError);
      }

      status.textContent = (json.data && json.data.message) || SINS_DATA.strings.connected;
      status.className = 'sins-ai-test-success';
    } catch (error) {
      status.textContent = error.message || SINS_DATA.strings.testError;
      status.className = 'sins-ai-test-error';
    } finally {
      button.disabled = false;
    }
  });
});
