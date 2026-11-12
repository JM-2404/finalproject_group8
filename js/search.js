const venueList = document.getElementById("venue-list");
const searchInput = document.getElementById("search-input");
const resultCount = document.getElementById("result-count");

// Show a list of venues as cards
function renderVenues(list) {
  venueList.innerHTML = "";

  if (list.length === 0) {
    venueList.innerHTML = "<p>No venues found.</p>";
  }

  list.forEach(function (v) {
    const card = document.createElement("article");
    card.className = "venue-card";
    card.innerHTML =
      "<h3>" + v.name + "</h3>" +
      "<p>" + v.location + " | " + v.category + "</p>" +
      "<p>Score: " + v.score + "/100 | Rating: " + v.rating + "</p>";
    venueList.appendChild(card);
  });

  resultCount.textContent = list.length + " venues found";
}

// Keep only venues that match the search text
function searchVenues(text) {
  const q = text.toLowerCase().trim();
  return venues.filter(function (v) {
    return v.name.toLowerCase().includes(q) ||
           v.location.toLowerCase().includes(q) ||
           v.category.toLowerCase().includes(q);
  });
}

// Search as the user types
searchInput.addEventListener("input", function () {
  renderVenues(searchVenues(searchInput.value));
});

// Show everything when the page loads
renderVenues(venues);