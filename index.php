<?php

session_start();

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/Validation.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$database = getDatabase();

require_once __DIR__ . '/includes/site-data.php';
require_once __DIR__ . '/actions/application-actions.php';

$searchTerm = trim($_GET['q'] ?? '');

if ($searchTerm !== '') {

    $postings = array_values(array_filter($postings, function ($posting) use ($searchTerm) {

        $haystack =
            $posting['title'] . ' ' .
            $posting['category_name'] . ' ' .
            $posting['client_org'] . ' ' .
            $posting['skills_needed'];

        return stripos($haystack, $searchTerm) !== false;
    }));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ComMEETtee — where skills and responsibility meets.</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="style.css?v=<?php echo file_exists(__DIR__ . '/style.css') ? filemtime(__DIR__ . '/style.css') : time(); ?>"
    >

   <script>
    window.IS_LOGGED_IN = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
</script>
<script
    src="script.js?v=<?php echo file_exists(__DIR__ . '/script.js') ? filemtime(__DIR__ . '/script.js') : time(); ?>"
    defer
></script>

</head>

<body>

<!-- ============ NAV BAR ============ -->

<header class="site-header">

    <div class="nav-wrap">

        <a href="#top" class="brand" aria-label="HOME">
            <img src="Pictures/logo.png" alt="ComMEETtee" class="brand-logo">
        </a>

        <nav class="primary-nav" aria-label="Primary">

            <ul>

                <li>
                    <a href="#top" class="nav-pill">Home</a>
                </li>

                <li>
                    <a href="#aspirants" class="nav-pill">Aspirants</a>
                </li>

                <li class="has-dropdown">

                    <a href="#clients" class="nav-pill nav-pill-label">
                        Clients
                    </a>

                    <button
                        type="button"
                        class="dropdown-toggle"
                        aria-expanded="false"
                        aria-label="Show Clients options"
                    >
                        <svg class="chev" viewBox="0 0 12 8" width="10" height="7">
                            <path
                                d="M1 1l5 5 5-5"
                                stroke="currentColor"
                                stroke-width="2"
                                fill="none"
                            />
                        </svg>
                    </button>

                    <ul class="dropdown">

                        <li>
                            <a href="#clients">All Clients</a>
                        </li>

                        <li>
                            <a href="register.php">
                                Post as Independent Client
                            </a>
                        </li>

                        <li>
                            <a href="register.php">
                                Post as Organization Client
                            </a>
                        </li>

                    </ul>

                </li>

                <li class="has-dropdown">

                    <a href="#committees" class="nav-pill nav-pill-label">
                        Committees
                    </a>

                    <button
                        type="button"
                        class="dropdown-toggle"
                        aria-expanded="false"
                        aria-label="Show Committees options"
                    >
                        <svg class="chev" viewBox="0 0 12 8" width="10" height="7">
                            <path
                                d="M1 1l5 5 5-5"
                                stroke="currentColor"
                                stroke-width="2"
                                fill="none"
                            />
                        </svg>
                    </button>

                    <ul class="dropdown">

                        <?php foreach ($categories as $category): ?>

                            <li>
                                <a
                                    href="#committees"
                                    data-category-link="<?php echo htmlspecialchars($category['id']); ?>"
                                >
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </li>

                <li>
                    <a href="#about" class="nav-pill">About Us</a>
                </li>

            </ul>

        </nav>

        <?php include __DIR__ . '/includes/nav-actions.php'; ?>

    </div>

    <!-- mobile menu -->

    <nav class="mobile-nav" id="mobileNav" aria-label="Mobile">

        <ul>

            <li>
                <a href="#top">Home</a>
            </li>

            <li>
                <a href="#aspirants">Aspirants</a>
            </li>

            <li>
                <a href="#clients">Clients</a>
            </li>

            <li>
                <a href="#committees">Committees</a>
            </li>

            <li>
                <a href="#about">About Us</a>
            </li>

            <?php if (!empty($_SESSION['user_id'])): ?>

                <li class="mobile-actions">

                    <a href="account.php" class="link-ghost">
                        Account
                    </a>

                    <a href="logout.php" class="btn btn--orange">
                        Log Out
                    </a>

                </li>

            <?php else: ?>

                <li class="mobile-actions">

                    <a href="login.php" class="link-ghost">
                        Log In
                    </a>

                    <a href="register.php" class="btn btn--orange">
                        Sign Up
                    </a>

                </li>

            <?php endif; ?>

        </ul>

    </nav>

</header>


<!-- ============ HERO ============ -->

<section class="hero" id="top">

    <div class="hero-pitch">

        <p class="aspirants">
            ASPIRANTS,
        </p>

        <h1 class="line">
            Find where your skills<br>
            can make an impact.
        </h1>

        <p class="spotlight-caption">
            Connect with university committees that match your responsibility,
            interests, and availability.
        </p>

    </div>


    <div class="hero-box-for-search">

        <form
            class="hero-search"
            role="search"
            action="index.php#committees"
            method="get"
        >

            <input
                type="search"
                name="q"
                value="<?php echo htmlspecialchars($searchTerm); ?>"
                placeholder="Organizations, committees, skills…"
                aria-label="Search committees"
            >

            <button type="submit" aria-label="Search">

                <svg
                    viewBox="0 0 24 24"
                    width="18"
                    height="18"
                    aria-hidden="true"
                >

                    <circle
                        cx="10.5"
                        cy="10.5"
                        r="7"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    />

                    <path
                        d="M20 20l-4.5-4.5"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    />

                </svg>

            </button>

        </form>

    </div>


    <div class="hero-seat-badge" aria-hidden="true">

        <svg
            viewBox="0 0 48 48"
            width="34"
            height="34"
            fill="none"
        >

            <path
                d="M12 26V14a4 4 0 0 1 4-4h16a4 4 0 0 1 4 4v12"
                stroke="currentColor"
                stroke-width="2"
            />

            <path
                d="M12 26h24v8a2 2 0 0 1-2 2h-2v4h-4v-4H20v4h-4v-4h-2a2 2 0 0 1-2-2v-8Z"
                stroke="currentColor"
                stroke-width="2"
                stroke-linejoin="round"
            />

        </svg>

        <span>
            YOUR SEAT<br>
            IS RESERVED
        </span>

    </div>


    <div class="hero-photo-frame" aria-hidden="true">

        <img src="Pictures/curn.jpg" alt="">

        <span>
            Connect with real committees.
        </span>

    </div>

</section>


<!-- ============ CATEGORIES ============ -->

<section class="categories" id="aspirants">

    <div class="categories-inner">

        <div class="categories-grid">

            <div class="category-card" style="--i:0">

                <img
                    src="Pictures/category-individual.jpg"
                    alt="Individual Aspirants"
                >

                <button
                    class="category-tag"
                    type="button"
                    onclick="location.hash='#committees'"
                >
                    INDIVIDUAL<br>
                    <small>ASPIRANTS</small>
                </button>

            </div>


            <div class="category-card" style="--i:1">

                <img
                    src="Pictures/category-independent.jpg"
                    alt="Independent Clients"
                >

                <button
                    class="category-tag"
                    type="button"
                    onclick="location.href='register.php'"
                >
                    INDEPENDENT<br>
                    <small>CLIENT</small>
                </button>

            </div>


            <div class="category-card" style="--i:2">

                <img
                    src="Pictures/category-organization.jpg"
                    alt="Organization Clients"
                >

                <button
                    class="category-tag"
                    type="button"
                    onclick="location.href='register.php'"
                >
                    ORGANIZATION<br>
                    <small>CLIENT</small>
                </button>

            </div>

        </div>


        <div class="categories-copy">

            <div class="thin-square"></div>

            <h2>
                FIND YOUR WAY TO THE RIGHT CONNECTION
            </h2>

            <p id="categories-description">
                Not sure where you fit?
                <strong>Click and explore</strong>
                each path below to see which one matches your goals and skills —
                whether you're here to contribute your talents or build out a team.
            </p>

        </div>

    </div>

</section>


<!-- ============ COMMITTEES + OPEN POSTINGS ============ -->

<section class="carousel-section" id="committees">

    <div class="categories-links">

        <a href="#committees" class="pill-link">
            Committees
        </a>

        <a href="#clients" class="pill-link">
            Organizations
        </a>

    </div>


    <p class="carousel-kicker">
        Check out
    </p>

    <p class="carousel-subtext">
        Committee descriptions &amp; the openings currently posted by registered organizations.
    </p>


    <div
        class="carousel"
        data-committees='<?php echo htmlspecialchars(
            json_encode(
                array_map(
                    fn($c) => [
                        'key' => $c['id'],
                        'title' => $c['name'],
                        'image' => $c['image'],
                        'desc' => $c['description']
                    ],
                    $categories
                )
            ),
            ENT_QUOTES
        ); ?>'
    >

        <button
            class="carousel-arrow carousel-arrow--prev"
            aria-label="Previous committee"
            type="button"
        >

            <svg viewBox="0 0 12 20" width="14" height="22">

                <path
                    d="M10 2L2 10l8 8"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />

            </svg>

        </button>


        <div class="carousel-track">

            <button
                class="carousel-slide carousel-slide--side"
                data-role="prev"
                type="button"
            >
                <img src="Pictures/Baby.jpg" alt="">
                <span class="carousel-slide-label"></span>
            </button>


            <div
                class="carousel-slide carousel-slide--center"
                data-role="center"
            >
                <img src="Pictures/HOKAGE.jpg" alt="">
                <span class="carousel-slide-label"></span>
            </div>


            <button
                class="carousel-slide carousel-slide--side"
                data-role="next"
                type="button"
            >
                <img src="Pictures/HOKAGE.jpg" alt="">
                <span class="carousel-slide-label"></span>
            </button>

        </div>


        <button
            class="carousel-arrow carousel-arrow--next"
            aria-label="Next committee"
            type="button"
        >

            <svg viewBox="0 0 12 20" width="14" height="22">

                <path
                    d="M2 2l8 8-8 8"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />

            </svg>

        </button>

    </div>


    <div class="carousel-description">

        <h3 id="carouselTitle">
            <?php echo htmlspecialchars($categories[0]['name'] ?? ''); ?>
        </h3>

        <p id="carouselDesc">
            <?php echo htmlspecialchars($categories[0]['description'] ?? ''); ?>
        </p>

        <a href="#postings" class="carousel-more">
            See open postings.
        </a>

    </div>


    <?php if (!empty($message)): ?>

        <div class="store-message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <!-- Open postings grid -->

    <div class="postings-wrap" id="postings">

        <?php if (!$postings): ?>

            <p class="postings-empty">

                No open postings right now

                <?php
                echo $searchTerm !== ''
                    ? ' matching "' . htmlspecialchars($searchTerm) . '"'
                    : '';
                ?>.

                Check back soon, or

                <a href="register.php">
                    post an opening
                </a>

                if you're a Client.

            </p>

        <?php endif; ?>


        <div class="postings-grid" id="postings-grid">

            <?php foreach ($postings as $posting):

                $remaining = max(
                    0,
                    (int) $posting['slots'] -
                    (int) $posting['filled_slots']
                );

                $myStatus = $myApplications[$posting['id']] ?? null;

            ?>

                <div
                    class="posting-card"
                    data-category="<?php echo htmlspecialchars($posting['category_id']); ?>"
                >

                    <div class="posting-thumb">

                        <img
                            src="<?php echo htmlspecialchars(
                                $posting['image']
                                    ?: 'Pictures/committee-' .
                                       $posting['category_id'] .
                                       '.jpg'
                            ); ?>"
                            alt="<?php echo htmlspecialchars($posting['title']); ?>"
                        >

                        <span class="posting-category-badge">
                            <?php echo htmlspecialchars($posting['category_name']); ?>
                        </span>

                    </div>


                    <h3>
                        <?php echo htmlspecialchars($posting['title']); ?>
                    </h3>


                    <p class="posting-org">
                        <?php
                        echo htmlspecialchars(
                            $posting['client_org']
                                ?: $posting['client_name']
                        );
                        ?>
                    </p>


                    <p class="posting-desc">
                        <?php echo htmlspecialchars($posting['description']); ?>
                    </p>


                    <?php if ($posting['skills_needed']): ?>

                        <p class="posting-skills">
                            Skills:
                            <?php echo htmlspecialchars($posting['skills_needed']); ?>
                        </p>

                    <?php endif; ?>


                    <div
                        class="posting-slots <?php echo $remaining < 1 ? 'posting-slots--full' : ''; ?>"
                    >

                        <?php
                        echo $remaining < 1
                            ? 'Slots full'
                            : $remaining .
                              ' slot' .
                              ($remaining === 1 ? '' : 's') .
                              ' open';
                        ?>

                    </div>


                    <?php if ($myStatus): ?>

                        <div
                            class="application-status application-status--<?php echo htmlspecialchars($myStatus); ?>"
                        >
                            <?php echo htmlspecialchars(ucfirst($myStatus)); ?>
                        </div>


                        <?php if (in_array($myStatus, ['pending', 'reviewed'], true)): ?>

                            <form
                                method="post"
                                data-application-form
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="withdraw"
                                >

                                <input
                                    type="hidden"
                                    name="posting_id"
                                    value="<?php echo (int) $posting['id']; ?>"
                                >

                                <button
                                    class="cart-button posting-withdraw-button"
                                    type="submit"
                                >
                                    Withdraw
                                </button>

                            </form>

                        <?php endif; ?>


                    <?php else: ?>

                        <form
                            method="post"
                            data-application-form
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="apply"
                            >

                            <input
                                type="hidden"
                                name="posting_id"
                                value="<?php echo (int) $posting['id']; ?>"
                            >

                            <button
                                class="cart-button <?php echo $remaining < 1 ? 'out-of-stock-button' : ''; ?>"
                                type="submit"
                                <?php echo $remaining < 1 ? 'disabled' : ''; ?>
                            >
                                <?php
                                echo $remaining < 1
                                    ? 'Slots Full'
                                    : 'Apply Now';
                                ?>
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</section>


