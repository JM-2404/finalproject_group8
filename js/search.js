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
// Sort the list based on the dropdown choice
function sortVenues(list) {
  const choice = document.getElementById("sort-select").value;
  const sorted = list.slice(); // copy, so the original list stays untouched

  if (choice === "score") {
    sorted.sort(function (a, b) { return b.score - a.score; });
  } else if (choice === "rating") {
    sorted.sort(function (a, b) { return b.rating - a.rating; });
  } else if (choice === "name") {
    sorted.sort(function (a, b) { return a.name.localeCompare(b.name); });
  }

  return sorted;
}

// Run search, then filters, then show the results
function updateResults() {
  let results = searchVenues(searchInput.value);
  results = filterByFeatures(results);
  results = sortVenues(results);
  renderVenues(results);
}

searchInput.addEventListener("input", updateResults);
document.getElementById("sort-select").addEventListener("change", updateResults);

document.querySelectorAll(".filters input[type='checkbox']").forEach(function (box) {
  box.addEventListener("change", updateResults);
});

document.getElementById("clear-filters").addEventListener("click", function () {
  document.querySelectorAll(".filters input[type='checkbox']").forEach(function (box) {
    box.checked = false;
  });
  updateResults();
});

// Show everything when the page loads
updateResults();