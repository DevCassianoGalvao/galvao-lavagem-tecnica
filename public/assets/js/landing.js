const LandingPage = (() => {
  const formatBytes = (bytes) => {
    if (!bytes) {
      return '0 KB';
    }

    const units = ['bytes', 'KB', 'MB'];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const value = bytes / Math.pow(1024, index);

    return `${value.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
  };

  const initHeader = () => {
    const header = document.querySelector('[data-landing-header]');

    if (!header) {
      return;
    }

    const update = () => {
      header.classList.toggle('is-scrolled', window.scrollY > 12);
    };

    update();
    window.addEventListener('scroll', update, { passive: true });
  };

  const initReveal = () => {
    const elements = document.querySelectorAll('.reveal');

    if (!('IntersectionObserver' in window)) {
      elements.forEach((element) => element.classList.add('is-visible'));
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.14,
    });

    elements.forEach((element) => observer.observe(element));
  };

  const initUpload = () => {
    const form = document.querySelector('[data-upload-form]');

    if (!form) {
      return;
    }

    const input = form.querySelector('[data-upload-input]');
    const dropzone = form.querySelector('[data-upload-dropzone]');
    const preview = form.querySelector('[data-upload-preview]');
    const previewImage = form.querySelector('[data-upload-image]');
    const previewName = form.querySelector('[data-upload-name]');
    const previewSize = form.querySelector('[data-upload-size]');
    const loading = form.querySelector('[data-upload-loading]');
    const button = form.querySelector('[data-upload-button]');
    const result = form.querySelector('[data-simulation-result]');
    const resultImage = form.querySelector('[data-simulation-image]');
    let selectedFile = null;
    let simulationCompleted = false;

    const compressImage = (file) => new Promise((resolve) => {
      const image = new Image();
      const reader = new FileReader();

      reader.addEventListener('load', () => {
        image.src = reader.result;
      });

      image.addEventListener('load', () => {
        const maxSize = 1600;
        const scale = Math.min(1, maxSize / Math.max(image.width, image.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(image.width * scale);
        canvas.height = Math.round(image.height * scale);
        const context = canvas.getContext('2d');

        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        canvas.toBlob((blob) => {
          if (!blob) {
            resolve(file);
            return;
          }

          resolve(new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), {
            type: 'image/jpeg',
            lastModified: Date.now(),
          }));
        }, 'image/jpeg', 0.82);
      });

      reader.readAsDataURL(file);
    });

    const setPreview = (file) => {
      if (!file || !file.type.startsWith('image/')) {
        return;
      }

      const reader = new FileReader();

      reader.addEventListener('load', () => {
        previewImage.src = reader.result;
        previewName.textContent = file.name;
        previewSize.textContent = formatBytes(file.size);
        preview.hidden = false;
        result.hidden = true;
      });

      reader.readAsDataURL(file);
    };

    const handleFiles = (files) => {
      const file = files?.[0];

      if (file) {
        selectedFile = file;
        setPreview(file);
      }
    };

    input?.addEventListener('change', () => handleFiles(input.files));

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
      const files = event.dataTransfer?.files;

      if (files?.length) {
        input.files = files;
        handleFiles(files);
      }
    });

    button?.addEventListener('click', async () => {
      if (simulationCompleted) {
        document.querySelector('#quiz')?.scrollIntoView({ behavior: 'smooth' });
        return;
      }

      if (!selectedFile) {
        input?.click();
        return;
      }

      loading.hidden = false;
      button.disabled = true;
      button.textContent = 'Analisando imagem...';

      try {
        const compressed = await compressImage(selectedFile);
        const formData = new FormData(form);
        formData.set('environment_image', compressed);

        const response = await fetch(form.action, {
          method: 'POST',
          body: formData,
          headers: { Accept: 'application/json' },
        });
        const payload = await response.json();

        if (payload.success && payload.simulation?.result_data_url) {
          resultImage.src = payload.simulation.result_data_url;
          result.hidden = false;
        }

        if (payload.next_cta) {
          button.textContent = payload.next_cta;
        }

        if (payload.success || payload.soft_block) {
          simulationCompleted = true;
        }
      } catch (error) {
        button.textContent = 'Solicitar orcamento';
        simulationCompleted = true;
      } finally {
        loading.hidden = true;
        button.disabled = false;
      }
    });
  };

  const init = () => {
    initHeader();
    initReveal();
    initUpload();
  };

  return { init };
})();

document.addEventListener('DOMContentLoaded', LandingPage.init);