<!-- ============ SET UP YOUR PROFILE ============ -->

<section class="profile-cta">

    <div class="profile-copy">

        <h2>
            Set up your<br>
            profile now!
        </h2>

        <p>
            <strong>Choose your role</strong>
            and complete
            <strong>your profile</strong>
            with the information that best represents you —
            your skills, interests, experience, and availability.
            This is what helps
            <strong>ComMEETtee</strong>
            connect you with the right opportunities.
        </p>

        <a
            href="<?php echo isLoggedIn() ? 'account.php' : 'register.php'; ?>"
            class="btn btn--outline"
        >
            Click Here!
        </a>

    </div>


    <div class="profile-preview" aria-hidden="true">

        <div class="profile-card">

            <div class="profile-avatar">

            
                </svg>

            

                    <image src="Pictures/HOKAGE.jpg" alt="Profile Avatar" class="profile-avatar-image"></image>
                        <circle cx="50%" cy="50%" r="50%" fill="none" stroke="var(--orange)" stroke-width="4"></circle>
                   

            </div>


            <!-- FIXED IMAGE TAG -->



            <p class="profile-name">
                Ciara Amber T. Saycon
            </p>

            <p class="profile-role">
                Graphic Designer
                <span>Aspirant</span>
            </p>

            <p class="profile-program">
                BS Information Technology · Year 3
            </p>

        </div>


        <div class="profile-panels">

            <div class="profile-panel">

                <h4>
                    Affiliations
                </h4>

                <ol>

                    <li>
                        Information Technology Society
                    </li>

                    <li>
                        League of Student Organizations
                    </li>

                </ol>

            </div>


            <div class="profile-panel profile-panel--row">

                <div>

                    <h4>
                        Portfolio Uploads
                    </h4>

                    <p class="profile-panel-hint">
                        Click to view documents.
                    </p>

                </div>

                <span class="profile-badge">
                    12+
                </span>

            </div>


            <div class="profile-panel">

                <h4>
                    About Me
                </h4>

                <ul>

                    <li>
                        Digital &amp; traditional visual artist
                    </li>

                    <li>
                        An aspiring graphic designer
                    </li>

                </ul>

            </div>


            <div class="profile-panel">

                <h4>
                    Ratings
                </h4>

                <div
                    class="profile-stars"
                    style="--fill:80%"
                ></div>

                <p class="profile-panel-hint">
                    Rated by 20 clients
                </p>

            </div>

        </div>

    </div>

