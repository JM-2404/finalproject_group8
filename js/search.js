/**
 * SEARCH, SORT AND DISPLAY (Member 2)
 * Uses the "venues" array that explore.php writes into the page
 * and the functions in filters.js.
 */

const venueList = document.getElementById("venue-list");
const searchInput = document.getElementById("search-input");
const resultCount = document.getElementById("result-count");

// Labels for each outcome. A symbol and words are used, never colour alone.
const OUTCOME_LABELS = {
  "met": "\u2713 Meets your needs",
  "unverified": "? Not yet verified",
  "not-met": "\u2717 Does not meet your needs"
};

// Escape text before putting it in HTML, so venue names can't inject code (XSS).
function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text == null ? "" : String(text);
  return div.innerHTML;
}

// Draw the venue cards.
function renderVenues(items) {
  venueList.innerHTML = "";

  if (items.length === 0) {
    venueList.innerHTML = "<p>No venues match your search.</p>";
  }

  items.forEach(function (item) {
    const v = item.venue;
    const m = item.match;

    const card = document.createElement("article");
    card.className = "venue-card";

    let html = "<h3>" + escapeHtml(v.name) + "</h3>";
    if (v.parentVenue) {
      html += "<p>" + escapeHtml(v.parentVenue) + "</p>";
    }
    html += "<p>" + escapeHtml(v.location) + " | " + escapeHtml(v.category) + "</p>";

    // Outcome badge and counts, only when the user ticked something.
    if (m.outcome !== "none") {
      html += '<p class="match-badge match-' + m.outcome + '">' +
              OUTCOME_LABELS[m.outcome] + "</p>";
      html += '<p class="match-counts">' + m.met + " met, " +
              m.missing.length + " missing, " + m.unknown.length + " not verified</p>";

      // Offer a request when something is not confirmed available.
      if (m.outcome === "not-met" || m.outcome === "unverified") {
        const ask = m.missing.concat(m.unknown);
        html += '<p><a class="request-link" href="send-request.php?venue_id=' + v.id +
                '&amp;features=' + encodeURIComponent(ask.join(",")) +
                '">Send a request to this venue</a></p>';
      }
    }

    const rating = v.rating === null ? "no reviews yet" : v.rating;
    html += "<p>Score: " + v.score + "/100 | Rating: " + rating + "</p>";

    card.innerHTML = html;
    venueList.appendChild(card);
  });

  resultCount.textContent = items.length + " venues found";
}

// Keep venues whose name, parent, location or category contains the search text.
function searchVenues(text) {
  const q = text.toLowerCase().trim();
  return venues.filter(function (v) {
    return v.name.toLowerCase().includes(q) ||
           (v.parentVenue || "").toLowerCase().includes(q) ||
           (v.location || "").toLowerCase().includes(q) ||
           (v.category || "").toLowerCase().includes(q);
  });
}

// Sort by the dropdown choice. slice() copies the list so the original stays unchanged.
function sortItems(items) {
  const choice = document.getElementById("sort-select").value;
  const sorted = items.slice();

  if (choice === "score") {
    sorted.sort(function (a, b) { return b.venue.score - a.venue.score; });
  } else if (choice === "rating") {
    sorted.sort(function (a, b) { return (b.venue.rating || 0) - (a.venue.rating || 0); });
  } else if (choice === "name") {
    sorted.sort(function (a, b) { return a.venue.name.localeCompare(b.venue.name); });
  }
  return sorted;
}

// Best matches first. Array.sort is stable, so the sort above is kept within each group.
const OUTCOME_RANK = { "met": 0, "unverified": 1, "not-met": 2, "none": 0 };

function updateResults() {
  const selected = getSelectedFeatures();
  const show = document.getElementById("outcome-filter").value;

  // 1. search, 2. match against the ticked features
  let items = searchVenues(searchInput.value).map(function (v) {
    return { venue: v, match: matchVenue(v, selected) };
  });

  // 3. "Show" dropdown (only has an effect when features are ticked)
  if (selected.length > 0 && show !== "all") {
    items = items.filter(function (item) { return item.match.outcome === show; });
  }

  // 4. sort, then bring the best matches to the top
  items = sortItems(items);
  if (selected.length > 0) {
    items.sort(function (a, b) {
      return OUTCOME_RANK[a.match.outcome] - OUTCOME_RANK[b.match.outcome];
    });
  }

  renderVenues(items);
}

// Re-run whenever the user changes something.
searchInput.addEventListener("input", updateResults);
document.getElementById("sort-select").addEventListener("change", updateResults);
document.getElementById("outcome-filter").addEventListener("change", updateResults);
document.querySelectorAll(".filters input[type='checkbox']").forEach(function (box) {
  box.addEventListener("change", updateResults);
});

document.getElementById("clear-filters").addEventListener("click", function () {
  document.querySelectorAll(".filters input[type='checkbox']").forEach(function (box) {
    box.checked = false;
  });
  document.getElementById("outcome-filter").value = "all";
  updateResults();
});

// Show everything when the page loads.
updateResults();