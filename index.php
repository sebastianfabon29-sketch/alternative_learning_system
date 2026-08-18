<?php
session_start();
require_once 'db_connect.php';
require_once 'languages.php';

// Check enrollment status
$enroll_status = 'open';
$enroll_start_time = '';
$enroll_end_time = '';

$status_res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('enrollment_status', 'enrollment_start_time', 'enrollment_end_time')");
if ($status_res) {
    while ($row = $status_res->fetch_assoc()) {
        if ($row['setting_key'] === 'enrollment_status') $enroll_status = $row['setting_value'];
        if ($row['setting_key'] === 'enrollment_start_time') $enroll_start_time = $row['setting_value'];
        if ($row['setting_key'] === 'enrollment_end_time') $enroll_end_time = $row['setting_value'];
    }
}

// Dynamically check if within schedule
if (!empty($enroll_start_time) && !empty($enroll_end_time)) {
    $now = date('Y-m-d H:i:s');
    if ($now >= $enroll_start_time && $now <= $enroll_end_time) {
        $enroll_status = 'open';
    } else {
        $enroll_status = 'closed';
    }
}
$is_admin_or_teacher = isset($_SESSION['role_id']) && ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2);

$submission_success = false;
$submission_error   = '';
$form_data          = [];

// ── ALS Schools in Biliran Province (Focusing on Culaba) ──
$als_schools = [
    [
        'name' => __('Culaba ALS Mobile Center, Culaba'),
        'desc' => __('Usa sa pinaka-aktibo nga ALS Center sa Culaba, nagtanyag og flexible learning para sa mga out-of-school youth ug adults. Nindot ang lugar kay presko ang hangin ug duol sa munisipyo, mas sayon tultulon ug daghang activities ang ginabuhat sa komunidad.'),
        'img'  => 'assets/img/schools/culaba_school_sign_1782813878841.png',
        'lat'  => 11.65709,
        'lng'  => 124.53882
    ],
    [
        'name' => __('Bool Central School — ALS Center, Culaba'),
        'desc' => __('Nahimutang sa Bool Central School, nindot ni nga center kay adunay dako nga espasyo ug kompleto sa mga basic nga pasilidad para sa mga ALS learners. Pabor kini sa mga nagpuyo sa mga bukirong barangay sa Culaba.'),
        'img'  => 'assets/img/schools/culaba_school_sign_1782813878841.png',
        'lat'  => 11.6778,
        'lng'  => 124.5169
    ],
    [
        'name' => __('Naval Central School — ALS Center, Naval'),
        'desc' => __('Ang pinakasentro nga ALS hub sa capital town sa Biliran. Moderno ang pamaagi sa pagtudlo, ug tungod kay naa sa sentro sa Naval, accessible kaayo sa transportasyon ug sa uban pang mga public services.'),
        'img'  => 'assets/img/schools/naval_central_school.png',
        'lat'  => 11.56137,
        'lng'  => 124.39766
    ],
    [
        'name' => __('Almeria Community Learning Center, Almeria'),
        'desc' => __('Kini nga center nahimutang sa malinawon nga lungsod sa Almeria. Ilado sa ilang maabiabihon nga mga mobile teachers, perfect ni para sa mga estudyante nga gusto maka-focus pag-ayo tungod sa ka-kalma sa palibot ug duol sa dagat.'),
        'img'  => 'assets/img/schools/almeria_school_sign_1782813868731.png',
        'lat'  => 11.62087,
        'lng'  => 124.37909
    ],
    [
        'name' => __('Maripipi ALS Mobile Teacher Center, Maripipi'),
        'desc' => __('Usa ka island municipality, nindot ang Maripipi Center kay adunay nindot kaayo nga island vibe, limpyo nga hangin, ug supportive kaayo ang komunidad. Sikat sa ilang pottery ug mga malipayong residente nga andam motabang sa mga nag-skwela.'),
        'img'  => 'assets/img/schools/maripipi_school_sign_1782813890031.png',
        'lat'  => 11.7787,
        'lng'  => 124.349
    ],
    [
        'name' => __('Kawayan District ALS, Kawayan'),
        'desc' => __('Nahimutang sa Kawayan district, ilado ni nga center nga naay dakong open quadrangle ug luag nga space. Nindot ang setting tungod sa kabugnaw sa palibot ug ang dedikasyon sa mga magtutudlo sa paghatag og kalidad nga edukasyon.'),
        'img'  => 'assets/img/schools/kawayan_school_sign_1782813901731.png',
        'lat'  => 11.6770,
        'lng'  => 124.3578
    ],
    [
        'name' => __('Biliran ALS Learning Hub, Biliran'),
        'desc' => __('Sentro sa probinsya sa Biliran. Kini nga hub mao ang tigom-anan sa mga dagkong seminars ug ALS sessions. Naa ra dool sa crossing, sikat ni kay sayon lang maabot sa mga public utility vehicles ug daghan og resources.'),
        'img'  => 'assets/img/schools/biliran_school.png',
        'lat'  => 11.4695,
        'lng'  => 124.4742
    ],
    [
        'name' => __('Caibiran ALS Center, Caibiran'),
        'desc' => __('Dool ra sa bantog nga mga busay (waterfalls) sa Caibiran, ang ALS center dire usa ka nindot nga balayan para sa mga working learners. Lami i-skwela dire kay natural ang ambiance ug pirme abtik ang komunidad.'),
        'img'  => 'assets/img/schools/caibiran_school.png',
        'lat'  => 11.5697,
        'lng'  => 124.5792
    ],
    [
        'name' => __('Cabucgayan Community ALS, Cabucgayan'),
        'desc' => __('Nag-atubang sa maanindot nga Carigara Bay, ang Cabucgayan ALS Center sikat sa ilang boardwalk-like surroundings. Makaparelax ang pagtuon dire tungod sa sea breeze ug tabian apan buotan nga mga locals.'),
        'img'  => 'assets/img/schools/cabucgayan_school.png',
        'lat'  => 11.47393,
        'lng'  => 124.57336
    ],
    [
        'name' => __('Naval National High School ALS Annex'),
        'desc' => __('Usa ka sikat kaayo nga eskwelahan sa buong probinsya sa Biliran. Dako og compound, moderno ang building, ug aduna sila\'y dedicated nga annex para lang sa mga ALS learners aron matutokan gayud sila og maayo sa ilang mga mobile teachers.'),
        'img'  => 'assets/img/schools/naval_school_sign_1782813858223.png',
        'lat'  => 11.57901,
        'lng'  => 124.4114
    ]
];

// ── Municipalities in Biliran Province ──
$biliran_municipalities = [
    [
        'name'       => __('Municipality of Naval'),
        'muni'       => 'Naval',
        'desc'       => __('Open for BLP, Elementary and Secondary A&E'),
        'icon'       => 'bi-geo-alt-fill',
        'badge'      => __('Capital Town'),
        'school_idx' => 2
    ],
    [
        'name'       => __('Municipality of Culaba'),
        'muni'       => 'Culaba',
        'desc'       => __('Open for BLP, Elementary and Secondary A&E'),
        'icon'       => 'bi-geo-alt-fill',
        'badge'      => __('Culaba District'),
        'school_idx' => 0
    ],
    [
        'name'       => __('Municipality of Almeria'),
        'muni'       => 'Almeria',
        'desc'       => __('Open for BLP, Elementary and Secondary A&E'),
        'icon'       => 'bi-geo-alt-fill',
        'badge'      => __('Almeria District'),
        'school_idx' => 3
    ],
    [
        'name'       => __('Municipality of Biliran'),
        'muni'       => 'Biliran',
        'desc'       => __('Open for BLP, Elementary and Secondary A&E'),
        'icon'       => 'bi-geo-alt-fill',
        'badge'      => __('Biliran District'),
        'school_idx' => 6
    ],
    [
        'name'       => __('Municipality of Cabucgayan'),
        'muni'       => 'Cabucgayan',
        'desc'       => __('Open for BLP, Elementary and Secondary A&E'),
        'icon'       => 'bi-geo-alt-fill',
        'badge'      => __('Cabucgayan District'),
        'school_idx' => 8
    ],
    [
        'name'       => __('Municipality of Caibiran'),
        'muni'       => 'Caibiran',
        'desc'       => __('Open for BLP, Elementary and Secondary A&E'),
        'icon'       => 'bi-geo-alt-fill',
        'badge'      => __('Caibiran District'),
        'school_idx' => 7
    ],
    [
        'name'       => __('Municipality of Kawayan'),
        'muni'       => 'Kawayan',
        'desc'       => __('Open for BLP, Elementary and Secondary A&E'),
        'icon'       => 'bi-geo-alt-fill',
        'badge'      => __('Kawayan District'),
        'school_idx' => 5
    ],
    [
        'name'       => __('Municipality of Maripipi'),
        'muni'       => 'Maripipi',
        'desc'       => __('Open for BLP, Elementary and Secondary A&E'),
        'icon'       => 'bi-geo-alt-fill',
        'badge'      => __('Island District'),
        'school_idx' => 4
    ]
];

// ── Handle Application Form Submission ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['als_apply'])) {
    $required = ['first_name','last_name','sex','birthdate','address','contact','email'];
    $form_data = $_POST;
    $missing   = false;

    foreach ($required as $field) {
        if (empty(trim($_POST[$field] ?? ''))) { $missing = true; break; }
    }

    if ($missing) {
        $submission_error = 'Please fill in all required fields.';
    } elseif (!filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL)) {
        $submission_error = 'Please enter a valid email address.';
    } else {
        $submission_success = true;
    }
}

// Reusable SVG for the official ALS Culaba District logo
$als_logo_svg = '
<svg viewBox="0 0 200 200" class="als-logo-svg" xmlns="http://www.w3.org/2000/svg" style="width: 100%; height: 100%;">
  <defs>
    <!-- Circular path for the text -->
    <path id="logoTextPath" d="M 100, 100 m -73, 0 a 73,73 0 1,1 146,0 a 73,73 0 1,1 -146,0" />
  </defs>
  
  <!-- Outer Ring -->
  <circle cx="100" cy="100" r="92" fill="none" stroke="#0d6efd" stroke-width="2.5" opacity="0.8" />
  <!-- Inner Ring (Dotted) -->
  <circle cx="100" cy="100" r="82" fill="none" stroke="#198754" stroke-width="1.5" stroke-dasharray="3,3" opacity="0.6" />
  
  <!-- White Circular Background for the letters -->
  <circle cx="100" cy="100" r="62" fill="#ffffff" stroke="rgba(15, 23, 42, 0.08)" stroke-width="1" />
  
  <!-- Circular text: ALTERNATIVE LEARNING SYSTEM • CULABA DISTRICT • DIVISION OF BILIRAN • -->
  <text font-family="\'Plus Jakarta Sans\', \'Segoe UI\', sans-serif" font-size="9.2" font-weight="800" fill="#0f172a" letter-spacing="0.5">
    <textPath href="#logoTextPath" startOffset="0%">
      ALTERNATIVE LEARNING SYSTEM • CULABA DISTRICT • DIVISION OF BILIRAN •
    </textPath>
  </text>
  
  <!-- Center Official ALS Logo -->
  <g transform="translate(97, 95)">
    <!-- Three dots above the A -->
    <circle cx="-28" cy="-28" r="4.5" fill="#009e49" />
    <circle cx="-16" cy="-28" r="4.5" fill="#003ca5" />
    <circle cx="-4" cy="-28" r="4.5" fill="#e50012" />
    
    <!-- Letter L (Green) - Drawn first so it is in the background -->
    <text x="-6" y="24" font-family="\'Arial Black\', Impact, sans-serif" font-weight="900" font-size="52" fill="#009e49" style="user-select: none;">L</text>
    
    <!-- Letter A (Red) - Drawn second to overlap L -->
    <text x="-38" y="24" font-family="\'Arial Black\', Impact, sans-serif" font-weight="900" font-size="52" fill="#e50012" style="user-select: none;">A</text>
    
    <!-- Letter S (Blue) - Drawn third to overlap L -->
    <text x="14" y="24" font-family="\'Arial Black\', Impact, sans-serif" font-weight="900" font-size="52" fill="#003ca5" style="user-select: none;">S</text>
  </g>
</svg>
';

$lang_names = [
    'en'  => 'English',
    'fil' => 'Filipino',
    'bis' => 'Bisaya',
    'war' => 'Waray'
];

