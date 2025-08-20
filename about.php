<?php include('include/header.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #fafafa;
      margin: 0;
      padding: 0;
      color: #333;
    }

    .about-container {
      max-width: 1100px;
      margin: 50px auto;
      padding: 30px;
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.08);
      animation: fadeIn 1s ease-in-out;
    }

    .about-header, .section-title {
      text-align: center;
      margin-bottom: 30px;
    }

    .about-header h1, .section-title h2 {
      font-size: 2.6em;
      color: #E91E63;
    }

    .about-header p {
      font-size: 1.1em;
      color: #777;
    }

    .about-content {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
    }

    .about-text, .story-text {
      flex: 1 1 55%;
      font-size: 1.1em;
      line-height: 1.8;
    }

    .about-image, .founder-image {
      flex: 1 1 40%;
    }

    .about-image img, .founder-image img {
      width: 100%;
      border-radius: 10px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.1);
    }

    .highlights {
      margin-top: 40px;
    }

    .highlight-item {
      background: #f8f8f8;
      border-left: 5px solid #E91E63;
      padding: 20px;
      margin-bottom: 15px;
      border-radius: 6px;
    }

    .highlight-item h3 {
      margin: 0 0 10px;
      color: #444;
    }

    .highlight-item p {
      margin: 0;
      color: #666;
    }

    .section {
      margin-top: 50px;
    }

    .vision-boxes {
      display: flex;
      gap: 30px;
      flex-wrap: wrap;
      margin-top: 20px;
    }

    .vision-box {
      flex: 1 1 45%;
      background: #ffeef3;
      padding: 20px;
      border-left: 5px solid #E91E63;
      border-radius: 6px;
    }

    .testimonials {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
      margin-top: 30px;
    }

    .testimonial {
      background: #f0f0f0;
      padding: 20px;
      border-radius: 10px;
      flex: 1 1 45%;
    }

    .testimonial h4 {
      margin: 10px 0 5px;
      color: #E91E63;
    }

    .testimonial p {
      font-style: italic;
    }

    .cta {
      text-align: center;
      margin-top: 50px;
    }

    .cta a {
      background: #E91E63;
      color: white;
      padding: 12px 30px;
      text-decoration: none;
      font-size: 1.1em;
      border-radius: 8px;
      transition: 0.3s ease;
    }

    .cta a:hover {
      background: #c2185b;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
      .about-content, .vision-boxes, .testimonials {
        flex-direction: column;
      }

      .about-header h1 {
        font-size: 2em;
      }
    }
  </style>
</head>
<body>

  <div class="about-container">

    <!-- Header -->
    <div class="about-header">
      <h1>About Us</h1>
      <p>Fashion that defines confidence and style.</p>
    </div>

    <!-- Intro -->
    <div class="about-content">
      <div class="about-text">
        <p>
          Welcome to <strong>Clothify</strong> – your one-stop destination for the latest trends in fashion. We are a passionate team of designers and enthusiasts bringing you handpicked collections blending comfort, elegance, and modern style.
        </p>
        <p>
          Since 2020, we’ve redefined the online clothing experience with premium-quality apparel for every occasion — be it casual, workwear, or celebrations.
        </p>
      </div>
      <div class="about-image">
        <img src="uploads/about-banner.jpg" alt="About Clothify">
      </div>
    </div>

    <!-- Highlights -->
    <div class="highlights">
      <div class="highlight-item">
        <h3>✔ Premium Quality Clothing</h3>
        <p>Handpicked fabrics, crafted by skilled artisans.</p>
      </div>
      <div class="highlight-item">
        <h3>✔ Affordable & Trendy</h3>
        <p>High fashion meets low pricing – no compromise.</p>
      </div>
      <div class="highlight-item">
        <h3>✔ Fast Shipping & Easy Returns</h3>
        <p>We deliver happiness quickly and take it back if you’re not 100% satisfied.</p>
      </div>
      <div class="highlight-item">
        <h3>✔ Made with Love in India</h3>
        <p>Locally designed with a global fashion outlook.</p>
      </div>
    </div>

    <!-- Story Section -->
    <div class="section">
      <div class="section-title">
        <h2>Our Story</h2>
      </div>
      <div class="about-content">
        <div class="story-text">
          <p>
            Clothify started from a tiny room with two sewing machines and a dream — to make fashion accessible to everyone. What began as a college project quickly turned into a full-fledged clothing brand loved by thousands.
          </p>
          <p>
            Every step of our journey has been driven by passion, creativity, and customer trust. Today, we deliver across the country and continue to innovate through our fabrics, designs, and tech-powered experience.
          </p>
        </div>
        <div class="founder-image">
          <img src="uploads/founder.jpg" alt="Founders of Clothify">
        </div>
      </div>
    </div>

    <!-- Mission & Vision -->
    <div class="section">
      <div class="section-title">
        <h2>Our Mission & Vision</h2>
      </div>
      <div class="vision-boxes">
        <div class="vision-box">
          <h3>🎯 Our Mission</h3>
          <p>To make fashion simple, stylish, and sustainable — for every individual regardless of size, gender, or background.</p>
        </div>
        <div class="vision-box">
          <h3>🌟 Our Vision</h3>
          <p>To become India’s most trusted fashion brand known for innovation, quality, and community support.</p>
        </div>
      </div>
    </div>

    <!-- Testimonials -->
    <div class="section">
      <div class="section-title">
        <h2>What Our Customers Say</h2>
      </div>
      <div class="testimonials">
        <div class="testimonial">
          <p>“Clothify’s dresses are a perfect mix of style and comfort. I always get compliments!”</p>
          <h4>- Riya S., Mumbai</h4>
        </div>
        <div class="testimonial">
          <p>“I love their quick delivery and quality. Even the return process was smooth!”</p>
          <h4>- Aman T., Delhi</h4>
        </div>
      </div>
    </div>

    <!-- Call to Action -->
    <div class="cta">
      <h2>Join Our Fashion Revolution</h2>
      <p>Over 50,000+ happy customers. Become one today!</p>
      <a href="product.php">Shop Now</a>
    </div>

  </div>

</body>
</html>
