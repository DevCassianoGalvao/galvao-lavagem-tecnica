document.addEventListener('DOMContentLoaded', () => {
  const toggles = document.querySelectorAll('[data-toggle]');

  toggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
      const target = document.querySelector(toggle.dataset.toggle);
      target?.classList.toggle('is-open');
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => {
      button.closest('.modal')?.classList.remove('is-open');
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal.is-open').forEach((modal) => modal.classList.remove('is-open'));
    }
  });

  document.querySelectorAll('.modal').forEach((modal) => {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        modal.classList.remove('is-open');
      }
    });
  });
});