$lang_flags = [
    'en'  => '🇬🇧',
    'fil' => '🇵🇭',
    'bis' => '🇵🇭',
    'war' => '🇵🇭'
];
$is_lang_selected = isset($_GET['lang']);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Alternative Learning System (ALS) — Culaba District, Division of Biliran') ?></title>
    <meta name="description" content="<?= __('Ang Alternative Learning System (ALS) ay isang programang pang-edukasyon sa Culaba, Biliran para sa mga out-of-school youth at adults.') ?>">
    <script>
      (function() {
          const size = localStorage.getItem('fontSize') || '16';
          document.documentElement.style.fontSize = size + 'px';
          document.documentElement.style.setProperty('--font-scale', size / 16);
      })();
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Animate On Scroll (AOS) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        body {
            zoom: var(--font-scale, 1);
        }
        /* ══════════════════════════════════════════════════════════
           DESIGN SYSTEM & VARIABLES — Premium ALS Theme v2
           ══════════════════════════════════════════════════════════ */
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Instrument+Serif:ital@0;1&display=swap');

        :root {
            /* Core Palette */
            --indigo:       #4f46e5;
            --indigo-dark:  #3730a3;
            --indigo-light: #818cf8;
            --teal:         #0d9488;
            --teal-light:   #2dd4bf;
            --amber:        #f59e0b;
            --rose:         #f43f5e;

            /* Semantic */
            --primary:      var(--indigo);
            --primary-rgb:  79, 70, 229;
            --accent:       var(--teal);
            --accent-rgb:   13, 148, 136;

            /* Neutrals */
            --navy-950:  #0b0f1a;
            --navy-900:  #0f172a;
            --navy-800:  #1e293b;
            --navy-700:  #334155;
            --navy-600:  #475569;
            --navy-400:  #94a3b8;
            --navy-200:  #e2e8f0;
            --navy-100:  #f1f5f9;
            --navy-50:   #f8fafc;

            /* Surface */
            --bg:         #f5f7fb;
            --surface:    #ffffff;
            --surface-2:  #fafbff;

            /* Gradients */
            --grad-primary:  linear-gradient(135deg, var(--indigo) 0%, #7c3aed 100%);
            --grad-accent:   linear-gradient(135deg, var(--teal) 0%, var(--teal-light) 100%);
            --grad-hero:     linear-gradient(135deg, #090a0f 0%, #0d1222 45%, #081622 100%);
            --grad-mesh:     radial-gradient(ellipse 80% 60% at 70% 0%, rgba(79,70,229,.22) 0%, transparent 70%),
                             radial-gradient(ellipse 60% 50% at 10% 90%, rgba(13,148,136,.16) 0%, transparent 70%);

            /* Shadows */
            --shadow-xs:  0 1px 3px rgba(0,0,0,.04), 0 1px 2px rgba(0,0,0,.03);
            --shadow-sm:  0 4px 12px rgba(0,0,0,.05), 0 2px 6px rgba(0,0,0,.04);
            --shadow-md:  0 10px 30px rgba(0,0,0,.07), 0 4px 12px rgba(0,0,0,.05);
            --shadow-lg:  0 20px 50px rgba(0,0,0,.09), 0 8px 24px rgba(0,0,0,.06);
            --shadow-xl:  0 35px 70px rgba(0,0,0,.13), 0 15px 35px rgba(0,0,0,.08);
            --shadow-glow-primary: 0 12px 40px -4px rgba(79,70,229,.35);
            --shadow-glow-accent:  0 12px 40px -4px rgba(13,148,136,.30);

            /* Borders */
            --border: rgba(15,23,42,.07);
            --border-accent: rgba(79,70,229,.15);

            /* Radius */
            --radius-sm:  8px;
            --radius-md:  14px;
            --radius-lg:  22px;
            --radius-xl:  32px;
            --radius-full: 9999px;

            /* Transitions */
            --ease:    cubic-bezier(.16,1,.3,1);
            --ease-in: cubic-bezier(.4,0,1,1);
        }

        /* ══ RESET & BASE ══ */
        *, *::before, *::after { box-sizing: border-box; }

        html {
            scroll-behavior: smooth;
            font-size: 16px;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: linear-gradient(160deg, #f0f4ff 0%, #e8fdf5 50%, #f5f0ff 100%) !important;
            background-attachment: fixed !important;
            color: var(--navy-900);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            letter-spacing: -.01em;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 7px; }
        ::-webkit-scrollbar-track { background: var(--navy-100); }
        ::-webkit-scrollbar-thumb {
            background: var(--indigo);
            border-radius: 6px;
            border: 2px solid var(--navy-100);
        }
        ::-webkit-scrollbar-thumb:hover { background: var(--teal); }

        /* ══════════════════════════════════════════════════════════
           UTILITY CLASSES
           ══════════════════════════════════════════════════════════ */
        .text-navy  { color: var(--navy-900) !important; }
        .text-muted { color: var(--navy-400) !important; }
        .fw-extrabold { font-weight: 800 !important; }

        /* Gradient text */
        .grad-text {
            background: var(--grad-primary);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .grad-text-accent {
            background: linear-gradient(135deg, var(--teal) 0%, var(--indigo-light) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Section tag pill */
        .section-tag {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
            padding: 6px 15px;
            border-radius: var(--radius-full);
            margin-bottom: 18px;
            background: rgba(79,70,229,.08);
            color: var(--indigo);
            border: 1.5px solid rgba(79,70,229,.14);
        }
        .section-tag-green {
            background: rgba(13,148,136,.08);
            color: var(--teal);
            border-color: rgba(13,148,136,.15);
        }
        .section-tag-amber {
            background: rgba(245,158,11,.08);
            color: var(--amber);
            border-color: rgba(245,158,11,.14);
        }

        /* Glass card */
        .glass {
            background: rgba(255,255,255,.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,.55);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }

        /* Hover lift */
        .hover-lift {
            transition: transform .4s var(--ease), box-shadow .4s var(--ease);
        }
        .hover-lift:hover {
            transform: translateY(-7px);
            box-shadow: var(--shadow-xl);
        }

        /* ══════════════════════════════════════════════════════════
           BUTTONS
           ══════════════════════════════════════════════════════════ */
        .btn-primary-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: var(--grad-primary);
            color: #fff !important;
            font-weight: 700;
            font-size: .95rem;
            padding: 14px 32px;
            border-radius: var(--radius-md);
            border: none;
            box-shadow: var(--shadow-glow-primary);
            transition: all .3s var(--ease);
            position: relative;
            overflow: hidden;
            text-decoration: none;
        }
        .btn-primary-custom::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg,rgba(255,255,255,.15) 0%,transparent 60%);
            opacity: 0;
            transition: opacity .3s;
        }
        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 40px -6px rgba(79,70,229,.45);
        }
        .btn-primary-custom:hover::before { opacity: 1; }

        .btn-secondary-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: rgba(255,255,255,.9);
            color: var(--navy-900) !important;
            font-weight: 700;
            font-size: .95rem;
            padding: 13px 30px;
            border-radius: var(--radius-md);
            border: 1.5px solid var(--border);
            box-shadow: var(--shadow-xs);
            transition: all .3s var(--ease);
            text-decoration: none;
        }
        .btn-secondary-custom:hover {
            background: var(--surface);
            border-color: rgba(79,70,229,.25);
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        /* Shimmer effect on CTA button */
        .btn-shimmer {
            position: relative;
            overflow: hidden;
        }
        .btn-shimmer::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 60%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.22), transparent);
            transform: skewX(-20deg);
            animation: shimmer 3s ease-in-out infinite;
        }
        @keyframes shimmer {
            0% { left: -100%; }
            60%, 100% { left: 150%; }
        }

        /* ══════════════════════════════════════════════════════════
           NAVBAR — Premium floating glass bar
           ══════════════════════════════════════════════════════════ */
        #main-nav {
            position: sticky;
            top: 0;
            z-index: 1030;
            padding: 14px 0;
            background: rgba(255,255,255,.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-bottom: 1px solid rgba(15,23,42,.06);
            box-shadow: 0 2px 20px rgba(0,0,0,.03);
            transition: all .4s var(--ease);
        }
        #main-nav.scrolled {
            padding: 10px 0;
            background: rgba(255,255,255,.96);
            border-bottom-color: rgba(79,70,229,.12);
            box-shadow: 0 8px 32px rgba(0,0,0,.06);
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--navy-900) !important;
            line-height: 1.2;
        }
        .navbar-brand strong { color: var(--indigo); }
        .navbar-brand span {
            font-size: .72rem;
            color: var(--navy-600);
            font-weight: 600;
            display: block;
            letter-spacing: .4px;
        }
        .nav-logo-icon-wrapper {
            transition: transform .4s var(--ease);
        }
        .navbar-brand:hover .nav-logo-icon-wrapper {
            transform: rotate(8deg) scale(1.05);
        }

        .nav-link {
            font-weight: 600;
            color: var(--navy-600) !important;
            padding: 7px 14px !important;
            border-radius: var(--radius-sm);
            transition: all .25s ease;
            font-size: .875rem;
            white-space: nowrap;
            position: relative;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 4px;
            left: 50%;
            transform: translateX(-50%) scaleX(0);
            width: 16px;
            height: 2.5px;
            border-radius: 2px;
            background: var(--indigo);
            transition: transform .25s var(--ease);
        }
        .nav-link:hover { color: var(--indigo) !important; background: rgba(79,70,229,.05); }
        .nav-link:hover::after,
        .nav-link.active::after { transform: translateX(-50%) scaleX(1); }
        .nav-link.active { color: var(--indigo) !important; font-weight: 700; }

        /* ══════════════════════════════════════════════════════════
           HERO SECTION
           ══════════════════════════════════════════════════════════ */
        #home-section {
            position: relative;
            background: var(--grad-hero);
            color: #fff;
            min-height: 88vh;
            display: flex;
            align-items: center;
            padding: 80px 0 100px;
            overflow: hidden;
        }
        .hero-video-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            pointer-events: none;
        }
        .hero-video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(11, 15, 26, 0.8) 0%, rgba(17, 24, 39, 0.7) 50%, rgba(13, 31, 45, 0.8) 100%);
            z-index: 1;
            pointer-events: none;
        }
        #home-section .container {
            position: relative;
            z-index: 3;
        }
        
        /* ── SECTION SEPARATORS ── */
        #about-als, #programs, #als-centers, #requirements, #enrollment-process, #why-choose-als, #faq-section {
            position: relative;
        }
        #about-als::after, #programs::after, #als-centers::after, #requirements::after, #enrollment-process::after, #why-choose-als::after, #faq-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent 5%, rgba(79, 70, 229, 0.65) 25%, rgba(13, 148, 136, 0.65) 75%, transparent 95%);
            pointer-events: none;
            z-index: 5;
        }
        .section-divider {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent 5%, rgba(79, 70, 229, 0.65) 25%, rgba(13, 148, 136, 0.65) 75%, transparent 95%);
            pointer-events: none;
            z-index: 5;
        }
        
        /* ── LEAFLET CUSTOM POPUP ── */
        .custom-leaflet-popup .leaflet-popup-content-wrapper {
            border-radius: 16px;
            padding: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .custom-leaflet-popup .leaflet-popup-tip {
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        /* ── PIN BOUNCE & PULSE ANIMATIONS ── */
        @keyframes pinBounce {
            0% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
            100% { transform: translateY(0); }
        }
        .bouncing-pin {
            animation: pinBounce 1.4s ease-in-out infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 12px rgba(59, 130, 246, 0); }
            100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }
        .pulse-dot {
            animation: pulse 1.8s infinite;
        }
        .hero-mesh {
            position: absolute;
            inset: 0;
            background: var(--grad-mesh);
            pointer-events: none;
        }
        .hero-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }
        .hero-noise {
            position: absolute;
            inset: 0;
            opacity: .025;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.8' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
            pointer-events: none;
        }

        /* Animated orbs */
        .hero-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .5;
            pointer-events: none;
            animation: orbFloat 14s ease-in-out infinite alternate;
        }
        .hero-orb-1 {
            width: 520px; height: 520px;
            background: radial-gradient(circle, rgba(79,70,229,.5), transparent 70%);
            top: -160px; right: -60px;
        }
        .hero-orb-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(13,148,136,.45), transparent 70%);
            bottom: -100px; left: -80px;
            animation-delay: -6s;
        }
        .hero-orb-3 {
            width: 280px; height: 280px;
            background: radial-gradient(circle, rgba(124,58,237,.35), transparent 70%);
            top: 30%; left: 35%;
            animation-delay: -3s;
        }
        @keyframes orbFloat {
            from { transform: translate(0,0) scale(1); }
            to   { transform: translate(30px,-25px) scale(1.08); }
        }

        /* Hero badge */
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.18);
            border-radius: var(--radius-full);
            padding: 6px 16px;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .5px;
            color: rgba(255,255,255,.9);
            margin-bottom: 20px;
            backdrop-filter: blur(8px);
        }
        .hero-badge .dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--teal-light);
            animation: pulseDot 2s ease-in-out infinite;
        }
        @keyframes pulseDot {
            0%,100% { opacity: 1; transform: scale(1); }
            50% { opacity: .6; transform: scale(.8); }
        }

        /* Hero title */
        .hero-title {
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 900;
            line-height: 1.12;
            color: #fff;
            margin-bottom: 22px;
        }
        .hero-title .accent-line {
            display: block;
            background: linear-gradient(90deg, var(--teal-light), var(--indigo-light));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-subtitle {
            font-size: 1.05rem;
            color: rgba(255,255,255,.72);
            line-height: 1.7;
            max-width: 480px;
            margin-bottom: 36px;
            font-weight: 400;
        }

        /* Floating decorations in hero */
        .floating-cap-deco {
            position: absolute;
            top: -25px; left: -25px;
            width: 54px; height: 54px;
            background: rgba(255,255,255,.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            animation: floatMiniItem 4s ease-in-out infinite;
            z-index: 10;
        }
        .floating-check-deco {
            position: absolute;
            bottom: -15px; right: -15px;
            width: 50px; height: 50px;
            background: rgba(13,148,136,.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            animation: floatMiniItem 4.5s ease-in-out infinite .5s;
            z-index: 10;
        }
        @keyframes floatMiniItem {
            0%,100% { transform: translateY(0) rotate(0deg); }
            50%      { transform: translateY(-10px) rotate(5deg); }
        }

        /* Hero portal / image frame */
        .hero-portal-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-image-frame {
            position: relative;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,.5);
            aspect-ratio: 16/10;
            width: 100%;
            max-width: 600px;
        }
        .hero-image-frame img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .hero-image-frame video {
            width: 100%; height: 100%;
            object-fit: cover;
            mix-blend-mode: screen;
        }
        .hero-image-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(11,15,26,.6) 0%, transparent 60%);
        }

        /* Logo slot (flying logo) */
        .logo-placeholder {
            display: inline-block;
            width: 90px; height: 90px;
            background: rgba(79,70,229,.06);
            border: 2px dashed rgba(79,70,229,.2);
            border-radius: 50%;
            vertical-align: middle;
            flex-shrink: 0;
            transition: all .4s var(--ease);
            position: relative;
        }
        .logo-placeholder[data-slot="home"] { width: 90px; height: 90px; }
        .logo-placeholder.active-slot {
            background: transparent !important;
            border-color: transparent !important;
        }
        #flying-logo {
            background: rgba(255,255,255,.12);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 8px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 16px 40px rgba(0,0,0,.35);
            border: 1.5px solid rgba(255,255,255,.2);
            width: 90px; height: 90px;
            box-sizing: border-box;
            z-index: 9999;
            backface-visibility: hidden;
        }

        /* Typing cursor */
        .type-cursor {
            display: inline-block;
            width: 3px;
            height: .9em;
            background: var(--teal-light);
            margin-left: 4px;
            animation: blinkCursor .8s step-end infinite;
            vertical-align: middle;
            border-radius: 2px;
        }
        @keyframes blinkCursor {
            from,to { background-color: transparent; }
            50%      { background-color: var(--teal-light); }
        }

        /* ══════════════════════════════════════════════════════════
           STATS STRIP
           ══════════════════════════════════════════════════════════ */
        #stats-strip {
            position: relative;
            background: transparent;
            padding: 0 0 8px;
            margin-top: -60px;
            z-index: 5;
        }
        .stats-panel {
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-xl);
            padding: 8px 0;
            overflow: hidden;
            position: relative;
        }
        .stats-panel::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--indigo) 0%, var(--teal) 100%);
        }
        .stat-cell {
            text-align: center;
            padding: 24px 16px;
            position: relative;
        }
        .stat-cell:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 50%; right: 0;
            transform: translateY(-50%);
            width: 1px; height: 45%;
            background: var(--border);
        }
        @media (max-width: 767px) {
            .stat-cell:not(:last-child)::after { display: none; }
        }
        .stat-number {
            font-weight: 900;
            font-size: 2.2rem;
            line-height: 1;
            background: linear-gradient(135deg, var(--indigo), var(--teal));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-label {
            display: block;
            margin-top: 6px;
            font-size: .76rem;
            font-weight: 700;
            letter-spacing: .3px;
            color: var(--navy-600);
        }

        /* ══════════════════════════════════════════════════════════
           PROGRAMS SHOWCASE — Vibrant Youth Cards
           ══════════════════════════════════════════════════════════ */

        /* Section wave separators */
        .section-wave {
            position: absolute;
            bottom: -1px; left: 0; right: 0;
            pointer-events: none;
            line-height: 0;
        }
        .section-wave-top {
            position: absolute;
            top: -1px; left: 0; right: 0;
            pointer-events: none;
            line-height: 0;
        }

        /* Floating particles background */
        .floating-particles-bg {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }
        .fp {
            position: absolute;
            border-radius: 50%;
            opacity: 0.15;
            animation: fpFloat linear infinite;
        }
        @keyframes fpFloat {
            0%   { transform: translateY(0) rotate(0deg); opacity: 0; }
            10%  { opacity: 0.18; }
            90%  { opacity: 0.18; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }

        /* Animated section badge */
        .section-badge-animated {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 16px;
            position: relative;
            overflow: hidden;
        }
        .section-badge-animated::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transform: translateX(-100%);
            animation: badgeShimmer 2.5s ease-in-out infinite;
        }
        @keyframes badgeShimmer {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(200%); }
        }

        /* Section headings with underline gradient */
        .section-title-xl {
            font-size: clamp(1.7rem, 4vw, 2.5rem);
            font-weight: 900;
            line-height: 1.15;
            letter-spacing: -0.5px;
            position: relative;
        }
        .section-title-xl .underline-word {
            position: relative;
            display: inline-block;
        }
        .section-title-xl .underline-word::after {
            content: '';
            position: absolute;
            bottom: -4px; left: 0; right: 0;
            height: 4px;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--indigo), var(--teal));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.6s var(--ease);
        }
        .section-title-xl .underline-word.animated::after {
            transform: scaleX(1);
        }

        /* ── PROGRAMS SECTION ── */
        #programs {
            background: transparent;
            position: relative;
            overflow: hidden;
        }

        .program-card {
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            padding: 0;
            height: 100%;
            transition: transform 0.45s var(--ease), box-shadow 0.45s var(--ease);
            cursor: pointer;
        }
        .program-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 30px 70px rgba(0,0,0,.14);
        }
        .program-card-inner {
            background: #ffffff;
            border-radius: 24px;
            padding: 36px 30px 32px;
            height: 100%;
            border: 1.5px solid rgba(255,255,255,0.9);
            position: relative;
            overflow: hidden;
        }
        .program-card-inner::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 160px; height: 160px;
            border-radius: 50%;
            opacity: 0.07;
            transition: transform 0.5s var(--ease);
        }
        .program-card:hover .program-card-inner::before {
            transform: scale(1.5);
        }
        .pc-blue .program-card-inner::before  { background: var(--indigo); }
        .pc-green .program-card-inner::before  { background: var(--teal); }
        .pc-violet .program-card-inner::before { background: #7c3aed; }

        .pc-blue .program-card-inner  { box-shadow: 0 8px 30px rgba(79,70,229,.12); }
        .pc-green .program-card-inner  { box-shadow: 0 8px 30px rgba(13,148,136,.12); }
        .pc-violet .program-card-inner { box-shadow: 0 8px 30px rgba(124,58,237,.12); }

        .program-emoji-badge {
            width: 72px; height: 72px;
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem;
            margin-bottom: 22px;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }
        .program-card:hover .program-emoji-badge {
            transform: scale(1.15) rotate(8deg);
        }
        .pc-blue  .program-emoji-badge { background: rgba(79,70,229,.1); }
        .pc-green  .program-emoji-badge { background: rgba(13,148,136,.1); }
        .pc-violet .program-emoji-badge { background: rgba(124,58,237,.1); }

        .program-level-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }
        .pc-blue  .program-level-pill { background: rgba(79,70,229,.1); color: var(--indigo); }
        .pc-green  .program-level-pill { background: rgba(13,148,136,.1); color: var(--teal); }
        .pc-violet .program-level-pill { background: rgba(124,58,237,.1); color: #7c3aed; }

        .program-card h4 {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--navy-900);
            margin-bottom: 12px;
        }
        .program-card p { color: var(--navy-600); font-size: 0.88rem; line-height: 1.7; }

        .program-learn-list {
            list-style: none;
            padding: 0; margin: 16px 0;
        }
        .program-learn-list li {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.82rem; color: var(--navy-700);
            margin-bottom: 7px;
            padding: 5px 10px;
            border-radius: 8px;
            transition: background 0.2s ease;
        }
        .program-learn-list li:hover { background: rgba(79,70,229,.04); }
        .program-learn-list li .check-dot {
            width: 20px; height: 20px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem; font-weight: 700; flex-shrink: 0;
        }
        .pc-blue  .check-dot { background: rgba(79,70,229,.1);  color: var(--indigo); }
        .pc-green  .check-dot { background: rgba(13,148,136,.1); color: var(--teal); }
        .pc-violet .check-dot { background: rgba(124,58,237,.1); color: #7c3aed; }

        .program-reward-box {
            padding: 14px 16px;
            border-radius: 14px;
            margin-top: 20px;
            display: flex; align-items: center; gap: 12px;
        }
        .pc-blue  .program-reward-box { background: linear-gradient(135deg, rgba(79,70,229,.07) 0%, rgba(129,140,248,.07) 100%); border: 1.5px solid rgba(79,70,229,.12); }
        .pc-green  .program-reward-box { background: linear-gradient(135deg, rgba(13,148,136,.07) 0%, rgba(45,212,191,.07) 100%); border: 1.5px solid rgba(13,148,136,.12); }
        .pc-violet .program-reward-box { background: linear-gradient(135deg, rgba(124,58,237,.07) 0%, rgba(167,139,250,.07) 100%); border: 1.5px solid rgba(124,58,237,.12); }

        .program-reward-icon { font-size: 1.6rem; flex-shrink: 0; }
        .program-reward-box p { font-size: 0.82rem; color: var(--navy-700); line-height: 1.5; margin: 0; }

        /* ── ALS CENTERS SECTION ── */
        #als-centers {
            background: transparent;
            position: relative;
            overflow: hidden;
        }
        #als-centers::before {
            content: '';
            position: absolute;
            top: -200px; right: -200px;
            width: 600px; height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(13,148,136,.06), transparent 70%);
            pointer-events: none;
        }

        .center-card {
            background: var(--surface);
            border-radius: 18px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            height: 100%;
            transition: all .35s var(--ease);
            cursor: pointer;
            border: 1.5px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        .center-card::after {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, var(--indigo), var(--teal));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .center-card:hover {
            border-color: rgba(79,70,229,.3);
            background: rgba(79,70,229,.025);
            transform: translateX(6px);
            box-shadow: var(--shadow-md);
        }
        .center-card:hover::after { opacity: 1; }
        .center-pin {
            width: 44px; height: 44px;
            border-radius: 13px;
            background: rgba(13,148,136,.1);
            color: var(--teal);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem; flex-shrink: 0;
            transition: all .3s ease;
        }
        .center-card:hover .center-pin {
            background: linear-gradient(135deg, var(--indigo), var(--teal));
            color: #fff;
            transform: rotate(-8deg) scale(1.1);
        }
        .center-number {
            position: absolute;
            top: 10px; right: 14px;
            font-size: 1.6rem;
            font-weight: 900;
            color: rgba(79,70,229,.06);
            font-family: 'Instrument Serif', serif;
            font-style: italic;
            line-height: 1;
        }

        /* ── REQUIREMENTS SECTION ── */
        #requirements {
            background: transparent;
            position: relative;
            overflow: hidden;
        }

        .requirement-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 32px 26px 28px;
            height: 100%;
            transition: all .45s var(--ease);
            position: relative;
            overflow: hidden;
            border: 1.5px solid rgba(255,255,255,.8);
            box-shadow: 0 4px 20px rgba(0,0,0,.05);
        }
        .requirement-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--grad-primary);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s var(--ease);
        }
        .req-green::before { background: var(--grad-accent); }
        .req-violet::before { background: linear-gradient(135deg, #7c3aed, #a78bfa); }
        .req-amber::before { background: linear-gradient(135deg, var(--amber), #f97316); }

        .requirement-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(79,70,229,.14);
            border-color: rgba(79,70,229,.2);
        }
        .requirement-card:hover::before { transform: scaleX(1); }

        .req-emoji {
            font-size: 2.8rem;
            margin-bottom: 14px;
            display: block;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .requirement-card:hover .req-emoji {
            transform: scale(1.2) rotate(-5deg);
        }
        .req-num-badge {
            position: absolute;
            top: 18px; right: 18px;
            width: 32px; height: 32px;
            border-radius: 50%;
            background: rgba(79,70,229,.08);
            color: var(--indigo);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.78rem; font-weight: 800;
        }
        .req-green .req-num-badge { background: rgba(13,148,136,.08); color: var(--teal); }
        .req-violet .req-num-badge { background: rgba(124,58,237,.08); color: #7c3aed; }
        .req-amber .req-num-badge { background: rgba(245,158,11,.08); color: var(--amber); }

        /* ── ENROLLMENT PROCESS SECTION ── */
        #enrollment-process {
            background: transparent;
            position: relative;
            overflow: hidden;
        }

        .step-timeline {
            position: relative;
            display: flex;
            gap: 0;
            justify-content: center;
            align-items: stretch;
        }
        .step-timeline::before {
            content: '';
            position: absolute;
            top: 36px;
            left: 15%; right: 15%;
            height: 3px;
            background: linear-gradient(90deg, var(--indigo), var(--teal));
            z-index: 0;
        }
        @media (max-width: 991px) {
            .step-timeline { flex-direction: column; align-items: center; }
            .step-timeline::before {
                top: 10%; bottom: 10%;
                left: 36px; right: auto;
                width: 3px; height: auto;
                background: linear-gradient(180deg, var(--indigo), var(--teal));
            }
            .step-timeline-item { flex-direction: row !important; text-align: left !important; max-width: 480px; width: 100%; }
            .step-timeline-item .step-badge-new { margin: 0 !important; flex-shrink: 0; }
        }
        .step-timeline-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 0 10px;
            position: relative;
            z-index: 1;
        }
        .step-badge-new {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--indigo), #7c3aed);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(79,70,229,.35);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            z-index: 2;
            border: 4px solid #fff;
            flex-shrink: 0;
        }
        .step-badge-new.s-teal { background: linear-gradient(135deg, var(--teal), #0ea5e9); box-shadow: 0 8px 24px rgba(13,148,136,.35); }
        .step-badge-new.s-violet { background: linear-gradient(135deg, #7c3aed, #a855f7); box-shadow: 0 8px 24px rgba(124,58,237,.35); }
        .step-badge-new.s-amber { background: linear-gradient(135deg, var(--amber), #f97316); box-shadow: 0 8px 24px rgba(245,158,11,.35); }
        .step-badge-new.s-rose { background: linear-gradient(135deg, var(--rose), #fb923c); box-shadow: 0 8px 24px rgba(244,63,94,.35); }

        .step-timeline-item:hover .step-badge-new { transform: scale(1.15) translateY(-5px); }

        .step-card-new {
            background: #ffffff;
            border-radius: 18px;
            padding: 20px 16px;
            margin-top: 0;
            border: 1.5px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: all 0.4s var(--ease);
        }
        .step-timeline-item:hover .step-card-new {
            border-color: rgba(79,70,229,.2);
            box-shadow: var(--shadow-md);
            transform: translateY(-4px);
        }



        /* ── WHY ALS SECTION ── */
        #why-choose-als {
            background: transparent;
            position: relative;
            overflow: hidden;
        }

        .feature-check-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 16px 20px;
            border-radius: var(--radius-md);
            background: var(--surface);
            border: 1.5px solid var(--border);
            margin-bottom: 12px;
            transition: all .35s var(--ease);
            position: relative;
            overflow: hidden;
        }
        .feature-check-item::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 3px;
            background: linear-gradient(180deg, var(--indigo), var(--teal));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .feature-check-item:hover {
            border-color: rgba(79,70,229,.2);
            background: rgba(79,70,229,.025);
            transform: translateX(6px);
            box-shadow: var(--shadow-sm);
        }
        .feature-check-item:hover::before { opacity: 1; }
        .check-badge {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(13,148,136,.15), rgba(13,148,136,.05));
            color: var(--teal);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; font-weight: 700;
            flex-shrink: 0; margin-top: 1px;
            transition: transform .3s ease, background .3s ease;
        }
        .feature-check-item:hover .check-badge {
            background: var(--teal);
            color: #fff;
            transform: scale(1.15) rotate(12deg);
        }

        /* ── FAQ SECTION ── */
        #faq-section {
            background: transparent;
            position: relative;
            overflow: hidden;
        }

        .faq-accordion .accordion-item {
            border: 1.5px solid rgba(79,70,229,.1) !important;
            border-radius: var(--radius-md) !important;
            margin-bottom: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,.04);
            transition: box-shadow .3s ease, transform .3s ease;
            background: #fff;
        }
        .faq-accordion .accordion-item:hover {
            box-shadow: 0 8px 24px rgba(79,70,229,.1);
            transform: translateY(-2px);
        }
        .faq-accordion .accordion-button {
            font-weight: 700;
            color: var(--navy-900);
            padding: 20px 24px;
            background: #fff;
            font-size: .93rem;
        }
        .faq-accordion .accordion-button:focus { box-shadow: none; }
        .faq-accordion .accordion-button:not(.collapsed) {
            color: var(--indigo);
            background: rgba(79,70,229,.03);
            box-shadow: inset 0 -1px 0 rgba(79,70,229,.08);
        }
        .faq-accordion .accordion-body {
            padding: 4px 24px 22px;
            color: var(--navy-600);
            font-size: .92rem;
            line-height: 1.75;
        }
        .faq-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px; height: 24px;
            border-radius: 6px;
            background: rgba(79,70,229,.1);
            color: var(--indigo);
            font-size: 0.72rem;
            font-weight: 800;
            margin-right: 10px;
            flex-shrink: 0;
        }

        /* ── CONTACT SECTION ── */
        #contact-section {
            background: transparent;
            position: relative;
            overflow: hidden;
        }
        .contact-info-card {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 18px;
            border-radius: 16px;
            margin-bottom: 12px;
            transition: all 0.3s var(--ease);
            border: 1.5px solid var(--border);
            background: var(--surface-2);
        }
        .contact-info-card:hover {
            border-color: rgba(79,70,229,.2);
            background: rgba(79,70,229,.025);
            transform: translateX(5px);
            box-shadow: var(--shadow-sm);
        }
        .contact-icon-wrap {
            width: 50px; height: 50px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
            transition: all .3s ease;
        }
        .contact-info-card:hover .contact-icon-wrap {
            transform: scale(1.1) rotate(-6deg);
        }
        .ci-blue  { background: rgba(79,70,229,.1); color: var(--indigo); }
        .ci-green { background: rgba(13,148,136,.1); color: var(--teal); }
        .ci-amber { background: rgba(245,158,11,.1); color: var(--amber); }
        .ci-rose  { background: rgba(244,63,94,.1); color: var(--rose); }

        /* ── CTA SECTION ── */
        #cta-section {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1.5px solid rgba(79, 70, 229, 0.12);
            border-radius: 32px;
            padding: 80px 50px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(79, 70, 229, 0.08);
            text-align: center;
        }
        .cta-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(79, 70, 229, 0.03);
            pointer-events: none;
        }
        .cta-shape-1 { width: 400px; height: 400px; top: -120px; right: -80px; }
        .cta-shape-2 { width: 280px; height: 280px; bottom: -100px; left: -80px; }
        .cta-shape-3 { width: 180px; height: 180px; top: 40%; left: 15%; background: rgba(79, 70, 229, 0.02); }

        .cta-float-emoji {
            position: absolute;
            font-size: 2rem;
            animation: ctaEmojiFloat ease-in-out infinite;
            pointer-events: none;
            opacity: 0.6;
        }
        @keyframes ctaEmojiFloat {
            0%,100% { transform: translateY(0) rotate(0deg); }
            50%      { transform: translateY(-15px) rotate(10deg); }
        }

        .btn-cta-big {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--grad-primary);
            color: #fff !important;
            font-weight: 800;
            font-size: 1rem;
            padding: 16px 40px;
            border-radius: var(--radius-full);
            text-decoration: none;
            transition: all 0.35s var(--ease);
            box-shadow: var(--shadow-glow-primary);
            position: relative;
            overflow: hidden;
        }
        .btn-cta-big::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.4), transparent);
            transform: translateX(-100%);
            animation: ctaBtnShimmer 2.5s ease-in-out infinite;
        }
        @keyframes ctaBtnShimmer {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(200%); }
        }
        .btn-cta-big:hover {
            transform: translateY(-4px) scale(1.04);
            box-shadow: 0 18px 40px -6px rgba(79,70,229,0.45);
        }

        /* ── FOOTER ENHANCEMENTS ── */
        footer {
            background: linear-gradient(160deg, #08121f 0%, #0d1a2d 100%);
            color: var(--navy-400);
            border-top: 1px solid rgba(255,255,255,.04);
            position: relative;
            overflow: hidden;
        }
        footer::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--indigo), var(--teal), #7c3aed, var(--indigo));
            background-size: 200% 100%;
            animation: footerRainbow 4s linear infinite;
        }
        @keyframes footerRainbow {
            0%   { background-position: 0% 0%; }
            100% { background-position: 200% 0%; }
        }
        footer a {
            color: var(--navy-400);
            text-decoration: none;
            transition: all .25s ease;
        }
        footer a:hover { color: #fff; padding-left: 3px; }



        /* ══════════════════════════════════════════════════════════
           BACK TO TOP
           ══════════════════════════════════════════════════════════ */
        #back-to-top {
            position: fixed;
            bottom: 28px; right: 28px;
            width: 48px; height: 48px;
            border-radius: 50%;
            background: var(--grad-primary);
            color: #fff;
            border: none;
            box-shadow: var(--shadow-glow-primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            cursor: pointer;
            z-index: 1000;
            opacity: 0; visibility: hidden;
            transition: all .3s var(--ease);
        }
        #back-to-top.show { opacity: 1; visibility: visible; }
        #back-to-top:hover {
            background: var(--grad-accent);
            transform: translateY(-4px) scale(1.08);
            box-shadow: var(--shadow-glow-accent);
        }

        /* ══════════════════════════════════════════════════════════
           ATTENTION BOUNCE
           ══════════════════════════════════════════════════════════ */
        @keyframes attentionBounce {
            0%,20%,50%,80%,100% { transform: translateY(0) scale(1); }
            40% { transform: translateY(-22px) scale(1.08); }
            60% { transform: translateY(-10px) scale(1.04); }
        }
        .attention-bounce {
            animation: attentionBounce 1.2s cubic-bezier(.175,.885,.32,1.275);
            box-shadow: 0 16px 40px rgba(79,70,229,.5) !important;
        }

        /* ══════════════════════════════════════════════════════════
           SECTION DIVIDERS
           ══════════════════════════════════════════════════════════ */
        .section-divider-short {
            width: 56px; height: 4px;
            border-radius: 4px;
            background: var(--grad-primary);
            margin: 16px auto 0;
        }
        .section-divider-short-left {
            margin-left: 0;
        }

        /* Pointing Arrows for Apply Button */
        .pointing-arrow-left {
            animation: pointLeftToRight 1.2s infinite ease-in-out;
        }
        .pointing-arrow-right {
            animation: pointRightToLeft 1.2s infinite ease-in-out;
        }
        @keyframes pointLeftToRight {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(6px); }
        }
        @keyframes pointRightToLeft {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(-6px); }
        }

        /* Global Dark Theme overrides removed by user request */

        /* ══════════════════════════════════════════════════════════
           LOGO PLACEHOLDER — Consistent Across All Sections
           ══════════════════════════════════════════════════════════ */
        .logo-placeholder {
            width: 90px; height: 90px;
            display: inline-block;
            flex-shrink: 0;
            border-radius: 50%;
            position: relative;
        }
        /* The floating logo element itself */
        #flying-logo {
            width: 100px; height: 100px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,.9);
            box-shadow: 0 8px 32px rgba(79,70,229,.2), 0 2px 8px rgba(0,0,0,.1);
            background: linear-gradient(135deg, #fff 0%, #f9fafb 100%);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            padding: 6px;
            transition: transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 500;
        }

        /* ── ABOUT ALS section ── */
        #about-als {
            background: transparent;
            position: relative;
            overflow: hidden;
        }
        #about-als::before {
            content: '';
            position: absolute;
            top: -150px; right: -150px;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(79,70,229,.06), transparent 70%);
            pointer-events: none;
        }
        #about-als::after {
            content: '';
            position: absolute;
            bottom: -100px; left: -100px;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(13,148,136,.05), transparent 70%);
            pointer-events: none;
        }

        .about-img-container {
            position: relative;
            padding: 12px;
        }
        .about-img-back-frame {
            position: absolute;
            top: 0; left: 0; right: 24px; bottom: 24px;
            background: linear-gradient(135deg, rgba(79,70,229,.08) 0%, rgba(13,148,136,.06) 100%);
            border: 1.5px solid rgba(79,70,229,.12);
            border-radius: 28px;
            z-index: 1;
        }
        .about-img-new {
            position: relative;
            z-index: 2;
            transition: transform 0.4s var(--ease), box-shadow 0.4s var(--ease);
            border: 4px solid #ffffff;
            box-shadow: 0 12px 40px rgba(0,0,0,.1) !important;
            border-radius: 24px;
        }
        .about-img-container:hover .about-img-new {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 0 24px 60px rgba(0,0,0,.14) !important;
        }

        /* Feature cards inside About ALS */
        .about-feature-card {
            background: var(--surface);
            border: 1.5px solid rgba(79,70,229,.08);
            border-radius: 16px;
            padding: 1.1rem 1.1rem;
            display: flex;
            align-items: start;
            gap: 1rem;
            transition: all 0.35s var(--ease);
            box-shadow: 0 2px 10px rgba(0,0,0,.03);
        }
        .about-feature-card:hover {
            transform: translateY(-4px);
            border-color: rgba(79,70,229,.2);
            box-shadow: 0 8px 24px rgba(79,70,229,.1);
        }
        .about-feature-icon {
            width: 42px; height: 42px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem; flex-shrink: 0;
            transition: transform 0.3s var(--ease);
        }
        .about-feature-card:hover .about-feature-icon { transform: scale(1.12) rotate(6deg); }
        .icon-blue   { background: rgba(79,70,229,.09);   color: var(--indigo); }
        .icon-green  { background: rgba(13,148,136,.09);  color: var(--teal); }
        .icon-teal   { background: rgba(45,212,191,.12);  color: #0ea5e9; }
        .icon-red    { background: rgba(244,63,94,.09);   color: var(--rose); }
        .icon-violet { background: rgba(124,58,237,.09);  color: #7c3aed; }
        .icon-amber  { background: rgba(245,158,11,.09);  color: var(--amber); }
        .about-feature-title { font-weight: 700; font-size: 0.9rem; color: var(--navy-900); margin-bottom: 3px; }
        .about-feature-desc  { font-size: 0.78rem; color: var(--navy-600); line-height: 1.5; margin: 0; }

        /* ALS by the numbers strip */
        .als-stats-strip {
            background: linear-gradient(135deg, var(--indigo), #7c3aed);
            border-radius: 24px;
            padding: 32px 36px;
            margin-top: 40px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(79,70,229,.35);
        }
        .als-stats-strip::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
            opacity: .04;
            pointer-events: none;
        }
        .als-stat-item { position: relative; }
        .als-stat-item + .als-stat-item::before {
            content: '';
            position: absolute;
            left: 0; top: 20%; bottom: 20%;
            width: 1px;
            background: rgba(255,255,255,.2);
        }
        .als-stat-big { font-size: 2rem; font-weight: 900; color: #fff; line-height: 1; }
        .als-stat-small { font-size: 0.72rem; font-weight: 600; color: rgba(255,255,255,.65); text-transform: uppercase; letter-spacing: .8px; margin-top: 4px; }

        /* ══════════════════════════════════════════════════════════
           WHY CHOOSE ALS — Redesigned
           ══════════════════════════════════════════════════════════ */
        #why-choose-als {
            background: transparent;
            position: relative;
            overflow: hidden;
        }
        .why-mesh {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 60% at 90% 10%, rgba(79,70,229,.25) 0%, transparent 60%),
                radial-gradient(ellipse 55% 50% at 5% 85%, rgba(13,148,136,.2) 0%, transparent 60%),
                radial-gradient(ellipse 40% 35% at 45% 50%, rgba(124,58,237,.12) 0%, transparent 60%);
            pointer-events: none;
        }
        .why-grid-bg {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 44px 44px;
            pointer-events: none;
        }

        .why-card {
            background: rgba(255, 255, 255, 0.7);
            border: 1.5px solid rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 24px 22px;
            height: 100%;
            position: relative;
            overflow: hidden;
            transition: all 0.4s var(--ease);
            backdrop-filter: blur(8px);
            box-shadow: var(--shadow-sm);
        }
        .why-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            border-radius: 2px 2px 0 0;
            opacity: 0;
            transition: opacity .35s ease;
        }
        .why-card:hover {
            transform: translateY(-8px);
            background: rgba(255, 255, 255, 0.9);
            border-color: rgba(79, 70, 229, 0.25);
            box-shadow: 0 20px 40px rgba(79, 70, 229, 0.12);
        }
        .why-card:hover::before { opacity: 1; }
        .wc-1::before { background: linear-gradient(90deg, #4f46e5, #818cf8); }
        .wc-2::before { background: linear-gradient(90deg, #0d9488, #2dd4bf); }
        .wc-3::before { background: linear-gradient(90deg, #7c3aed, #a855f7); }
        .wc-4::before { background: linear-gradient(90deg, #f59e0b, #f97316); }
        .wc-5::before { background: linear-gradient(90deg, #f43f5e, #fb923c); }
        .wc-6::before { background: linear-gradient(90deg, #06b6d4, #0ea5e9); }

        .why-icon {
            width: 56px; height: 56px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 16px;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .why-card:hover .why-icon { transform: scale(1.18) rotate(8deg); }
        .wc-1 .why-icon { background: rgba(79,70,229,.18); }
        .wc-2 .why-icon { background: rgba(13,148,136,.18); }
        .wc-3 .why-icon { background: rgba(124,58,237,.18); }
        .wc-4 .why-icon { background: rgba(245,158,11,.18); }
        .wc-5 .why-icon { background: rgba(244,63,94,.18); }
        .wc-6 .why-icon { background: rgba(6,182,212,.18); }

        .why-card h5 { color: var(--navy-900); font-weight: 800; font-size: 1rem; margin-bottom: 8px; }
        .why-card p  { color: var(--navy-600); font-size: 0.84rem; line-height: 1.65; margin: 0; }

        /* Consistent section logo style (visible in hero, used in sections) */
        .section-logo-icon {
            width: 48px; height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fff, #eef2ff);
            border: 2px solid rgba(79,70,229,.2);
            box-shadow: 0 4px 16px rgba(79,70,229,.18), 0 1px 4px rgba(0,0,0,.08);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            padding: 5px;
            flex-shrink: 0;
        }
        .section-logo-icon-dark {
            width: 48px; height: 48px;
            border-radius: 50%;
            background: rgba(255,255,255,.08);
            border: 2px solid rgba(255,255,255,.2);
            box-shadow: 0 4px 16px rgba(0,0,0,.2);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            padding: 5px;
            flex-shrink: 0;
        }

        /* ABOUT ALS enhanced header */
        .about-header-modern {
            font-weight: 900;
            color: var(--navy-900) !important;
            letter-spacing: -0.5px;
        }

        /* ══════════════════════════════════════════════════════════
           RESPONSIVE
           ══════════════════════════════════════════════════════════ */
        @media (max-width: 991px) {
            #home-section {
                padding-top: 60px;
                padding-bottom: 80px;
                min-height: auto;
                text-align: center;
            }
            .hero-subtitle { max-width: 100%; margin-left: auto; margin-right: auto; }
            .hero-actions { justify-content: center; }
            .hero-portal-container { margin-top: 48px; justify-content: center; }
            #cta-section { padding: 64px 24px; border-radius: 24px; }
        }
        @media (max-width: 767px) {
            .hero-title { font-size: 1.9rem; }
            .stat-number { font-size: 1.8rem; }
        }

        /* ══════════════════════════════════════════════════════════
           SMARTPHONE VIDEO MOCKUP FRAME
           ══════════════════════════════════════════════════════════ */
        .phone-mockup-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 8px 0;
            width: 100%;
        }
        .phone-mockup-device {
            position: relative;
            width: 220px;
            height: 420px;
            background: #090d16;
            border-radius: 36px;
            border: 8px solid #1e293b;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.65), 0 0 0 2px rgba(255, 255, 255, 0.12), inset 0 0 12px rgba(0, 0, 0, 0.9);
            overflow: hidden;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.4s ease;
            margin: 0 auto;
        }
        .phone-mockup-device:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 30px 65px rgba(0, 0, 0, 0.75), 0 0 25px rgba(99, 102, 241, 0.3);
        }
        .phone-notch-bar {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 90px;
            height: 18px;
            background: #0f172a;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            z-index: 15;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .phone-notch-bar .speaker-slot {
            width: 28px;
            height: 3px;
            background: #334155;
            border-radius: 3px;
        }
        .phone-notch-bar .camera-lens {
            width: 7px;
            height: 7px;
            background: #020617;
            border-radius: 50%;
            border: 1px solid #1e293b;
        }
        .phone-screen-area {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #000;
        }
        .phone-screen-video-el {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .phone-screen-notice {
            position: absolute;
            top: 26px;
            left: 8px;
            right: 8px;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1.5px solid rgba(245, 158, 11, 0.5);
            border-radius: 14px;
            padding: 9px 11px;
            z-index: 12;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }
        .phone-screen-bottom-bar {
            position: absolute;
            bottom: 6px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 4px;
            z-index: 14;
        }

        /* ══════════════════════════════════════════════════════════
           LOCATION MODAL MAP ENHANCEMENTS
           ══════════════════════════════════════════════════════════ */
        .modal-content {
            border: none !important;
            border-radius: var(--radius-xl) !important;
            overflow: hidden;
            box-shadow: var(--shadow-xl) !important;
        }
        .modal-header {
            background: var(--grad-primary);
            color: #fff;
            border: none;
            padding: 20px 24px;
        }
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: .7;
        }
        .modal-header .btn-close:hover { opacity: 1; }
        .modal-title { font-weight: 800; font-size: 1rem; }
        .modal-body { padding: 0; }
            border: 1px solid rgba(79, 70, 229, 0.15);
            border-radius: 24px;
            z-index: 1;
        }
        .about-img-new {
            position: relative;
            z-index: 2;
            transition: transform 0.4s var(--ease), box-shadow 0.4s var(--ease);
            border: 4px solid #ffffff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06) !important;
            border-radius: 24px;
        }
        .about-img-container:hover .about-img-new {
            transform: translateY(-5px) scale(1.01);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1) !important;
        }

        /* Header Style */
        .about-header-modern {
            position: relative;
            font-weight: 800;
            color: var(--navy-900) !important;
            letter-spacing: -0.5px;
        }

        /* Clean Feature Card */
        .about-feature-card {
            background: var(--surface);
            border: 1px solid rgba(15, 23, 42, 0.06);
            border-radius: 16px;
            padding: 1.25rem;
            display: flex;
            align-items: start;
            gap: 1rem;
            transition: all 0.3s var(--ease);
            box-shadow: 0 2px 8px rgba(0,0,0,0.01);
        }
        .about-feature-card:hover {
            transform: translateY(-3px);
            border-color: rgba(79, 70, 229, 0.15);
            box-shadow: var(--shadow-md);
        }

        /* Feature Icon Wrappers */
        .about-feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
            transition: transform 0.3s var(--ease);
        }
        .about-feature-card:hover .about-feature-icon {
            transform: scale(1.1);
        }

        /* Colors matched to existing variables */
        .about-feature-icon.icon-blue {
            background: rgba(79, 70, 229, 0.08);
            color: var(--indigo);
        }
        .about-feature-icon.icon-green {
            background: rgba(13, 148, 136, 0.08);
            color: var(--teal);
        }
        .about-feature-icon.icon-teal {
            background: rgba(45, 212, 191, 0.12);
            color: #0ea5e9;
        }
        .about-feature-icon.icon-red {
            background: rgba(244, 63, 94, 0.08);
            color: var(--rose);
        }

        /* Card typography */
        .about-feature-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--navy-900);
            margin-bottom: 4px;
        }
        .about-feature-desc {
            font-size: 0.8rem;
            color: var(--navy-600);
            line-height: 1.4;
            margin: 0;
        }
    </style>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
</head>
<body>

    <!-- ══════════════════════════════════════════════════════
         NAVBAR
    ══════════════════════════════════════════════════════ -->
    <nav class="navbar navbar-expand-lg sticky-top bg-white shadow-sm p-0" id="main-nav" style="z-index: 1030;">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 py-0" href="#home-section">
                <div class="nav-logo-icon-wrapper" style="width: 50px; height: 50px; flex-shrink: 0; background: #fff; padding: 0;">
                    <?= $als_logo_svg ?>
                </div>
                <div class="py-2" style="font-size: 1.15rem;">
                    <strong class="d-block lh-1">ALS Culaba</strong>
                    <span class="d-none d-sm-block text-secondary mt-1" style="font-size: 0.95rem; font-weight: 500;"><?= __("District Enrollment System") ?></span>
                </div>
            </a>
            <button class="navbar-toggler border-0 p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon" style="transform: scale(0.85);"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-0" style="font-size: 0.85rem; font-weight: 600;">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home-section"><?= __("Home") ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about-als"><?= __("About ALS") ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#programs"><?= __("Programs") ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#als-centers"><?= __("Centers") ?></a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#faq-section"><?= __("FAQs") ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#enrollment-process"><?= __("Process") ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact-section"><?= __("Contact") ?></a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-3 py-2">
                    <!-- Language Selector -->
                    <div class="dropdown">
                        <a href="#" class="text-decoration-none text-navy d-flex align-items-center gap-1 dropdown-toggle" id="langDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.85rem; font-weight: 600;">
                            <i class="bi bi-globe"></i>
                            <?php if ($is_lang_selected): ?>
                                <?= $lang_flags[$lang] ?> <?= $lang_names[$lang] ?>
                            <?php else: ?>
                                <?= __("Wika") ?>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3 p-2" aria-labelledby="langDropdown">
                            <li><a class="dropdown-item rounded-2 py-2 d-flex align-items-center justify-content-between <?= $lang === 'en' ? 'active bg-primary text-white' : '' ?>" href="?lang=en">English <span>🇬🇧</span></a></li>
                            <li><a class="dropdown-item rounded-2 py-2 d-flex align-items-center justify-content-between <?= $lang === 'fil' ? 'active bg-primary text-white' : '' ?>" href="?lang=fil">Filipino <span>🇵🇭</span></a></li>
                            <li><a class="dropdown-item rounded-2 py-2 d-flex align-items-center justify-content-between <?= $lang === 'bis' ? 'active bg-primary text-white' : '' ?>" href="?lang=bis">Bisaya <span>🇵🇭</span></a></li>
                            <li><a class="dropdown-item rounded-2 py-2 d-flex align-items-center justify-content-between <?= $lang === 'war' ? 'active bg-primary text-white' : '' ?>" href="?lang=war">Waray <span>🇵🇭</span></a></li>
                        </ul>
                    </div>

                    <button type="button" id="navbar-login-btn" onclick="openLearnerLogin()" class="btn btn-primary px-3 py-1 text-white" style="font-size: 0.8rem; border-radius: 20px; font-weight: 700; border: none; cursor: pointer; display:inline-flex; align-items:center; gap:6px; transition: all .2s;">
                        <i class="bi bi-box-arrow-in-right"></i> <?= __("Login") ?>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- ══════════════════════════════════════════════════════
         HERO SECTION
    ══════════════════════════════════════════════════════ -->
    <section id="home-section">
        <div class="hero-mesh" style="z-index: 2; opacity: 0.25;"></div>
        <div class="hero-grid" style="z-index: 2; opacity: 0.10;"></div>
        <div class="hero-orb hero-orb-1" style="z-index: 2; opacity: 0.35;"></div>
        <div class="hero-orb hero-orb-2" style="z-index: 2; opacity: 0.35;"></div>
        <div class="hero-orb hero-orb-3" style="z-index: 2; opacity: 0.30;"></div>
        
        <div class="container">
            <div class="row align-items-center">
                <!-- Text Content -->
                <div class="col-lg-6" data-aos="fade-right">

                    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap flex-sm-nowrap">
                        <div class="als-hero-logo-wrapper" style="position: relative; width: 100px; height: 100px; flex-shrink: 0;">
                            <div class="logo-placeholder" data-slot="home" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1;"></div>
                            <div id="flying-logo" style="position: absolute; top: 0; left: 0; width: 100px; height: 100px; z-index: 2;">
                                <?= $als_logo_svg ?>
                            </div>
                        </div>
                        <h1 class="lh-sm mb-0" style="font-size: 1.85rem; font-weight: 900; color: #fff;">
                            <?= __("Alternative Learning System (ALS)") ?><br>
                            <span class="grad-text-accent" id="typeTarget"></span><span class="type-cursor"></span>
                        </h1>
                    </div>
                    <h5 class="fw-semibold mb-3 lh-base fs-6" style="color: rgba(255,255,255,.6);">
                        <?= __("\"Mag-aral Muli. Abutin ang Pangarap. Baguhin ang Kinabukasan sa Culaba.\"") ?>
                    </h5>
                    <p style="color: rgba(255,255,255,.65); margin-bottom: 1.5rem; font-size: .97rem; line-height: 1.75;">
                        <?= __("Ang Alternative Learning System (ALS) ay isang programang pang-edukasyon ng pamahalaan para sa mga Pilipinong hindi nakapagtapos ng pormal na pag-aaral. Sa pamamagitan ng ALS Culaba District, maaari kang makapagpatuloy ng pag-aaral at magkaroon ng pagkakataong makamit ang iyong mga pangarap sa buhay.") ?>
                    </p>
                    <div class="d-flex flex-wrap align-items-center gap-3 hero-actions">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-chevron-double-right text-white fs-4 pointing-arrow-left"></i>
                            <a href="javascript:void(0)" id="hero-apply-btn" class="btn-primary-custom btn-pulse-glow position-relative" onclick="openModal()">
                                <i class="bi bg-transparent bi-arrow-right-circle-fill fs-5"></i> <?= __("Mag Apply Ngayon") ?>
                            </a>
                            <i class="bi bi-chevron-double-left text-white fs-4 pointing-arrow-right"></i>
                        </div>
                        <a href="#about-als" class="btn-secondary-custom">
                            <i class="bi bi-info-circle-fill"></i> <?= __("Alamin Pa") ?>
                        </a>
                    </div>
                </div>

                <div class="col-lg-6" data-aos="fade-left">
                    <div class="hero-portal-container">
                        <!-- Scene Video Frame - Replaced Image with Video -->
                        <div class="hero-image-frame hover-lift">
                            <video autoplay muted loop playsinline class="d-block w-100 h-100">
                                <source src="assets/animation_wheel.webm" type="video/webm">
                            </video>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════
         IMPACT STATS STRIP
    ══════════════════════════════════════════════════════ -->
    <section id="stats-strip">
        <div class="container">
            <div class="stats-panel" data-aos="fade-up">
                <div class="row g-0">
                    <div class="col-6 col-md-3 stat-cell">
                        <span class="stat-number" data-count="10">0</span>
                        <span class="stat-label"><?= __("ALS Learning Centers") ?></span>
                    </div>
                    <div class="col-6 col-md-3 stat-cell">
                        <span class="stat-number" data-count="350">0</span>
                        <span class="stat-label"><?= __("Aktibong Mag-aaral") ?></span>
                    </div>
                    <div class="col-6 col-md-3 stat-cell">
                        <span class="stat-number" data-count="92" data-suffix="%">0%</span>
                        <span class="stat-label"><?= __("Passing Rate sa A&E Test") ?></span>
                    </div>
                    <div class="col-6 col-md-3 stat-cell">
                        <span class="stat-number" data-count="100" data-suffix="%">0%</span>
                        <span class="stat-label"><?= __("Libre at Walang Bayad") ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════
         SECTION 1: ABOUT ALS — Redesigned
    ══════════════════════════════════════════════════════ -->
    <section id="about-als" class="py-5 position-relative">
        <div class="container py-5 position-relative" style="z-index:1;">
            <div class="row align-items-center g-5">
                <!-- Visual Card — Left -->
                <div class="col-lg-5" data-aos="fade-right">
                    <div class="about-img-container">
                        <div class="about-img-back-frame"></div>
                        <img src="assets/als_classroom_payag.jpg" alt="Klase sa ALS" class="img-fluid w-100 about-img-new" style="border-radius:22px;object-fit:cover;height:400px;">
                    </div>
                </div>

                <!-- Text Content — Right -->
                <div class="col-lg-7" data-aos="fade-left">
                    <!-- Logo + badge row -->
                    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
                        <div class="logo-placeholder" data-slot="about"></div>
                        <div class="section-badge-animated" style="background:rgba(13,148,136,.08);color:var(--teal);margin-bottom:0;">
                            <?= __("Alamin Natin") ?>
                        </div>
                    </div>

                    <h2 class="section-title-xl mb-3 about-header-modern"><?= __("Ano ang") ?> <span class="grad-text"><?= __("Alternative Learning System?") ?></span></h2>
                    
                    <div class="about-explanation-text text-start text-secondary" style="font-size: 1.02rem; line-height: 1.85; font-weight: 500;">
                        <p class="mb-3">
                            <?= __("Ang Alternative Learning System (ALS) ay isang paralelong sistema ng edukasyon sa Pilipinas na nagbibigay ng pagkakataon sa mga hindi nakakapag-aral o hindi nakatapos sa pormal na paaralan. Layunin nitong buksan ang pintuan ng karunungan para sa bawat Pilipino, anuman ang kanilang katayuan o edad sa buhay.") ?>
                        </p>
                        <p class="mb-3">
                            <?= __("Idinisenyo ang programang ito upang maging bukas at madaling maabot ng mga out-of-school youth, mga nagtatrabahong mamamayan, mga may-ari ng pamilya, at mga nakatatandang nagnanais pa ring matuto at makakuha ng pormal na sertipikasyon.") ?>
                        </p>
                        <p class="mb-0">
                            <?= __("Sa pamamagitan ng mga modyul at gabay ng mga dedikadong Mobile Teachers sa iba't ibang Learning Centers, ang mga mag-aaral ay ginagabayan upang maging handa sa Accreditation and Equivalency (A&E) Test. Ang pagpasa sa pagsusulit na ito ay nagbibigay ng diploma na katumbas ng pormal na Elementary o High School graduation, na magagamit sa trabaho, TESDA, o kolehiyo.") ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="section-divider"></div>
    </section>

    <!-- ══════════════════════════════════════════════════════
         SECTION 1.5: ALS PROGRAMS SHOWCASE — Redesigned
    ══════════════════════════════════════════════════════ -->
    <section id="programs" class="py-5 position-relative">
        <div class="container py-5 position-relative" style="z-index:1;">
            <div class="text-center mb-5" data-aos="fade-up">
                <div class="d-flex justify-content-center align-items-center mb-3">
                    <div class="logo-placeholder" data-slot="programs"></div>
                </div>
                <div class="section-badge-animated" style="background:rgba(79,70,229,.1);color:var(--indigo);">
                    <?= __("Mga Antas / Programa") ?>
                </div>
                <h2 class="section-title-xl mb-3"><?= __("Mga Programang Pang-edukasyon") ?> <span class="underline-word grad-text">ng ALS</span></h2>
                <p class="text-secondary mx-auto" style="max-width:580px;font-size:.95rem;line-height:1.7;"><?= __("Piliin ang programang nababagay sa iyong antas at kakayahan upang simulan ang iyong bagong bukas.") ?></p>
            </div>

            <div class="row g-4">
                <!-- Program 1: BLP -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="program-card pc-blue">
                        <div class="program-card-inner d-flex flex-column">
                            <div class="program-emoji-badge"><i class="bi bi-book"></i></div>
                            <div class="program-level-pill"><i class="bi bi-circle-fill" style="font-size:.5rem;"></i> <?= __("Antas 1") ?></div>
                            <h4><?= __("Basic Literacy Program") ?></h4>
                            <p><?= __("Para sa mga hindi pa nakakabasa at nakakasulat. Matuto ng pangunahing pagbasa, pagsulat, at simpleng matematika.") ?></p>
                            <ul class="program-learn-list mt-auto">
                                <li><span class="check-dot"><i class="bi bi-check-lg"></i></span><?= __("Pagbasa at Pagsulat") ?></li>
                                <li><span class="check-dot"><i class="bi bi-check-lg"></i></span><?= __("Basic Numeracy") ?></li>
                                <li><span class="check-dot"><i class="bi bi-check-lg"></i></span><?= __("Pakikipagtalastasan") ?></li>
                            </ul>
                            <div class="program-reward-box">
                                <span class="program-reward-icon"><i class="bi bi-award text-primary"></i></span>
                                <p><?= __("Certificate of Completion — Elementary Level Ready") ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Program 2: Elementary A&E -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="program-card pc-green">
                        <div class="program-card-inner d-flex flex-column">
                            <div class="program-emoji-badge"><i class="bi bi-mortarboard"></i></div>
                            <div class="program-level-pill"><i class="bi bi-circle-fill" style="font-size:.5rem;"></i> <?= __("Antas 2") ?></div>
                            <h4><?= __("Elementary A&E Program") ?></h4>
                            <p><?= __("Para sa mga gustong makuha ang katibayan ng pagtatapos ng Elementarya. Bukas sa edad 15 pataas.") ?></p>
                            <ul class="program-learn-list mt-auto">
                                <li><span class="check-dot"><i class="bi bi-check-lg"></i></span><?= __("5 Learning Strands") ?></li>
                                <li><span class="check-dot"><i class="bi bi-check-lg"></i></span><?= __("Life & Career Skills") ?></li>
                                <li><span class="check-dot"><i class="bi bi-check-lg"></i></span><?= __("Understanding Self & Society") ?></li>
                            </ul>
                            <div class="program-reward-box">
                                <span class="program-reward-icon"><i class="bi bi-trophy text-success"></i></span>
                                <p><?= __("Elementary Certificate — Katumbas ng Grade 6 Diploma") ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Program 3: Secondary A&E -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="program-card pc-violet">
                        <div class="program-card-inner d-flex flex-column">
                            <div class="program-emoji-badge"><i class="bi bi-star"></i></div>
                            <div class="program-level-pill"><i class="bi bi-circle-fill" style="font-size:.5rem;"></i> <?= __("Antas 3") ?></div>
                            <h4><?= __("Secondary A&E Program") ?></h4>
                            <p><?= __("Para sa mga gustong magkolehiyo o magkaroon ng pormal na trabaho. High School level ang katumbas.") ?></p>
                            <ul class="program-learn-list mt-auto">
                                <li><span class="check-dot"><i class="bi bi-check-lg"></i></span><?= __("English & Filipino") ?></li>
                                <li><span class="check-dot"><i class="bi bi-check-lg"></i></span><?= __("Math & Science") ?></li>
                                <li><span class="check-dot"><i class="bi bi-check-lg"></i></span><?= __("World Vision & Values") ?></li>
                            </ul>
                            <div class="program-reward-box">
                                <span class="program-reward-icon"><i class="bi bi-patch-check text-indigo"></i></span>
                                <p><?= __("High School Certificate — Para sa Kolehiyo, TESDA, o Trabaho") ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="section-divider"></div>
    </section>

    <!-- ══════════════════════════════════════════════════════
         SECTION 1.6: MUNICIPALITIES OF BILIRAN PROVINCE
    ══════════════════════════════════════════════════════ -->
    <section id="als-centers" class="py-5 bg-white">
        <div class="container py-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-5" data-aos="fade-right">
                    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                        <div class="logo-placeholder" data-slot="als-centers"></div>
                        <div class="section-tag section-tag-green mb-0">
                            <i class="bi bi-geo-alt-fill"></i> <?= __("Mga Sakop nga Lungsod") ?>
                        </div>
                    </div>
                    <h2 class="display-6 fw-bold mb-3 text-navy">
                        <?= __("Mga Munisipyo sa Buong Probinsya ng Biliran") ?>
                    </h2>
                    <p class="text-secondary mb-4 fs-6 lh-lg">
                        <?= __("Naglilingkod ang ALS MIS sa lahat ng walo (8) na munisipyo sa buong Probinsya ng Biliran upang magbigay ng pantay at maningning na oportunidad sa edukasyon para sa bawat ALS learner.") ?>
                    </p>
                    <button type="button" class="btn-secondary-custom d-flex align-items-center gap-2" onclick="requestUserLocationBeforeMap(null, this)" style="border:none;">
                        <i class="bi bi-pin-map-fill"></i> <?= __("Hanapin ang Pinakamalapit na Sentro") ?>
                    </button>
                </div>
                <div class="col-lg-7" data-aos="fade-left">
                    <div class="row g-3">
                        <?php foreach ($biliran_municipalities as $muni): ?>
                        <div class="col-md-6">
                            <div class="center-card d-flex align-items-center gap-3 p-3 bg-white rounded-4 border shadow-sm h-100" 
                                 style="cursor: pointer; transition: all 0.25s ease;" 
                                 onclick="requestUserLocationBeforeMap(<?= $muni['school_idx'] ?>)"
                                 onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 24px rgba(37,99,235,0.12)';this.style.borderColor='#93c5fd';"
                                 onmouseout="this.style.transform='none';this.style.boxShadow='none';this.style.borderColor='#e2e8f0';"
                            >
                                <div style="width: 42px; height: 42px; min-width: 42px; border-radius: 12px; background: linear-gradient(135deg, rgba(37,99,235,0.12) 0%, rgba(124,58,237,0.12) 100%); color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                                    <i class="bi bi-geo-alt-fill"></i>
                                </div>
                                <div class="w-100">
                                    <h6 class="fw-bold text-navy mb-1" style="font-size: 0.92rem; line-height: 1.25;"><?= htmlspecialchars($muni['name']) ?></h6>
                                    <small class="text-secondary d-block" style="font-size: 0.76rem; font-weight: 500; color: #64748b;"><?= htmlspecialchars($muni['desc']) ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════
         SECTION 5: ENROLLMENT PROCESS & REQUIRED DOCUMENTS — Combined
    ══════════════════════════════════════════════════════ -->
    <section id="enrollment-process" class="py-5 position-relative">
        <div class="container py-5 position-relative" style="z-index:1;">
            
            <!-- Part A: Required Documents to Upload -->
            <div class="text-center mb-5" data-aos="fade-up">
                <div class="d-flex justify-content-center align-items-center mb-3">
                    <div class="logo-placeholder" data-slot="enrollment-process"></div>
                </div>
                <div class="section-badge-animated" style="background:rgba(124,58,237,.1);color:#7c3aed;">
                    <?= __("Mga Kakailanganin") ?>
                </div>
                <h3 class="section-title-xl mb-3"><?= __("Mga Dokumento na") ?> <span class="underline-word" style="background:linear-gradient(90deg,#7c3aed,var(--teal));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;"><?= __("Dapat i-Upload") ?></span></h3>
                <p class="text-secondary mx-auto" style="max-width:580px;font-size:.95rem;line-height:1.7;"><?= __("Ihanda at i-upload ang malilinaw na kopya ng mga sumusunod na dokumento bago punan ang application form.") ?></p>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="requirement-card">
                        <span class="req-num-badge">01</span>
                        <span class="req-emoji"><i class="bi bi-file-earmark-text"></i></span>
                        <h6 class="fw-bold mb-2" style="color:var(--navy-900);"><?= __("Birth Certificate") ?></h6>
                        <p class="text-secondary small mb-0" style="line-height:1.6;"><?= __("Orihinal o sertipikadong kopya mula sa PSA o lokal na civil registrar.") ?></p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="requirement-card req-green">
                        <span class="req-num-badge">02</span>
                        <span class="req-emoji"><i class="bi bi-person-vcard"></i></span>
                        <h6 class="fw-bold mb-2" style="color:var(--navy-900);"><?= __("Valid ID o Barangay Certificate") ?></h6>
                        <p class="text-secondary small mb-0" style="line-height:1.6;"><?= __("Anumang government-issued ID o sertipiko mula sa iyong barangay.") ?></p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="requirement-card req-violet">
                        <span class="req-num-badge">03</span>
                        <span class="req-emoji"><i class="bi bi-person-bounding-box"></i></span>
                        <h6 class="fw-bold mb-2" style="color:var(--navy-900);"><?= __("2x2 ID Picture") ?></h6>
                        <p class="text-secondary small mb-0" style="line-height:1.6;"><?= __("Dalawang (2) kopya na kuha sa nakaraang anim na buwan.") ?></p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
                    <div class="requirement-card req-amber">
                        <span class="req-num-badge">04</span>
                        <span class="req-emoji"><i class="bi bi-card-text"></i></span>
                        <h6 class="fw-bold mb-2" style="color:var(--navy-900);"><?= __("Form 137 (kung meron)") ?></h6>
                        <p class="text-secondary small mb-0" style="line-height:1.6;"><?= __("Kung dati kang nag-aral, dalhin ang iyong huling school record.") ?></p>
                    </div>
                </div>
            </div>

            <!-- Part B: Enrollment Steps -->
            <div class="text-center mt-5 pt-5 mb-5" data-aos="fade-up">
                <div class="section-badge-animated" style="background:rgba(13,148,136,.1);color:var(--teal);">
                    <span>🗺️</span> <?= __("Enrollment Steps") ?>
                </div>
                <h2 class="section-title-xl mb-3"><?= __("Proseso ng") ?> <span class="underline-word grad-text-accent"><?= __("Pag-enroll") ?></span></h2>
                <p class="text-secondary mx-auto" style="max-width:580px;font-size:.95rem;line-height:1.7;"><?= __("Napakadali at simpleng paraan upang simulan ang iyong pag-aaral sa ALS. 5 hakbang lang!") ?></p>
            </div>

            <div class="step-timeline d-lg-flex gap-lg-0 gap-4">
                <!-- Step 1 -->
                <div class="step-timeline-item" data-aos="fade-up" data-aos-delay="100" onclick="openModal()" style="cursor:pointer;">
                    <div class="step-badge-new"><i class="bi bi-mouse"></i></div>
                    <div class="step-card-new">
                        <h6 class="fw-bold mb-2" style="color:var(--navy-900);font-size:.9rem;"><?= __("Pindutin ang \"Mag Apply\"") ?></h6>
                        <p class="text-secondary mb-0" style="font-size:.8rem;line-height:1.6;"><?= __("I-click ang Apply Now button sa website.") ?></p>
                    </div>
                </div>
                <!-- Step 2 -->
                <div class="step-timeline-item" data-aos="fade-up" data-aos-delay="200" onclick="openModal()" style="cursor:pointer;">
                    <div class="step-badge-new s-teal"><i class="bi bi-pencil-square"></i></div>
                    <div class="step-card-new">
                        <h6 class="fw-bold mb-2" style="color:var(--navy-900);font-size:.9rem;"><?= __("Punan ang Form") ?></h6>
                        <p class="text-secondary mb-0" style="font-size:.8rem;line-height:1.6;"><?= __("Ilagay ang iyong kumpletong impormasyon.") ?></p>
                    </div>
                </div>
                <!-- Step 3 -->
                <div class="step-timeline-item" data-aos="fade-up" data-aos-delay="300" onclick="openModal()" style="cursor:pointer;">
                    <div class="step-badge-new s-violet"><i class="bi bi-send-fill"></i></div>
                    <div class="step-card-new">
                        <h6 class="fw-bold mb-2" style="color:var(--navy-900);font-size:.9rem;"><?= __("I-submit ang Application") ?></h6>
                        <p class="text-secondary mb-0" style="font-size:.8rem;line-height:1.6;"><?= __("Suriing mabuti bago ipadala sa amin.") ?></p>
                    </div>
                </div>
                <!-- Step 4 -->
                <div class="step-timeline-item" data-aos="fade-up" data-aos-delay="400" onclick="openModal()" style="cursor:pointer;">
                    <div class="step-badge-new s-amber"><i class="bi bi-envelope-paper-fill"></i></div>
                    <div class="step-card-new border-warning">
                        <h6 class="fw-bold mb-2" style="color:var(--navy-900);font-size:.9rem;"><?= __("Tanggapin ang Email Credentials") ?></h6>
                        <p class="text-secondary mb-0" style="font-size:.8rem;line-height:1.6;"><?= __("Hintayin ang email message para sa iyong username at password.") ?></p>
                    </div>
                </div>
                <!-- Step 5 -->
                <div class="step-timeline-item" data-aos="fade-up" data-aos-delay="500" onclick="openModal()" style="cursor:pointer;">
                    <div class="step-badge-new s-rose"><i class="bi bi-box-arrow-in-right"></i></div>
                    <div class="step-card-new">
                        <h6 class="fw-bold mb-2" style="color:var(--navy-900);font-size:.9rem;"><?= __("I-login at Simulan ang ALS Journey!") ?></h6>
                        <p class="text-secondary mb-0" style="font-size:.8rem;line-height:1.6;"><?= __("Gamitin ang credentials sa email para maka-login sa portal.") ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- ══════════════════════════════════════════════════════
         SECTION 3: WHY CHOOSE ALS? — Dark Redesigned
    ══════════════════════════════════════════════════════ -->
    <section id="why-choose-als" class="py-5 position-relative">
        <div class="container py-5 position-relative" style="z-index:1;">
            <!-- Header -->
            <div class="text-center mb-5" data-aos="fade-up">
                <div class="d-flex justify-content-center align-items-center gap-3 mb-3">
                    <div class="logo-placeholder" data-slot="why-choose-als"></div>
                </div>
                <div class="section-badge-animated mb-3" style="background:rgba(79,70,229,.08);color:var(--indigo);border:1.5px solid rgba(79,70,229,.14);">
                    <?= __("Bakit Kami?") ?>
                </div>
                <h2 class="section-title-xl mb-3" style="color:var(--navy-900);"><?= __("Bakit Dapat Piliin ang") ?> <span style="background:linear-gradient(90deg,var(--teal),var(--indigo-light));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;"><?= __("ALS?") ?></span></h2>
                <p style="color:var(--navy-600);max-width:580px;margin:0 auto;font-size:.95rem;line-height:1.7;"><?= __("Maraming dahilan kung bakit ang Alternative Learning System ay ang pinaka-epektibong solusyon para sa mga gustong magpatuloy ng pag-aaral.") ?></p>
            </div>

            <!-- Cards grid -->
            <div class="row g-4">
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="why-card wc-1">
                        <div class="why-icon"><i class="bi bi-wallet2"></i></div>
                        <h5><?= __("100% Libre ang Pag-aaral") ?></h5>
                        <p><?= __("Walang matrikula, walang bayad. Lahat ng modyul at materyales ay ibinibigay ng DepEd nang libre.") ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="why-card wc-2">
                        <div class="why-icon"><i class="bi bi-clock"></i></div>
                        <h5><?= __("Flexible ang Iskedyul") ?></h5>
                        <p><?= __("Malaya kang makapipili ng oras ng pag-aaral batay sa iyong trabaho at pamilya.") ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="why-card wc-3">
                        <div class="why-icon"><i class="bi bi-building"></i></div>
                        <h5><?= __("Opisyal ng DepEd") ?></h5>
                        <p><?= __("Opisyal na programa ng Department of Education (DepEd) — kinikilala ng buong Pilipinas.") ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="why-card wc-4">
                        <div class="why-icon"><i class="bi bi-award"></i></div>
                        <h5><?= __("May Diploma / Certificate") ?></h5>
                        <p><?= __("Makakukuha ng opisyal na sertipikasyon na katumbas ng High School o Elementary diploma.") ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="why-card wc-5">
                        <div class="why-icon"><i class="bi bi-people"></i></div>
                        <h5><?= __("May Suportang Guro") ?></h5>
                        <p><?= __("Hindi ka mag-aaral nang nag-iisa. Ang bawat ALS learner ay may nakatalagang Mobile Teacher.") ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="why-card wc-6">
                        <div class="why-icon"><i class="bi bi-briefcase-fill"></i></div>
                        <h5><?= __("Bukas ang Kolehiyo at Trabaho") ?></h5>
                        <p><?= __("Pagkatapos ng ALS, bukas na ang pintuan ng kolehiyo, TESDA, at mas magandang oportunidad sa trabaho.") ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════


    <!-- ══════════════════════════════════════════════════════
         SECTION 4.5: FAQ — Redesigned
    ══════════════════════════════════════════════════════ -->
    <section id="faq-section" class="py-5 position-relative bg-light">
        <div class="container py-5 position-relative" style="z-index:1;">
            <div class="row align-items-start g-5">
                <!-- Left Side: Header and Title info -->
                <div class="col-lg-4 text-start text-lg-start" data-aos="fade-right" style="position: sticky; top: 120px; z-index: 2;">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="logo-placeholder" data-slot="faq-section"></div>
                        <div class="section-badge-animated" style="background:rgba(79,70,229,.1);color:var(--indigo); margin-bottom: 0;">
                            <?= __("Mga Tanong") ?>
                        </div>
                    </div>
                    <h2 class="section-title-xl mb-3 text-start"><?= __("Madalas na Itanong") ?><br><span class="underline-word grad-text"><?= __("(FAQ)") ?></span></h2>
                    <p class="text-secondary mb-0" style="font-size:.95rem;line-height:1.75;"><?= __("Hindi sigurado kung paano magsimula? Narito ang mga sagot sa mga pinakakaraniwang tanong tungkol sa ALS.") ?></p>
                </div>

                <!-- Right Side: Accordion with Questions and Answers -->
                <div class="col-lg-8" data-aos="fade-left">
                    <div class="accordion faq-accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">
                                    <span class="faq-number">1</span> <?= __("Sino ang maaaring mag-enroll sa ALS?") ?>
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <?= __("Bukas ang ALS para sa mga out-of-school youth at adults na may edad 18 pataas (15 pataas para sa ilang programa) na hindi nakatapos ng Elementarya o Sekondarya sa pormal na paaralan.") ?>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">
                                    <span class="faq-number">2</span> <?= __("Magkano ang babayaran para mag-enroll?") ?>
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <?= __("Walang bayad ang ALS. Ang lahat ng modyul, materyales, at sesyon ng pagtuturo ay 100% libre, sapagkat ito ay programang pinopondohan ng pamahalaan sa pamamagitan ng DepEd.") ?>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">
                                    <span class="faq-number">3</span> <?= __("Gaano katagal ang programa bago matapos?") ?>
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <?= __("Depende ito sa iyong antas at bilis ng pag-aaral. Karaniwan, ang Elementary o Secondary A&E Program ay tinatapos sa loob ng 10 buwan, ngunit may mga learners na mas mabilis o mas matagal ayon sa kanilang sitwasyon.") ?>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false" aria-controls="faq4">
                                    <span class="faq-number">4</span> <?= __("Saan ako pwedeng pumunta para mag-klase?") ?>
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <?= __("Mayroon kaming mga ALS Mobile Center at Community Learning Center sa iba't ibang barangay sa Culaba at karatig na bayan ng Biliran. Maaari kang piliin ang pinakamalapit sa inyo — tingnan ang listahan sa seksyong \"Mga ALS Learning Center\" sa itaas.") ?>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5" aria-expanded="false" aria-controls="faq5">
                                    <span class="faq-number">5</span> <?= __("Kinikilala ba ang sertipiko ng ALS para sa trabaho o kolehiyo?") ?>
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <?= __("Opo. Ang A&E Certificate ay opisyal na kinikilala ng DepEd bilang katumbas ng diploma at tinatanggap ng mga kolehiyo, TESDA, at mga employer sa Pilipinas.") ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    

    <!-- ══════════════════════════════════════════════════════
         SECTION 5.5: VISIT / CONTACT US — Redesigned
    ══════════════════════════════════════════════════════ -->
    <section id="contact-section" class="py-5 position-relative">
        <div class="container py-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-5" data-aos="fade-right">
                    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
                        <div class="logo-placeholder" data-slot="contact-section"></div>
                        <div class="section-badge-animated" style="background:rgba(79,70,229,.1);color:var(--indigo);">
                            <?= __("Makipag-ugnayan") ?>
                        </div>
                    </div>
                    <h2 class="section-title-xl mb-3"><?= __("Bisitahin o") ?> <span class="underline-word grad-text"><?= __("Tawagan Kami") ?></span></h2>
                    <p class="text-secondary mb-4" style="font-size:.95rem;line-height:1.8;">
                        <?= __("May tanong pa? Handa kaming tulungan ka sa bawat hakbang ng iyong pagbalik sa pag-aaral. Bisitahin ang ALS District Office o padalhan kami ng mensahe.") ?>
                    </p>

                    <div class="contact-info-card">
                        <div class="contact-icon-wrap ci-blue"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1" style="color:var(--navy-900);"><?= __("ALS Culaba District Office") ?></h6>
                            <p class="text-secondary small mb-0"><?= __("Brgy. Centro, Culaba, Biliran, Philippines") ?></p>
                        </div>
                    </div>
                    <div class="contact-info-card">
                        <div class="contact-icon-wrap ci-green"><i class="bi bi-telephone-fill"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1" style="color:var(--navy-900);"><?= __("Tumawag sa Amin") ?></h6>
                            <p class="text-secondary small mb-0">(053) 500-0000</p>
                        </div>
                    </div>
                    <div class="contact-info-card">
                        <div class="contact-icon-wrap ci-amber"><i class="bi bi-envelope-fill"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1" style="color:var(--navy-900);"><?= __("Mag-email sa Amin") ?></h6>
                            <p class="text-secondary small mb-0">als.culaba@deped.gov.ph</p>
                        </div>
                    </div>
                    <div class="contact-info-card">
                        <div class="contact-icon-wrap ci-rose"><i class="bi bi-clock-fill"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1" style="color:var(--navy-900);"><?= __("Oras ng Opisina") ?></h6>
                            <p class="text-secondary small mb-0"><?= __("Lunes – Biyernes, 8:00 AM – 5:00 PM") ?></p>
                        </div>
                    </div>
                </div>

                <!-- Image side -->
                <div class="col-lg-7" data-aos="fade-left">
                    <div class="position-relative">
                        <!-- Main image frame -->
                        <div style="border-radius:28px;overflow:hidden;box-shadow:0 24px 70px rgba(0,0,0,.14);border:4px solid #fff;aspect-ratio:4/3;">
                            <img src="assets/img/schools/culaba_school_sign_1782813878841.png"
                                 class="d-block w-100 h-100"
                                 alt="ALS Culaba Main Center"
                                 style="object-fit:cover;object-position:center;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════
         SECTION 6: CALL TO ACTION (CTA) — Replica of Mockup
    ══════════════════════════════════════════════════════ -->
    <section class="py-5" style="background: transparent;">
        <div class="container" data-aos="zoom-in">
            <div id="cta-section" style="background: #ffffff; border-radius: 32px; padding: 48px; box-shadow: 0 20px 60px rgba(15,23,42,0.06); border: 1px solid rgba(15,23,42,0.05); text-align: left;">
                <div class="row align-items-center g-4 g-lg-5">
                    <!-- Left Side Content -->
                    <div class="col-lg-6">
                        <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill mb-3" style="background: rgba(99, 102, 241, 0.09); color: #4f46e5; font-size: 0.78rem; font-weight: 800; letter-spacing: 0.5px;">
                            <i class="bi bi-mortarboard-fill"></i> <?= __("START YOUR JOURNEY") ?>
                        </div>
                        <h2 class="display-5 text-navy mb-3" style="font-weight: 850; line-height: 1.15; letter-spacing: -0.5px;">
                            <?= __("Are You Ready to") ?><br>
                            <?= __("Start Your") ?> <span style="color: #6366f1; position: relative;"><?= __("Dream?") ?></span>
                        </h2>
                        <div class="d-flex align-items-center gap-2 mb-4" style="width: 140px;">
                            <div style="height: 2px; background: rgba(99, 102, 241, 0.2); flex-grow: 1;"></div>
                            <i class="bi bi-diamond-fill" style="color: #6366f1; font-size: 8px;"></i>
                            <div style="height: 2px; background: rgba(99, 102, 241, 0.2); width: 30px;"></div>
                        </div>
                        <p class="text-secondary mb-4" style="font-size: 0.95rem; line-height: 1.7; max-width: 480px;">
                            <?= __("Enroll today and be part of the Alternative Learning System. Free, flexible, and designed for you!") ?>
                        </p>
                        
                        <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
                            <a href="javascript:void(0)" class="btn text-white fw-bold px-4 py-3 rounded-pill shadow-sm d-inline-flex align-items-center gap-2 hover-lift" onclick="openModal()" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); font-size: 0.92rem; border: none; box-shadow: 0 10px 25px rgba(99, 102, 241, 0.35) !important;">
                                <i class="bi bi-send-fill" style="transform: rotate(-30deg); display: inline-block;"></i> <?= __("Apply Now") ?>
                            </a>
                            <a href="javascript:void(0)" onclick="openModal()" class="btn fw-bold px-4 py-3 rounded-pill d-inline-flex align-items-center gap-2 hover-lift" style="border: 1.5px solid rgba(99, 102, 241, 0.4); color: #4f46e5; font-size: 0.92rem; background: #ffffff;">
                                <i class="bi bi-journal-text"></i> <?= __("How to Apply") ?>
                            </a>
                        </div>

                        <div class="d-flex align-items-center gap-3 pt-2 flex-wrap">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: rgba(99, 102, 241, 0.08); color: #6366f1;">
                                    <i class="bi bi-lightning-charge-fill fs-6"></i>
                                </div>
                                <div>
                                    <div class="fw-extrabold text-navy" style="font-size: 0.82rem; line-height: 1.1; font-weight: 800;"><?= __("Fast") ?></div>
                                    <div class="text-secondary" style="font-size: 0.72rem;"><?= __("Quick & Easy") ?></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: rgba(99, 102, 241, 0.08); color: #6366f1;">
                                    <i class="bi bi-shield-check-fill fs-6"></i>
                                </div>
                                <div>
                                    <div class="fw-extrabold text-navy" style="font-size: 0.82rem; line-height: 1.1; font-weight: 800;"><?= __("Secure") ?></div>
                                    <div class="text-secondary" style="font-size: 0.72rem;"><?= __("Your Data is Safe") ?></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: rgba(99, 102, 241, 0.08); color: #6366f1;">
                                    <i class="bi bi-emoji-smile-fill fs-6"></i>
                                </div>
                                <div>
                                    <div class="fw-extrabold text-navy" style="font-size: 0.82rem; line-height: 1.1; font-weight: 800;"><?= __("Simple") ?></div>
                                    <div class="text-secondary" style="font-size: 0.72rem;"><?= __("User Friendly") ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side Image -->
                    <div class="col-lg-6">
                        <div class="position-relative overflow-hidden shadow-lg hover-lift" style="border-radius: 28px; border: 4px solid #ffffff; height: 380px; width: 100%;">
                            <img src="assets/Alternative_Learnning_system.png" alt="ALS Learning Center Building" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════
         FOOTER — Replica of Mockup
    ══════════════════════════════════════════════════════ -->
    <footer class="py-5 text-white-50" style="background: #09122b; border-top: 3px solid #6366f1;">
        <div class="container py-4">
            <div class="row g-4 mb-4">
                <!-- Col 1: Logo & Socials -->
                <div class="col-lg-4">
                    <a href="#home-section" class="d-flex align-items-center gap-2 mb-3 text-white text-decoration-none">
                        <div class="nav-logo-icon-wrapper" style="width: 44px; height: 44px; flex-shrink: 0; background: #fff; border-radius: 50%; padding: 2px;">
                            <?= $als_logo_svg ?>
                        </div>
                        <div>
                            <span class="fw-bold fs-5 d-block lh-1 text-white">ALS Culaba</span>
                            <span style="font-size:.72rem; color:rgba(255,255,255,.5);"><?= __("District Enrollment System") ?></span>
                        </div>
                    </a>
                    <p class="small mb-4 text-white-50" style="line-height: 1.7; max-width: 320px;">
                        <?= __("Empowering communities through accessible and inclusive education for out-of-school youth and adults in Culaba, Biliran.") ?>
                    </p>
                    <div class="d-flex gap-2">
                        <a href="#" class="d-flex align-items-center justify-content-center text-white" style="width:36px; height:36px; border-radius:50%; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); transition:all .25s ease;" onmouseover="this.style.background='#6366f1';" onmouseout="this.style.background='rgba(255,255,255,.08)';"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="d-flex align-items-center justify-content-center text-white" style="width:36px; height:36px; border-radius:50%; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); transition:all .25s ease;" onmouseover="this.style.background='#6366f1';" onmouseout="this.style.background='rgba(255,255,255,.08)';"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="d-flex align-items-center justify-content-center text-white" style="width:36px; height:36px; border-radius:50%; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); transition:all .25s ease;" onmouseover="this.style.background='#f43f5e';" onmouseout="this.style.background='rgba(255,255,255,.08)';"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>

                <!-- Col 2: Quotes -->
                <div class="col-md-6 col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 28px; height: 28px; background: #6366f1; font-size: 0.75rem;">
                            <i class="bi bi-quote"></i>
                        </div>
                        <h6 class="text-white fw-bold mb-0" style="font-size:.85rem; letter-spacing: 1px; text-transform: uppercase;"><?= __("INSPIRATIONAL QUOTES") ?></h6>
                    </div>
                    <div style="border-left: 2px solid rgba(99, 102, 241, 0.3); padding-left: 14px;">
                        <p class="small fst-italic mb-3 text-white-50" style="line-height: 1.6;">
                            <?= __("\"Education is the most powerful weapon which you can use to change the world.\" — Nelson Mandela") ?>
                        </p>
                        <div style="height: 1px; background: rgba(255,255,255,0.06); margin-bottom: 12px;"></div>
                        <p class="small fst-italic mb-0 text-white-50" style="line-height: 1.6;">
                            <?= __("\"An investment in knowledge pays the best interest.\" — Benjamin Franklin") ?>
                        </p>
                    </div>
                </div>

                <!-- Col 3: Contact Us -->
                <div class="col-md-6 col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 28px; height: 28px; background: #6366f1; font-size: 0.75rem;">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <h6 class="text-white fw-bold mb-0" style="font-size:.85rem; letter-spacing: 1px; text-transform: uppercase;"><?= __("CONTACT US") ?></h6>
                    </div>
                    <div class="d-flex flex-column gap-2.5 small text-white-50" style="font-size: 0.88rem;">
                        <div class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-geo-alt text-primary fs-6"></i>
                            <span>Culaba, Biliran, Philippines</span>
                        </div>
                        <div class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-telephone text-primary fs-6"></i>
                            <span>(053) 123-4567</span>
                        </div>
                        <div class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-envelope text-primary fs-6"></i>
                            <span>info@alsculaba.gov.ph</span>
                        </div>
                        <div class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-clock text-primary fs-6"></i>
                            <span>Mon - Fri: 8:00 AM - 5:00 PM</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Copyright -->
            <div class="border-top pt-4 text-center" style="border-color: rgba(255,255,255,0.06) !important;">
                <span class="small text-white-50">© 2026 ALS Culaba District Enrollment System. All rights reserved.</span>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="back-to-top" aria-label="Back to top" onclick="scrollToTop()">
        <i class="bi bi-arrow-up-short"></i>
    </button>

    <!-- ══════════════════════════════════════════════════════
         HOW TO APPLY PROCESS MODAL
    ══════════════════════════════════════════════════════ -->
    <div class="modal fade" id="howToApplyModal" tabindex="-1" aria-labelledby="howToApplyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 920px;">
            <div class="modal-content border-0" style="border-radius: 28px; overflow: hidden; box-shadow: 0 40px 100px rgba(0,0,0,.18);">

                <!-- HEADER -->
                <div class="modal-header border-0 px-4 py-3" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%); position: relative; overflow: hidden;">
                    <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);background-size:32px 32px;pointer-events:none;"></div>
                    <div style="position:absolute;top:-40px;right:-40px;width:220px;height:220px;background:radial-gradient(circle,rgba(99,102,241,.35),transparent 70%);pointer-events:none;"></div>
                    <div style="position:absolute;bottom:-40px;left:-20px;width:160px;height:160px;background:radial-gradient(circle,rgba(13,148,136,.25),transparent 70%);pointer-events:none;"></div>
                    <div class="d-flex align-items-center gap-3" style="position:relative;z-index:2;">
                        <div style="width:44px;height:44px;border-radius:14px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                            <i class="bi bi-play-circle-fill" style="color:#a5b4fc;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="howToApplyModalLabel" style="color:#fff;font-size:1.05rem;letter-spacing:-.2px;"><?= __('Paano Mag-Apply sa ALS?') ?></h5>
                            <span style="font-size:.75rem;color:rgba(255,255,255,.55);font-weight:600;"><?= __('Sundan ang mga hakbang sa ibaba para sa application at login') ?></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close" style="opacity:.6;position:relative;z-index:2;"></button>
                </div>

                <!-- BODY -->
                <div class="modal-body p-0" style="background: linear-gradient(160deg, #f0f4ff 0%, #e8fdf5 50%, #f5f0ff 100%);">
                    <div class="row g-0" style="min-height: 480px;">

                        <!-- LEFT: Phone Mockup Video Panel -->
                        <div class="col-lg-5 d-flex flex-column justify-content-between p-4" style="background: linear-gradient(180deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%); position: relative; overflow: hidden; min-height: 480px;">
                            
                            <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:28px 28px;pointer-events:none;"></div>
                            <div style="position:absolute;top:-40px;left:-40px;width:180px;height:180px;background:radial-gradient(circle,rgba(99,102,241,.3),transparent 70%);pointer-events:none;"></div>
                            <div style="position:absolute;bottom:-40px;right:-40px;width:180px;height:180px;background:radial-gradient(circle,rgba(13,148,136,.25),transparent 70%);pointer-events:none;"></div>

                            <div style="position:relative;z-index:2;" class="w-100">
                                <!-- Top Pill Header -->
                                <div class="d-flex justify-content-center mb-3">
                                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill" style="background:rgba(99,102,241,.25);border:1px solid rgba(99,102,241,.4);backdrop-filter:blur(8px);">
                                        <i class="bi bi-phone-vibrate-fill text-warning fs-6"></i>
                                        <span style="font-size:.72rem;font-weight:800;color:#5eead4;letter-spacing:.8px;"><?= __('LEARNER MOBILE APP & TUTORIAL') ?></span>
                                    </div>
                                </div>

                                <!-- Smartphone Mockup Device Frame housing Video -->
                                <div class="phone-mockup-container">
                                    <div class="phone-mockup-device">
                                        <!-- Smartphone Camera / Speaker Notch -->
                                        <div class="phone-notch-bar">
                                            <div class="speaker-slot"></div>
                                            <div class="camera-lens"></div>
                                        </div>

                                        <!-- Phone Screen Area with Video -->
                                        <div class="phone-screen-area">
                                            <video autoplay muted loop playsinline class="phone-screen-video-el">
                                                <source src="assets/animation_wheel.webm" type="video/webm">
                                            </video>

                                            <!-- Floating Screen Notification Box -->
                                            <div class="phone-screen-notice">
                                                <div class="d-flex align-items-start gap-2 mb-1">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 24px; height: 24px; background: rgba(245, 158, 11, 0.25); color: #fbbf24; font-size: 0.75rem;">
                                                        <i class="bi bi-envelope-fill"></i>
                                                    </div>
                                                    <div>
                                                        <span class="badge bg-warning text-dark fw-bold" style="font-size: 0.58rem; letter-spacing: 0.3px;"><?= __('IMPORTANT') ?></span>
                                                        <div class="fw-bold text-white" style="font-size: 0.74rem; line-height: 1.2; margin-top: 1px;"><?= __('Wait for Email Credentials!') ?></div>
                                                    </div>
                                                </div>
                                                <p class="text-white-50 mb-0" style="font-size: 0.66rem; line-height: 1.35;">
                                                    <?= __('Check your email for account Username & Password after applying.') ?>
                                                </p>
                                            </div>

                                            <!-- Bottom Home Bar Indicator -->
                                            <div class="phone-screen-bottom-bar"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Header Text -->
                            <div style="position:relative;z-index:2;" class="text-center pt-2">
                                <h6 style="color:#fff;font-size:0.92rem;font-weight:800;margin-bottom:4px;">
                                    <?= __('Simulan ang Iyong Pag-aaral sa ALS') ?>
                                </h6>
                                <p style="color:rgba(255,255,255,.65);font-size:.78rem;line-height:1.45;margin:0;">
                                    <?= __('Sundan ang 5 ka simpleng hakbang sa kanan para sa online registration at pag-login.') ?>
                                </p>
                            </div>
                        </div>

                        <!-- RIGHT: Steps panel -->
                        <div class="col-lg-7 d-flex flex-column p-4" style="overflow-y:auto;max-height:520px;">

                            <p class="fw-bold mb-4" style="color:#475569;font-size:.82rem;text-transform:uppercase;letter-spacing:1.2px;"><?= __('Mga Hakbang sa Pag-apply at Pag-login') ?></p>

                            <!-- Step 1 -->
                            <div class="d-flex gap-3 mb-3 p-3 rounded-3" style="background:#fff;border:1.5px solid rgba(99,102,241,.12);box-shadow:0 2px 8px rgba(0,0,0,.04);">
                                <div style="width:44px;height:44px;min-width:44px;border-radius:14px;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.15rem;box-shadow:0 6px 16px rgba(99,102,241,.3);">
                                    <i class="bi bi-pencil-square"></i>
                                </div>
                                <div>
                                    <div style="font-weight:800;color:#0f172a;font-size:.92rem;margin-bottom:3px;"><?= __('Hakbang 1: Mag-fill out ng Application Form') ?></div>
                                    <div style="font-size:.8rem;color:#64748b;line-height:1.55;"><?= __('I-click ang "Mag Apply Ngayon" para ma-access ang online application form. Kumpletuhin ang lahat ng kinakailangang impormasyon.') ?></div>
                                </div>
                            </div>

                            <!-- Step 2 -->
                            <div class="d-flex gap-3 mb-3 p-3 rounded-3" style="background:#fff;border:1.5px solid rgba(13,148,136,.12);box-shadow:0 2px 8px rgba(0,0,0,.04);">
                                <div style="width:44px;height:44px;min-width:44px;border-radius:14px;background:linear-gradient(135deg,#0d9488,#0ea5e9);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.15rem;box-shadow:0 6px 16px rgba(13,148,136,.3);">
                                    <i class="bi bi-cloud-upload-fill"></i>
                                </div>
                                <div>
                                    <div style="font-weight:800;color:#0f172a;font-size:.92rem;margin-bottom:3px;"><?= __('Hakbang 2: I-submit ang mga Dokumento') ?></div>
                                    <div style="font-size:.8rem;color:#64748b;line-height:1.55;"><?= __('I-upload ang mga kinakailangang dokumento tulad ng PSA Birth Certificate, ID photo, at iba pa. Siguraduhing malinaw at kumpleto ang lahat.') ?></div>
                                </div>
                            </div>

                            <!-- Step 3 -->
                            <div class="d-flex gap-3 mb-3 p-3 rounded-3" style="background:#fff;border:1.5px solid rgba(99,102,241,.12);box-shadow:0 2px 8px rgba(0,0,0,.04);">
                                <div style="width:44px;height:44px;min-width:44px;border-radius:14px;background:linear-gradient(135deg,#6366f1,#7c3aed);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.15rem;box-shadow:0 6px 16px rgba(99,102,241,.3);">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                                <div>
                                    <div style="font-weight:800;color:#0f172a;font-size:.92rem;margin-bottom:3px;"><?= __('Hakbang 3: Antayin ang Pagsusuri ng Application') ?></div>
                                    <div style="font-size:.8rem;color:#64748b;line-height:1.55;"><?= __('Matapos i-submit ang iyong application, antayin ang pagsusuri ng iyong Mobile Teacher o ALS Coordinator.') ?></div>
                                </div>
                            </div>

                            <!-- Step 4 — EMAIL CREDENTIALS HIGHLIGHT -->
                            <div class="d-flex gap-3 mb-3 p-3 rounded-3" style="background:linear-gradient(135deg,rgba(99,102,241,.08) 0%,rgba(245,158,11,.08) 100%);border:2px solid rgba(245,158,11,.35);box-shadow:0 6px 20px rgba(245,158,11,.12);position:relative;overflow:hidden;">
                                <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#f59e0b,#6366f1,#0d9488,#f59e0b);background-size:200% 100%;animation:shimmer 2.5s linear infinite;"></div>
                                <div style="width:46px;height:46px;min-width:46px;border-radius:14px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;box-shadow:0 6px 16px rgba(245,158,11,.4);">
                                    <i class="bi bi-envelope-paper-fill"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                        <span style="font-weight:800;color:#0f172a;font-size:.93rem;"><?= __('Hakbang 4: Hintayin ang Email para sa Account Credentials') ?></span>
                                        <span class="badge bg-warning text-dark fw-bold" style="font-size: 0.65rem;"><?= __('Wait for Email') ?></span>
                                    </div>
                                    <div style="font-size:.82rem;color:#334155;line-height:1.65;" class="mb-2">
                                        <?= __('Kapag na-verify na ang iyong application, makakatanggap ka ng') ?>
                                        <strong style="color:#d97706;"><?= __(' email message ') ?></strong>
                                        <?= __('na naglalaman ng iyong official') ?>
                                        <strong style="color:#4f46e5;"><?= __(' account credentials (Username & Password)') ?></strong>.
                                    </div>
                                    <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-3" style="background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.25);">
                                        <i class="bi bi-exclamation-triangle-fill" style="color:#d97706;font-size:.9rem;flex-shrink:0;"></i>
                                        <span style="font-size:.78rem;font-weight:700;color:#b45309;"><?= __('Siguraduhing tama at aktibo ang email address na ilalagay sa form!') ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 5 — LOGIN PROCESS -->
                            <div class="d-flex gap-3 p-3 rounded-3" style="background:linear-gradient(135deg,rgba(16,185,129,.06) 0%,rgba(13,148,136,.06) 100%);border:2px solid rgba(16,185,129,.3);box-shadow:0 4px 16px rgba(16,185,129,.1);">
                                <div style="width:46px;height:46px;min-width:46px;border-radius:14px;background:linear-gradient(135deg,#10b981,#0d9488);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;box-shadow:0 6px 16px rgba(16,185,129,.35);">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                        <span style="font-weight:800;color:#0f172a;font-size:.93rem;"><?= __('Hakbang 5: I-login ang iyong Account') ?></span>
                                        <span class="badge bg-success fw-bold" style="font-size: 0.65rem;"><?= __('Login Process') ?></span>
                                    </div>
                                    <div style="font-size:.82rem;color:#475569;line-height:1.6;">
                                        <?= __('Pagka-receive ng iyong email credentials, i-click ang ') ?>
                                        <strong style="color:#0d9488;"><?= __('"Login na"') ?></strong>
                                        <?= __(' button upang ilagay ang iyong Username at Password sa Learner Portal at ma-access ang iyong dashboard.') ?>
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm text-white fw-bold px-3 py-1.5 rounded-2 d-inline-flex align-items-center gap-1.5 shadow-sm" onclick="proceedToLearnerLogin()" style="background:linear-gradient(135deg,#10b981,#0d9488);font-size:.78rem;border:none;">
                                            <i class="bi bi-box-arrow-in-right"></i> <?= __('Pumunta sa Login Form') ?>
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- FOOTER -->
                <div class="modal-footer border-0 px-4 py-3 d-flex align-items-center gap-2 flex-wrap" style="background:#f8fafc;border-top:1px solid rgba(0,0,0,.06) !important;">
                    <div class="me-auto d-flex align-items-center gap-2" style="font-size:.78rem;color:#64748b;font-weight:600;">
                        <i class="bi bi-info-circle-fill" style="color:#6366f1;"></i>
                        <?= __('Libre ang pag-apply. Walang bayad.') ?>
                    </div>
                    <button type="button" class="btn fw-bold px-4 py-2" data-bs-dismiss="modal" style="border-radius:12px;border:1.5px solid rgba(99,102,241,.3);color:#4f46e5;font-size:.87rem;background:#fff;">
                        <i class="bi bi-x-circle"></i> <?= __('Isara') ?>
                    </button>
                    <button type="button" class="btn fw-bold px-4 py-2" onclick="proceedToLearnerLogin()" style="border-radius:12px;background:linear-gradient(135deg,#10b981,#0d9488);color:#fff;border:none;font-size:.87rem;box-shadow:0 6px 18px rgba(13,148,136,.35);">
                        <i class="bi bi-box-arrow-in-right"></i> <?= __('Login na') ?>
                    </button>
                    <button type="button" class="btn fw-bold px-4 py-2" id="howToApplyProceedBtn" style="border-radius:12px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;font-size:.87rem;box-shadow:0 6px 18px rgba(99,102,241,.35);">
                        <i class="bi bi-send-fill"></i> <?= __('Mag Apply Ngayon') ?>
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         APPLICATION MODAL (Preserved for compatibility)
    ══════════════════════════════════════════════════════ -->
    <div class="modal fade" id="applyModal" tabindex="-1" aria-labelledby="applyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content glass-panel" style="border: 1px solid var(--glass-border); border-radius: 28px; overflow: hidden; box-shadow: var(--shadow-premium);">
                <div class="modal-body p-0" style="height: 85vh; background: #f8fafc;">
                    <iframe src="students_apply.php?iframe=1" style="width: 100%; height: 100%; border: none;" id="applyIframe"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         ENROLLMENT CLOSED MODAL
    ══════════════════════════════════════════════════════ -->
    <div class="modal fade" id="enrollmentClosedModal" tabindex="-1" aria-labelledby="closedModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-panel" style="border: 1px solid var(--glass-border); border-radius: 24px; overflow: hidden; box-shadow: var(--shadow-premium); background: rgba(255, 255, 255, 0.95);">
                <div class="modal-header border-0 pt-4 px-4 d-flex align-items-center justify-content-between">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2 text-navy" id="closedModalLabel">
                        <i class="bi bi-info-circle-fill text-danger"></i> <?= __("Enrollment is Closed") ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <p class="text-secondary mb-0 fw-medium" style="font-size: 15px; line-height: 1.6;">
                        <?= __("Wait for teacher announce in your brgy.") ?>
                    </p>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-2 d-flex gap-2">
                    <button type="button" class="btn btn-light px-4 py-2 fw-bold text-secondary" data-bs-dismiss="modal" style="border-radius: 10px; border: 1px solid var(--border-light); font-size: 14px;">
                        <?= __("Close") ?>
                    </button>
                    <button type="button" class="btn btn-primary px-4 py-2 fw-bold" data-bs-dismiss="modal" style="border-radius: 10px; font-size: 14px; background: var(--primary-blue); border: none; box-shadow: var(--shadow-glowing);">
                        <?= __("Wait for Announcement") ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         JAVASCRIPT / SCRIPTS
    ══════════════════════════════════════════════════════ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        const isEnrollmentOpen = <?php echo ($enroll_status === 'open') ? 'true' : 'false'; ?>;
        // Initialize Scroll Animations (AOS)
        AOS.init({
            duration: 800,
            easing: 'ease-out-cubic',
            once: true,
            offset: 80
        });

        // Typewriter Effect
        const typeLines = <?= json_encode([
            __('Enrollment Management System'),
            __('Education For All Filipinos'),
            __('Learn at Your Own Pace'),
            __('Your Future Starts Here'),
        ]) ?>;
        let tli = 0, tci = 0, deleting = false;
        const typeEl = document.getElementById('typeTarget');
        function typeWriter() {
            const line = typeLines[tli];
            if (!deleting) {
                typeEl.textContent = line.slice(0, ++tci);
                if (tci === line.length) { deleting = true; setTimeout(typeWriter, 1800); return; }
            } else {
                typeEl.textContent = line.slice(0, --tci);
                if (tci === 0) { deleting = false; tli = (tli + 1) % typeLines.length; setTimeout(typeWriter, 300); return; }
            }
            setTimeout(typeWriter, deleting ? 45 : 70);
        }
        if (typeEl) {
            typeWriter();
        }

        // Animated Counter for Impact Stats Strip
        const statNumbers = document.querySelectorAll('.stat-number');
        if (statNumbers.length) {
            const animateCount = (el) => {
                const target = parseInt(el.getAttribute('data-count'), 10) || 0;
                const suffix = el.getAttribute('data-suffix') || '';
                const duration = 1400;
                const startTime = performance.now();
                function step(now) {
                    const progress = Math.min((now - startTime) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    el.textContent = Math.floor(eased * target) + suffix;
                    if (progress < 1) {
                        requestAnimationFrame(step);
                    } else {
                        el.textContent = target + suffix;
                    }
                }
                requestAnimationFrame(step);
            };

            const statObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateCount(entry.target);
                        statObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.4 });

            statNumbers.forEach(el => statObserver.observe(el));
        }

        // Sticky Navbar and Flying Logo Transition Effect
        const nav = document.getElementById('main-nav');
        const sections = document.querySelectorAll('section');
        const navLinks = document.querySelectorAll('.nav-link');
        
        // Flying Logo setup
        const flyingLogo = document.getElementById('flying-logo');
        let lastSection = 'home-section';
        let lastSlotId = 'home';
        let currentRotation = 0;
        let transitionTimeout = null;
        let isTransitioning = false;

        // Initialize flying logo by re-parenting it to body
        if (flyingLogo) {
            flyingLogo.style.position = 'fixed';
            flyingLogo.style.margin = '0';
            flyingLogo.style.pointerEvents = 'none';
            flyingLogo.style.width = '100px';
            flyingLogo.style.height = '100px';
            document.body.appendChild(flyingLogo);
        }

        function updateLogoPosition(immediate = false) {
            if (!flyingLogo) return;

            // Find current active section (keep in Home section if scroll position is near top)
            let currentSec = 'home-section';
            if (window.scrollY > 50) {
                sections.forEach(sec => {
                    const id = sec.getAttribute('id');
                    if (id && window.scrollY >= (sec.offsetTop - 250)) {
                        currentSec = id;
                    }
                });
            }

            // Map section ID to slot data-slot
            let slotId = '';
            if (currentSec === 'home-section') slotId = 'home';
            else if (currentSec === 'about-als') slotId = 'about';
            else if (currentSec === 'programs') slotId = 'programs';
            else if (currentSec === 'als-centers') slotId = 'als-centers';
            else if (currentSec === 'requirements') slotId = 'requirements';

            else if (currentSec === 'why-choose-als') slotId = 'why-choose-als';
            else if (currentSec === 'testimonials') slotId = 'testimonials';
            else if (currentSec === 'faq-section') slotId = 'faq-section';
            else if (currentSec === 'enrollment-process') slotId = 'enrollment-process';
            else if (currentSec === 'contact-section') slotId = 'contact-section';

            if (!slotId) {
                slotId = lastSlotId;
            }

            const activeSlot = document.querySelector(`.logo-placeholder[data-slot="${slotId}"]`) || document.querySelector('.logo-placeholder[data-slot="home"]');
            if (!activeSlot) return;

            // Manage active styles for all slots
            document.querySelectorAll('.logo-placeholder').forEach(slot => {
                if (slot === activeSlot) {
                    slot.classList.add('active-slot');
                } else {
                    slot.classList.remove('active-slot');
                }
            });

            const rect = activeSlot.getBoundingClientRect();
            if (rect.width === 0) return; // Hide or ignore if slot is not visible

            const scale = rect.width / 100; // base width is 100px

            if (slotId !== lastSlotId && !immediate) {
                // Section slot changed! Trigger flip & slide transition (overshoot bounce)
                lastSlotId = slotId;
                currentRotation += 360;
                isTransitioning = true;

                flyingLogo.style.transition = 'transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.8s ease';
                
                if (transitionTimeout) clearTimeout(transitionTimeout);
                transitionTimeout = setTimeout(() => {
                    isTransitioning = false;
                }, 800);
            } else if (immediate) {
                flyingLogo.style.transition = 'none';
                isTransitioning = false;
            } else if (!isTransitioning) {
                // Floating tracking with a slight delay so the animation remains visible on scroll
                flyingLogo.style.transition = 'transform 0.25s cubic-bezier(0.25, 1, 0.5, 1)';
            }

            flyingLogo.style.transform = `translate3d(${rect.left}px, ${rect.top}px, 0) scale(${scale}) rotateY(${currentRotation}deg)`;
        }

        // Initialize position
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => updateLogoPosition(true), 100);
        });
        window.addEventListener('load', () => updateLogoPosition(true));
        window.addEventListener('resize', () => updateLogoPosition(true));

        window.addEventListener('scroll', () => {
            // Background color add/remove
            if (window.scrollY > 40) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }

            // Back to top show/hide
            const btt = document.getElementById('back-to-top');
            if (window.scrollY > 300) {
                btt.classList.add('show');
            } else {
                btt.classList.remove('show');
            }

            // Active Nav Item on Scroll
            let currentSec = '';
            sections.forEach(sec => {
                const secTop = sec.offsetTop;
                if (window.scrollY >= (secTop - 150)) {
                    currentSec = sec.getAttribute('id') || currentSec;
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${currentSec}`) {
                    link.classList.add('active');
                }
            });

            // Update flying logo tracking
            updateLogoPosition();
        });

        // Scroll to Top helper
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // ── HOW TO APPLY MODAL FLOW ──
        // openModal() now shows the "How to Apply" process modal first
        function openModal() {
            const howToApplyEl = document.getElementById('howToApplyModal');
            if (howToApplyEl) {
                const howModal = new bootstrap.Modal(howToApplyEl);
                howModal.show();
            }
        }

        // Called from "Mag Apply Ngayon" button inside the How-to-Apply modal
        function openApplyFormModal() {
            const howToApplyEl = document.getElementById('howToApplyModal');
            const doOpen = () => {
                fetch('get_enrollment_status.php')
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'open') {
                            const el = document.getElementById('applyModal');
                            if (el) new bootstrap.Modal(el).show();
                        } else {
                            const el = document.getElementById('enrollmentClosedModal');
                            if (el) new bootstrap.Modal(el).show();
                        }
                    })
                    .catch(() => {
                        if (isEnrollmentOpen) {
                            const el = document.getElementById('applyModal');
                            if (el) new bootstrap.Modal(el).show();
                        } else {
                            const el = document.getElementById('enrollmentClosedModal');
                            if (el) new bootstrap.Modal(el).show();
                        }
                    });
            };
            if (howToApplyEl && howToApplyEl.classList.contains('show')) {
                const howModal = bootstrap.Modal.getInstance(howToApplyEl);
                if (howModal) howModal.hide();
                howToApplyEl.addEventListener('hidden.bs.modal', doOpen, { once: true });
            } else {
                doOpen();
            }
        }

        // Called from "Login na" button inside the How-to-Apply modal
        function proceedToLearnerLogin() {
            const howToApplyEl = document.getElementById('howToApplyModal');
            const doLogin = () => openLearnerLogin();
            if (howToApplyEl && howToApplyEl.classList.contains('show')) {
                const howModal = bootstrap.Modal.getInstance(howToApplyEl);
                if (howModal) howModal.hide();
                howToApplyEl.addEventListener('hidden.bs.modal', doLogin, { once: true });
            } else {
                doLogin();
            }
        }

        // Wire up the "Mag Apply Ngayon" button inside the How-to-Apply modal
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('howToApplyProceedBtn');
            if (btn) btn.addEventListener('click', openApplyFormModal);
        });

        // Handle clicks on other "Apply Now" buttons (Navbar and CTA)
        document.querySelectorAll('.secondary-apply-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Smooth scroll to home section
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                
                // Trigger attention animation on the main hero button after scrolling starts
                setTimeout(() => {
                    const heroBtn = document.getElementById('hero-apply-btn');
                    if (heroBtn) {
                        heroBtn.classList.add('attention-bounce');
                        
                        // Remove the class after animation ends so it can be re-triggered
                        heroBtn.addEventListener('animationend', () => {
                            heroBtn.classList.remove('attention-bounce');
                        }, { once: true });
                    }
                }, 600); // Wait 600ms for smooth scroll to get close to top
            });
        });

        // Make the main hero button open the modal when clicked
        const heroApplyBtn = document.getElementById('hero-apply-btn');
        if (heroApplyBtn) {
            heroApplyBtn.addEventListener('click', (e) => {
                e.preventDefault();
                openModal();
            });
        }

        // Refresh page when modals are closed to prevent scroll lock and reset state
        ['applyModal', 'enrollmentClosedModal'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('hidden.bs.modal', function () {
                    window.location.reload();
                });
            }
        });
    </script>
    <!-- 🌍 Location Modal -->
    <div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1140px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden; background: #f8fafc;">
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <!-- Left Side: Center details & Photos -->
                        <div class="col-lg-5 bg-white p-4 p-md-5 d-flex flex-column justify-content-between" style="min-height: 600px; max-height: 90vh; overflow-y: auto;">
                            <div>
                                <!-- District tag -->
                                <div class="d-inline-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill fw-bold text-primary mb-3" style="background: rgba(79, 70, 229, 0.08); font-size: 0.8rem; letter-spacing: 0.5px;">
                                    <i class="bi bi-geo-alt-fill"></i> <?= __("ALS District") ?>
                                </div>
                                
                                <!-- Center Name -->
                                <h2 class="fw-extrabold text-navy mb-2" id="locModalName" style="font-weight: 850; font-size: 1.95rem; line-height: 1.25; letter-spacing: -0.5px;">Culaba ALS Mobile Center</h2>
                                
                                <!-- Subtitle / Location -->
                                <div class="d-flex align-items-center gap-2 text-secondary mb-3 fw-semibold" style="font-size: 0.95rem;">
                                    <i class="bi bi-geo-alt text-primary"></i> <span id="locModalSub">Culaba, Biliran</span>
                                </div>
                                
                                <!-- Gradient bar -->
                                <div class="mb-4" style="width: 50px; height: 4px; background: linear-gradient(90deg, var(--teal), var(--indigo)); border-radius: 2px;"></div>
                                
                                <!-- Thumbnail Image frame -->
                                <div class="position-relative mb-4 overflow-hidden rounded-4 shadow-sm hover-lift" style="height: 170px; width: 100%; border: 1.5px solid rgba(0,0,0,0.04);">
                                    <img id="locModalImg" src="" alt="School Image" style="width: 100%; height: 100%; object-fit: cover;">
                                    <button class="btn btn-sm btn-white position-absolute bottom-0 end-0 m-3 px-3 py-1.5 bg-white text-navy rounded-pill shadow d-flex align-items-center gap-1.5 border-0 hover-lift" style="font-size: 0.72rem; font-weight: 750; pointer-events: auto; border: 1px solid rgba(0,0,0,0.05); transition: all 0.2s;" onclick="openSchoolImageModal()">
                                        <i class="bi bi-eye-fill text-primary"></i> <span><?= __("View School") ?></span>
                                    </button>
                                </div>
                                
                                <!-- Standard details block (to be hidden when directions are active) -->
                                <div id="locModalStandardDetails">
                                    <!-- Description -->
                                    <h5 class="fw-bold text-navy mb-2" style="font-size: 1.05rem;"><?= __("Unsay naka nindot ngadto?") ?></h5>
                                    <p id="locModalDesc" class="text-secondary lh-lg mb-4" style="font-size: 0.92rem; font-weight: 500;"></p>
                                    
                                    <!-- Available Programs -->
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-navy mb-2.5" style="font-size: 0.88rem; opacity: 0.95; letter-spacing: 0.2px;"><i class="bi bi-check-circle-fill text-teal"></i> <?= __("Mga Bukas nga Programa") ?></h6>
                                        <div class="d-flex flex-wrap gap-2 pt-1">
                                            <span class="badge rounded-pill px-3 py-2 border d-inline-flex align-items-center gap-1.5" style="font-size: 0.72rem; font-weight: 700; background: rgba(13, 148, 136, 0.05); border-color: rgba(13, 148, 136, 0.15) !important; color: var(--teal) !important;"><i class="bi bi-book fs-6"></i> <?= __("Basic Literacy") ?></span>
                                            <span class="badge rounded-pill px-3 py-2 border d-inline-flex align-items-center gap-1.5" style="font-size: 0.72rem; font-weight: 700; background: rgba(79, 70, 229, 0.05); border-color: rgba(79, 70, 229, 0.15) !important; color: var(--primary) !important;"><i class="bi bi-mortarboard fs-6"></i> <?= __("Elementary A&E Program") ?></span>
                                            <span class="badge rounded-pill px-3 py-2 border d-inline-flex align-items-center gap-1.5" style="font-size: 0.72rem; font-weight: 700; background: rgba(99, 102, 241, 0.05); border-color: rgba(99, 102, 241, 0.15) !important; color: var(--indigo) !important;"><i class="bi bi-award fs-6"></i> <?= __("Secondary A&E Program") ?></span>
                                        </div>
                                    </div>

                                    <!-- Saved Locations List (hidden if empty) -->
                                    <div class="mb-4 d-none" id="locModalSavedContainer">
                                        <h6 class="fw-bold text-navy mb-2.5" style="font-size: 0.88rem; opacity: 0.95; letter-spacing: 0.2px;"><i class="bi bi-bookmark-star-fill text-primary"></i> <?= __("Mga Na-save na Lokasyon") ?></h6>
                                        <div class="d-flex flex-column gap-2" id="locModalSavedList" style="max-height: 150px; overflow-y: auto;">
                                            <!-- Dynamically populated -->
                                        </div>
                                    </div>

                                    <!-- Card 2: Interactive action -->
                                    <div class="p-3 rounded-4 d-flex align-items-center justify-content-between cursor-pointer hover-lift shadow-sm border bg-white" style="transition: all 0.25s ease;" onclick="findNearestCenter(document.getElementById('nearestCenterBtn'))" id="nearestCenterBtn">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="p-2 rounded-3 bg-light-blue text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; flex-shrink: 0;">
                                                <i class="bi bi-geo-fill fs-5"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-1 text-navy" style="font-size: 0.88rem;"><?= __("Find the Nearest Center") ?></h6>
                                                <p class="text-secondary mb-0 small"><?= __("Discover other ALS centers near you") ?></p>
                                            </div>
                                        </div>
                                        <div class="btn btn-sm btn-light rounded-circle shadow-sm p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border: 1px solid rgba(0,0,0,0.05);">
                                            <i class="bi bi-arrow-right text-primary"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- Trip Info details block (hidden by default) -->
                                <div id="locModalTripDetails" class="d-none mt-4 p-4 rounded-4 shadow-sm border bg-white" style="background: rgba(79, 70, 229, 0.02); border-color: rgba(79, 70, 229, 0.08) !important;">
                                    <h5 class="fw-bold text-navy mb-3" style="font-size: 1.05rem;"><i class="bi bi-info-circle-fill text-primary"></i> <?= __("Impormasyon sa Biyahe") ?></h5>
                                    <div class="d-flex flex-column gap-3 mb-4" style="font-size: 0.88rem;">
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2" style="border-bottom-style: dashed !important;">
                                            <span class="text-secondary fw-semibold"><?= __("Distance") ?>:</span>
                                            <span id="routeDistanceLeft" class="fw-bold text-navy fs-6">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2" style="border-bottom-style: dashed !important;">
                                            <span class="text-secondary fw-semibold"><?= __("Normal conditions") ?>:</span>
                                            <span id="routeTimeNormalLeft" class="fw-bold text-success fs-6">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2" style="border-bottom-style: dashed !important;">
                                            <span class="text-secondary fw-semibold"><?= __("Heavy Traffic") ?>:</span>
                                            <span id="routeTimeTrafficLeft" class="fw-bold text-danger fs-6">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center pb-2">
                                            <span class="text-secondary fw-semibold"><?= __("Arrival Time") ?>:</span>
                                            <span id="routeETALeft" class="fw-bold text-primary fs-6">-</span>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-danger w-100 py-2.5 d-flex align-items-center justify-content-center gap-2 fw-bold" onclick="resetLocation()" style="font-size: 0.85rem; border-radius: 12px; transition: all 0.2s;">
                                        <i class="bi bi-arrow-counterclockwise fs-5"></i> <?= __("Reset Location") ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Side: Satellite Map with overlays -->
                        <div class="col-lg-7 position-relative" style="min-height: 600px; height: 90vh;">
                            <!-- Map Container -->
                            <div id="locModalMap" style="width: 100%; height: 100%; background: #eee;"></div>

                            
                            <!-- Top overlay: Close button -->
                            <div class="position-absolute top-0 end-0 m-3" style="z-index: 1000;">
                                <button type="button" class="btn btn-light bg-white rounded-3 p-2 shadow-md border hover-light" data-bs-dismiss="modal" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease;">
                                    <i class="bi bi-x-lg text-navy fw-bold" style="font-size: 1.1rem; -webkit-text-stroke: 1px;"></i>
                                </button>
                            </div>
                            
                            <!-- Top overlay: Current Center header card -->
                            <div class="position-absolute top-0 start-0 m-3" style="z-index: 1000; pointer-events: none;">
                                <div class="bg-white rounded-3 shadow-md px-3.5 py-2.5 d-flex align-items-center gap-2.5 border" style="pointer-events: auto;">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.8rem;">
                                        <i class="bi bi-geo-alt-fill"></i>
                                    </div>
                                    <span id="locMapHeaderTitle" class="fw-bold text-navy" style="font-size: 0.85rem;">Culaba ALS Mobile Center, Culaba</span>
                                </div>
                            </div>
                            
                            <!-- Bottom overlay: quick actions -->
                            <div class="position-absolute bottom-0 start-0 end-0 p-3" style="z-index: 1000; pointer-events: none;">
                                <div class="bg-white rounded-4 shadow-lg p-2 d-flex align-items-center justify-content-around mx-auto border" style="max-width: 480px; pointer-events: auto; width: 95%;">
                                    <button class="btn border-0 bg-transparent text-secondary d-flex flex-column align-items-center gap-1 px-3 py-1 hover-light-blue" style="font-size: 0.72rem; font-weight: 700; transition: all 0.2s;" onclick="getDirections()">
                                        <i class="bi bi-send text-primary fs-5"></i>
                                        <span><?= __("Directions") ?></span>
                                    </button>
                                    <button class="btn border-0 bg-transparent text-secondary d-flex flex-column align-items-center gap-1 px-3 py-1 hover-light-blue" style="font-size: 0.72rem; font-weight: 700; transition: all 0.2s;" onclick="saveLocation()">
                                        <i class="bi bi-bookmark text-primary fs-5"></i>
                                        <span><?= __("Save Location") ?></span>
                                    </button>
                                    <button class="btn border-0 bg-transparent text-secondary d-flex flex-column align-items-center gap-1 px-3 py-1 hover-light-blue" style="font-size: 0.72rem; font-weight: 700; transition: all 0.2s;" onclick="viewNearbyCenters()">
                                        <i class="bi bi-geo-alt text-primary fs-5"></i>
                                        <span><?= __("Nearby Centers") ?></span>
                                    </button>
                                    <button class="btn border-0 bg-transparent text-secondary d-flex flex-column align-items-center gap-1 px-3 py-1 hover-light-blue" style="font-size: 0.72rem; font-weight: 700; transition: all 0.2s;" onclick="shareLocation()">
                                        <i class="bi bi-share text-primary fs-5"></i>
                                        <span><?= __("Share Location") ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 🖼️ School Image Viewer Modal -->
    <div class="modal fade" id="schoolImageModal" tabindex="-1" aria-hidden="true" style="z-index: 1070;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 bg-transparent">
                <div class="modal-body p-0 position-relative text-center">
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close" style="z-index: 10; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));"></button>
                    <img id="fullSchoolImg" src="" alt="School Image Full" class="img-fluid rounded-4 shadow-lg" style="max-height: 85vh; object-fit: contain; width: auto; max-width: 100%;">
                </div>
            </div>
        </div>
    </div>

    <!-- ❓ Reset Confirmation Modal -->
    <div class="modal fade" id="resetConfirmModal" tabindex="-1" aria-hidden="true" style="z-index: 1065;">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-body p-4 text-center">
                    <div class="rounded-circle bg-light-blue text-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 54px; height: 54px; background: rgba(79, 70, 229, 0.08);">
                        <i class="bi bi-question-lg fs-3 fw-bold text-primary"></i>
                    </div>
                    <h5 class="fw-bold text-navy mb-2"><?= __("I-reset ang Lokasyon?") ?></h5>
                    <p class="text-secondary small mb-4" style="line-height: 1.5;"><?= __("Sigurado ka ba na nais mong i-reset ang iyong lokasyon at ruta sa mapa?") ?></p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-light w-50 py-2 fw-semibold rounded-3" data-bs-dismiss="modal" style="font-size: 0.8rem;"><?= __("Cancel") ?></button>
                        <button type="button" class="btn btn-sm btn-primary w-50 py-2 fw-semibold rounded-3" style="background: var(--navy-900) !important; border: none; font-size: 0.8rem;" onclick="confirmResetLocation()"><?= __("Okay") ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 🗺️ Directions Confirmation Modal -->
    <div class="modal fade" id="directionsConfirmModal" tabindex="-1" aria-hidden="true" style="z-index: 1065;">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-body p-4 text-center">
                    <div class="rounded-circle bg-light-blue text-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 54px; height: 54px; background: rgba(79, 70, 229, 0.08);">
                        <i class="bi bi-geo-alt-fill fs-3 text-primary"></i>
                    </div>
                    <h5 class="fw-bold text-navy mb-2"><?= __("Kumuha ng Ruta?") ?></h5>
                    <p class="text-secondary small mb-4" style="line-height: 1.5;">
                        <?= __("Sigurado ka ba na nais mong kumuha ng ruta patungo sa") ?> <strong id="directionsSchoolName" class="text-navy"></strong>?
                    </p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-light w-50 py-2 fw-semibold rounded-3" data-bs-dismiss="modal" style="font-size: 0.8rem;"><?= __("Cancel") ?></button>
                        <button type="button" class="btn btn-sm btn-primary w-50 py-2 fw-semibold rounded-3" style="background: var(--navy-900) !important; border: none; font-size: 0.8rem;" onclick="confirmGetDirections()"><?= __("Okay") ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 📍 Enable Location Access Modal -->
    <div class="modal fade" id="enableLocationModal" tabindex="-1" aria-hidden="true" style="z-index: 1065;">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-body p-4 text-center">
                    <div class="rounded-circle bg-light-blue text-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 54px; height: 54px; background: rgba(79, 70, 229, 0.08);">
                        <i class="bi bi-geo-fill fs-3 text-primary"></i>
                    </div>
                    <h5 class="fw-bold text-navy mb-2"><?= __("I-on ang Lokasyon") ?></h5>
                    <p class="text-secondary small mb-4" style="line-height: 1.5;"><?= __("Upang maipakita ang pinakamalapit na ALS centers at ang ruta papunta rito, mangyaring i-on ang iyong lokasyon.") ?></p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-light w-50 py-2 fw-semibold rounded-3" onclick="declineUserLocation()" style="font-size: 0.8rem;"><?= __("Cancel") ?></button>
                        <button type="button" class="btn btn-sm btn-primary w-50 py-2 fw-semibold rounded-3" style="background: var(--navy-900) !important; border: none; font-size: 0.8rem;" onclick="approveUserLocation()"><?= __("Okay") ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ❓ Nearby Centers Confirmation Modal -->
    <div class="modal fade" id="nearbyConfirmModal" tabindex="-1" aria-hidden="true" style="z-index: 1065;">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-body p-4 text-center">
                    <div class="rounded-circle bg-light-blue text-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 54px; height: 54px; background: rgba(79, 70, 229, 0.08);">
                        <i class="bi bi-question-lg fs-3 fw-bold text-primary"></i>
                    </div>
                    <h5 class="fw-bold text-navy mb-2"><?= __("Tingnan ang Malalapit?") ?></h5>
                    <p class="text-secondary small mb-4" style="line-height: 1.5;"><?= __("Sigurado ka ba na nais mong tingnan ang mga pinakamalapit na ALS centers sa iyong lokasyon?") ?></p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-light w-50 py-2 fw-semibold rounded-3" data-bs-dismiss="modal" style="font-size: 0.8rem;"><?= __("Cancel") ?></button>
                        <button type="button" class="btn btn-sm btn-primary w-50 py-2 fw-semibold rounded-3" style="background: var(--navy-900) !important; border: none; font-size: 0.8rem;" onclick="confirmViewNearbyCenters()"><?= __("Okay") ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 🏫 Nearby Centers List Modal -->
    <div class="modal fade" id="nearbyCentersModal" tabindex="-1" aria-hidden="true" style="z-index: 1065;">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; background: #f8fafc;">
                <div class="modal-header border-0 pt-4 px-4 pb-2">
                    <h5 class="modal-title fw-bold text-navy"><i class="bi bi-buildings-fill text-primary"></i> <?= __("Malalapit na ALS Centers") ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pb-4 pt-2">
                    <p class="text-secondary small mb-3"><?= __("Narito ang mga ALS learning centers na malapit sa iyong kasalukuyang lokasyon:") ?></p>
                    <div id="nearbyCentersList" class="d-flex flex-column gap-2" style="max-height: 350px; overflow-y: auto; padding-right: 4px;">
                        <!-- Populated via Javascript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 💾 Save Location Success Modal -->
    <div class="modal fade" id="saveLocationSuccessModal" tabindex="-1" aria-hidden="true" style="z-index: 1065;">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-body p-4 text-center">
                    <div class="rounded-circle bg-light-blue text-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 54px; height: 54px; background: rgba(13, 148, 136, 0.08);">
                        <i class="bi bi-bookmark-star-fill fs-3 text-teal"></i>
                    </div>
                    <h5 class="fw-bold text-navy mb-2"><?= __("Na-save ang Lokasyon") ?></h5>
                    <p class="text-secondary small mb-4" style="line-height: 1.5;"><?= __("Lokasyon ay matagumpay na na-save sa iyong bookmarks!") ?></p>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-sm btn-primary w-50 py-2 fw-semibold rounded-3" style="background: var(--navy-900) !important; border: none; font-size: 0.8rem;" data-bs-dismiss="modal"><?= __("Okay") ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        const alsSchoolsData = <?= json_encode($als_schools) ?>;
        const trans = {
            directions: <?= json_encode(__("Directions")) ?>,
            resetLocation: <?= json_encode(__("Reset Location")) ?>,
            yourLocation: <?= json_encode(__("Iyong Lokasyon")) ?>,
            routeResetSuccess: <?= json_encode(__("Ruta at lokasyon ay matagumpay na na-reset!")) ?>,
            locationSaveSuccess: <?= json_encode(__("Lokasyon ay matagumpay na na-save sa iyong bookmarks!")) ?>,
            searchingNearby: <?= json_encode(__("Naghahanap ng mga katabing centers sa Culaba District...")) ?>,
            shareText: <?= json_encode(__("Mag-enroll na sa pinakamalapit na ALS Learning Center!")) ?>,
            copiedClipboard: <?= json_encode(__("Link copied to clipboard!")) ?>,
            openAE: <?= json_encode(__("Open for BLP, Elem & Sec")) ?>,
            district: <?= json_encode(__("Culaba, Biliran")) ?>,
            gpsError: <?= json_encode(__("Hindi makuha ang iyong kasalukuyang lokasyon. Pakisiguradong naka-on ang GPS o location services sa iyong browser.")) ?>,
            geoUnsupported: <?= json_encode(__("Hindi suportado ng iyong browser ang geolocation.")) ?>,
            gpsErrorNearest: <?= json_encode(__("Hindi makuha ang iyong lokasyon. Siguraduhing naka-on ang iyong location services.")) ?>,
            searchingNearest: <?= json_encode(__("Hinahanap...")) ?>,
            navalPortTerminal: <?= json_encode(__("Naval Port Terminal")) ?>,
            viewThisLocation: <?= json_encode(__("View this Location")) ?>,
            noNearbySchools: <?= json_encode(__("Walang mga eskwelahan na malapit sa loob ng 15km.")) ?>
        };
        let locMap = null;
        let selectedSchoolLat = null;
        let selectedSchoolLng = null;
        let locMarkers = [];
        let userLocationMarker = null;

        // Device Geolocation Prompt Workflow
        let pendingSchoolIndex = null;
        let pendingButton = null;
        let userDeviceLat = null;
        let userDeviceLng = null;

        function requestUserLocationBeforeMap(index, btn = null) {
            pendingSchoolIndex = index;
            pendingButton = btn;
            
            // Show the enable location modal prompt
            const locModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('enableLocationModal'));
            locModal.show();
        }

        function approveUserLocation() {
            const locModal = bootstrap.Modal.getInstance(document.getElementById('enableLocationModal'));
            if (locModal) locModal.hide();

            // Request browser geolocation
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(position => {
                    userDeviceLat = position.coords.latitude;
                    userDeviceLng = position.coords.longitude;
                    proceedToLocationModal();
                }, error => {
                    console.log("Device Geolocation Denied/Failed, falling back: ", error);
                    userDeviceLat = 11.5617;
                    userDeviceLng = 124.3934;
                    proceedToLocationModal();
                });
            } else {
                userDeviceLat = 11.5617;
                userDeviceLng = 124.3934;
                proceedToLocationModal();
            }
        }

        function declineUserLocation() {
            const locModal = bootstrap.Modal.getInstance(document.getElementById('enableLocationModal'));
            if (locModal) locModal.hide();
            
            // Fallback to Naval Port Terminal
            userDeviceLat = 11.5617;
            userDeviceLng = 124.3934;
            proceedToLocationModal();
        }

        function proceedToLocationModal() {
            if (pendingSchoolIndex !== null) {
                openLocationModal(pendingSchoolIndex);
                pendingSchoolIndex = null;
                pendingButton = null;
            } else if (pendingButton !== null) {
                findNearestCenter(pendingButton);
                pendingButton = null;
            } else {
                openLocationModal(0);
            }
        }

        function openSchoolImageModal() {
            const imgSrc = document.getElementById('locModalImg').src;
            document.getElementById('fullSchoolImg').src = imgSrc;
            const imageModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('schoolImageModal'));
            imageModal.show();
        }

        function updateModalLeftPanel(school) {
            document.getElementById('locModalName').innerText = school.name;
            document.getElementById('locModalDesc').innerText = school.desc;
            document.getElementById('locModalImg').src = school.img;
            document.getElementById('locModalSub').innerText = school.location || 'Culaba, Biliran';
            document.getElementById('locMapHeaderTitle').innerText = school.name + ', Culaba';
            hideRouteInfoCard();
        }

        function openLocationModal(index) {
            const school = alsSchoolsData[index];
            updateModalLeftPanel(school);
            selectedSchoolLat = school.lat;
            selectedSchoolLng = school.lng;
            
            // Render Saved Locations list in the left details column
            renderSavedLocations();

            // Show the Modal
            const locationModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('locationModal'));
            locationModal.show();
            
            // Open corresponding marker popup on map if initialized
            if (locMap && locMarkers[index]) {
                setTimeout(() => {
                    locMarkers[index].openPopup();
                }, 600); // Wait for modal slide transition
            }
        }

        document.getElementById('locationModal').addEventListener('shown.bs.modal', function () {
            if (!locMap) {
                // Initialize Map with Satellite Tiles (Esri World Imagery) without default zoom control
                locMap = L.map('locModalMap', { zoomControl: false }).setView([selectedSchoolLat, selectedSchoolLng], 18);
                
                // Position zoom controls in the bottom-left corner to match mockup
                L.control.zoom({ position: 'bottomleft' }).addTo(locMap);

                L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    attribution: 'Tiles &copy; Esri'
                }).addTo(locMap);

                // Custom Red Pin Icon (with bouncing-pin animation class)
                const redPinIcon = L.divIcon({
                    html: '<i class="bi bi-geo-alt-fill text-danger bouncing-pin" style="font-size: 2.5rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.8); display: inline-block;"></i>',
                    iconSize: [40, 40],
                    iconAnchor: [20, 40],
                    className: 'bg-transparent border-0',
                    tooltipAnchor: [20, -30]
                });

                // Add markers for all schools
                alsSchoolsData.forEach((s, idx) => {
                    const marker = L.marker([s.lat, s.lng], { icon: redPinIcon }).addTo(locMap);
                    locMarkers[idx] = marker;
                    
                    // Permanent tooltip for the school name
                    marker.bindTooltip(`<div style="font-size:0.85rem; font-weight:bold;" class="text-navy">${s.name}</div>`, {
                        permanent: true, 
                        direction: 'top',
                        className: 'bg-white border-0 shadow rounded-3 px-2 py-1',
                        offset: [-10, -30],
                        opacity: 0.95
                    }).openTooltip();

                    // Beautiful popup card to match mockup
                    const popupContent = `
                        <div class="p-1" style="min-width: 220px; font-family: inherit;">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="rounded-circle bg-teal text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px; flex-shrink: 0; background-color: var(--teal) !important;">
                                    <i class="bi bi-building fs-6"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-navy" style="font-size: 0.85rem; font-weight: 700;">${s.name}</h6>
                                    <p class="text-secondary mb-0 small" style="font-size: 0.75rem;">Culaba, Biliran</p>
                                </div>
                            </div>
                            <div class="d-flex">
                                <button class="btn btn-sm btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-1.5" style="font-size: 0.75rem; font-weight: 700; background: var(--navy-900) !important; border: none; border-radius: 8px;" onclick="getDirections()">
                                    <i class="bi bi-send-fill" style="font-size: 0.7rem;"></i> ${trans.directions}
                                </button>
                            </div>
                        </div>
                    `;
                    marker.bindPopup(popupContent, {
                        maxWidth: 280,
                        className: 'custom-leaflet-popup shadow-lg rounded-4'
                    });

                    marker.on('click', () => {
                        updateModalLeftPanel(s);
                        selectedSchoolLat = s.lat;
                        selectedSchoolLng = s.lng;
                        locMap.flyTo([s.lat, s.lng], 18, { animate: true, duration: 1 });
                    });
                });
            } else {
                locMap.invalidateSize();
                locMap.flyTo([selectedSchoolLat, selectedSchoolLng], 18, { animate: true, duration: 0.5 });
            }

            // Draw or update the user's permanent location marker on the map!
            drawOrUpdateUserLocationMarker();
            
            // Automatically open popup for selected marker
            const currentIdx = alsSchoolsData.findIndex(s => s.lat === selectedSchoolLat && s.lng === selectedSchoolLng);
            if (currentIdx !== -1 && locMarkers[currentIdx]) {
                setTimeout(() => {
                    locMarkers[currentIdx].openPopup();
                }, 500);
            }
        });

        document.getElementById('locationModal').addEventListener('hidden.bs.modal', function () {
            // Reset route & markers when modal is closed
            if (routeLine) {
                locMap.removeLayer(routeLine);
                routeLine = null;
            }
            if (userMarker) {
                locMap.removeLayer(userMarker);
                userMarker = null;
            }
            if (userLocationMarker) {
                locMap.removeLayer(userLocationMarker);
                userLocationMarker = null;
            }
            hideRouteInfoCard();
        });

        // Quick action helper functions
        let userMarker = null;
        let routeLine = null;

        function getDirections() {
            const schoolName = document.getElementById('locModalName').innerText;
            document.getElementById('directionsSchoolName').innerText = schoolName;
            const confirmModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('directionsConfirmModal'));
            confirmModal.show();
        }

        function confirmGetDirections() {
            const confirmModal = bootstrap.Modal.getInstance(document.getElementById('directionsConfirmModal'));
            if (confirmModal) confirmModal.hide();
            
            // Close Leaflet popup on map
            if (locMap) {
                locMap.closePopup();
            }

            calculateRoute();
        }

        function calculateRoute() {
            // Use device location if available, otherwise fallback to Naval Port Terminal
            const startLat = userDeviceLat || 11.5617;
            const startLng = userDeviceLng || 124.3934;

            // 1. Remove existing route & user markers if any
            if (routeLine) locMap.removeLayer(routeLine);
            if (userMarker) locMap.removeLayer(userMarker);
            if (userLocationMarker) {
                locMap.removeLayer(userLocationMarker);
                userLocationMarker = null;
            }

            // 2. Add marker for starting location
            const blueDotIcon = L.divIcon({
                html: '<div class="pulse-dot" style="width: 20px; height: 20px; background: #3b82f6; border: 3px solid #fff; border-radius: 50%; box-shadow: 0 0 10px rgba(59,130,246,0.8);"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10],
                className: 'bg-transparent border-0'
            });
            userMarker = L.marker([startLat, startLng], { icon: blueDotIcon }).addTo(locMap);
            
            // Tooltip specifies whether it's GPS or fallback starting point
            const startTooltip = (userDeviceLat && userDeviceLng && (userDeviceLat !== 11.5617 || userDeviceLng !== 124.3934)) ? trans.yourLocation : trans.navalPortTerminal;
            userMarker.bindTooltip(`<div class="fw-bold text-navy" style="font-size: 0.75rem;">${startTooltip}</div>`).openTooltip();

            // 3. Fetch driving route from OSRM Routing API (follows roads)
            const url = `https://router.project-osrm.org/route/v1/driving/${startLng},${startLat};${selectedSchoolLng},${selectedSchoolLat}?geometries=geojson`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.routes && data.routes.length > 0) {
                        // OSRM returns coordinates as [lng, lat], Leaflet polyline expects [lat, lng]
                        const routeCoords = data.routes[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);
                        
                        // Draw route line following the roads
                        routeLine = L.polyline(routeCoords, {
                            color: '#4f46e5',
                            weight: 5,
                            opacity: 0.85
                        }).addTo(locMap);

                        // Fit bounds to show the entire route
                        locMap.fitBounds(routeLine.getBounds(), { padding: [80, 80] });

                        // Route Info Calculation
                        const distanceMeters = data.routes[0].distance;
                        const distanceKm = Number((distanceMeters / 1000).toFixed(1));
                        
                        // Calculate realistic local travel durations for Biliran
                        const durationMin = Math.max(2, Math.round((distanceKm / 30) * 60)); // 30 km/h average normal speed
                        const durationTrafficMin = Math.max(3, Math.round((distanceKm / 20) * 60)); // 20 km/h heavy traffic speed

                        // Format ETA
                        const now = new Date();
                        now.setMinutes(now.getMinutes() + durationMin);
                        let hours = now.getHours();
                        const minutes = now.getMinutes().toString().padStart(2, '0');
                        const ampm = hours >= 12 ? 'PM' : 'AM';
                        hours = hours % 12;
                        hours = hours ? hours : 12;
                        const etaStr = `${hours}:${minutes} ${ampm}`;

                        // Update left panel elements
                        document.getElementById('routeDistanceLeft').innerText = `${distanceKm} km`;
                        document.getElementById('routeTimeNormalLeft').innerText = `${durationMin} mins`;
                        document.getElementById('routeTimeTrafficLeft').innerText = `${durationTrafficMin} mins`;
                        document.getElementById('routeETALeft').innerText = etaStr;

                        // Toggle Left Panel Layout
                        document.getElementById('locModalStandardDetails').classList.add('d-none');
                        document.getElementById('locModalTripDetails').classList.remove('d-none');
                    } else {
                        // Fallback to straight line if OSRM fails
                        drawFallbackLine(startLat, startLng);
                    }
                })
                .catch(err => {
                    console.error("OSRM Routing Error: ", err);
                    // Fallback to straight line if API fails
                    drawFallbackLine(startLat, startLng);
                });
        }

        // Fallback straight line polyline helper
        function drawFallbackLine(userLat, userLng) {
            routeLine = L.polyline([[userLat, userLng], [selectedSchoolLat, selectedSchoolLng]], {
                color: '#4f46e5',
                weight: 4,
                dashArray: '10, 10',
                opacity: 0.85
            }).addTo(locMap);
            
            const bounds = L.latLngBounds([[userLat, userLng], [selectedSchoolLat, selectedSchoolLng]]);
            locMap.fitBounds(bounds, { padding: [80, 80] });

            // Fallback Trip Info Estimation
            const distKm = Number(getDistance(userLat, userLng, selectedSchoolLat, selectedSchoolLng).toFixed(1));
            const durationMin = Math.max(2, Math.round((distKm / 30) * 60)); // Assumes 30 km/h average
            const durationTrafficMin = Math.max(3, Math.round((distKm / 20) * 60)); // Assumes 20 km/h average

            const now = new Date();
            now.setMinutes(now.getMinutes() + durationMin);
            let hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            const etaStr = `${hours}:${minutes} ${ampm}`;

            // Update left panel elements
            document.getElementById('routeDistanceLeft').innerText = `${distKm} km`;
            document.getElementById('routeTimeNormalLeft').innerText = `${durationMin} mins`;
            document.getElementById('routeTimeTrafficLeft').innerText = `${durationTrafficMin} mins`;
            document.getElementById('routeETALeft').innerText = etaStr;

            // Toggle Left Panel Layout
            document.getElementById('locModalStandardDetails').classList.add('d-none');
            document.getElementById('locModalTripDetails').classList.remove('d-none');
        }

        function hideRouteInfoCard() {
            // Restore standard details column visibility
            const stdDetails = document.getElementById('locModalStandardDetails');
            const tripDetails = document.getElementById('locModalTripDetails');
            if (stdDetails) stdDetails.classList.remove('d-none');
            if (tripDetails) tripDetails.classList.add('d-none');
        }

        function drawOrUpdateUserLocationMarker() {
            if (!locMap) return;

            const userLat = userDeviceLat || 11.5617;
            const userLng = userDeviceLng || 124.3934;

            // Custom Blue Pulse Marker representing User's Current Location
            const userPulseIcon = L.divIcon({
                html: '<div class="pulse-dot" style="width: 20px; height: 20px; background: #3b82f6; border: 3px solid #fff; border-radius: 50%; box-shadow: 0 0 10px rgba(59,130,246,0.8);"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10],
                className: 'bg-transparent border-0'
            });

            if (userLocationMarker) {
                userLocationMarker.setLatLng([userLat, userLng]);
            } else {
                userLocationMarker = L.marker([userLat, userLng], { icon: userPulseIcon }).addTo(locMap);
                userLocationMarker.bindTooltip(`<div class="fw-bold text-navy" style="font-size: 0.75rem;">${trans.yourLocation}</div>`, {
                    permanent: true,
                    direction: 'bottom',
                    className: 'bg-white border-0 shadow rounded-3 px-2 py-1',
                    offset: [0, 15]
                }).openTooltip();
            }
        }

        function resetLocation() {
            // Trigger Reset Confirmation Modal
            const confirmModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('resetConfirmModal'));
            confirmModal.show();
        }

        function confirmResetLocation() {
            // Hide the confirmation modal
            const confirmModal = bootstrap.Modal.getInstance(document.getElementById('resetConfirmModal'));
            if (confirmModal) confirmModal.hide();

            // Run actual reset logic
            if (routeLine) {
                locMap.removeLayer(routeLine);
                routeLine = null;
            }
            if (userMarker) {
                locMap.removeLayer(userMarker);
                userMarker = null;
            }
            drawOrUpdateUserLocationMarker();
            hideRouteInfoCard();
            if (selectedSchoolLat && selectedSchoolLng) {
                locMap.setView([selectedSchoolLat, selectedSchoolLng], 18);
                
                // Reopen the popup for the current school
                const currentIdx = alsSchoolsData.findIndex(s => s.lat === selectedSchoolLat && s.lng === selectedSchoolLng);
                if (currentIdx !== -1 && locMarkers[currentIdx]) {
                    locMarkers[currentIdx].openPopup();
                }
            }
        }

        // Saved Locations Feature
        function getSavedCenters() {
            try {
                return JSON.parse(localStorage.getItem('savedALSCenters')) || [];
            } catch (e) {
                return [];
            }
        }

        function saveLocation() {
            const currentIdx = alsSchoolsData.findIndex(s => s.lat === selectedSchoolLat && s.lng === selectedSchoolLng);
            if (currentIdx === -1) return;

            let saved = getSavedCenters();
            if (!saved.includes(currentIdx)) {
                saved.push(currentIdx);
                localStorage.setItem('savedALSCenters', JSON.stringify(saved));
                renderSavedLocations();
                
                // Show Bootstrap Modal instead of browser alert
                const successModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('saveLocationSuccessModal'));
                successModal.show();
            }
        }

        function removeSavedLocation(index) {
            let saved = getSavedCenters();
            saved = saved.filter(idx => idx !== index);
            localStorage.setItem('savedALSCenters', JSON.stringify(saved));
            renderSavedLocations();
        }

        function viewSavedLocation(index) {
            // Close Leaflet popup if open
            if (locMap) locMap.closePopup();
            
            const school = alsSchoolsData[index];
            updateModalLeftPanel(school);
            selectedSchoolLat = school.lat;
            selectedSchoolLng = school.lng;

            if (locMap) {
                locMap.setView([school.lat, school.lng], 18);
                if (locMarkers[index]) {
                    setTimeout(() => {
                        locMarkers[index].openPopup();
                    }, 400);
                }
            }
        }

        function renderSavedLocations() {
            const saved = getSavedCenters();
            const container = document.getElementById('locModalSavedContainer');
            const listEl = document.getElementById('locModalSavedList');
            
            if (!listEl) return;

            if (saved.length === 0) {
                container.classList.add('d-none');
                listEl.innerHTML = '';
                return;
            }

            container.classList.remove('d-none');
            
            let html = '';
            saved.forEach(index => {
                const school = alsSchoolsData[index];
                if (!school) return;
                html += `
                    <div class="d-flex align-items-center justify-content-between p-2.5 rounded-3 border bg-light shadow-sm" style="font-size: 0.8rem; background: #fafafa !important;">
                        <span class="fw-bold text-navy text-truncate me-2" style="max-width: 160px;">${school.name}</span>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-light py-1 px-2 border" onclick="viewSavedLocation(${index})" title="${trans.directions}">
                                <i class="bi bi-eye-fill text-primary" style="font-size: 0.8rem;"></i>
                            </button>
                            <button class="btn btn-sm btn-light py-1 px-2 border" onclick="removeSavedLocation(${index})" title="Remove">
                                <i class="bi bi-trash-fill text-danger" style="font-size: 0.8rem;"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            listEl.innerHTML = html;
        }

        // Nearby Centers Feature
        function viewNearbyCenters() {
            const confirmModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('nearbyConfirmModal'));
            confirmModal.show();
        }

        function confirmViewNearbyCenters() {
            const confirmModal = bootstrap.Modal.getInstance(document.getElementById('nearbyConfirmModal'));
            if (confirmModal) confirmModal.hide();

            const userLat = userDeviceLat || 11.5617;
            const userLng = userDeviceLng || 124.3934;

            let schoolList = alsSchoolsData.map((school, index) => {
                const dist = getDistance(userLat, userLng, school.lat, school.lng);
                return {
                    index: index,
                    name: school.name,
                    dist: dist
                };
            }).filter(item => item.dist <= 15); // Show only schools within 15km

            schoolList.sort((a, b) => a.dist - b.dist);

            let html = '';
            if (schoolList.length === 0) {
                html = `
                    <div class="text-center p-4 rounded-4 border bg-white shadow-sm">
                        <i class="bi bi-geo-alt text-secondary fs-2 mb-2 d-block"></i>
                        <span class="text-secondary small fw-bold">${trans.noNearbySchools}</span>
                    </div>
                `;
            } else {
                schoolList.forEach(item => {
                    html += `
                        <div class="d-flex align-items-center justify-content-between p-3 mb-2 rounded-4 border bg-white shadow-sm hover-lift" style="transition: all 0.2s;">
                            <div class="me-3 text-truncate" style="max-width: 260px;">
                                <h6 class="fw-bold text-navy mb-1" style="font-size: 0.88rem;">${item.name}</h6>
                                <span class="text-secondary small fw-semibold" style="font-size: 0.75rem;"><i class="bi bi-geo-fill text-primary"></i> ${item.dist.toFixed(1)} km away</span>
                            </div>
                            <button class="btn btn-sm btn-primary py-2 px-3 fw-bold" onclick="selectNearbyCenter(${item.index})" style="font-size: 0.72rem; border-radius: 8px; background: var(--navy-900) !important; border: none;">
                                <i class="bi bi-eye-fill"></i> ${trans.viewThisLocation}
                            </button>
                        </div>
                    `;
                });
            }

            document.getElementById('nearbyCentersList').innerHTML = html;

            const listModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('nearbyCentersModal'));
            listModal.show();
        }

        function selectNearbyCenter(index) {
            const listModal = bootstrap.Modal.getInstance(document.getElementById('nearbyCentersModal'));
            if (listModal) listModal.hide();

            viewSavedLocation(index);
        }

        function findNearestCenter(btn) {
            const originalText = btn.innerHTML;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${trans.searchingNearest}`;
            btn.disabled = true;

            const userLat = userDeviceLat || 11.5617;
            const userLng = userDeviceLng || 124.3934;

            let minIndex = 0;
            let minDistance = Infinity;

            alsSchoolsData.forEach((school, index) => {
                const dist = getDistance(userLat, userLng, school.lat, school.lng);
                if (dist < minDistance) {
                    minDistance = dist;
                    minIndex = index;
                }
            });

            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                openLocationModal(minIndex);
            }, 600);
        }
        function shareLocation() {
            if (navigator.share) {
                navigator.share({
                    title: document.getElementById('locModalName').innerText,
                    text: trans.shareText,
                    url: window.location.href
                }).catch(err => console.log(err));
            } else {
                navigator.clipboard.writeText(window.location.href);
                alert(trans.copiedClipboard);
            }
        }

        function getDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }
    </script>

