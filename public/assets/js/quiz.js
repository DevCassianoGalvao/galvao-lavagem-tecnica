const GalvaoQuiz = (() => {
  const maxImages = 10;

  const state = {
    currentStep: 0,
    selectedFiles: [],
  };

  const formatBytes = (bytes) => {
    if (!bytes) {
      return '0 KB';
    }

    const units = ['bytes', 'KB', 'MB'];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const value = bytes / Math.pow(1024, index);

    return `${value.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
  };

  const setFeedback = (quiz, message = '') => {
    const feedback = quiz.querySelector('[data-quiz-feedback]');

    if (!feedback) {
      return;
    }

    feedback.hidden = !message;
    feedback.textContent = message;
  };

  const updateProgress = (quiz) => {
    const steps = [...quiz.querySelectorAll('[data-quiz-step]')];
    const current = steps[state.currentStep];
    const progress = quiz.querySelector('[data-quiz-progress]');
    const counter = quiz.querySelector('[data-quiz-counter]');
    const title = quiz.querySelector('[data-quiz-title]');
    const previousButton = quiz.querySelector('[data-quiz-prev]');
    const nextButton = quiz.querySelector('[data-quiz-next]');
    const percentage = ((state.currentStep + 1) / steps.length) * 100;

    steps.forEach((step, index) => {
      step.classList.toggle('is-active', index === state.currentStep);
    });

    if (progress) {
      progress.style.width = `${percentage}%`;
    }

    if (counter) {
      counter.textContent = `Etapa ${state.currentStep + 1} de ${steps.length}`;
    }

    if (title) {
      title.textContent = current?.dataset.stepTitle || '';
    }

    if (previousButton) {
      previousButton.disabled = state.currentStep === 0;
    }

    if (nextButton) {
      nextButton.textContent = state.currentStep === steps.length - 2 ? 'Enviar diagnostico' : 'Continuar';
      nextButton.hidden = state.currentStep === steps.length - 1;
    }
  };

  const validateRequiredGroup = (step, groupName) => {
    return step.querySelectorAll(`[data-required-group="${groupName}"]:checked`).length > 0;
  };

  const validateStep = (quiz) => {
    const step = quiz.querySelectorAll('[data-quiz-step]')[state.currentStep];
    const fields = [...step.querySelectorAll('input[required], textarea[required], select[required]')];
    const requiredGroups = [...new Set([...step.querySelectorAll('[data-required-group]')].map((item) => item.dataset.requiredGroup))];

    for (const field of fields) {
      if (!field.checkValidity()) {
        field.reportValidity();
        setFeedback(quiz, 'Preencha esta etapa antes de continuar.');
        return false;
      }
    }

    for (const groupName of requiredGroups) {
      if (!validateRequiredGroup(step, groupName)) {
        setFeedback(quiz, 'Selecione pelo menos uma opcao para continuar.');
        return false;
      }
    }

    setFeedback(quiz);
    return true;
  };

  const renderPreviews = (quiz) => {
    const preview = quiz.querySelector('[data-quiz-preview]');

    if (!preview) {
      return;
    }

    preview.innerHTML = '';

    state.selectedFiles.forEach((file) => {
      const figure = document.createElement('figure');
      const image = document.createElement('img');
      const caption = document.createElement('figcaption');

      image.src = URL.createObjectURL(file);
      image.alt = `Preview de ${file.name}`;
      image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true });
      caption.textContent = `${file.name} · ${formatBytes(file.size)}`;

      figure.append(image, caption);
      preview.append(figure);
    });
  };

  const syncFileInput = (input) => {
    if (!input || typeof DataTransfer === 'undefined') {
      return;
    }

    const transfer = new DataTransfer();
    state.selectedFiles.forEach((file) => transfer.items.add(file));
    input.files = transfer.files;
  };

  const handleFiles = (quiz, files) => {
    const input = quiz.querySelector('[data-quiz-upload]');
    const validFiles = [...files].filter((file) => file.type.startsWith('image/'));
    const nextFiles = [...state.selectedFiles, ...validFiles].slice(0, maxImages);

    state.selectedFiles = nextFiles;
    syncFileInput(input);
    renderPreviews(quiz);

    if (validFiles.length && state.selectedFiles.length === maxImages) {
      setFeedback(quiz, 'Limite de 10 imagens atingido. As primeiras imagens foram mantidas.');
    } else {
      setFeedback(quiz);
    }
  };

  const initUpload = (quiz) => {
    const input = quiz.querySelector('[data-quiz-upload]');
    const dropzone = quiz.querySelector('[data-quiz-dropzone]');

    input?.addEventListener('change', () => handleFiles(quiz, input.files || []));

    ['dragenter', 'dragover'].forEach((eventName) => {
      dropzone?.addEventListener(eventName, (event) => {
        event.preventDefault();
        dropzone.classList.add('is-dragging');
      });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
      dropzone?.addEventListener(eventName, (event) => {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');
      });
    });

    dropzone?.addEventListener('drop', (event) => {
      handleFiles(quiz, event.dataTransfer?.files || []);
    });
  };

  const initLocation = (quiz) => {
    const button = quiz.querySelector('[data-location-button]');
    const status = quiz.querySelector('[data-location-status]');
    const latitude = quiz.querySelector('[data-quiz-latitude]');
    const longitude = quiz.querySelector('[data-quiz-longitude]');

    button?.addEventListener('click', () => {
      if (!navigator.geolocation) {
        status.textContent = 'Geolocalizacao indisponivel neste navegador.';
        return;
      }

      status.textContent = 'Localizando com seguranca...';
      button.disabled = true;

      navigator.geolocation.getCurrentPosition((position) => {
        latitude.value = position.coords.latitude;
        longitude.value = position.coords.longitude;
        status.textContent = 'Localizacao capturada. Futuramente ela podera consultar OpenStreetMap/Nominatim.';
        button.disabled = false;
      }, () => {
        status.textContent = 'Nao foi possivel capturar a localizacao. Preencha o endereco manualmente.';
        button.disabled = false;
      }, {
        enableHighAccuracy: true,
        timeout: 10000,
      });
    });
  };

  const submitQuiz = async (quiz) => {
    const form = quiz.querySelector('[data-quiz-form]');
    const loading = quiz.querySelector('[data-quiz-loading]');
    const nextButton = quiz.querySelector('[data-quiz-next]');
    const steps = quiz.querySelectorAll('[data-quiz-step]');

    loading.hidden = false;
    nextButton.disabled = true;

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
          Accept: 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error('Falha ao enviar diagnostico.');
      }

      loading.hidden = true;
      nextButton.disabled = false;
      state.currentStep = steps.length - 1;
      updateProgress(quiz);
    } catch (error) {
      loading.hidden = true;
      nextButton.disabled = false;
      setFeedback(quiz, 'Nao foi possivel enviar agora. A estrutura esta pronta para backend; tente novamente em instantes.');
    }
  };

  const initNavigation = (quiz) => {
    const previousButton = quiz.querySelector('[data-quiz-prev]');
    const nextButton = quiz.querySelector('[data-quiz-next]');
    const steps = quiz.querySelectorAll('[data-quiz-step]');

    previousButton?.addEventListener('click', () => {
      if (state.currentStep > 0) {
        state.currentStep -= 1;
        setFeedback(quiz);
        updateProgress(quiz);
      }
    });

    nextButton?.addEventListener('click', () => {
      if (!validateStep(quiz)) {
        return;
      }

      if (state.currentStep === steps.length - 2) {
        submitQuiz(quiz);
        return;
      }

      state.currentStep += 1;
      updateProgress(quiz);
    });
  };

  const init = () => {
    const quiz = document.querySelector('[data-quiz]');

    if (!quiz) {
      return;
    }

    initNavigation(quiz);
    initUpload(quiz);
    initLocation(quiz);
    updateProgress(quiz);
  };

  return { init };
})();

document.addEventListener('DOMContentLoaded', GalvaoQuiz.init);
