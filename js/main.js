
/* =========================================
   ACCESSABLE - MAIN JAVASCRIPT
========================================= */

document.addEventListener("DOMContentLoaded", function () {

    // MOBILE NAVIGATION

    const menuToggle = document.getElementById("menuToggle");
    const navLinks = document.getElementById("navLinks");

    if (menuToggle && navLinks) {

        menuToggle.addEventListener("click", function () {

            const isOpen = navLinks.classList.toggle("open");

            menuToggle.setAttribute("aria-expanded", isOpen);

            menuToggle.setAttribute(
                "aria-label",
                isOpen ? "Close navigation menu" : "Open navigation menu"
            );

        });

        // Close navigation after selecting a link

        const navigationItems = navLinks.querySelectorAll("a");

        navigationItems.forEach(function (link) {

            link.addEventListener("click", function () {

                navLinks.classList.remove("open");

                menuToggle.setAttribute("aria-expanded", "false");
                menuToggle.setAttribute("aria-label", "Open navigation menu");

            });

        });

        // Close navigation when clicking outside it

        document.addEventListener("click", function (event) {

            const clickedInsideMenu = navLinks.contains(event.target);
            const clickedToggle = menuToggle.contains(event.target);

            if (!clickedInsideMenu && !clickedToggle) {

                navLinks.classList.remove("open");

                menuToggle.setAttribute("aria-expanded", "false");
                menuToggle.setAttribute("aria-label", "Open navigation menu");

            }

        });

    }


    // SEARCH FORM VALIDATION

    const searchForm = document.querySelector(".venue-search");

    if (searchForm) {

        searchForm.addEventListener("submit", function (event) {

            const venueInput = document.getElementById("venue-search");
            const locationInput = document.getElementById("venue-location");

            const venueValue = venueInput ? venueInput.value.trim() : "";
            const locationValue = locationInput ? locationInput.value.trim() : "";

            if (!venueValue && !locationValue) {

                event.preventDefault();

                alert("Please enter a venue name or location to search.");

                if (venueInput) {
                    venueInput.focus();
                }

            }

        });

    }


    // SMOOTH SCROLLING

    const scrollLinks = document.querySelectorAll('a[href^="#"]');

    scrollLinks.forEach(function (link) {

        link.addEventListener("click", function (event) {

            const targetId = link.getAttribute("href");

            if (targetId && targetId.length > 1) {

                const targetElement = document.querySelector(targetId);

                if (targetElement) {

                    event.preventDefault();

                    targetElement.scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    });

                }

            }

        });

    });

});