<!-- ══════════════════════════════════════════════════════════════
     LEARNER PORTAL LOGIN MODAL
     ══════════════════════════════════════════════════════════════ -->
<div id="learnerLoginOverlay" style="
    display: none;
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(10, 15, 40, 0.72);
    backdrop-filter: blur(18px) saturate(1.6);
    -webkit-backdrop-filter: blur(18px) saturate(1.6);
    align-items: center; justify-content: center;
    padding: 20px;
    animation: learnerFadeIn .25s ease;
" onclick="closeLearnerLoginOnOverlay(event)">

    <div id="learnerLoginBox" style="
        background: rgba(255,255,255,0.97);
        border-radius: 24px;
        width: 100%; max-width: 420px;
        box-shadow: 0 32px 80px rgba(0,0,0,0.35), 0 0 0 1px rgba(255,255,255,0.5) inset;
        overflow: hidden;
        animation: learnerSlideUp .3s cubic-bezier(.22,1,.36,1);
        position: relative;
    ">
        <!-- Gradient header -->
        <div style="background: linear-gradient(135deg, #1e3a8a 0%, #7c3aed 60%, #2563eb 100%); padding: 32px 28px 28px; position:relative; overflow:hidden; text-align:center;">
            <!-- Decorative circles -->
            <div style="position:absolute;top:-30px;right:-30px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,0.07);"></div>
            <div style="position:absolute;bottom:-20px;left:-10px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.05);"></div>
            <!-- Close button -->
            <button onclick="closeLearnerLogin()" style="
                position:absolute;top:14px;right:14px;
                width:32px;height:32px;border-radius:50%;
                background:rgba(255,255,255,0.15);border:none;cursor:pointer;
                color:#fff;font-size:16px;display:flex;align-items:center;justify-content:center;
                transition:all .2s;
            " onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">✕</button>
            <!-- ALS Logo centered -->
            <div style="position:relative; display:inline-flex;align-items:center;justify-content:center;width:86px;height:86px;border-radius:50%;background:#ffffff;padding:6px;box-shadow:0 8px 24px rgba(0,0,0,0.22), 0 0 0 4px rgba(255,255,255,0.25);margin-bottom:14px;">
                <img src="assets/logo.svg" alt="ALS Logo" style="width:100%;height:100%;object-fit:contain;">
            </div>
            <!-- Welcome text -->
            <div id="learnerModalTitle" style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:900;color:#fff;line-height:1.2;margin-bottom:5px;">Login Your Account</div>
            <div style="font-size:12.5px;color:rgba(196,181,253,.95);font-weight:600;letter-spacing:.3px;">ALS — Culaba District, Biliran</div>
        </div>

        <!-- Form Body -->
        <div style="padding: 26px 28px 28px;">
            <!-- Alert box (hidden by default) -->
            <div id="learnerLoginAlert" style="
                display:none; padding:11px 14px; border-radius:10px;
                font-size:13px; font-weight:600; margin-bottom:16px;
                border-left: 4px solid; line-height:1.5;
            "></div>

            <!-- LOGIN VIEW -->
            <div id="learnerLoginView">
                <form id="learnerLoginForm" onsubmit="submitLearnerLogin(event)" autocomplete="on">

                    <!-- Email -->
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:11.5px;font-weight:800;color:#334155;text-transform:uppercase;letter-spacing:.6px;margin-bottom:7px;">
                            <i class="bi bi-envelope-fill" style="color:#2563eb;"></i> Gmail Address
                        </label>
                        <div style="position:relative;">
                            <input type="email" id="learnerEmail" name="email" placeholder="yourname@gmail.com"
                                autocomplete="email" required
                                style="
                                    width:100%;padding:12px 14px 12px 42px;
                                    border:1.5px solid #e2e8f0;border-radius:12px;
                                    font-size:14px;font-family:'Inter',sans-serif;color:#0f172a;
                                    outline:none;background:#f8fafc;
                                    transition:all .2s;
                                "
                                onfocus="this.style.borderColor='#2563eb';this.style.background='#fff';this.style.boxShadow='0 0 0 3px rgba(37,99,235,.12)'"
                                onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc';this.style.boxShadow='none'"
                            >
                            <i class="bi bi-envelope-fill" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;pointer-events:none;"></i>
                        </div>
                    </div>

                    <!-- Password -->
                    <div style="margin-bottom:20px;">
                        <label style="display:block;font-size:11.5px;font-weight:800;color:#334155;text-transform:uppercase;letter-spacing:.6px;margin-bottom:7px;">
                            <i class="bi bi-lock-fill" style="color:#7c3aed;"></i> Password
                        </label>
                        <div style="position:relative;">
                            <input type="password" id="learnerPassword" name="password" placeholder="Password from your acceptance email"
                                autocomplete="current-password" required
                                style="
                                    width:100%;padding:12px 42px 12px 42px;
                                    border:1.5px solid #e2e8f0;border-radius:12px;
                                    font-size:14px;font-family:'Inter',sans-serif;color:#0f172a;
                                    outline:none;background:#f8fafc;
                                    transition:all .2s;
                                "
                                onfocus="this.style.borderColor='#7c3aed';this.style.background='#fff';this.style.boxShadow='0 0 0 3px rgba(124,58,237,.12)'"
                                onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc';this.style.boxShadow='none'"
                            >
                            <i class="bi bi-lock-fill" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;pointer-events:none;"></i>
                            <button type="button" id="pwToggleBtn" onclick="toggleLearnerPw()" style="
                                position:absolute;right:12px;top:50%;transform:translateY(-50%);
                                background:none;border:none;cursor:pointer;color:#94a3b8;font-size:15px;
                                padding:2px 4px;transition:color .2s;
                            " title="Show/hide password">
                                <i class="bi bi-eye-fill" id="pwToggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Forgot Password Link -->
                    <div style="display:flex; justify-content:flex-end; align-items:center; margin-top:-10px; margin-bottom:18px;">
                        <button type="button" onclick="showLearnerForgotView()" style="
                            background:none; border:none; padding:0; cursor:pointer;
                            color:#7c3aed; font-size:12.5px; font-weight:700;
                            display:inline-flex; align-items:center; gap:4px; transition:all .2s;
                        " onmouseover="this.style.color='#6d28d9';this.style.textDecoration='underline'" onmouseout="this.style.color='#7c3aed';this.style.textDecoration='none'">
                            <i class="bi bi-key-fill" style="font-size:13px;"></i> Forgot Password?
                        </button>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="learnerLoginBtn" style="
                        width:100%;padding:13px;
                        background:linear-gradient(135deg, #1e3a8a 0%, #7c3aed 60%, #2563eb 100%);
                        color:#fff;border:none;border-radius:12px;
                        font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:800;
                        cursor:pointer;
                        box-shadow:0 6px 20px rgba(37,99,235,.35);
                        display:flex;align-items:center;justify-content:center;gap:9px;
                        transition:all .25s;
                    "
                    onmouseover="this.style.filter='brightness(1.1)';this.style.transform='translateY(-1px)'"
                    onmouseout="this.style.filter='none';this.style.transform='none'"
                    >
                        <i class="bi bi-box-arrow-in-right" id="learnerLoginBtnIcon"></i>
                        <span id="learnerLoginBtnText">Login to Learner Portal</span>
                    </button>
                </form>

                <!-- Info note -->
                <div style="margin-top:14px;padding:12px;background:#f0fdf4;border:1px solid rgba(22,163,74,.2);border-radius:10px;font-size:12px;color:#166534;line-height:1.5;">
                    <i class="bi bi-info-circle-fill" style="color:#16a34a;margin-right:5px;"></i>
                    Your login credentials were sent to your Gmail after your teacher accepted your application.
                </div>
            </div>

            <!-- FORGOT PASSWORD VIEW -->
            <div id="learnerForgotView" style="display:none;">
                <!-- Step 1: Send OTP -->
                <div id="learnerForgotStep1">
                    <p style="font-size:13px;color:#475569;margin-bottom:16px;line-height:1.5;">
                        Enter your registered Gmail address below. We will send a 6-digit recovery code to your Gmail inbox.
                    </p>
                    <form id="learnerForgotForm1" onsubmit="submitLearnerSendOtp(event)">
                        <div style="margin-bottom:18px;">
                            <label style="display:block;font-size:11.5px;font-weight:800;color:#334155;text-transform:uppercase;letter-spacing:.6px;margin-bottom:7px;">
                                <i class="bi bi-envelope-fill" style="color:#2563eb;"></i> Gmail Address
                            </label>
                            <div style="position:relative;">
                                <input type="email" id="learnerForgotEmail" name="email" placeholder="yourname@gmail.com" required
                                    style="width:100%;padding:12px 14px 12px 42px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:14px;font-family:'Inter',sans-serif;color:#0f172a;outline:none;background:#f8fafc;transition:all .2s;"
                                    onfocus="this.style.borderColor='#2563eb';this.style.background='#fff';this.style.boxShadow='0 0 0 3px rgba(37,99,235,.12)'"
                                    onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc';this.style.boxShadow='none'"
                                >
                                <i class="bi bi-envelope-fill" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;pointer-events:none;"></i>
                            </div>
                        </div>

                        <button type="submit" id="learnerForgotSendBtn" style="width:100%;padding:13px;background:linear-gradient(135deg, #1e3a8a 0%, #7c3aed 60%, #2563eb 100%);color:#fff;border:none;border-radius:12px;font-family:'Plus Jakarta Sans',sans-serif;font-size:14.5px;font-weight:800;cursor:pointer;box-shadow:0 6px 20px rgba(37,99,235,.35);display:flex;align-items:center;justify-content:center;gap:9px;transition:all .25s;">
                            <i class="bi bi-send-fill" id="learnerForgotSendIcon"></i>
                            <span id="learnerForgotSendText">Send Recovery Code</span>
                        </button>
                    </form>
                </div>

                <!-- Step 2: Enter & Verify 6-Digit OTP Code ONLY -->
                <div id="learnerForgotStep2" style="display:none;">
                    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 12px;font-size:12.5px;color:#1e40af;margin-bottom:16px;line-height:1.4;">
                        <i class="bi bi-info-circle-fill" style="margin-right:4px;"></i> Recovery code sent to <strong id="learnerOtpSentEmail">your email</strong>.
                    </div>
                    <form id="learnerForgotForm2" onsubmit="submitLearnerVerifyOtp(event)">
                        <!-- OTP Input -->
                        <div style="margin-bottom:18px;">
                            <label style="display:block;font-size:11.5px;font-weight:800;color:#334155;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;">
                                <i class="bi bi-shield-lock-fill" style="color:#16a34a;"></i> 6-Digit Code
                            </label>
                            <input type="text" id="learnerForgotOtp" maxlength="6" pattern="[0-9]{6}" placeholder="123456" required
                                style="width:100%;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:20px;font-weight:800;letter-spacing:8px;text-align:center;font-family:monospace;color:#0f172a;outline:none;background:#f8fafc;"
                                onfocus="this.style.borderColor='#16a34a';this.style.background='#fff'"
                                onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'"
                            >
                        </div>

                        <button type="submit" id="learnerVerifyBtn" style="width:100%;padding:13px;background:linear-gradient(135deg, #16a34a 0%, #15803d 100%);color:#fff;border:none;border-radius:12px;font-family:'Plus Jakarta Sans',sans-serif;font-size:14.5px;font-weight:800;cursor:pointer;box-shadow:0 6px 20px rgba(22,163,74,.35);display:flex;align-items:center;justify-content:center;gap:9px;transition:all .25s;">
                            <i class="bi bi-shield-check" id="learnerVerifyIcon"></i>
                            <span id="learnerVerifyText">Verify Recovery Code</span>
                        </button>
                    </form>
                </div>

                <!-- Step 3: Set New Password (UNLOCKED ONLY AFTER CODE IS VERIFIED) -->
                <div id="learnerForgotStep3" style="display:none;">
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:10px 12px;font-size:12.5px;color:#166534;margin-bottom:16px;line-height:1.4;">
                        <i class="bi bi-check-circle-fill" style="margin-right:4px;color:#16a34a;"></i> Code Verified! Enter your new password below.
                    </div>
                    <form id="learnerForgotForm3" onsubmit="submitLearnerResetPassword(event)">
                        <!-- New Password -->
                        <div style="margin-bottom:14px;">
                            <label style="display:block;font-size:11.5px;font-weight:800;color:#334155;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;">
                                <i class="bi bi-key-fill" style="color:#7c3aed;"></i> New Password
                            </label>
                            <input type="password" id="learnerNewPassword" minlength="8" placeholder="At least 8 characters" required
                                style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:14px;color:#0f172a;outline:none;background:#f8fafc;"
                            >
                        </div>

                        <!-- Confirm New Password -->
                        <div style="margin-bottom:18px;">
                            <label style="display:block;font-size:11.5px;font-weight:800;color:#334155;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;">
                                <i class="bi bi-check2-circle" style="color:#7c3aed;"></i> Confirm New Password
                            </label>
                            <input type="password" id="learnerConfirmPassword" minlength="8" placeholder="Re-enter new password" required
                                style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:14px;color:#0f172a;outline:none;background:#f8fafc;"
                            >
                        </div>

                        <button type="submit" id="learnerResetBtn" style="width:100%;padding:13px;background:linear-gradient(135deg, #1e3a8a 0%, #7c3aed 60%, #2563eb 100%);color:#fff;border:none;border-radius:12px;font-family:'Plus Jakarta Sans',sans-serif;font-size:14.5px;font-weight:800;cursor:pointer;box-shadow:0 6px 20px rgba(37,99,235,.35);display:flex;align-items:center;justify-content:center;gap:9px;transition:all .25s;">
                            <i class="bi bi-check-circle-fill" id="learnerResetIcon"></i>
                            <span id="learnerResetText">Save New Password</span>
                        </button>
                    </form>
                </div>

                <!-- Footer Back to Login button -->
                <div style="margin-top:16px;text-align:center;">
                    <button type="button" onclick="showLearnerLoginView()" style="background:none;border:none;color:#475569;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:color .2s;" onmouseover="this.style.color='#1e3a8a'" onmouseout="this.style.color='#475569'">
                        <i class="bi bi-arrow-left"></i> Back to Learner Login
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Hide browser native password reveal/clear icons so only custom eye icon shows */
input[type="password"]::-ms-reveal,
input[type="password"]::-ms-clear,
input[type="password"]::-webkit-contacts-auto-fill-button,
input[type="password"]::-webkit-credentials-auto-fill-button {
    display: none !important;
    width: 0 !important;
    height: 0 !important;
    pointer-events: none !important;
}
@keyframes learnerFadeIn { from { opacity:0 } to { opacity:1 } }
@keyframes learnerSlideUp { from { opacity:0; transform:translateY(24px) scale(.97) } to { opacity:1; transform:translateY(0) scale(1) } }
</style>

<script>
// ── LEARNER MODAL FUNCTIONS ──
function openLearnerLogin() {
    const overlay = document.getElementById('learnerLoginOverlay');
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('learnerEmail')?.focus(), 150);
}
function closeLearnerLogin() {
    const overlay = document.getElementById('learnerLoginOverlay');
    overlay.style.display = 'none';
    document.body.style.overflow = '';
    clearLearnerAlert();
    document.getElementById('learnerLoginForm')?.reset();
    document.getElementById('learnerForgotForm1')?.reset();
    document.getElementById('learnerForgotForm2')?.reset();
    document.getElementById('learnerForgotForm3')?.reset();
    showLearnerLoginView();
}
function closeLearnerLoginOnOverlay(e) {
    if (e.target === document.getElementById('learnerLoginOverlay')) closeLearnerLogin();
}
function clearLearnerAlert() {
    const a = document.getElementById('learnerLoginAlert');
    if (a) { a.style.display = 'none'; a.innerHTML = ''; }
}
function showLearnerAlert(msg, type = 'error') {
    const a = document.getElementById('learnerLoginAlert');
    if (!a) return;
    const isError = type === 'error';
    a.style.display = 'block';
    a.style.background = isError ? '#fef2f2' : '#f0fdf4';
    a.style.color       = isError ? '#991b1b' : '#166534';
    a.style.borderColor = isError ? '#dc2626' : '#16a34a';
    a.innerHTML = (isError ? '❌ ' : '✅ ') + msg;
    a.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function showLearnerForgotView() {
    clearLearnerAlert();
    const loginView  = document.getElementById('learnerLoginView');
    const forgotView = document.getElementById('learnerForgotView');
    const title      = document.getElementById('learnerModalTitle');
    
    if (loginView)  loginView.style.display  = 'none';
    if (forgotView) forgotView.style.display = 'block';
    if (title)      title.textContent = 'Forgot Password';

    // Copy email if already typed in login
    const emailVal = document.getElementById('learnerEmail')?.value;
    if (emailVal && document.getElementById('learnerForgotEmail')) {
        document.getElementById('learnerForgotEmail').value = emailVal;
    }

    // Default to Step 1
    const step1 = document.getElementById('learnerForgotStep1');
    const step2 = document.getElementById('learnerForgotStep2');
    const step3 = document.getElementById('learnerForgotStep3');
    if (step1) step1.style.display = 'block';
    if (step2) step2.style.display = 'none';
    if (step3) step3.style.display = 'none';

    setTimeout(() => document.getElementById('learnerForgotEmail')?.focus(), 150);
}

function showLearnerLoginView() {
    clearLearnerAlert();
    const loginView  = document.getElementById('learnerLoginView');
    const forgotView = document.getElementById('learnerForgotView');
    const title      = document.getElementById('learnerModalTitle');

    if (forgotView) forgotView.style.display = 'none';
    if (loginView)  loginView.style.display  = 'block';
    if (title)      title.textContent = 'Login Your Account';

    // Reset forgot steps
    const step1 = document.getElementById('learnerForgotStep1');
    const step2 = document.getElementById('learnerForgotStep2');
    const step3 = document.getElementById('learnerForgotStep3');
    if (step1) step1.style.display = 'block';
    if (step2) step2.style.display = 'none';
    if (step3) step3.style.display = 'none';

    setTimeout(() => document.getElementById('learnerEmail')?.focus(), 150);
}

function toggleLearnerPw() {
    const inp  = document.getElementById('learnerPassword');
    const icon = document.getElementById('pwToggleIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'bi bi-eye-slash-fill';
    } else {
        inp.type = 'password';
        icon.className = 'bi bi-eye-fill';
    }
}
// Close on ESC
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && document.getElementById('learnerLoginOverlay').style.display === 'flex') {
        closeLearnerLogin();
    }
});

