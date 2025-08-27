<?php
// =============================================================
// Valeria Photo — Single Page Portfolio (index.php)
// Tech: PHP + HTML + CSS + jQuery (no heavy deps)
// Notes:
//  - Single-file build for quick deploy.
//  - Configure EMAIL and other settings below.
//  - Uses mail() by default. For SMTP/PHPMailer, see stub at bottom.
//  - Multi-language: EN / UK (Ukrainian). Uses data-i18n attributes.
//  - Security: CSRF token (session), honeypot, basic sanitization.
//  - BEM-style classes, minimalist design, responsive, soft animations.
//  - SEO: canonical, OG/Twitter cards, JSON-LD (Person/LocalBusiness/Offers/WebSite), robots, favicon.
//  - Marketing: UTM capture -> hidden form fields -> appears in email.
// =============================================================

session_start();

// ----------------------------
// CONFIG
// ----------------------------
$CONFIG = [
    'site_name'   => 'Valeria Photo',
    'brand'       => 'Valeria Photo',
    'city'        => 'Одеса',
    'currency'    => '₴', // UAH symbol
    'admin_email' => 'hello@valeriaphoto.com', // TODO: replace with your email
    'lang_default'=> 'uk', // 'en' or 'uk'
];

// Canonical URL (no query params)
$SCHEME = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$HOST = $_SERVER['HTTP_HOST'] ?? 'localhost';
$REQUEST_URI = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$canonical = $SCHEME . '://' . $HOST . $REQUEST_URI;
if (getenv('PUBLIC_URL')) { $canonical = rtrim(getenv('PUBLIC_URL'), '/'); }

// ----------------------------
// CSRF TOKEN
// ----------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ----------------------------
// HELPERS
// ----------------------------
function sanitize_text($v){
    $v = trim($v);
    $v = strip_tags($v);
    $v = preg_replace('/[
]+/', ' ', $v); // prevent header injection
    return $v;
}

function is_human($honeypot){ return empty($honeypot); }
function valid_email($email){ return (bool)filter_var($email, FILTER_VALIDATE_EMAIL); }

// ----------------------------
// FORM PROCESSING
// ----------------------------
$form_errors = [];
$form_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_name'] ?? '') === 'booking') {
    // CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $form_errors[] = 'Invalid session token.';
    }

    // Honeypot
    if (!is_human($_POST['company'] ?? '')) {
        $form_errors[] = 'Spam detected.';
    }

    // Fields
    $name     = sanitize_text($_POST['name'] ?? '');
    $email    = sanitize_text($_POST['email'] ?? '');
    $phone    = sanitize_text($_POST['phone'] ?? '');
    $type     = sanitize_text($_POST['shoot_type'] ?? '');
    $date     = sanitize_text($_POST['date'] ?? '');
    $message  = sanitize_text($_POST['message'] ?? '');
    $agree    = isset($_POST['agree']);

    // UTM
    $utm_source   = sanitize_text($_POST['utm_source'] ?? '');
    $utm_medium   = sanitize_text($_POST['utm_medium'] ?? '');
    $utm_campaign = sanitize_text($_POST['utm_campaign'] ?? '');
    $utm_content  = sanitize_text($_POST['utm_content'] ?? '');
    $utm_term     = sanitize_text($_POST['utm_term'] ?? '');
    $referrer     = sanitize_text($_POST['referrer'] ?? '');

    if ($name === '') $form_errors[] = 'Please enter your name.';
    if (!valid_email($email)) $form_errors[] = 'Please enter a valid email address.';
    if ($agree !== true) $form_errors[] = 'Please accept the policy.';

    if (!$form_errors) {
        // Build email
        $to = $CONFIG['admin_email'];
        $subject = 'New Booking Request — ' . $CONFIG['brand'];
        $body  = "Name: {$name}
";
        $body .= "Email: {$email}
";
        $body .= "Phone: {$phone}
";
        $body .= "Type: {$type}
";
        $body .= "Date: {$date}
";
        $body .= "Message: {$message}

";
        if ($utm_source || $utm_medium || $utm_campaign || $utm_content || $utm_term || $referrer) {
            $body .= "— Marketing —
";
            if($utm_source)   $body .= "utm_source: {$utm_source}
";
            if($utm_medium)   $body .= "utm_medium: {$utm_medium}
";
            if($utm_campaign) $body .= "utm_campaign: {$utm_campaign}
";
            if($utm_content)  $body .= "utm_content: {$utm_content}
";
            if($utm_term)     $body .= "utm_term: {$utm_term}
";
            if($referrer)     $body .= "referrer: {$referrer}
";
        }
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: '.$CONFIG['brand'].' <no-reply@'.($_SERVER['HTTP_HOST'] ?? 'localhost').'>',
            'Reply-To: '.$email,
        ];

        // Send
        $sent = @mail($to, $subject, $body, implode("
", $headers));
        if ($sent) {
            $form_success = true;
            // rotate token to avoid resubmission
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $csrf_token = $_SESSION['csrf_token'];
        } else {
            $form_errors[] = 'Sending failed. Please try later.';
        }
    }
}

