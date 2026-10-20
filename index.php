<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Find a car for your next journey with CarForYou.">
  <title>CarForYou | Car Rental</title>
  <style>
    :root {
      color-scheme: light;
      font-family: Arial, sans-serif;
      color: #17212b;
      background: #f5f7fa;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      line-height: 1.6;
    }

    header,
    main,
    footer {
      width: min(100% - 2rem, 1100px);
      margin: 0 auto;
    }

    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.25rem 0;
    }

    .brand {
      color: #17212b;
      font-size: 1.3rem;
      font-weight: 700;
      text-decoration: none;
    }

    .hero {
      margin: 2rem 0;
      padding: clamp(2rem, 7vw, 5rem);
      border-radius: 1rem;
      color: #fff;
      background: linear-gradient(125deg, #12314a, #176b78);
    }

    .hero h1 {
      max-width: 12ch;
      margin: 0 0 1rem;
      font-size: clamp(2.4rem, 7vw, 4.5rem);
      line-height: 1.05;
    }

    .hero p {
      max-width: 48ch;
      margin: 0 0 1.5rem;
      color: #e2f2f4;
      font-size: 1.1rem;
    }

    .button {
      display: inline-block;
      padding: 0.75rem 1.1rem;
      border-radius: 0.4rem;
      color: #12314a;
      background: #fff;
      font-weight: 700;
      text-decoration: none;
    }

    .availability {
      margin: 2rem 0;
      padding: 1.5rem;
      border: 1px solid #d9e1e8;
      border-radius: 0.75rem;
      background: #fff;
    }

    .availability h2 {
      margin-top: 0;
    }

    .availability p {
      margin-bottom: 0;
      color: #536271;
    }

    footer {
      padding: 1.5rem 0;
      color: #536271;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <header>
    <a class="brand" href="./">CarForYou</a>
  </header>
  <main>
    <section class="hero">
      <h1>Find the right car for your next journey</h1>
      <p>Explore your options and get ready for the road ahead.</p>
      <a class="button" href="#availability">Explore car rentals</a>
    </section>
    <section class="availability" id="availability">
      <h2>Car listings are coming soon</h2>
      <p>Vehicle listings and bookings will be available after database setup is complete.</p>
    </section>
  </main>
  <footer>
    &copy; 2026 CarForYou
  </footer>
</body>
</html>