// ── AJAX SUBMIT LOGIN ──
async function submitLearnerLogin(e) {
    e.preventDefault();
    clearLearnerAlert();
    const btn     = document.getElementById('learnerLoginBtn');
    const btnText = document.getElementById('learnerLoginBtnText');
    const btnIcon = document.getElementById('learnerLoginBtnIcon');
    btn.disabled   = true;
    btnText.textContent = 'Logging in...';
    btnIcon.className   = 'bi bi-hourglass-split';

    try {
        const fd = new FormData(document.getElementById('learnerLoginForm'));
        const res = await fetch('learner_login.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            showLearnerAlert(data.message || 'Login successful! Redirecting...', 'success');
            btnText.textContent = 'Redirecting...';
            btnIcon.className   = 'bi bi-check-circle-fill';
            btn.style.background = 'linear-gradient(135deg, #16a34a, #15803d)';
            setTimeout(() => { window.location.href = data.redirect || 'learner/dashboard.php'; }, 1200);
        } else {
            showLearnerAlert(data.message || 'Login failed. Please try again.');
            btn.disabled   = false;
            btnText.textContent = 'Login to Learner Portal';
            btnIcon.className   = 'bi bi-box-arrow-in-right';
        }
    } catch (err) {
        showLearnerAlert('Network error. Please check your connection and try again.');
        btn.disabled   = false;
        btnText.textContent = 'Login to Learner Portal';
        btnIcon.className   = 'bi bi-box-arrow-in-right';
    }
}

