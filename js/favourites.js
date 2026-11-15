
const searchInput = document.getElementById("favourite-search");

const favouriteCards = document.querySelectorAll(".favourite-card");

searchInput.addEventListener("input", function () {

    const searchValue = searchInput.value.toLowerCase();

    favouriteCards.forEach(function (card) {

        const venueName = card.querySelector("h2")
            .textContent.toLowerCase();

        const venueLocation = card.querySelector("p")
            .textContent.toLowerCase();

        const matches =
            venueName.includes(searchValue) ||
            venueLocation.includes(searchValue);

        card.style.display = matches ? "block" : "none";

    });

});
