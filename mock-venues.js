const venues = [
  { id: 1, name: "Accra Mall", location: "Tetteh Quarshie", category: "Shopping", score: 85, rating: 4.5,
    features: { wheelchairEntrance: true, ramps: true, elevator: true, accessibleToilet: true, parking: true, wideDoors: true } },
  { id: 2, name: "Kwame Nkrumah Memorial Park", location: "Central Accra", category: "Park", score: 60, rating: 4.2,
    features: { wheelchairEntrance: true, ramps: true, elevator: false, accessibleToilet: false, parking: true, wideDoors: true } },
  { id: 3, name: "Osu Cafe", location: "Osu", category: "Restaurant", score: 35, rating: 3.8,
    features: { wheelchairEntrance: false, ramps: false, elevator: false, accessibleToilet: false, parking: true, wideDoors: false } },
  { id: 4, name: "Korle Bu Hospital", location: "Korle Bu", category: "Hospital", score: 78, rating: 4.0,
    features: { wheelchairEntrance: true, ramps: true, elevator: true, accessibleToilet: true, parking: false, wideDoors: true } },
  { id: 5, name: "National Theatre", location: "Cantonments", category: "Entertainment", score: 55, rating: 4.1,
    features: { wheelchairEntrance: true, ramps: false, elevator: false, accessibleToilet: true, parking: true, wideDoors: false } },
  { id: 6, name: "Labadi Beach Hotel", location: "Labadi", category: "Hotel", score: 72, rating: 4.4,
    features: { wheelchairEntrance: true, ramps: true, elevator: true, accessibleToilet: false, parking: true, wideDoors: true } }
];