// ── AJAX SUBMIT FORGOT PASSWORD STEP 1: SEND OTP ──
async function submitLearnerSendOtp(e) {
    e.preventDefault();
    clearLearnerAlert();
    const btn   = document.getElementById('learnerForgotSendBtn');
    const text  = document.getElementById('learnerForgotSendText');
    const icon  = document.getElementById('learnerForgotSendIcon');
    const email = document.getElementById('learnerForgotEmail')?.value.trim();

    if (!email) {
        showLearnerAlert('Please enter your Gmail address.');
        return;
    }

    btn.disabled     = true;
    text.textContent = 'Sending Code to Gmail...';
    icon.className   = 'bi bi-hourglass-split';

    try {
        const fd = new FormData();
        fd.append('action', 'send_otp');
        fd.append('email', email);

        const res  = await fetch('learner_forgot_password.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            showLearnerAlert(data.message, 'success');
            const sentEmailEl = document.getElementById('learnerOtpSentEmail');
            if (sentEmailEl) sentEmailEl.textContent = email;
            
            document.getElementById('learnerForgotStep1').style.display = 'none';
            document.getElementById('learnerForgotStep2').style.display = 'block';
            document.getElementById('learnerForgotStep3').style.display = 'none';
            setTimeout(() => document.getElementById('learnerForgotOtp')?.focus(), 150);
        } else {
            showLearnerAlert(data.message || 'Failed to send recovery code.');
        }
    } catch (err) {
        showLearnerAlert('Network error. Please check your connection and try again.');
    } finally {
        btn.disabled     = false;
        text.textContent = 'Send Recovery Code';
        icon.className   = 'bi bi-send-fill';
    }
}