</section>


<!-- ============ CLIENTS STRIP ============ -->

<section class="cta-strip" id="clients">

    <div class="cta-strip-inner">

        <div>

            <p class="eyebrow eyebrow--dark">
                Every roster starts somewhere
            </p>

            <h2>
                Post a role. Fill the seat.
            </h2>

        </div>


        <a
            href="<?php echo isLoggedIn() && isClient() ? 'account.php' : 'register.php'; ?>"
            class="btn btn--dark"
        >
            Post an Opening
        </a>

    </div>

</section>


<!-- ============ TESTIMONIALS ============ -->

<section class="testimonials">

    <img
        src="Pictures/testimonial-bg.jpg"
        alt=""
        class="testimonials-bg"
        aria-hidden="true"
    >

    <div
        class="testimonials-overlay"
        aria-hidden="true"
    ></div>


    <h2>
        Testimonies
    </h2>


    <div class="testimonials-grid">

        <blockquote
            class="testimonial-card"
            style="--i:0"
        >

            <p class="testimonial-quote">

                &ldquo;ComMEETtee made it easier for me to discover opportunities
                that matched my skills and interests. Instead of asking around
                for open committee slots, I found them all in one place.&rdquo;

            </p>

            <cite>
                &mdash;
                <strong>Imnot O. Kay</strong>,
                Aspirant
            </cite>

        </blockquote>


        <blockquote
            class="testimonial-card"
            style="--i:1"
        >

            <p class="testimonial-quote">

                &ldquo;I needed someone with graphic design and video editing
                skills for a project. ComMEETtee helped me find applicants whose
                skills matched exactly what I was looking for.&rdquo;

            </p>

            <cite>
                &mdash;
                <strong>Ineed S. Leep</strong>,
                Independent Client
            </cite>

        </blockquote>

    </div>


    <p class="testimonials-tagline">

        Connect &bull; Co
        <span>Meet</span>
        &bull; Commit

    </p>

