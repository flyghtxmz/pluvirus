(function () {
  var MODEL_IMAGE_SIZES = {
    'gemini-3-pro-image-preview': ['1K', '2K', '4K'],
    'gemini-2.5-flash-image': ['1K'],
    'imagen-4.0-fast-generate-001': ['1K', '2K'],
    'imagen-4.0-generate-001': ['1K', '2K'],
    'imagen-4.0-ultra-generate-001': ['1K', '2K']
  };
  var rewardedDownloadState = {
    url: '',
    filename: '',
    active: false,
    trigger: null,
    startedAt: 0,
    settling: false,
    timeoutId: 0
  };
  var REWARDED_PENDING_MIN_MS = 5000;
  var REWARDED_WATCHDOG_MS = 5500;

  function isRewardedGrantedResult(result) {
    if (!result) {
      return false;
    }

    if (typeof result === 'string') {
      return [
        'rewarded-complete',
        'rewarded_complete',
        'rewarded-granted',
        'rewarded_granted',
        'granted',
        'completed',
        'complete'
      ].indexOf(result) !== -1;
    }

    if (typeof result === 'object') {
      if (true === result.granted || true === result.rewarded || true === result.completed || true === result.complete || true === result.success) {
        return true;
      }

      var status = result.status || result.type || result.event || '';

      if (typeof status === 'string') {
        return [
          'rewarded-complete',
          'rewarded_complete',
          'rewarded-granted',
          'rewarded_granted',
          'granted',
          'completed',
          'complete'
        ].indexOf(status) !== -1;
      }
    }

    return false;
  }

  function parseFilenameFromHeader(contentDisposition) {
    if (!contentDisposition || typeof contentDisposition !== 'string') {
      return '';
    }

    var utfMatch = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i);

    if (utfMatch && utfMatch[1]) {
      try {
        return decodeURIComponent(utfMatch[1]).replace(/["']/g, '');
      } catch (error) {
        return utfMatch[1].replace(/["']/g, '');
      }
    }

    var basicMatch = contentDisposition.match(/filename=([^;]+)/i);

    return basicMatch && basicMatch[1]
      ? basicMatch[1].replace(/["']/g, '').trim()
      : '';
  }

  function getTriggerLabel(triggerElement, labelType) {
    if (!triggerElement) {
      return '';
    }

    return triggerElement.getAttribute('data-' + labelType + '-label') || '';
  }

  function setRewardedTriggerLabel(triggerElement, label) {
    if (!triggerElement || !label) {
      return;
    }

    triggerElement.textContent = label;
  }

  function setRewardedTriggerState(triggerElement, isPending) {
    if (!triggerElement) {
      return;
    }

    if (isPending) {
      triggerElement.classList.add('is-pending');
      triggerElement.setAttribute('aria-disabled', 'true');
      triggerElement.setAttribute('tabindex', '-1');
      setRewardedTriggerLabel(triggerElement, getTriggerLabel(triggerElement, 'pending'));
      return;
    }

    triggerElement.classList.remove('is-pending');
    triggerElement.removeAttribute('aria-disabled');
    triggerElement.removeAttribute('tabindex');
  }

  function clearRewardedWatchdog() {
    if (rewardedDownloadState.timeoutId) {
      window.clearTimeout(rewardedDownloadState.timeoutId);
      rewardedDownloadState.timeoutId = 0;
    }
  }

  function fetchDownloadResponse(url) {
    return fetch(url).then(function (response) {
      if (!response.ok) {
        throw new Error('download-failed');
      }

      return response.blob().then(function (blob) {
        return {
          blob: blob,
          responseFilename: parseFilenameFromHeader(response.headers.get('Content-Disposition'))
        };
      });
    });
  }

  function triggerFileDownload(url, filename) {
    if (!url) {
      return;
    }

    fetchDownloadResponse(url).then(function (payload) {
      var objectUrl = window.URL.createObjectURL(payload.blob);
      var link = document.createElement('a');
      link.href = objectUrl;
      link.download = filename || payload.responseFilename || 'imagem-gerada';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.setTimeout(function () {
        window.URL.revokeObjectURL(objectUrl);
      }, 1500);
    }).catch(function () {
      var fallbackLink = document.createElement('a');
      fallbackLink.href = url;
      fallbackLink.download = filename || '';
      document.body.appendChild(fallbackLink);
      fallbackLink.click();
      document.body.removeChild(fallbackLink);
    });
  }

  function finalizeRewardedDownload(shouldDownload) {
    if ((!rewardedDownloadState.url && !rewardedDownloadState.active) || rewardedDownloadState.settling) {
      return;
    }

    rewardedDownloadState.settling = true;
    clearRewardedWatchdog();
    var url = rewardedDownloadState.url;
    var filename = rewardedDownloadState.filename;
    var triggerElement = rewardedDownloadState.trigger;
    var remainingDelay = Math.max(0, (rewardedDownloadState.startedAt + REWARDED_PENDING_MIN_MS) - Date.now());

    window.setTimeout(function () {
      rewardedDownloadState.url = '';
      rewardedDownloadState.filename = '';
      rewardedDownloadState.active = false;
      rewardedDownloadState.trigger = null;
      rewardedDownloadState.startedAt = 0;
      rewardedDownloadState.settling = false;
      setRewardedTriggerState(triggerElement, false);
      setRewardedTriggerLabel(triggerElement, getTriggerLabel(triggerElement, 'retry'));

      if (shouldDownload && url) {
        triggerFileDownload(url, filename);
      }
    }, remainingDelay);
  }

  function completeRewardedDownload() {
    if (!rewardedDownloadState.url) {
      return;
    }

    finalizeRewardedDownload(true);
  }

  function cancelRewardedDownload() {
    finalizeRewardedDownload(false);
  }

  function isRewardedCompleteMessage(data) {
    if (!data) {
      return false;
    }

    if (typeof data === 'string') {
      return [
        'rewarded-complete',
        'rewarded_complete',
        'rewarded-granted',
        'rewarded_granted',
        'joinads-rewarded-complete'
      ].indexOf(data) !== -1;
    }

    if (typeof data === 'object') {
      var type = data.type || data.event || data.status || '';

      return [
        'rewarded-complete',
        'rewarded_complete',
        'rewarded-granted',
        'rewarded_granted',
        'joinads-rewarded-complete'
      ].indexOf(type) !== -1;
    }

    return false;
  }

  function isRewardedCancelMessage(data) {
    if (!data) {
      return false;
    }

    if (typeof data === 'string') {
      return [
        'rewarded-cancel',
        'rewarded_cancel',
        'rewarded-closed',
        'rewarded_closed'
      ].indexOf(data) !== -1;
    }

    if (typeof data === 'object') {
      var type = data.type || data.event || data.status || '';

      return [
        'rewarded-cancel',
        'rewarded_cancel',
        'rewarded-closed',
        'rewarded_closed'
      ].indexOf(type) !== -1;
    }

    return false;
  }

  function openRewardedDownload(url, filename, triggerElement) {
    if (!url || rewardedDownloadState.active) {
      return;
    }

    rewardedDownloadState.url = url;
    rewardedDownloadState.filename = filename || '';
    rewardedDownloadState.active = true;
    rewardedDownloadState.trigger = triggerElement || null;
    rewardedDownloadState.startedAt = Date.now();
    rewardedDownloadState.settling = false;
    setRewardedTriggerState(triggerElement, true);
    clearRewardedWatchdog();
    rewardedDownloadState.timeoutId = window.setTimeout(function () {
      if (rewardedDownloadState.active && !rewardedDownloadState.settling) {
        cancelRewardedDownload();
      }
    }, REWARDED_WATCHDOG_MS);

    if (!window.wrapper || typeof window.wrapper.openReward !== 'function') {
      cancelRewardedDownload();
      return;
    }

    try {
      var rewardResult = window.wrapper.openReward();

      if (rewardResult && typeof rewardResult.then === 'function') {
        rewardResult.then(function (result) {
          if (false === result) {
            cancelRewardedDownload();
            return;
          }

          if (isRewardedGrantedResult(result)) {
            completeRewardedDownload();
          }
        }).catch(function () {
          cancelRewardedDownload();
        });
        return;
      }

      if (false === rewardResult) {
        cancelRewardedDownload();
        return;
      }

      if (isRewardedGrantedResult(rewardResult)) {
        completeRewardedDownload();
      }
    } catch (error) {
      cancelRewardedDownload();
    }
  }

  function setupRewardedDownloads(root) {
    var links = root.querySelectorAll('[data-fcrs-download-link]');

    Array.prototype.forEach.call(links, function (link) {
      link.addEventListener('click', function (event) {
        var url = link.getAttribute('data-download-url') || link.getAttribute('data-url') || link.getAttribute('href') || '';
        var filename = link.getAttribute('data-download-filename') || '';

        event.preventDefault();

        if (link.getAttribute('data-fcrs-rewarded-download') !== '1') {
          triggerFileDownload(url, filename);
          return;
        }

        openRewardedDownload(url, filename, link);
      });
    });
  }

  window.FCRSRewardedDownload = {
    start: openRewardedDownload,
    complete: completeRewardedDownload,
    cancel: cancelRewardedDownload
  };

  document.addEventListener('fcrs:rewarded-complete', completeRewardedDownload);
  document.addEventListener('fcrs:rewarded-cancel', cancelRewardedDownload);
  window.addEventListener('message', function (event) {
    if (isRewardedCompleteMessage(event.data)) {
      completeRewardedDownload();
      return;
    }

    if (isRewardedCancelMessage(event.data)) {
      cancelRewardedDownload();
    }
  });

  function setupReferencePreview(root) {
    var input = root.querySelector('input[name="referenceImage[]"], input[name="referenceImage"]');
    var wrapper = root.querySelector('[data-fcrs-preview]');
    var grid = root.querySelector('[data-fcrs-preview-grid]');

    if (!input || !wrapper || !grid) {
      return;
    }

    input.addEventListener('change', function () {
      var files = input.files ? Array.prototype.slice.call(input.files) : [];
      var maxImages = parseInt(input.getAttribute('data-fcrs-max-reference-images') || '', 10);

      if (!maxImages || maxImages < 1) {
        maxImages = window.FCRSUi && window.FCRSUi.maxReferenceImages
          ? window.FCRSUi.maxReferenceImages
          : 7;
      }

      grid.innerHTML = '';

      if (!files.length) {
        wrapper.style.display = 'none';
        return;
      }

      if (files.length > maxImages) {
        files = files.slice(0, maxImages);

        if (typeof window.DataTransfer !== 'undefined') {
          var transfer = new window.DataTransfer();

          files.forEach(function (selectedFile) {
            transfer.items.add(selectedFile);
          });

          input.files = transfer.files;
        }
      }

      files.forEach(function (file) {
        if (!file || !file.type || file.type.indexOf('image/') !== 0) {
          return;
        }

        var reader = new FileReader();

        reader.onload = function (event) {
          var src = event.target && event.target.result ? event.target.result : '';

          if (!src) {
            return;
          }

          var tile = document.createElement('div');
          tile.className = 'fcrs-preview-tile';

          var image = document.createElement('img');
          image.src = src;
          image.alt = file.name || 'Preview da imagem de referencia';
          tile.appendChild(image);
          grid.appendChild(tile);
          wrapper.style.display = 'block';
        };

        reader.readAsDataURL(file);
      });
    });
  }

  function getTextParameterValidationMessage(form) {
    var input = form.querySelector('[data-fcrs-text-parameter-input]');

    if (!input) {
      return '';
    }

    var rawValue = (input.value || '').trim();
    var label = input.getAttribute('data-parameter-placeholder')
      || input.getAttribute('data-parameter-label')
      || 'campo';
    var wordLimit = parseInt(input.getAttribute('data-word-limit') || '', 10);
    var letterLimit = parseInt(input.getAttribute('data-letter-limit') || '', 10);
    var requiredMessage = input.getAttribute('data-required-message') || 'Preencha o campo %label%.';
    var wordLimitMessage = input.getAttribute('data-word-limit-message') || 'O campo %label% aceita no maximo %limit% palavras.';
    var letterLimitMessage = input.getAttribute('data-letter-limit-message') || 'Cada palavra do campo %label% pode ter no maximo %limit% letras.';

    if (!input.required && !rawValue) {
      return '';
    }

    if (!rawValue) {
      return requiredMessage.replace('%label%', label);
    }

    var words = rawValue.split(/\s+/).filter(Boolean);

    if (wordLimit > 0 && words.length > wordLimit) {
      return wordLimitMessage
        .replace('%label%', label)
        .replace('%limit%', String(wordLimit));
    }

    if (letterLimit > 0) {
      var exceedsLimit = words.some(function (word) {
        return Array.from(word).length > letterLimit;
      });

      if (exceedsLimit) {
        return letterLimitMessage
          .replace('%label%', label)
          .replace('%limit%', String(letterLimit));
      }
    }

    return '';
  }

  function parsePanelMessages(panel) {
    var loadingMessages = [];

    if (panel) {
      try {
        loadingMessages = JSON.parse(panel.getAttribute('data-fcrs-loading-messages') || '[]');
      } catch (error) {
        loadingMessages = [];
      }
    }

    if (!Array.isArray(loadingMessages) || !loadingMessages.length) {
      loadingMessages = Array.isArray(window.FCRSUi.loadingMessages) ? window.FCRSUi.loadingMessages : [];
    }

    return {
      loadingMessages: loadingMessages,
      waitingMessage: panel && panel.getAttribute('data-fcrs-waiting-message')
        ? panel.getAttribute('data-fcrs-waiting-message')
        : 'Seu pedido foi enviado. Aguarde só mais um pouquinho.',
      pollFailedMessage: panel && panel.getAttribute('data-fcrs-poll-failed-message')
        ? panel.getAttribute('data-fcrs-poll-failed-message')
        : 'Falha ao consultar o andamento do job.',
      queryFailedMessage: panel && panel.getAttribute('data-fcrs-query-failed-message')
        ? panel.getAttribute('data-fcrs-query-failed-message')
        : 'Falha ao consultar o job.'
    };
  }

  function renderTemplateGenerationCount(panel, count) {
    var counter = panel ? panel.querySelector('[data-fcrs-template-counter]') : null;
    var textNode = panel ? panel.querySelector('[data-fcrs-template-counter-text]') : null;
    var numericCount = parseInt(count, 10);
    var messageTemplate = '';

    if (!counter || !textNode) {
      return;
    }

    if (isNaN(numericCount) || numericCount < 0) {
      numericCount = 0;
    }

    if (0 === numericCount) {
      messageTemplate = panel.getAttribute('data-fcrs-template-count-zero') || '';
    } else if (1 === numericCount) {
      messageTemplate = panel.getAttribute('data-fcrs-template-count-singular') || '';
    } else {
      messageTemplate = panel.getAttribute('data-fcrs-template-count-plural') || '';
    }

    if (!messageTemplate) {
      return;
    }

    textNode.textContent = messageTemplate.replace('%d', numericCount);
  }

  function updateResultPreview(resultImage, url) {
    if (!resultImage || !url) {
      return;
    }

    if ('IMG' === resultImage.tagName) {
      resultImage.src = url;
      resultImage.style.display = 'block';

      if (resultImage.parentElement) {
        resultImage.parentElement.classList.add('has-image');
      }

      return;
    }

    resultImage.style.backgroundImage = 'url("' + url + '")';
    resultImage.classList.add('has-image');
  }

  function updateFormatOptions(form) {
    var formatSelect = form.querySelector('[data-fcrs-output-format-select]');
    var qualityWrapper = form.querySelector('[data-fcrs-jpeg-quality-wrapper]');

    if (!formatSelect || !qualityWrapper) {
      return;
    }

    qualityWrapper.style.display = formatSelect.value === 'image/jpeg' ? 'block' : 'none';
  }

  function updateImageSizeOptions(form) {
    var modelSelect = form.querySelector('[data-fcrs-image-model-select]');
    var sizeSelect = form.querySelector('[data-fcrs-image-size-select]');

    if (!modelSelect || !sizeSelect) {
      return;
    }

    var model = modelSelect.value || '';
    var supportedSizes = MODEL_IMAGE_SIZES[model] || ['1K'];
    var firstVisible = '';

    Array.prototype.forEach.call(sizeSelect.options, function (option) {
      var isVisible = supportedSizes.indexOf(option.value) !== -1;
      option.hidden = !isVisible;

      if (isVisible && !firstVisible) {
        firstVisible = option.value;
      }
    });

    if (!sizeSelect.value || sizeSelect.selectedOptions[0].hidden) {
      sizeSelect.value = firstVisible;
    }
  }

  function updateDynamicForm(form) {
    var typeSelect = form.querySelector('[data-fcrs-generation-type]');
    var modelSelect = form.querySelector('[data-fcrs-model-select]');
    var templateSelect = form.querySelector('[data-fcrs-template-select]');
    var submitButton = form.querySelector('[data-fcrs-submit]');
    var videoNote = form.querySelector('[data-fcrs-video-note]');
    var templateWrapper = form.querySelector('[data-fcrs-template-wrapper]');
    var parameterWrapper = form.querySelector('[data-fcrs-template-parameter-wrapper]');
    var parameterLabel = form.querySelector('[data-fcrs-template-parameter-label]');
    var parameterInput = form.querySelector('[data-fcrs-text-parameter-input]');

    if (!typeSelect || !modelSelect || !templateSelect) {
      return;
    }

    var generationType = typeSelect.value || 'image';
    var firstVisibleModel = '';
    var firstVisibleTemplate = '';

    Array.prototype.forEach.call(modelSelect.options, function (option) {
      var isVisible = option.getAttribute('data-generation-type') === generationType;
      option.hidden = !isVisible;

      if (isVisible && !firstVisibleModel) {
        firstVisibleModel = option.value;
      }
    });

    if (!modelSelect.selectedOptions.length || modelSelect.selectedOptions[0].hidden) {
      modelSelect.value = firstVisibleModel;
    }

    Array.prototype.forEach.call(templateSelect.options, function (option) {
      var isVisible = option.getAttribute('data-generation-type') === generationType;
      option.hidden = !isVisible;

      if (isVisible && !firstVisibleTemplate) {
        firstVisibleTemplate = option.value;
      }
    });

    if (!templateSelect.selectedOptions.length || templateSelect.selectedOptions[0].hidden) {
      templateSelect.value = firstVisibleTemplate;
    }

    var isVideo = generationType === 'video';

    if (videoNote) {
      videoNote.style.display = isVideo ? 'block' : 'none';
    }

    if (templateWrapper) {
      templateWrapper.style.display = isVideo ? 'none' : 'block';
    }

    if (parameterWrapper && parameterInput) {
      var selectedTemplateOption = templateSelect.selectedOptions.length ? templateSelect.selectedOptions[0] : null;
      var usesTextParameter = !!selectedTemplateOption && !isVideo && selectedTemplateOption.getAttribute('data-uses-text-parameter') === '1';
      var parameterPlaceholder = usesTextParameter ? (selectedTemplateOption.getAttribute('data-parameter-placeholder') || '') : '';
      var wordLimit = usesTextParameter ? (selectedTemplateOption.getAttribute('data-word-limit') || '') : '';
      var letterLimit = usesTextParameter ? (selectedTemplateOption.getAttribute('data-letter-limit') || '') : '';

      parameterWrapper.style.display = usesTextParameter ? 'block' : 'none';
      parameterInput.required = usesTextParameter;
      parameterInput.setAttribute('data-parameter-label', parameterPlaceholder);
      parameterInput.setAttribute('data-parameter-placeholder', parameterPlaceholder);
      parameterInput.setAttribute('data-word-limit', wordLimit);
      parameterInput.setAttribute('data-letter-limit', letterLimit);

      if (!usesTextParameter) {
        parameterInput.value = '';
      }

      if (parameterLabel) {
        parameterLabel.textContent = parameterPlaceholder ? 'Parametro ' + parameterPlaceholder : 'Parametro';
      }
    }

    if (submitButton) {
      submitButton.disabled = isVideo || !firstVisibleModel;
    }
  }

  function pollJobStatus(container) {
    if (!window.FCRSUi) {
      return;
    }

    var useBlockingUi = container.getAttribute('data-fcrs-blocking-ui') !== '0';
    var jobId = container.getAttribute('data-job-id');
    var resultToken = container.getAttribute('data-result-token');
    var statusNode = container.querySelector('[data-fcrs-job-status-text]');
    var messageNode = container.querySelector('[data-fcrs-job-message]');
    var panel = container.closest('.fcrs-panel') || container.parentNode;
    var resultCard = panel ? panel.querySelector('[data-fcrs-result-card]') : null;
    var resultImage = panel ? panel.querySelector('[data-fcrs-result-image]') : null;
    var downloadLink = panel ? panel.querySelector('[data-fcrs-download-link]') : null;
    var resetLink = panel ? panel.querySelector('[data-fcrs-reset-link]') : null;
    var formCard = panel ? panel.querySelector('[data-fcrs-form-card]') : null;
    var metaNode = container.querySelector('[data-fcrs-job-meta]');
    var loadingPopup = container.querySelector('[data-fcrs-loading-popup]');
    var loadingMessage = container.querySelector('[data-fcrs-loading-message]');
    var submitButton = panel ? panel.querySelector('[data-fcrs-submit]') : null;
    var panelMessages = parsePanelMessages(panel);
    var loadingMessages = panelMessages.loadingMessages;
    var loadingMessageIndex = 0;
    var loadingIntervalId = 0;

    if (!jobId || !resultToken) {
      return;
    }

    function startLoadingMessages() {
      if (!loadingMessage || !loadingMessages.length || loadingIntervalId) {
        return;
      }

      loadingIntervalId = window.setInterval(function () {
        loadingMessageIndex = (loadingMessageIndex + 1) % loadingMessages.length;
        loadingMessage.textContent = loadingMessages[loadingMessageIndex];
      }, 2600);
    }

    function stopLoadingMessages() {
      if (loadingIntervalId) {
        window.clearInterval(loadingIntervalId);
        loadingIntervalId = 0;
      }
    }

    var pollAttempt = 0;

    if (useBlockingUi && submitButton) {
      submitButton.disabled = true;
    }

    if (useBlockingUi && resultCard) {
      resultCard.classList.add('is-hidden');
    }

    if (useBlockingUi) {
      startLoadingMessages();
    }

    function getNextPollDelay(hadError) {
      if (hadError) {
        return window.FCRSUi.pollErrorDelayMs || 15000;
      }

      var schedule = Array.isArray(window.FCRSUi.pollScheduleMs)
        ? window.FCRSUi.pollScheduleMs
        : [6000, 10000, 15000, 20000];
      var index = Math.min(pollAttempt, schedule.length - 1);
      var nextDelay = schedule[index] || 10000;

      if ('hidden' === document.visibilityState) {
        nextDelay = Math.max(nextDelay, window.FCRSUi.pollHiddenTabMs || 30000);
      }

      pollAttempt += 1;
      return nextDelay;
    }

    function requestStatus() {
      var body = new URLSearchParams();
      body.set('action', window.FCRSUi.statusAction);
      body.set('security', window.FCRSUi.statusNonce);
      body.set('jobId', jobId);
      body.set('resultToken', resultToken);

      fetch(window.FCRSUi.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: body.toString()
      }).then(function (response) {
        return response.json();
      }).then(function (payload) {
        if (!payload || !payload.success || !payload.data) {
          if (messageNode) {
            messageNode.textContent = payload && payload.data && payload.data.message
              ? payload.data.message
              : panelMessages.queryFailedMessage;
          }

          window.setTimeout(requestStatus, getNextPollDelay(true));
          return;
        }

        if (statusNode && payload.data.status) {
          statusNode.textContent = payload.data.status;
        }

        if (messageNode && payload.data.message) {
          messageNode.textContent = payload.data.message;
        }

      if (payload.data.terminal) {
        container.classList.remove('is-loading');
        container.removeAttribute('data-fcrs-job-monitor');
        container.removeAttribute('data-job-id');
        container.removeAttribute('data-result-token');
        stopLoadingMessages();

        if (loadingPopup) {
          loadingPopup.classList.add('is-hidden');
        }

          if (payload.data.result) {
            if (messageNode && payload.data.result.message) {
              messageNode.textContent = payload.data.result.message;
            }

            if (resultCard && payload.data.result.preview_url) {
              resultCard.classList.remove('is-hidden');
            }

            if (resultImage && payload.data.result.preview_url) {
              updateResultPreview(resultImage, payload.data.result.preview_url);
            }

            if (resetLink && payload.data.result.preview_url) {
              resetLink.classList.remove('is-hidden');
            }

            if (downloadLink && payload.data.result.download_url) {
              if (downloadLink.getAttribute('data-fcrs-rewarded-download') === '1') {
                downloadLink.href = '#';
              } else {
                downloadLink.href = payload.data.result.download_url;
              }

              downloadLink.setAttribute('data-url', payload.data.result.download_url);
              downloadLink.setAttribute('data-download-url', payload.data.result.download_url);
              downloadLink.setAttribute('data-download-filename', payload.data.result.download_filename || '');
              downloadLink.classList.remove('is-hidden');
            }

            if (formCard) {
              formCard.classList.toggle('is-hidden', !!payload.data.result.preview_url);
            }

            if (typeof payload.data.result.template_generation_count !== 'undefined') {
              renderTemplateGenerationCount(panel, payload.data.result.template_generation_count);
            }

            if (metaNode) {
              metaNode.classList.toggle(
                'is-hidden',
                !payload.data.result.temporary_result && !payload.data.result.attachment_id
              );
            }
          }

          if (useBlockingUi && 'success' === payload.data.status && resultCard) {
            container.classList.add('is-hidden');
          }

          if (submitButton) {
            submitButton.disabled = false;
          }

          return;
        }

        window.setTimeout(requestStatus, getNextPollDelay(false));
      }).catch(function () {
        if (messageNode) {
          messageNode.textContent = panelMessages.pollFailedMessage;
        }

        window.setTimeout(requestStatus, getNextPollDelay(true));
      });
    }

    window.setTimeout(requestStatus, window.FCRSUi.pollInitialDelayMs || 1500);
  }

  document.addEventListener('DOMContentLoaded', function () {
    var panels = document.querySelectorAll('.fcrs-panel');

    Array.prototype.forEach.call(panels, function (panel) {
      setupReferencePreview(panel);
      setupRewardedDownloads(panel);
    });

    var forms = document.querySelectorAll('.fcrs-panel form');

    Array.prototype.forEach.call(forms, function (form) {
      var formatSelect = form.querySelector('[data-fcrs-output-format-select]');
      var modelSelect = form.querySelector('[data-fcrs-image-model-select]');
      var submitButton = form.querySelector('[data-fcrs-submit]');
      var panel = form.closest('.fcrs-panel');
      var useBlockingUi = form.getAttribute('data-fcrs-blocking-ui') !== '0';
      var statusCard = panel ? panel.querySelector('.fcrs-status-card') : null;
      var loadingPopup = panel ? panel.querySelector('[data-fcrs-loading-popup]') : null;
      var loadingMessage = panel ? panel.querySelector('[data-fcrs-loading-message]') : null;
      var messageNode = panel ? panel.querySelector('[data-fcrs-job-message]') : null;
      var metaNode = panel ? panel.querySelector('[data-fcrs-job-meta]') : null;
      var resultCard = panel ? panel.querySelector('[data-fcrs-result-card]') : null;
      var panelMessages = parsePanelMessages(panel);

      updateFormatOptions(form);
      updateImageSizeOptions(form);

      if (formatSelect) {
        formatSelect.addEventListener('change', function () {
          updateFormatOptions(form);
        });
      }

      if (modelSelect) {
        modelSelect.addEventListener('change', function () {
          updateImageSizeOptions(form);
        });
      }

      form.addEventListener('submit', function (event) {
        var textParameterValidationMessage = getTextParameterValidationMessage(form);

        if (textParameterValidationMessage) {
          event.preventDefault();
          window.alert(textParameterValidationMessage);
          return;
        }

        if (submitButton) {
          submitButton.disabled = true;
        }

        if (useBlockingUi && resultCard) {
          resultCard.classList.add('is-hidden');
        }

        if (statusCard) {
          statusCard.classList.remove('is-hidden');

          if (useBlockingUi) {
            statusCard.classList.add('is-loading');
          }
        }

        if (messageNode) {
          messageNode.textContent = panelMessages.waitingMessage;
        }

        if (metaNode) {
          metaNode.classList.add('is-hidden');
        }

        if (useBlockingUi && loadingPopup) {
          loadingPopup.classList.remove('is-hidden');
        }

        if (useBlockingUi && loadingMessage && panelMessages.loadingMessages.length) {
          loadingMessage.textContent = panelMessages.loadingMessages[0];
        }
      });
    });

    var dynamicForms = document.querySelectorAll('.fcrs-dynamic-form');

    Array.prototype.forEach.call(dynamicForms, function (form) {
      var typeSelect = form.querySelector('[data-fcrs-generation-type]');
      var templateSelect = form.querySelector('[data-fcrs-template-select]');

      updateDynamicForm(form);

      if (typeSelect) {
        typeSelect.addEventListener('change', function () {
          updateDynamicForm(form);
        });
      }

      if (templateSelect) {
        templateSelect.addEventListener('change', function () {
          updateDynamicForm(form);
        });
      }
    });

    var jobMonitors = document.querySelectorAll('[data-fcrs-job-monitor]');

    Array.prototype.forEach.call(jobMonitors, function (container) {
      pollJobStatus(container);
    });
  });
})();