// ── AJAX SUBMIT FORGOT PASSWORD STEP 2: VERIFY RECOVERY OTP CODE ONLY ──
async function submitLearnerVerifyOtp(e) {
    e.preventDefault();
    clearLearnerAlert();

    const btn   = document.getElementById('learnerVerifyBtn');
    const text  = document.getElementById('learnerVerifyText');
    const icon  = document.getElementById('learnerVerifyIcon');
    const email = document.getElementById('learnerForgotEmail')?.value.trim();
    const otp   = document.getElementById('learnerForgotOtp')?.value.trim();

    if (!otp || otp.length !== 6) {
        showLearnerAlert('Please enter the complete 6-digit recovery code.');
        return;
    }

    btn.disabled     = true;
    text.textContent = 'Verifying Code...';
    icon.className   = 'bi bi-hourglass-split';

    try {
        const fd = new FormData();
        fd.append('action', 'verify_otp');
        fd.append('email', email);
        fd.append('otp_code', otp);

        const res  = await fetch('learner_forgot_password.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            showLearnerAlert(data.message, 'success');
            // Hide Step 2 and unlock Step 3 (New Password fields!)
            document.getElementById('learnerForgotStep2').style.display = 'none';
            document.getElementById('learnerForgotStep3').style.display = 'block';
            setTimeout(() => document.getElementById('learnerNewPassword')?.focus(), 150);
        } else {
            showLearnerAlert(data.message || 'Invalid or expired recovery code.');
        }
    } catch (err) {
        showLearnerAlert('Network error. Please check your connection and try again.');
    } finally {
        btn.disabled     = false;
        text.textContent = 'Verify Recovery Code';
        icon.className   = 'bi bi-shield-check';
    }
}

