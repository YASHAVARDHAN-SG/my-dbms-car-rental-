<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Find a car for your next journey with Car Rental Portal.">
  <title>Car Rental Portal</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <div class="header-inner">
      <a class="brand" href="./" aria-label="Car Rental Portal home">
        <span class="brand-mark" aria-hidden="true">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
            <path d="M5 11 6.5 6.8A2.7 2.7 0 0 1 9 5h6a2.7 2.7 0 0 1 2.5 1.8L19 11l1.3 1.1c.5.4.7 1 .7 1.6V18h-2v-1H5v1H3v-4.3c0-.6.2-1.2.7-1.6L5 11Zm2.1-.5h9.8l-1.2-3.3a.8.8 0 0 0-.8-.5H9a.8.8 0 0 0-.8.5l-1.1 3.3ZM6.5 14a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Zm11 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3ZM4 12.5v2h2v-2H4Zm14 0v2h2v-2h-2Z"/>
          </svg>
        </span>
        <span class="brand-name">Car Rental<br>Portal</span>
      </a>

      <div class="contact-items">
        <a class="contact-item" href="mailto:info@gmail.com">
          <span class="contact-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm9 7.2L4.8 7h-.3v.5l7.2 5.3a.5.5 0 0 0 .6 0l7.2-5.3V7h-.3L12 12.2Z"/>
            </svg>
          </span>
          <span class="contact-copy">
            <strong>FOR SUPPORT MAIL US:</strong>
            <span>info@gmail.com</span>
          </span>
        </a>
        <a class="contact-item" href="tel:8974561236">
          <span class="contact-icon" aria-hidden="true">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor">
              <path d="M6.6 2.8 9 2.2a1.5 1.5 0 0 1 1.7.8l1.1 2.5a1.5 1.5 0 0 1-.4 1.8L9.9 8.6a14.7 14.7 0 0 0 5.5 5.5l1.3-1.5a1.5 1.5 0 0 1 1.8-.4l2.5 1.1a1.5 1.5 0 0 1 .8 1.7l-.6 2.4a2.5 2.5 0 0 1-2.4 1.9C10.4 20.8 3.2 13.6 2.7 5.2a2.5 2.5 0 0 1 1.9-2.4h2Z"/>
            </svg>
          </span>
          <span class="contact-copy">
            <strong>SERVICE HELPLINE CALL US:</strong>
            <span>8974561236</span>
          </span>
        </a>
      </div>

      <a class="login-link" href="#availability">LOGIN / REGISTER</a>
    </div>
  </header>

  <nav class="main-nav" aria-label="Main navigation">
    <div class="nav-inner">
      <ul class="nav-links">
        <li><a href="./">HOME</a></li>
        <li><a href="#about">ABOUT US</a></li>
        <li><a href="#availability">CAR LISTING</a></li>
        <li><a href="#faqs">FAQS</a></li>
        <li><a href="mailto:info@gmail.com">CONTACT US</a></li>
      </ul>

      <a class="account-link" href="#availability" aria-label="Account options">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm0 4a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 12.2a8.1 8.1 0 0 1-5.8-2.4c.3-1.9 4-2.9 5.8-2.9s5.5 1 5.8 2.9a8.1 8.1 0 0 1-5.8 2.4Z"/>
        </svg>
        <span aria-hidden="true">⌄</span>
      </a>
      <form class="search-form" action="#availability" role="search">
        <input type="search" name="q" placeholder="Search..." aria-label="Search cars">
        <button type="submit" aria-label="Search">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <circle cx="10.8" cy="10.8" r="6.3"/>
            <path d="m15.5 15.5 5 5"/>
          </svg>
        </button>
      </form>
    </div>
  </nav>

  <main>
    <section class="hero" aria-label="Car rental">
      <img
        class="hero-image"
        src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&amp;fit=crop&amp;w=2200&amp;q=85"
        alt="A sports car on a scenic road"
        fetchpriority="high"
      >
      <a class="settings-link" href="#availability" aria-label="View site options">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="m19.4 13 .1-1-.1-1 2-1.5-2-3.5-2.4 1a8 8 0 0 0-1.7-1L15 3h-6l-.3 3a8 8 0 0 0-1.7 1l-2.4-1-2 3.5 2 1.5-.1 1 .1 1-2 1.5 2 3.5 2.4-1a8 8 0 0 0 1.7 1L9 21h6l.3-3a8 8 0 0 0 1.7-1l2.4 1 2-3.5-2-1.5ZM12 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7Z"/>
        </svg>
      </a>
    </section>

    <section class="listing-section" id="availability">
      <div class="content-inner">
        <div class="section-heading" id="about">
          <h1>Find the Best <span>Car For You</span></h1>
          <p>Discover a car that fits your next journey. Vehicle listings and bookings will be available after database setup is complete.</p>
        </div>
        <p class="coming-soon" id="faqs">Car listings are coming soon.</p>
      </div>
    </section>
  </main>
</body>
</html>
