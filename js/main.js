const toggleBtn = document.getElementById('toggleBtn');
  const sidebar = document.getElementById('sidebar');

  toggleBtn.addEventListener('click', () => {
    if (window.innerWidth > 768) {
      sidebar.classList.toggle('hide-sidebar');
    } else {
      sidebar.classList.toggle('show-sidebar');
    }
  });

  const menuLinks = document.querySelectorAll('.menu-link');
  menuLinks.forEach(link => {
    link.addEventListener('click', function () {
      menuLinks.forEach(l => l.classList.remove('active'));
      this.classList.add('active');
    });
  });

  const profileIcon = document.getElementById('profileIcon');
  const profileDropdown = document.querySelector('.profile-dropdown');

  profileIcon.addEventListener('click', (e) => {
    e.stopPropagation();
    profileDropdown.classList.toggle('show');
  });

  document.addEventListener('click', (e) => {
    if (!profileDropdown.contains(e.target)) {
      profileDropdown.classList.remove('show');
    }
  });