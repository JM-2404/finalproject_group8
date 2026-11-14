
<?php
$pageTitle = "AccessAble | Find Spaces That Welcome Everyone";

$featuredVenues = [
    [
        "name" => "Ashesi University Campus",
        "location" => "Berekuso, Eastern Region",
        "category" => "Education",
        "description" => "Explore campus facilities, learning spaces, student residences and common areas.",
        "image" => "https://res.cloudinary.com/bizzaboprod/image/upload/q_auto%2Cf_auto%2Cc_scale%2Cc_crop%2Cg_custom/v1680132964/yaehuu00wwhbvpdec7oc",
        "tag" => "Campus"
    ],
    [
        "name" => "Rose Garden Restaurant",
        "location" => "Accra, Ghana",
        "category" => "Restaurant",
        "description" => "Explore a community dining space and discover its accessibility facilities.",
        "image" => "https://images.squarespace-cdn.com/content/v1/65cfd1369377d32bcd0051fa/5cd4f62f-c406-46d5-b2e6-e90bbcedd39c/Rose%2BGarden-%2BSheeda%2BTravel%2BTribe.jpg?format=1000w",
        "tag" => "Dining"
    ],
    [
        "name" => "Kwame Nkrumah Memorial Park",
        "location" => "Accra, Greater Accra",
        "category" => "Recreation and Heritage",
        "description" => "Discover a historic public attraction, its gardens, museum and visitor facilities.",
        "image" => "https://a.storyblok.com/f/69559/1920x1282/b25afa33da/accra-1947674_1920.jpg",
        "tag" => "Heritage"
    ]
];

$accessibilityFeatures = [
    [
        "icon" => "♿",
        "title" => "Wheelchair Access",
        "description" => "Find information about wheelchair-friendly entrances and spaces."
    ],
    [
        "icon" => "↗",
        "title" => "Ramps",
        "description" => "Explore venues with ramp access and step-free routes."
    ],
    [
        "icon" => "↕",
        "title" => "Elevators",
        "description" => "Discover information about elevator availability."
    ],
    [
        "icon" => "🚻",
        "title" => "Accessible Toilets",
        "description" => "Check whether accessible restroom facilities are provided."
    ],
    [
        "icon" => "🅿",
        "title" => "Accessible Parking",
        "description" => "Find information about designated accessible parking."
    ],
    [
        "icon" => "◎",
        "title" => "Guidance and Signage",
        "description" => "Explore tactile guidance and Braille signage information."
    ]
];

$pageStyles = ['css/home.css'];
include 'includes/header.php';
include 'includes/navigation.php';
?>