// ── AJAX SUBMIT FORGOT PASSWORD STEP 2: RESET PASSWORD ──
async function submitLearnerResetPassword(e) {
    e.preventDefault();
    clearLearnerAlert();

    const btn  = document.getElementById('learnerResetBtn');
    const text = document.getElementById('learnerResetText');
    const icon = document.getElementById('learnerResetIcon');

    const email           = document.getElementById('learnerForgotEmail')?.value.trim();
    const otp             = document.getElementById('learnerForgotOtp')?.value.trim();
    const newPassword     = document.getElementById('learnerNewPassword')?.value;
    const confirmPassword = document.getElementById('learnerConfirmPassword')?.value;

    if (!otp || otp.length !== 6) {
        showLearnerAlert('Please enter the 6-digit verification code.');
        return;
    }
    if (newPassword !== confirmPassword) {
        showLearnerAlert('Passwords do not match. Please re-type your password.');
        return;
    }
    if (newPassword.length < 8) {
        showLearnerAlert('Password must be at least 8 characters.');
        return;
    }

    btn.disabled     = true;
    text.textContent = 'Updating Password...';
    icon.className   = 'bi bi-hourglass-split';

    try {
        const fd = new FormData();
        fd.append('action', 'reset_password');
        fd.append('email', email);
        fd.append('otp_code', otp);
        fd.append('new_password', newPassword);
        fd.append('confirm_password', confirmPassword);

        const res  = await fetch('learner_forgot_password.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            showLearnerAlert(data.message, 'success');
            if (document.getElementById('learnerEmail')) {
                document.getElementById('learnerEmail').value = email;
            }
            setTimeout(() => {
                showLearnerLoginView();
                showLearnerAlert('Password updated successfully! Please log in with your new password.', 'success');
            }, 1800);
        } else {
            showLearnerAlert(data.message || 'Failed to reset password.');
        }
    } catch (err) {
        showLearnerAlert('Network error. Please check your connection and try again.');
    } finally {
        btn.disabled     = false;
        text.textContent = 'Save New Password';
        icon.className   = 'bi bi-check-circle-fill';
    }
}

// Auto-open learner login modal if returning from reset password or URL flag
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('open_learner_login') === '1' || urlParams.get('login') === 'learner') {
        openLearnerLogin();
        if (urlParams.get('reset') === 'success') {
            showLearnerAlert('Password updated successfully! Please log in with your new password.', 'success');
        }
    }
});
</script>

</body>
</html>