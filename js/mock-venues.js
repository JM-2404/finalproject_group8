const venues = [
  {
    id: 1,
    name: "Library",
    location: "Ashesi University, Berekuso",
    parentVenue: "Ashesi University Campus",
    category: "Academic",
    score: 85,
    rating: 4.5,
    features: {
      wheelchairEntrance: true,
      ramps: true,
      elevator: false,
      accessibleToilet: true,
      parking: true,
      wideDoors: true
    }
  },
  {
    id: 2,
    name: "Engineering Building",
    location: "Ashesi University, Berekuso",
    parentVenue: "Ashesi University Campus",
    category: "Academic",
    score: 70,
    rating: 4.2,
    features: {
      wheelchairEntrance: true,
      ramps: true,
      elevator: true,
      accessibleToilet: false,
      parking: true,
      wideDoors: true
    }
  },
  {
    id: 3,
    name: "Cafeteria",
    location: "Ashesi University, Berekuso",
    parentVenue: "Ashesi University Campus",
    category: "Dining",
    score: 60,
    rating: 4.0,
    features: {
      wheelchairEntrance: true,
      ramps: true,
      elevator: false,
      accessibleToilet: false,
      parking: false,
      wideDoors: true
    }
  },
  {
    id: 4,
    name: "Amphitheatre",
    location: "Ashesi University, Berekuso",
    parentVenue: "Ashesi University Campus",
    category: "Events",
    score: 45,
    rating: 4.3,
    features: {
      wheelchairEntrance: false,
      ramps: true,
      elevator: false,
      accessibleToilet: false,
      parking: true,
      wideDoors: false
    }
  },
  {
    id: 5,
    name: "Sports Field",
    location: "Ashesi University, Berekuso",
    parentVenue: "Ashesi University Campus",
    category: "Sports",
    score: 35,
    rating: 3.9,
    features: {
      wheelchairEntrance: false,
      ramps: false,
      elevator: false,
      accessibleToilet: false,
      parking: true,
      wideDoors: false
    }
  },
  {
    id: 6,
    name: "On-Campus Housing",
    location: "Ashesi University, Berekuso",
    parentVenue: "Ashesi University Campus",
    category: "Residence",
    score: 75,
    rating: 4.1,
    features: {
      wheelchairEntrance: true,
      ramps: true,
      elevator: false,
      accessibleToilet: true,
      parking: true,
      wideDoors: true
    }
  },
  {
    id: 7,
    name: "Off-Campus Housing",
    location: "Berekuso",
    parentVenue: "Berekuso",
    category: "Residence",
    score: 30,
    rating: 3.5,
    features: {
      wheelchairEntrance: false,
      ramps: false,
      elevator: false,
      accessibleToilet: false,
      parking: true,
      wideDoors: true
    }
  },
  {
    id: 8,
    name: "STEM Building",
    location: "Ashesi University, Berekuso",
    parentVenue: "Ashesi University Campus",
    category: "Academic",
    score: 95,
    rating: 4.7,
    features: {
      wheelchairEntrance: true,
      ramps: true,
      elevator: true,
      accessibleToilet: true,
      parking: true,
      wideDoors: true
    }
  },
  {
    id: 9,
    name: "The Hive",
    location: "Ashesi University, Berekuso",
    parentVenue: "Ashesi University Campus",
    category: "Innovation",
    score: 72,
    rating: 4.3,
    features: {
      wheelchairEntrance: true,
      ramps: true,
      elevator: false,
      accessibleToilet: true,
      parking: false,
      wideDoors: false
    }
  }
];