?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($CONFIG['brand']) ?> — Portfolio</title>
  <meta name="description" content="<?= htmlspecialchars($CONFIG['brand']) ?> — професійний фотограф, <?= htmlspecialchars($CONFIG['city']) ?>. Весілля, love story, випускні альбоми, хрестини, персональні зйомки." />
  <meta property="og:title" content="<?= htmlspecialchars($CONFIG['brand']) ?>" />
  <meta property="og:description" content="Wedding, love story, family & portraits in <?= htmlspecialchars($CONFIG['city']) ?>." />
  <meta property="og:type" content="website" />
  <meta property="og:image" content="https://images.unsplash.com/photo-1526772662000-3f88f10405ff?w=1200" />
  <meta name="robots" content="index,follow,max-image-preview:large" />
  <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>" />
  <meta property="og:site_name" content="<?= htmlspecialchars($CONFIG['brand']) ?>" />
  <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>" />
  <meta property="og:locale" content="uk_UA" />
  <meta property="og:locale:alternate" content="en_US" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?= htmlspecialchars($CONFIG['brand']) ?>" />
  <meta name="twitter:description" content="Wedding, love story, family & portraits in <?= htmlspecialchars($CONFIG['city']) ?>." />
  <meta name="theme-color" content="#ffffff" />
  <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
  <link rel="preload" as="image" href="https://images.unsplash.com/photo-1526772662000-3f88f10405ff?w=1600" imagesrcset="https://images.unsplash.com/photo-1526772662000-3f88f10405ff?w=800 800w, https://images.unsplash.com/photo-1526772662000-3f88f10405ff?w=1200 1200w, https://images.unsplash.com/photo-1526772662000-3f88f10405ff?w=1600 1600w" imagesizes="(max-width: 980px) 92vw, 50vw" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap&subset=cyrillic,cyrillic-ext" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <style>
  :root{
    --bg:#ffffff; --text:#0e0e0e; --muted:#666; --accent:#0e0e0e; --border:#ececec;
    --radius:12px; --space:28px;
  }
  *{box-sizing:border-box}
  html,body{height:100%}
  html{scroll-behavior:smooth}
  body{
    margin:0; font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
    color:var(--text); background:var(--bg); -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;
    transition:opacity .5s ease;
  }
  html:not(.is-ready) body{opacity:0}
  img{max-width:100%; display:block}
  a{color:inherit; text-decoration:none}
  .container{width:min(1100px, 92vw); margin:0 auto}

  /* Header */
  .header{position:fixed; inset:0 0 auto 0; z-index:50; background:transparent; border-bottom:1px solid transparent; transition:background .35s ease, border-color .35s ease}
  .header--solid{background:rgba(255,255,255,.75); backdrop-filter:saturate(180%) blur(10px); border-color:var(--border)}
  .header__row{display:flex; align-items:center; justify-content:space-between; padding:18px 0}
  .logo{font-family:"Cormorant Garamond", serif; font-weight:600; font-size:26px; letter-spacing:.02em}
  .nav{display:flex; gap:24px; align-items:center}
  .nav__link{position:relative; padding:8px 0; text-transform:uppercase; font-size:12px; letter-spacing:.14em}
  .nav__link::after{content:""; position:absolute; left:0; right:0; bottom:-6px; height:1px; background:transparent; transition:background .25s ease}
  .nav__link:hover::after{background:#000}
  .lang{display:flex; gap:8px; align-items:center}
  .lang__btn{font-size:11px; padding:6px 10px; border:1px solid var(--border); border-radius:999px; cursor:pointer; background:#fff}
  .lang__btn--active{background:#000; color:#fff; border-color:#000}
  .burger{display:none; width:36px; height:36px; border:1px solid var(--border); border-radius:10px; align-items:center; justify-content:center; cursor:pointer; background:#fff}
  .burger span{width:18px; height:2px; background:#111; position:relative; display:block}
  .burger span::before,.burger span::after{content:""; position:absolute; left:0; right:0; height:2px; background:#111}
  .burger span::before{top:-6px} .burger span::after{top:6px}
  .nav--mobile{display:none; flex-direction:column; gap:6px; padding:8px 0}

  /* Hero */
  .hero{min-height:92vh; display:grid; place-items:center; position:relative; isolation:isolate; padding:0}
  .hero::before{content:""; position:absolute; inset:0; background:linear-gradient(180deg, rgba(0,0,0,.28), rgba(0,0,0,.08) 40%, rgba(255,255,255,0)); z-index:0}
  .hero::after{content:""; position:absolute; inset:0; background:var(--hero) center/cover no-repeat; z-index:-1; transform:translateY(calc(var(--parallax,0)*-1px)); transition:transform .2s ease}
  .hero__inner{padding-top:86px; padding-bottom:46px; text-align:center; color:#111}
  .hero__kicker{letter-spacing:.18em; text-transform:uppercase; font-size:12px; color:#444; margin-bottom:14px}
  .hero__title{font-family:"Cormorant Garamond", serif; font-weight:600; font-size:64px; line-height:1.03; margin:0 0 10px; letter-spacing:-.01em}
  .hero__text{color:#333; margin:0 auto 24px; font-size:18px; max-width:680px}
  .btn{display:inline-flex; align-items:center; justify-content:center; gap:10px; padding:12px 18px; border-radius:999px; border:1px solid #111; background:#111; color:#fff; cursor:pointer; transition:transform .25s ease}
  .btn:hover{transform:translateY(-1px)}
  .btn--ghost{background:transparent; color:#111}

  /* Section */
  .section{padding:110px 0 96px}
  .section__head{display:flex; align-items:flex-end; justify-content:space-between; gap:20px; margin-bottom:28px}
  .section__title{font-family:"Cormorant Garamond", serif; font-weight:600; font-size:40px; margin:0}
  .section__sub{color:var(--muted)}

  /* Gallery → Masonry via CSS columns (no JS lib) */
  .grid{column-count:3; column-gap:16px}
  .grid__item{break-inside:avoid; margin:0 0 16px; border-radius:12px; overflow:hidden; background:#f3f3f3}
  .grid__item img{width:100%; height:auto; display:block; transform:scale(1.02); transition:transform .6s ease, filter .6s ease; filter:saturate(.9)}
  .grid__item:hover img{transform:scale(1.05); filter:saturate(1)}
  .grid__badge{display:none}

  /* Pricing → minimalist list */
  .cards{display:block}
  .card{display:flex; align-items:baseline; gap:18px; padding:18px 0; border-bottom:1px solid var(--border); border-radius:0}
  .card:first-child{border-top:1px solid var(--border)}
  .card__title{font-weight:600; min-width:160px}
  .price{font-family:"Cormorant Garamond", serif; font-size:28px; margin-left:auto}
  .features{display:flex; gap:14px; flex-wrap:wrap}
  .features li{list-style:none; color:#333}

  /* About */
  .about{display:grid; grid-template-columns:1.1fr .9fr; gap:40px; align-items:center}
  .about__photo{border-radius:12px; overflow:hidden}

  /* Testimonials → editorial quotes */
  .review{border:none; padding:0; position:relative}
  .review::before{content:'“'; position:absolute; left:-8px; top:-8px; font-family:"Cormorant Garamond", serif; font-size:54px; line-height:1; color:#000; opacity:.15}
  .review .muted{font-size:18px; color:#222}
  .review__name{margin-top:8px; color:#555}
  .reviews{display:grid; grid-template-columns:repeat(3, 1fr); gap:28px}

  /* Process */
  .steps{display:grid; grid-template-columns:repeat(4, 1fr); gap:18px}
  .step{border:1px solid var(--border); border-radius:12px; padding:18px}
  .step__num{font-family:"Cormorant Garamond", serif; font-size:24px}

  /* Contact */
  .form{display:grid; grid-template-columns:1fr 1fr; gap:14px}
  .form .full{grid-column:1/-1}
  .input{width:100%; padding:12px 14px; border:1px solid var(--border); border-radius:10px; background:#fff}
  .checkbox{display:flex; align-items:flex-start; gap:10px}
  .note{font-size:13px; color:var(--muted)}
  .alert{padding:12px 14px; border-radius:12px; margin-bottom:14px}
  .alert--ok{background:#e8f8ee; border:1px solid #bfe7cc}
  .alert--err{background:#fdecec; border:1px solid #f4b7b7}

  /* Footer */
  .skip-link{position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden}
  .skip-link:focus{left:12px;top:12px;width:auto;height:auto;background:#000;color:#fff;padding:8px 12px;border-radius:8px;z-index:9999}
  .footer{padding:64px 0; border-top:1px solid var(--border); color:var(--muted)}

  /* Reveal */
  .reveal{opacity:0; transform:translateY(10px); transition:opacity .6s ease, transform .6s ease}
  .reveal--visible{opacity:1; transform:none}

  @media (max-width: 1100px){ .hero__title{font-size:56px} }
  @media (max-width: 980px){
    .about{grid-template-columns:1fr}
    .grid{column-count:2}
    .reviews{grid-template-columns:1fr}
    .steps{grid-template-columns:repeat(2, 1fr)}
    .form{grid-template-columns:1fr}
    .nav{display:none}
    .burger{display:flex}
    .nav--mobile{display:flex}
  }
  @media (max-width: 560px){ .grid{column-count:1} .hero__title{font-size:40px} }
  @media (prefers-reduced-motion: reduce){ *{animation:none !important; transition:none !important} }

  /* HERO text improvements */
  .hero__title,
  .hero__text {
      color: #fff; /* белый текст */
      text-shadow: 0 2px 6px rgba(0,0,0,0.5); /* мягкая тень для читаемости */
  }

  .hero::before {
      background: linear-gradient(180deg, rgba(0,0,0,0.4), rgba(0,0,0,0.15) 40%, rgba(255,255,255,0));
  }

  /* Portfolio filter buttons */
  .filter {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 30px;
  }

  .filter__btn {
      padding: 8px 16px;
      border-radius: 999px;
      border: 1px solid #ccc;
      background: #fff;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.25s ease;
  }

  .filter__btn:hover {
      background: #000;
      color: #fff;
      border-color: #000;
  }

  .filter__btn--active {
      background: #000;
      color: #fff;
      border-color: #000;
  }

  /* Reviews */
  .review::before{content:none!important}
  .review{padding-left:0}

  /* Pricing */
  .price{
      display:inline-flex;
      align-items:baseline;
      gap:8px;
      font-family:"Cormorant Garamond", serif;
      font-size:28px;
      letter-spacing:.01em;
      font-variant-numeric:lining-nums tabular-nums;
  }
  .price .currency{
      font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
      font-size:18px;
      line-height:1;
      transform:translateY(-1px);
      opacity:.9;
  }
  .hero__title, .section__title, .logo{
      font-family:"Playfair Display", Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
  }
</style>
  <noscript><style>.reveal{opacity:1!important;transform:none!important}</style></noscript>
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "WebSite",
        "name": <?= json_encode($CONFIG['brand'], JSON_UNESCAPED_UNICODE) ?>,
        "url": <?= json_encode($canonical, JSON_UNESCAPED_UNICODE) ?>
      },
      {
        "@type": "Person",
        "name": "Valeria",
        "jobTitle": "Photographer",
        "image": "https://images.unsplash.com/photo-1520813792240-56fc4a3765a7?w=600",
        "worksFor": {"@type": "LocalBusiness", "name": <?= json_encode($CONFIG['brand'], JSON_UNESCAPED_UNICODE) ?>},
        "knowsLanguage": ["uk", "en"],
        "url": <?= json_encode($canonical, JSON_UNESCAPED_UNICODE) ?>
      },
      {
        "@type": "LocalBusiness",
        "name": <?= json_encode($CONFIG['brand'], JSON_UNESCAPED_UNICODE) ?>,
        "address": {"@type":"PostalAddress","addressLocality": <?= json_encode($CONFIG['city'], JSON_UNESCAPED_UNICODE) ?>, "addressCountry":"UA"},
        "areaServed": {"@type":"City","name": <?= json_encode($CONFIG['city'], JSON_UNESCAPED_UNICODE) ?>},
        "contactPoint": {"@type": "ContactPoint", "contactType": "customer support", "email": <?= json_encode($CONFIG['admin_email'], JSON_UNESCAPED_UNICODE) ?>},
        "url": <?= json_encode($canonical, JSON_UNESCAPED_UNICODE) ?>
      },
      {
        "@type": "OfferCatalog",
        "name": "Photography packages",
        "itemListElement": [
          {"@type":"Offer","name":"Standard","price":"3500","priceCurrency":"UAH"},
          {"@type":"Offer","name":"Premium","price":"7000","priceCurrency":"UAH"},
          {"@type":"Offer","name":"Wedding","price":"18000","priceCurrency":"UAH"}
        ]
      }
    ]
  }
  </script>
</head>
<body>
  <a class="skip-link" href="#home">Skip to content</a>
  <header class="header" aria-label="Main navigation">
    <div class="container">
      <div class="header__row">
        <div class="logo"><?= htmlspecialchars($CONFIG['brand']) ?></div>
        <nav class="nav" role="navigation" aria-label="Primary">
          <a class="nav__link" href="#home" data-i18n="nav_home">Головна</a>
          <a class="nav__link" href="#portfolio" data-i18n="nav_portfolio">Портфоліо</a>
          <a class="nav__link" href="#pricing" data-i18n="nav_pricing">Ціни</a>
          <a class="nav__link" href="#about" data-i18n="nav_about">Про мене</a>
          <a class="nav__link" href="#contact" data-i18n="nav_contact">Контакти</a>
        </nav>
        <div class="lang">
          <button class="lang__btn" data-lang="uk">UA</button>
          <button class="lang__btn" data-lang="en">EN</button>
          <button class="burger" aria-label="Menu"><span></span></button>
        </div>
      </div>
      <nav class="nav nav--mobile" role="navigation" aria-label="Mobile">
        <a class="nav__link" href="#home" data-i18n="nav_home">Головна</a>
        <a class="nav__link" href="#portfolio" data-i18n="nav_portfolio">Портфоліо</a>
        <a class="nav__link" href="#pricing" data-i18n="nav_pricing">Ціни</a>
        <a class="nav__link" href="#about" data-i18n="nav_about">Про мене</a>
        <a class="nav__link" href="#contact" data-i18n="nav_contact">Контакти</a>
      </nav>
    </div>
  </header>

  <main>
    <!-- HERO -->
    <section id="home" class="hero" style="--hero:url('https://images.unsplash.com/photo-1526772662000-3f88f10405ff?w=1600')">
  <div class="container hero__inner">
    <div class="hero__kicker">VALERIA PHOTO</div>
    <h1 class="hero__title" data-i18n="hero_title">Світло. Почуття. Історії, що залишаються.</h1>
    <p class="hero__text" data-i18n="hero_text">Весілля, lovestory, сімейні та персональні зйомки у <?= htmlspecialchars($CONFIG['city']) ?> та по всій Україні.</p>
    <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap">
      <a href="#portfolio" class="btn" data-i18n="cta_view_work">Подивитись роботи</a>
      <a href="#contact" class="btn btn--ghost" data-i18n="cta_book">Забронювати зйомку</a>
    </div>
  </div>
</section>

    <!-- GALLERY -->
    <section id="portfolio" class="section">
      <div class="container">
        <div class="section__head">
          <h2 class="section__title" data-i18n="portfolio_title">Портфоліо</h2>
          <div class="section__sub muted" data-i18n="portfolio_sub">Вибірка з останніх зйомок</div>
        </div>
        <div class="filter">
          <button class="filter__btn filter__btn--active" data-filter="all" data-i18n="filter_all">Усі</button>
          <button class="filter__btn" data-filter="wedding" data-i18n="filter_wedding">Весілля</button>
          <button class="filter__btn" data-filter="love" data-i18n="filter_love">Love Story</button>
          <button class="filter__btn" data-filter="graduation" data-i18n="filter_grad">Випускні альбоми</button>
          <button class="filter__btn" data-filter="christening" data-i18n="filter_christening">Хрестини</button>
          <button class="filter__btn" data-filter="personal" data-i18n="filter_personal">Персональні</button>
        </div>
        <div class="grid" id="gallery">
          <?php
          // demo gallery items (replace with your images)
          $items = [
            ['cat'=>'wedding','img'=>'https://images.unsplash.com/photo-1522673607200-164d1b6ce486?w=1200','label'=>'Wedding'],
            ['cat'=>'love','img'=>'https://images.unsplash.com/photo-1518640467707-6811f4a6ab73?w=1200','label'=>'Love Story'],
            ['cat'=>'graduation','img'=>'https://images.unsplash.com/photo-1520975922284-5a810fda9664?w=1200','label'=>'Graduation'],
            ['cat'=>'christening','img'=>'https://images.unsplash.com/photo-1519741497674-611481863552?w=1200','label'=>'Christening'],
            ['cat'=>'personal','img'=>'https://images.unsplash.com/photo-1503341733017-1901578f9b84?w=1200','label'=>'Portrait'],
            ['cat'=>'wedding','img'=>'https://images.unsplash.com/photo-1519741496292-0f0ef3d6c7d1?w=1200','label'=>'Wedding'],
            ['cat'=>'love','img'=>'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=1200','label'=>'Love Story'],
            ['cat'=>'graduation','img'=>'https://images.unsplash.com/photo-1512100356356-de1b84283e18?w=1200','label'=>'Graduation'],
            ['cat'=>'personal','img'=>'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=1200','label'=>'Portrait'],
          ];
          foreach($items as $i=>$it): ?>
            <figure class="grid__item reveal" data-cat="<?= htmlspecialchars($it['cat']) ?>">
              <img src="<?= htmlspecialchars($it['img']) ?>" alt="<?= htmlspecialchars($it['label']) ?>" loading="lazy" data-index="<?= $i ?>">
              <figcaption class="grid__badge"><?= htmlspecialchars($it['label']) ?></figcaption>
            </figure>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- PRICING -->
    <section id="pricing" class="section">
      <div class="container">
        <div class="section__head">
          <h2 class="section__title" data-i18n="pricing_title">Пакети та ціни</h2>
          <div class="section__sub muted" data-i18n="pricing_sub">Прозоро та без сюрпризів</div>
        </div>
        <div class="cards">
          <div class="card reveal">
            <h3 class="card__title" data-i18n="pack_standard">Стандарт</h3>
            <p class="price"><span class="currency"><?= htmlspecialchars($CONFIG['currency']) ?></span><span data-i18n="price_standard">3500</span></p>
            <ul class="features">
              <li>• <span data-i18n="feat_std_1">60–90 хв зйомки</span></li>
              <li>• <span data-i18n="feat_std_2">30+ фото з базовою корекцією</span></li>
              <li>• <span data-i18n="feat_std_3">5 ретушованих портретів</span></li>
              <li class="muted" data-i18n="feat_std_4">Готовність до 5 днів</li>
            </ul>
          </div>
          <div class="card reveal">
            <h3 class="card__title" data-i18n="pack_premium">Преміум</h3>
            <p class="price"><span class="currency"><?= htmlspecialchars($CONFIG['currency']) ?></span><span data-i18n="price_premium">7000</span></p>
            <ul class="features">
              <li>• <span data-i18n="feat_pr_1">до 3 годин зйомки</span></li>
              <li>• <span data-i18n="feat_pr_2">100+ фото з корекцією</span></li>
              <li>• <span data-i18n="feat_pr_3">15 ретушованих портретів</span></li>
              <li class="muted" data-i18n="feat_pr_4">Консультація зі стилю</li>
            </ul>
          </div>
          <div class="card reveal">
            <h3 class="card__title" data-i18n="pack_wedding">Весільний</h3>
            <p class="price"><span class="currency"><?= htmlspecialchars($CONFIG['currency']) ?></span><span data-i18n="price_wedding">18000</span></p>
            <ul class="features">
              <li>• <span data-i18n="feat_wed_1">8 годин репортажу</span></li>
              <li>• <span data-i18n="feat_wed_2">Від 400 фото</span></li>
              <li>• <span data-i18n="feat_wed_3">Онлайн-галерея для гостей</span></li>
              <li class="muted" data-i18n="feat_wed_4">Тизер фото за 48 год</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- ABOUT -->
    <section id="about" class="section">
      <div class="container about">
        <div class="about__photo reveal">
          <img src="https://images.unsplash.com/photo-1520813792240-56fc4a3765a7?w=1000" alt="Valeria portrait" loading="lazy">
        </div>
        <div>
          <h2 class="section__title" data-i18n="about_title">Валерія — фотограф з любов’ю до світла</h2>
          <p class="muted" data-i18n="about_text_1">Я знімаю щирі історії про людей і для людей. Мені важливо, щоб фото було про вас — ваші почуття, характер, зв’язок.</p>
          <p class="muted" data-i18n="about_text_2">Працюю в Одесі та подорожую на зйомки по Україні. Люблю природне світло, спокійні кольори й невимушені кадри.</p>
        </div>
      </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="section">
      <div class="container">
        <div class="section__head">
          <h2 class="section__title" data-i18n="rev_title">Відгуки</h2>
          <div class="section__sub muted" data-i18n="rev_sub">Трохи тепла від клієнтів</div>
        </div>
        <div class="reviews">
          <div class="review reveal">
            <div class="muted" data-i18n="rev_1_text">Фото перевершили очікування! Дуже легко і комфортно на зйомці.</div>
            <div class="review__name">— Daria</div>
          </div>
          <div class="review reveal">
            <div class="muted" data-i18n="rev_2_text">Весільний день пролетів, але ці кадри — назавжди. Дякуємо!</div>
            <div class="review__name">— Andrii & Iryna</div>
          </div>
          <div class="review reveal">
            <div class="muted" data-i18n="rev_3_text">Делікатна ретуш, природні кольори — саме те, що я хотіла.</div>
            <div class="review__name">— Kateryna</div>
          </div>
        </div>
      </div>
    </section>

    <!-- PROCESS -->
    <section class="section">
      <div class="container">
        <div class="section__head">
          <h2 class="section__title" data-i18n="proc_title">Як відбувається зйомка</h2>
          <div class="section__sub muted" data-i18n="proc_sub">Коротко про кроки</div>
        </div>
        <div class="steps">
          <div class="step reveal"><div class="step__num">1</div><div class="muted" data-i18n="proc_1">Заявка та консультація</div></div>
          <div class="step reveal"><div class="step__num">2</div><div class="muted" data-i18n="proc_2">Підбір локації та образу</div></div>
          <div class="step reveal"><div class="step__num">3</div><div class="muted" data-i18n="proc_3">Зйомка</div></div>
          <div class="step reveal"><div class="step__num">4</div><div class="muted" data-i18n="proc_4">Відбір, корекція, готові фото</div></div>
        </div>
      </div>
    </section>

    <!-- CONTACT -->
    <section id="contact" class="section">
      <div class="container">
        <div class="section__head">
          <h2 class="section__title" data-i18n="contact_title">Зв’язатися</h2>
          <div class="section__sub muted" data-i18n="contact_sub">Розкажіть, що хочете зняти — я на зв’язку</div>
        </div>

        <?php if ($form_success): ?>
          <div class="alert alert--ok" data-i18n="form_success">Дякую! Я отримала вашу заявку і відповім найближчим часом.</div>
        <?php endif; ?>
        <?php if ($form_errors): ?>
          <div class="alert alert--err">
            <?php foreach($form_errors as $e): ?>
              <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" class="form" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <input type="hidden" name="form_name" value="booking">
          <!-- Honeypot -->
          <input type="text" name="company" style="display:none" tabindex="-1" autocomplete="off" />
          <!-- UTM tracking (auto-filled) -->
          <input type="hidden" name="utm_source" id="utm_source" />
          <input type="hidden" name="utm_medium" id="utm_medium" />
          <input type="hidden" name="utm_campaign" id="utm_campaign" />
          <input type="hidden" name="utm_content" id="utm_content" />
          <input type="hidden" name="utm_term" id="utm_term" />
          <input type="hidden" name="referrer" id="utm_referrer" />

          <label class="full">
            <span class="muted" data-i18n="f_name">Ім’я</span>
            <input class="input" name="name" type="text" required placeholder="Ваше ім’я">
          </label>

          <label>
            <span class="muted" data-i18n="f_email">Email</span>
            <input class="input" name="email" type="email" required placeholder="name@example.com">
          </label>

          <label>
            <span class="muted" data-i18n="f_phone">Телефон</span>
            <input class="input" name="phone" type="tel" placeholder="+380 ...">
          </label>

          <label>
            <span class="muted" data-i18n="f_type">Тип зйомки</span>
            <select class="input select" name="shoot_type">
              <option value="Wedding" data-i18n="opt_wedding">Весілля</option>
              <option value="Love Story" data-i18n="opt_love">Love Story</option>
              <option value="Graduation" data-i18n="opt_grad">Випускні альбоми</option>
              <option value="Christening" data-i18n="opt_christening">Хрестини</option>
              <option value="Personal" data-i18n="opt_personal">Персональна</option>
            </select>
          </label>

          <label>
            <span class="muted" data-i18n="f_date">Дата</span>
            <input class="input" name="date" type="date">
          </label>

          <label class="full">
            <span class="muted" data-i18n="f_msg">Повідомлення</span>
            <textarea class="input" name="message" rows="4" placeholder="Кілька слів про зйомку"></textarea>
          </label>

          <label class="checkbox full">
            <input type="checkbox" name="agree" required>
            <span class="note" data-i18n="f_agree">Я погоджуюсь на обробку персональних даних.</span>
          </label>

          <div class="full" style="display:flex; gap:10px; flex-wrap:wrap">
            <button class="btn" type="submit" data-i18n="f_send">Надіслати</button>
            <a href="mailto:<?= htmlspecialchars($CONFIG['admin_email']) ?>" class="btn btn--ghost" aria-label="Email">Email</a>
          </div>
        </form>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between">
      <div class="muted">© <?= date('Y') ?> <?= htmlspecialchars($CONFIG['brand']) ?> · <?= htmlspecialchars($CONFIG['city']) ?></div>
      <div class="muted">Instagram · Telegram</div>
    </div>
  </footer>

  <!-- Lightbox overlay -->
  <div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Gallery preview">
    <button class="lightbox__close" aria-label="Close" id="lbClose">✕</button>
    <button class="lightbox__prev" aria-label="Previous" id="lbPrev">‹</button>
    <img class="lightbox__img" id="lbImg" alt="Preview">
    <button class="lightbox__next" aria-label="Next" id="lbNext">›</button>
  </div>

  <script>
    // ================= I18N =================
    const I18N = {
      en: {
        nav_home: 'Home', nav_portfolio:'Portfolio', nav_pricing:'Pricing', nav_about:'About', nav_contact:'Contact',
        hero_title:'Light. Emotion. Stories that stay.',
        hero_text:'Weddings, love stories, family and portraits in Odesa and across Ukraine.',
        cta_view_work:'View Work', cta_book:'Book a Session',
        portfolio_title:'Portfolio', portfolio_sub:'Selection of recent shoots',
        filter_all:'All', filter_wedding:'Wedding', filter_love:'Love Story', filter_grad:'Graduation', filter_christening:'Christening', filter_personal:'Personal',
        pricing_title:'Packages & Pricing', pricing_sub:'Transparent, no surprises',
        pack_standard:'Standard', pack_premium:'Premium', pack_wedding:'Wedding',
        price_standard:'3500', price_premium:'7000', price_wedding:'18000',
        feat_std_1:'60–90 min shoot', feat_std_2:'30+ edited photos', feat_std_3:'5 retouched portraits', feat_std_4:'Delivery within 5 days',
        feat_pr_1:'up to 3 hours', feat_pr_2:'100+ edited photos', feat_pr_3:'15 retouched portraits', feat_pr_4:'Styling consultation',
        feat_wed_1:'8 hours coverage', feat_wed_2:'400+ photos', feat_wed_3:'Online gallery for guests', feat_wed_4:'Teaser within 48h',
        about_title:'Valeria — photographer with love for light',
        about_text_1:'I capture honest stories about people and for people. Photos are about you: your feelings, character, connection.',
        about_text_2:'Based in Odesa, available across Ukraine. Natural light, calm colors, effortless frames.',
        rev_title:'Testimonials', rev_sub:'A bit of warmth from clients',
        rev_1_text:'Photos exceeded expectations! The shoot felt easy and comfortable.',
        rev_2_text:'The wedding day flew by, but these frames will stay forever. Thank you!',
        rev_3_text:'Delicate retouch, natural colors — exactly what I wanted.',
        proc_title:'How a shoot works', proc_sub:'Quick steps',
        proc_1:'Inquiry & consultation', proc_2:'Location & styling', proc_3:'Shooting', proc_4:'Selection, edit, delivery',
        contact_title:'Get in touch', contact_sub:'Tell me about your idea — I’m listening',
        form_success:'Thanks! I got your request and will reply shortly.',
        f_name:'Name', f_email:'Email', f_phone:'Phone', f_type:'Shoot type', f_date:'Date', f_msg:'Message', f_agree:'I agree to the processing of personal data.', f_send:'Send',
        opt_wedding:'Wedding', opt_love:'Love Story', opt_grad:'Graduation', opt_christening:'Christening', opt_personal:'Personal'
      },
      uk: {
        nav_home: 'Головна', nav_portfolio:'Портфоліо', nav_pricing:'Ціни', nav_about:'Про мене', nav_contact:'Контакти',
        hero_title:'Світло. Почуття. Історії, що залишаються.',
        hero_text:'Весілля, lovestory, сімейні та персональні зйомки у Одесі та по всій Україні.',
        cta_view_work:'Подивитись роботи', cta_book:'Забронювати зйомку',
        portfolio_title:'Портфоліо', portfolio_sub:'Вибірка з останніх зйомок',
        filter_all:'Усі', filter_wedding:'Весілля', filter_love:'Love Story', filter_grad:'Випускні альбоми', filter_christening:'Хрестини', filter_personal:'Персональні',
        pricing_title:'Пакети та ціни', pricing_sub:'Прозоро та без сюрпризів',
        pack_standard:'Стандарт', pack_premium:'Преміум', pack_wedding:'Весільний',
        price_standard:'3500', price_premium:'7000', price_wedding:'18000',
        feat_std_1:'60–90 хв зйомки', feat_std_2:'30+ фото з базовою корекцією', feat_std_3:'5 ретушованих портретів', feat_std_4:'Готовність до 5 днів',
        feat_pr_1:'до 3 годин зйомки', feat_pr_2:'100+ фото з корекцією', feat_pr_3:'15 ретушованих портретів',
        feat_pr_4:'Консультація зі стилю',
        feat_wed_1:'8 годин репортажу', feat_wed_2:'Від 400 фото', feat_wed_3:'Онлайн-галерея для гостей', feat_wed_4:'Тизер фото за 48 год',
        about_title:'Валерія — фотограф з любов’ю до світла',
        about_text_1:'Я знімаю щирі історії про людей і для людей. Мені важливо, щоб фото було про вас — ваші почуття, характер, зв’язок.',
        about_text_2:'Працюю в Одесі та подорожую на зйомки по Україні. Люблю природне світло, спокійні кольори й невимушені кадри.',
        rev_title:'Відгуки', rev_sub:'Трохи тепла від клієнтів',
        rev_1_text:'Фото перевершили очікування! Дуже легко і комфортно на зйомці.',
        rev_2_text:'Весільний день пролетів, але ці кадри — назавжди. Дякуємо!',
        rev_3_text:'Делікатна ретуш, природні кольори — саме те, що я хотіла.',
        proc_title:'Як відбувається зйомка', proc_sub:'Коротко про кроки',
        proc_1:'Заявка та консультація', proc_2:'Підбір локації та образу', proc_3:'Зйомка', proc_4:'Відбір, корекція, готові фото',
        contact_title:'Зв’язатися', contact_sub:'Розкажіть, що хочете зняти — я на зв’язку',
        form_success:'Дякую! Я отримала вашу заявку і відповім найближчим часом.',
        f_name:'Ім’я', f_email:'Email', f_phone:'Телефон', f_type:'Тип зйомки', f_date:'Дата', f_msg:'Повідомлення', f_agree:'Я погоджуюсь на обробку персональних даних.', f_send:'Надіслати',
        opt_wedding:'Весілля', opt_love:'Love Story', opt_grad:'Випускні альбоми', opt_christening:'Хрестини', opt_personal:'Персональна'
      }
    };

    const langBtns = document.querySelectorAll('.lang__btn');
    const i18nNodes = document.querySelectorAll('[data-i18n]');

    function setLang(lang){
      localStorage.setItem('lang', lang);
      langBtns.forEach(b=>b.classList.toggle('lang__btn--active', b.dataset.lang === lang));
      i18nNodes.forEach(el=>{
        const key = el.getAttribute('data-i18n');
        if(I18N[lang] && I18N[lang][key]){ el.textContent = I18N[lang][key]; }
      });
      document.documentElement.lang = lang === 'en' ? 'en' : 'uk';
    }

    langBtns.forEach(b=> b.addEventListener('click', ()=> setLang(b.dataset.lang)));
    setLang(localStorage.getItem('lang') || '<?= $CONFIG['lang_default'] ?>');

    // ================= Page ready
    document.addEventListener('DOMContentLoaded',()=> document.documentElement.classList.add('is-ready'));

    // ================= Header appearance on scroll (minimalist + parallax)
    (function(){
      const header = document.querySelector('.header');
      const hero = document.querySelector('.hero');
      function onScroll(){
        const y = window.scrollY || 0;
        if(y > 8){ header.classList.add('header--solid'); } else { header.classList.remove('header--solid'); }
        hero && (hero.style.setProperty('--parallax', Math.min(y*0.2, 60)));
      }
      onScroll();
      window.addEventListener('scroll', onScroll, {passive:true});
    })();

    // ================= Smooth scroll
    $(document).on('click', 'a[href^="#"]', function(e){
      const target = $($(this).attr('href'));
      if (target.length){ e.preventDefault(); $('html,body').animate({scrollTop: target.offset().top - 70}, 600); }
    });

    // ================= Mobile nav toggle
    $('.burger').on('click', function(){ $('.nav--mobile').slideToggle(180); });
    $('.nav--mobile .nav__link').on('click', function(){ $('.nav--mobile').slideUp(180); });

    // ================= Reveal on scroll
    const io = new IntersectionObserver((entries)=>{
      entries.forEach(en=>{ if(en.isIntersecting){ en.target.classList.add('reveal--visible'); io.unobserve(en.target);} });
    }, {threshold:.15});
    document.querySelectorAll('.reveal').forEach(el=> io.observe(el));

    // ================= Gallery filter
    $('.filter__btn').on('click', function(){
      $('.filter__btn').removeClass('filter__btn--active');
      $(this).addClass('filter__btn--active');
      const f = $(this).data('filter');
      $('#gallery .grid__item').each(function(){
        const show = f === 'all' || $(this).data('cat') === f;
        $(this).stop().fadeTo(200, show ? 1 : 0).css('pointer-events', show? 'auto':'none');
        $(this).toggle(show);
      });
    });

    // ================= Lightbox
    const lb = document.getElementById('lightbox');
    const lbImg = document.getElementById('lbImg');
    const lbClose = document.getElementById('lbClose');
    const lbPrev = document.getElementById('lbPrev');
    const lbNext = document.getElementById('lbNext');
    const imgs = Array.from(document.querySelectorAll('#gallery img'));
    let idx = 0;

    function openLB(i){ idx=i; lbImg.src=imgs[idx].src; lb.style.display='flex'; document.body.style.overflow='hidden'; }
    function closeLB(){ lb.style.display='none'; document.body.style.overflow='auto'; }
    function prev(){ idx = (idx - 1 + imgs.length) % imgs.length; lbImg.src = imgs[idx].src; }
    function next(){ idx = (idx + 1) % imgs.length; lbImg.src = imgs[idx].src; }
    imgs.forEach((im, i)=> im.addEventListener('click', ()=> openLB(i)));
    lbClose.addEventListener('click', closeLB);
    lbPrev.addEventListener('click', prev);
    lbNext.addEventListener('click', next);
    lb.addEventListener('click', (e)=>{ if(e.target===lb) closeLB(); });
    document.addEventListener('keydown', (e)=>{ if(lb.style.display==='flex'){ if(e.key==='Escape') closeLB(); if(e.key==='ArrowLeft') prev(); if(e.key==='ArrowRight') next(); }});

    // ================= UTM capture & persist (90 days)
    (function(){
      const qs = new URLSearchParams(location.search);
      const keys = ['utm_source','utm_medium','utm_campaign','utm_content','utm_term'];
      const data = {};
      keys.forEach(k=>{ const v = qs.get(k); if(v) data[k]=v; });
      const ref = document.referrer && document.referrer.indexOf(location.origin) === -1 ? document.referrer : '';
      if(ref) data['referrer'] = ref;
      if(Object.keys(data).length){
        localStorage.setItem('utm_data', JSON.stringify({ ...JSON.parse(localStorage.getItem('utm_data')||'{}'), ...data, ts: Date.now() }));
      }
      const saved = JSON.parse(localStorage.getItem('utm_data')||'{}');
      if(saved.ts && (Date.now() - saved.ts) > 90*24*60*60*1000){ localStorage.removeItem('utm_data'); }
      const fill = (id, k)=>{ const el=document.getElementById(id); if(el && saved[k]) el.value = saved[k]; };
      ['utm_source','utm_medium','utm_campaign','utm_content','utm_term'].forEach(k=> fill(k,k));
      const r = document.getElementById('utm_referrer'); if(r && (saved.referrer||ref)) r.value = saved.referrer || ref;
    })();
  </script>

  <!-- GA4 (optional): Replace G-XXXXXXX with your Measurement ID -->
  <script>
      document.querySelectorAll('.price').forEach(p=>{
          const cur=p.querySelector('.currency'); if(!cur) return;
          const num=p.querySelector('span:not(.currency)');
          if(!num) return;
          const v=+num.textContent.replace(/\D+/g,'');
          if(!isNaN(v)) num.textContent=new Intl.NumberFormat('uk-UA').format(v);
      });
  </script>

  <!--
  ===============================================
  SMTP (Optional):
  For production-grade delivery, integrate PHPMailer.
  Quick steps (server access required):
  1) composer require phpmailer/phpmailer
  2) Replace mail() call with PHPMailer SMTP block.
  3) Fill SMTP creds from your provider.

  Example (pseudo):
  // $mail = new PHPMailer(true);
  // $mail->isSMTP();
  // $mail->Host = 'smtp.yourhost.com';
  // $mail->SMTPAuth = true;
  // $mail->Username = 'user';
  // $mail->Password = 'pass';
  // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  // $mail->Port = 587;
  // $mail->setFrom('no-reply@domain', 'Valeria Photo');
  // $mail->addAddress($CONFIG['admin_email']);
  // $mail->Subject = $subject;
  // $mail->Body = $body;
  // $mail->send();
  ===============================================
  -->
</body>
</html>
