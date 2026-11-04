const lightbox = document.querySelector('#lightbox');
document.querySelectorAll('.photo-open').forEach(button => {
  button.addEventListener('click', () => {
    lightbox.querySelector('img').src = button.dataset.src;
    lightbox.querySelector('img').alt = button.dataset.title;
    lightbox.querySelector('h3').textContent = button.dataset.title;
    lightbox.querySelector('p').textContent = button.dataset.description || '';
    lightbox.showModal();
  });
});
lightbox.addEventListener('click', event => {
  if (event.target === lightbox) lightbox.close();
});
