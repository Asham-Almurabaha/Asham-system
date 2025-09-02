// Sidebar toggle and mobile enhancements
document.addEventListener('DOMContentLoaded', function () {
  var body = document.body;
  function toggleSidebar(force) {
    if (typeof force === 'boolean') {
      body.classList.toggle('toggle-sidebar', force);
    } else {
      body.classList.toggle('toggle-sidebar');
    }
  }

  // Click on header toggle button or icon
  document.addEventListener('click', function (e) {
    var t = e.target;
    if (!t) return;
    var isIcon = t.classList.contains('toggle-sidebar-btn') || (t.closest && t.closest('.toggle-sidebar-btn'));
    var isToggleButton = t.closest && t.closest('[aria-label="Toggle sidebar"]');
    if (isIcon || isToggleButton) {
      e.preventDefault();
      toggleSidebar();
    }
  });

  // Close sidebar when clicking outside on small screens
  document.addEventListener('click', function (e) {
    if (!body.classList.contains('toggle-sidebar')) return;
    var sidebar = document.getElementById('sidebar');
    var isInsideSidebar = sidebar && (sidebar === e.target || (sidebar.contains && sidebar.contains(e.target)));
    var isToggle = e.target.closest && e.target.closest('.toggle-sidebar-btn');
    if (!isInsideSidebar && !isToggle && window.innerWidth < 1200) {
      toggleSidebar(false);
    }
  });

  // ESC to close on small screens
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && body.classList.contains('toggle-sidebar') && window.innerWidth < 1200) {
      toggleSidebar(false);
    }
  });
});
