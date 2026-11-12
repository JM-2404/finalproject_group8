<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Explore Venues | AccessAble</title>
  <link rel="stylesheet" href="css/explore.css">
</head>
<body>
  <main class="explore">
    <h1>Explore Venues</h1>

    <!-- Search and sort -->
    <section class="search-bar">
      <label for="search-input">Search venues</label>
      <input type="text" id="search-input" placeholder="Search by name, location or category">

      <label for="sort-select">Sort by</label>
      <select id="sort-select">
        <option value="score">Accessibility score</option>
        <option value="rating">Rating</option>
        <option value="name">A to Z</option>
      </select>
    </section>

    <div class="explore-layout">
      <!-- Filters -->
      <aside class="filters">
        <h2>Accessibility filters</h2>
        <label><input type="checkbox" value="wheelchairEntrance"> Wheelchair entrance</label>
        <label><input type="checkbox" value="ramps"> Ramps</label>
        <label><input type="checkbox" value="elevator"> Elevator</label>
        <label><input type="checkbox" value="accessibleToilet"> Accessible toilet</label>
        <label><input type="checkbox" value="parking"> Accessible parking</label>
        <label><input type="checkbox" value="wideDoors"> Wide doors</label>
        <button type="button" id="clear-filters">Clear filters</button>
      </aside>

      <!-- Results -->
      <section class="results">
        <p id="result-count" aria-live="polite"></p>
        <div id="venue-list"></div>
      </section>
    </div>
  </main>

  <script src="js/mock-venues.js"></script>
  <script src="js/filters.js"></script>
  <script src="js/search.js"></script>
</body>
</html>