document.addEventListener('DOMContentLoaded', () => {
  const button = document.querySelector('.hp-menu-toggle');
  const menu = document.querySelector('.hp-nav');
  if (!button || !menu) return;
  button.addEventListener('click', () => {
    const open = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', String(!open));
    menu.classList.toggle('is-open', !open);
  });
});
