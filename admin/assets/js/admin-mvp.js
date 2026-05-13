document.querySelectorAll('[data-confirm]').forEach((button) => {
  button.addEventListener('click', (event) => {
    if (!confirm(button.dataset.confirm || 'Confirmar ação?')) {
      event.preventDefault();
    }
  });
});

const menuToggle = document.querySelector('.menu-toggle');
const sidebar = document.querySelector('.sidebar');

if (menuToggle && sidebar) {
  menuToggle.addEventListener('click', () => {
    const isOpen = sidebar.classList.toggle('is-open');
    menuToggle.setAttribute('aria-expanded', String(isOpen));
  });
}

const lightbox = document.createElement('div');
lightbox.className = 'lightbox';
lightbox.setAttribute('aria-hidden', 'true');
lightbox.innerHTML = `
  <button class="lightbox-close" type="button" aria-label="Fechar imagem">×</button>
  <img class="lightbox-image" alt="">
`;
document.body.appendChild(lightbox);

const lightboxImage = lightbox.querySelector('.lightbox-image');
const lightboxClose = lightbox.querySelector('.lightbox-close');

function closeLightbox() {
  lightbox.classList.remove('is-open');
  lightbox.setAttribute('aria-hidden', 'true');
  lightboxImage.removeAttribute('src');
  lightboxImage.alt = '';
}

function openLightbox(image) {
  lightboxImage.src = image.src;
  lightboxImage.alt = image.alt || 'Imagem enviada pelo contato';
  lightbox.classList.add('is-open');
  lightbox.setAttribute('aria-hidden', 'false');
}

document.querySelectorAll('[data-gallery] img').forEach((image) => {
  image.setAttribute('tabindex', '0');
  image.setAttribute('role', 'button');
  image.setAttribute('aria-label', 'Ampliar imagem enviada');

  image.addEventListener('click', () => {
    openLightbox(image);
  });

  image.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      openLightbox(image);
    }
  });
});

lightboxClose.addEventListener('click', closeLightbox);
lightbox.addEventListener('click', (event) => {
  if (event.target === lightbox) {
    closeLightbox();
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && lightbox.classList.contains('is-open')) {
    closeLightbox();
  }
});

document.addEventListener('click', async (event) => {
  const link = event.target.closest('[data-calendar-link]');

  if (!link || !window.fetch) {
    return;
  }

  event.preventDefault();

  const calendarCard = document.querySelector('[data-calendar-card]');

  if (!calendarCard) {
    window.location.href = link.href;
    return;
  }

  calendarCard.classList.add('is-loading');

  try {
    const response = await fetch(link.href, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });
    const html = await response.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const nextCalendar = doc.querySelector('[data-calendar-card]');

    if (!nextCalendar) {
      window.location.href = link.href;
      return;
    }

    calendarCard.replaceWith(nextCalendar);
    window.history.pushState({}, '', link.href);
  } catch (error) {
    window.location.href = link.href;
  }
});