<main>

    <!-- HERO SECTION -->
    <section class="hero-section">
        <div class="container hero-content">

            <div class="hero-text">
                <span class="eyebrow">
                    <span class="eyebrow-dot"></span>
                    ACCESSIBILITY STARTS WITH INFORMATION
                </span>

                <h1>
                    Find spaces that
                    <span>welcome everyone.</span>
                </h1>

                <p class="hero-description">
                    Discover venues, explore accessibility features
                    and make informed decisions before you visit.
                    Because everyone deserves to feel welcome.
                </p>

                <div class="hero-buttons">
                    <a href="explore.php" class="btn btn-primary">
                        Explore Venues <span>→</span>
                    </a>

                    <a href="#how-it-works" class="btn btn-outline">
                        How it works
                    </a>
                </div>

                <div class="hero-note">
                    <span class="note-icon">✓</span>
                    <span>Discover. Understand. Explore.</span>
                </div>
            </div>

            <div class="hero-visual">
                <div class="hero-image-wrapper">
                    <img
                        src="https://images.unsplash.com/photo-1511818966892-d7d671e672a2?auto=format&fit=crop&w=1000&q=85"
                        alt="Modern public building with an inviting architectural entrance"
                    >

                    <div class="floating-card floating-card-top">
                        <span class="floating-icon">♿</span>
                        <div>
                            <strong>Accessibility matters</strong>
                            <small>Information for everyone</small>
                        </div>
                    </div>

                    <div class="floating-card floating-card-bottom">
                        <span class="floating-check">✓</span>
                        <div>
                            <strong>Make informed choices</strong>
                            <small>Before you visit</small>
                        </div>
                    </div>
                </div>

                <div class="hero-decoration"></div>
            </div>

        </div>
    </section>


    <!-- SEARCH SECTION -->
    <section class="search-section">
        <div class="container">

            <div class="search-panel">
                <div class="search-heading">
                    <span class="section-label">START EXPLORING</span>
                    <h2>Where would you like to go?</h2>
                    <p>Search for a venue and discover its accessibility information.</p>
                </div>

                <form action="explore.php" method="GET" class="venue-search">

                    <div class="search-field">
                        <label for="venue-search">Venue or place</label>
                        <div class="input-wrapper">
                            <span>⌕</span>
                            <input
                                type="search"
                                id="venue-search"
                                name="search"
                                placeholder="Enter a venue name..."
                            >
                        </div>
                    </div>

                    <div class="search-field">
                        <label for="venue-location">Location</label>
                        <div class="input-wrapper">
                            <span>⌖</span>
                            <input
                                type="text"
                                id="venue-location"
                                name="location"
                                placeholder="e.g. Accra"
                            >
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary search-button">
                        Search Venues →
                    </button>

                </form>
            </div>

        </div>
    </section>


    <!-- FEATURED VENUES -->
    <section class="featured-section section-padding">
        <div class="container">

            <div class="section-heading">
                <div>
                    <span class="section-label">DISCOVER PLACES</span>
                    <h2>Explore spaces around you.</h2>
                    <p>
                        Start discovering different types of public spaces
                        and the accessibility information available about them.
                    </p>
                </div>

                <a href="explore.php" class="text-link">
                    Explore all venues →
                </a>
            </div>

            <div class="venue-grid">

                <?php foreach ($featuredVenues as $venue): ?>

                    <article class="venue-card">

                        <div class="venue-image">
                            <img
                                src="<?php echo htmlspecialchars($venue['image']); ?>"
                                alt="<?php echo htmlspecialchars($venue['category']); ?> venue example"
                                loading="lazy"
                            >

                            <span class="venue-category">
                                <?php echo htmlspecialchars($venue['tag']); ?>
                            </span>
                        </div>

                        <div class="venue-info">

                            <span class="venue-location">
                                ⌖ <?php echo htmlspecialchars($venue['location']); ?>
                            </span>

                            <h3>
                                <?php echo htmlspecialchars($venue['name']); ?>
                            </h3>

                            <p>
                                <?php echo htmlspecialchars($venue['description']); ?>
                            </p>

                            <div class="venue-card-bottom">
                                <span class="demo-label">
                                    Example listing
                                </span>

                                <a href="explore.php" class="card-link">
                                    Explore <span>→</span>
                                </a>
                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

            <p class="sample-disclaimer">
                These are demonstration listings, not verified venue records.
                Accessibility information will be provided through the project database.
            </p>

        </div>
    </section>


    <!-- ACCESSIBILITY FEATURES -->
    <section class="features-section section-padding">
        <div class="container">

            <div class="centered-heading">
                <span class="section-label">WHAT MATTERS</span>
                <h2>Accessibility looks different for everyone.</h2>
                <p>
                    Explore the features that can make public spaces
                    more comfortable and accessible.
                </p>
            </div>

            <div class="features-grid">

                <?php foreach ($accessibilityFeatures as $feature): ?>

                    <article class="feature-card">

                        <div class="feature-icon">
                            <?php echo $feature['icon']; ?>
                        </div>

                        <h3>
                            <?php echo htmlspecialchars($feature['title']); ?>
                        </h3>

                        <p>
                            <?php echo htmlspecialchars($feature['description']); ?>
                        </p>

                    </article>

                <?php endforeach; ?>

            </div>

        </div>
    </section>


    <!-- HOW IT WORKS -->
    <section class="how-section section-padding" id="how-it-works">
        <div class="container">

            <div class="centered-heading">
                <span class="section-label">SIMPLE AND INFORMATIVE</span>
                <h2>How AccessAble works.</h2>
                <p>
                    Finding useful accessibility information should
                    be straightforward.
                </p>
            </div>

            <div class="steps-grid">

                <article class="step-card">
                    <span class="step-number">01</span>
                    <div class="step-icon">⌕</div>
                    <h3>Search for a venue</h3>
                    <p>
                        Find public spaces by searching for their name,
                        location or category.
                    </p>
                </article>

                <article class="step-card">
                    <span class="step-number">02</span>
                    <div class="step-icon">◎</div>
                    <h3>Explore accessibility</h3>
                    <p>
                        Review available accessibility features,
                        venue details and visitor feedback.
                    </p>
                </article>

                <article class="step-card">
                    <span class="step-number">03</span>
                    <div class="step-icon">✓</div>
                    <h3>Make informed choices</h3>
                    <p>
                        Use the information to decide which spaces
                        best meet your needs.
                    </p>
                </article>

            </div>

        </div>
    </section>


    <!-- MISSION SECTION -->
    <section class="mission-section section-padding">
        <div class="container mission-container">

            <div class="mission-content">
                <span class="section-label">OUR PURPOSE</span>

                <h2>
                    A more accessible world begins with better information.
                </h2>

                <p>
                    AccessAble believes that people should be able to
                    understand the accessibility of a space before
                    they decide to visit it.
                </p>

                <p>
                    By bringing accessibility information together,
                    we hope to encourage inclusion, awareness and
                    more informed decisions.
                </p>

                <a href="about.php" class="btn btn-light">
                    Learn about us →
                </a>
            </div>

            <div class="mission-visual">
                <div class="mission-circle">
                    <span>ACCESS</span>
                    <strong>FOR</strong>
                    <span>EVERYONE</span>
                    <div class="mission-symbol">♿</div>
                </div>
            </div>

        </div>
    </section>


    <!-- CALL TO ACTION -->
    <section class="cta-section">
        <div class="container cta-container">

            <div>
                <span class="section-label">YOUR NEXT VISIT STARTS HERE</span>
                <h2>Explore with confidence.</h2>
                <p>
                    Start discovering public spaces and the
                    accessibility information that matters.
                </p>
            </div>

            <a href="explore.php" class="btn btn-primary">
                Explore AccessAble →
            </a>

        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>