</section>


<!-- ============ ABOUT US ============ -->

<section
    class="about-section"
    id="about"
>

    <div class="wrap about-grid">

        <div class="about-copy">

            <span class="eyebrow">
                About Us
            </span>

            <h2 class="display">
                Where skills and responsibility meets.
            </h2>

            <p>
                ComMEETtee centralizes and simplifies committee recruitment
                by connecting university organizations with students based
                on their skills, interests, and availability — making
                opportunities more visible for everyone involved.
            </p>

        </div>


        <div class="about-pillars">

            <div class="about-pillar">

                <h3>
                    Our Vision
                </h3>

                <p>
                    To build a connected university community where every
                    student can find opportunities to contribute their skills,
                    take on responsibilities, and make a meaningful impact.
                </p>

            </div>


            <div class="about-pillar">

                <h3>
                    Our Mission
                </h3>

                <p>
                    To centralize and simplify committee recruitment by
                    connecting university organizations with students based
                    on their skills, interests, and availability.
                </p>

            </div>

        </div>

    </div>

</section>


<!-- ============ BANNER ============ -->

<section class="banner">

    <div class="wrap">

        <span class="eyebrow">
            Where skills and responsibility meets
        </span>

        <h2 class="display">
            Ready to find<br>
            your committee?
        </h2>

        <a
            class="link-solid"
            href="#committees"
        >
            Browse Openings
        </a>

    </div>

</section>


<?php include __DIR__ . '/includes/footer.php'; ?>