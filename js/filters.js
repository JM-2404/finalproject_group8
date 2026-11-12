// Keep only venues that have ALL the ticked features
function filterByFeatures(list) {
  const ticked = document.querySelectorAll(".filters input[type='checkbox']:checked");
  const selected = Array.from(ticked).map(function (box) {
    return box.value;
  });

  return list.filter(function (v) {
    return selected.every(function (feature) {
      return v.features[feature];
    });
  });
}