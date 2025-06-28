document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    // const mainContent = document.querySelector('.flex-1.overflow-auto'); // To potentially add an overlay effect later

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('-translate-x-full'); // For small screens
            // Optional: Add an overlay to the main content when sidebar is open on mobile
            // For example, create a div, append it, and toggle its visibility.
        });
    }

    // Optional: Close sidebar if clicking outside of it on mobile
    // document.addEventListener('click', function(event) {
    //     const isClickInsideSidebar = sidebar.contains(event.target);
    //     const isClickOnToggle = sidebarToggle.contains(event.target);
    //     if (!isClickInsideSidebar && !isClickOnToggle && !sidebar.classList.contains('-translate-x-full') && window.innerWidth < 1024) {
    //         sidebar.classList.add('-translate-x-full');
    //     }
    // });
});
