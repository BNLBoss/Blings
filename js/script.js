document.addEventListener("DOMContentLoaded", () => {
  const menuBtn = document.querySelector('.menu-btn');
  const sidePanel = document.querySelector('.side-panel');

  menuBtn.addEventListener('click', function () {
    sidePanel.classList.toggle('expanded');
  });

  document.addEventListener('click', function (event) {
    if (!sidePanel.contains(event.target) && !menuBtn.contains(event.target)) {
      sidePanel.classList.remove('expanded');
    }
  });
});
