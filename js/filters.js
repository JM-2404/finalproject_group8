/**
 * FILTERS (Member 2)
 * Works out how well a venue matches the features the user ticked.
 * Each venue feature has one of three statuses: available, unavailable, unknown.
 */

// Display names for each feature key (the keys match the database).
const FEATURE_LABELS = {
  wheelchairEntrance: "Wheelchair entrance",
  ramps: "Ramps",
  elevator: "Elevator",
  accessibleToilet: "Accessible toilet",
  parking: "Accessible parking",
  wideDoors: "Wide doors"
};

// Read the ticked checkboxes and return their keys, e.g. ["ramps", "elevator"].
function getSelectedFeatures() {
  const ticked = document.querySelectorAll(".filters input[type='checkbox']:checked");
  return Array.from(ticked).map(function (box) {
    return box.value;
  });
}

// Compare one venue against the ticked features.
function matchVenue(venue, selected) {
  let met = 0;
  const missing = [];   // confirmed unavailable
  const unknown = [];   // not verified

  selected.forEach(function (key) {
    // A missing record counts as UNKNOWN, never as unavailable.
    const status = venue.features[key] || "unknown";
    if (status === "available") {
      met++;
    } else if (status === "unavailable") {
      missing.push(key);
    } else {
      unknown.push(key);
    }
  });

  // The outcome follows the three rules.
  let outcome = "none";                          // nothing ticked
  if (selected.length > 0) {
    if (missing.length > 0) {
      outcome = "not-met";                       // something is confirmed missing
    } else if (unknown.length > 0) {
      outcome = "unverified";                    // nothing missing, but not all verified
    } else {
      outcome = "met";                           // everything confirmed available
    }
  }

  return { met: met, missing: missing, unknown: unknown, outcome: outcome };
}