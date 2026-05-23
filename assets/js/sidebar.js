document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("toggleSidebar");
    const sidebar = document.querySelector(".sidebar");
    const mobileMenuToggle = document.getElementById("mobileMenuToggle");
    const sidebarOverlay = document.querySelector(".sidebar-overlay");
    // Menu group expand button (toggle submenus) - with improved touch support
    document.querySelectorAll(".menu-group-expand").forEach(function (btn) {
        function toggleSubmenu(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            var group = btn.closest(".menu-group");
            if (group) group.classList.toggle("open");
        }
        
        btn.addEventListener("click", toggleSubmenu);
        
        // Add touch support for mobile - use touchend instead of touchstart for better UX
        btn.addEventListener("touchend", function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSubmenu();
        });
    });

    // Auto-expand menu group containing current page
    var currentPage = window.location.pathname.split("/").pop();
    document.querySelectorAll(".menu-sub a").forEach(function (link) {
        var href = link.getAttribute("href");
        if (href && href === currentPage) {
            link.classList.add("active");
            var group = link.closest(".menu-group");
            if (group) group.classList.add("open");
        }
    });
    document.querySelectorAll(".sidebar a:not(.menu-group-toggle)").forEach(function (link) {
        var href = link.getAttribute("href");
        if (href && href === currentPage && !link.closest(".menu-sub")) {
            link.classList.add("active");
        }
    });

    // Desktop sidebar toggle (collapse/expand/hide)
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            if (window.innerWidth > 770) {
                if (sidebar.classList.contains("hidden")) {
                    sidebar.classList.remove("hidden");
                    sidebar.classList.add("collapsed");
                } else if (sidebar.classList.contains("collapsed")) {
                    sidebar.classList.remove("collapsed");
                } else {
                    sidebar.classList.add("hidden");
                }
            }
        });
    }

    // Mobile menu toggle - open sidebar overlay with improved touch support
    if (mobileMenuToggle && sidebar) {
        function toggleMobileSidebar(e) {
            e.stopPropagation();
            sidebar.classList.toggle("open");
            if (sidebarOverlay) sidebarOverlay.classList.toggle("active");
        }
        
        mobileMenuToggle.addEventListener("click", toggleMobileSidebar);
        mobileMenuToggle.addEventListener("touchstart", function (e) {
            e.preventDefault();
            toggleMobileSidebar(e);
        });
    }

    // Close sidebar when overlay clicked
    if (sidebarOverlay) {
        function closeSidebar(e) {
            e.stopPropagation();
            sidebar.classList.remove("open");
            sidebarOverlay.classList.remove("active");
        }
        
        sidebarOverlay.addEventListener("click", closeSidebar);
        sidebarOverlay.addEventListener("touchstart", function (e) {
            e.preventDefault();
            closeSidebar(e);
        });
    }

    // Close sidebar when clicking a sidebar link on mobile
    document.querySelectorAll(".sidebar .menu a").forEach(function (link) {
        link.addEventListener("click", function () {
            if (window.innerWidth <= 770) {
                sidebar.classList.remove("open");
                if (sidebarOverlay) sidebarOverlay.classList.remove("active");
            }
        });
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener("click", function (e) {
        if (window.innerWidth <= 770 && sidebar && sidebar.classList.contains("open")) {
            if (!sidebar.contains(e.target) && mobileMenuToggle && !mobileMenuToggle.contains(e.target)) {
                sidebar.classList.remove("open");
                if (sidebarOverlay) sidebarOverlay.classList.remove("active");
            }
        }
    });

    // Handle window resize
    window.addEventListener("resize", function () {
        if (window.innerWidth > 770) {
            sidebar.classList.remove("open");
            if (sidebarOverlay) sidebarOverlay.classList.remove("active");
        }
        var mobileToggle = document.getElementById("mobileMenuToggle");
        var desktopToggle = document.getElementById("toggleSidebar");
        if (window.innerWidth <= 770) {
            if (mobileToggle) {
                mobileToggle.style.display = "flex";
                mobileToggle.style.visibility = "visible";
                mobileToggle.style.opacity = "1";
                mobileToggle.style.pointerEvents = "auto";
            }
            if (desktopToggle) {
                desktopToggle.style.display = "none";
                desktopToggle.style.visibility = "hidden";
                desktopToggle.style.pointerEvents = "none";
            }
        } else {
            if (mobileToggle) {
                mobileToggle.style.display = "none";
                mobileToggle.style.visibility = "hidden";
            }
            if (desktopToggle) {
                desktopToggle.style.display = "block";
                desktopToggle.style.visibility = "visible";
                desktopToggle.style.opacity = "1";
                desktopToggle.style.pointerEvents = "auto";
            }
        }
    });
});
