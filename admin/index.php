<?php
// index.php - Main entry point and landing page
require_once 'config.php';

// Check if user is logged in and redirect if needed
if (isLoggedIn()) {
    // Redirect to dashboard
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MZ Income - আয় করুন ঘরে বসে</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom landing page styles -->
    <style>
        :root {
            --yellow: #FFC107;
            --dark-yellow: #FFA000;
            --black: #212121;
            --white: #FFFFFF;
            --off-white: #F5F5F5;
            --green: #4CAF50;
            --dark-green: #388E3C;
        }
        
        body {
            font-family: 'Hind Siliguri', Arial, sans-serif;
            color: var(--black);
            background-color: var(--white);
            overflow-x: hidden;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--black) 0%, #424242 100%);
            color: var(--white);
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('assets/img/pattern.png');
            opacity: 0.1;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .hero-cta {
            margin-top: 2rem;
        }
        
        .btn-yellow {
            background-color: var(--yellow);
            color: var(--black);
            font-weight: bold;
            padding: 12px 30px;
            border-radius: 30px;
            border: none;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-yellow:hover {
            background-color: var(--dark-yellow);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline-white {
            color: var(--white);
            border: 2px solid var(--white);
            background-color: transparent;
            padding: 10px 30px;
            border-radius: 30px;
            font-weight: bold;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-outline-white:hover {
            background-color: var(--white);
            color: var(--black);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .hero-image img {
            max-width: 100%;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        
        /* Features Section */
        .features-section {
            padding: 80px 0;
            background-color: var(--off-white);
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
            position: relative;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--black);
            margin-bottom: 20px;
        }
        
        .section-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background-color: var(--yellow);
            margin: 0 auto;
        }
        
        .feature-card {
            background-color: var(--white);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--yellow);
            color: var(--black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--black);
        }
        
        .feature-card p {
            color: #555;
            font-size: 1rem;
            line-height: 1.6;
        }
        
        /* Packages Section */
        .packages-section {
            padding: 80px 0;
            background-color: var(--white);
        }
        
        .package-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }
        
        .package-header {
            padding: 25px;
            text-align: center;
        }
        
        .package-name {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .package-price {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        
        .package-duration {
            font-size: 1rem;
            opacity: 0.8;
        }
        
        .package-body {
            padding: 25px;
            background-color: var(--white);
        }
        
        .package-features {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .package-features li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
        }
        
        .package-features li:last-child {
            border-bottom: none;
        }
        
        .package-features li i {
            color: var(--green);
            margin-right: 10px;
        }
        
        .package-footer {
            padding: 25px;
            text-align: center;
            background-color: var(--off-white);
        }
        
        .basic-package .package-header {
            background-color: var(--black);
            color: var(--white);
        }
        
        .gold-package .package-header {
            background: linear-gradient(135deg, #FFD700 0%, #FFA000 100%);
            color: var(--black);
        }
        
        .diamond-package .package-header {
            background: linear-gradient(135deg, #B9F2FF 0%, #8BCCDE 100%);
            color: var(--black);
        }
        
        .btn-green {
            background-color: var(--green);
            color: var(--white);
            font-weight: bold;
            padding: 12px 30px;
            border-radius: 30px;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-green:hover {
            background-color: var(--dark-green);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        /* How It Works Section */
        .how-it-works-section {
            padding: 80px 0;
            background-color: var(--off-white);
        }
        
        .step-card {
            text-align: center;
            padding: 20px;
            position: relative;
        }
        
        .step-number {
            width: 50px;
            height: 50px;
            background-color: var(--yellow);
            color: var(--black);
            font-size: 1.5rem;
            font-weight: 700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .step-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .step-description {
            color: #555;
            font-size: 1rem;
        }
        
        .step-connector {
            position: absolute;
            top: 45px;
            right: -30px;
            width: 60px;
            height: 2px;
            background-color: var(--yellow);
            z-index: 1;
        }
        
        /* Testimonials Section */
        .testimonials-section {
            padding: 80px 0;
            background-color: var(--white);
            position: relative;
            overflow: hidden;
        }
        
        .testimonial-card {
            background-color: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin: 20px 15px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .testimonial-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .testimonial-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            border: 4px solid var(--yellow);
        }
        
        .testimonial-text {
            font-style: italic;
            margin-bottom: 20px;
            color: #555;
            position: relative;
        }
        
        .testimonial-text::before,
        .testimonial-text::after {
            content: '"';
            font-size: 3rem;
            color: var(--yellow);
            opacity: 0.3;
            position: absolute;
        }
        
        .testimonial-text::before {
            top: -20px;
            left: -10px;
        }
        
        .testimonial-text::after {
            bottom: -40px;
            right: -10px;
        }
        
        .testimonial-name {
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .testimonial-role {
            color: var(--yellow);
            font-size: 0.9rem;
        }
        
        /* CTA Section */
        .cta-section {
            padding: 100px 0;
            background: linear-gradient(135deg, var(--green) 0%, var(--dark-green) 100%);
            color: var(--white);
            text-align: center;
            position: relative;
        }
        
        .cta-section::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('assets/img/pattern.png');
            opacity: 0.1;
        }
        
        .cta-content {
            position: relative;
            z-index: 1;
        }
        
        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .cta-subtitle {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        /* Footer */
        .footer {
            background-color: var(--black);
            color: var(--white);
            padding: 60px 0 20px;
        }
        
        .footer-logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--white);
        }
        
        .footer-description {
            margin-bottom: 20px;
            color: rgba(255,255,255,0.7);
        }
        
        .footer-social {
            margin-bottom: 30px;
        }
        
        .social-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.1);
            color: var(--white);
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .social-icon:hover {
            background-color: var(--yellow);
            color: var(--black);
            transform: translateY(-3px);
        }
        
        .footer-links h5 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--white);
            font-weight: 600;
        }
        
        .footer-links ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links ul li {
            margin-bottom: 10px;
        }
        
        .footer-links ul li a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer-links ul li a:hover {
            color: var(--yellow);
            padding-left: 5px;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.5);
        }
        
        /* Floating Elements */
        .floating-element {
            position: absolute;
            background-color: var(--yellow);
            border-radius: 50%;
            opacity: 0.1;
            z-index: 0;
        }
        
        .float-1 {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 5%;
            animation: float-animation 8s infinite ease-in-out;
        }
        
        .float-2 {
            width: 150px;
            height: 150px;
            bottom: 10%;
            right: 5%;
            animation: float-animation 12s infinite ease-in-out;
        }
        
        .float-3 {
            width: 80px;
            height: 80px;
            top: 40%;
            right: 10%;
            animation: float-animation 10s infinite ease-in-out;
        }
        
        @keyframes float-animation {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }
        
        /* Responsive Adjustments */
        @media (max-width: 991px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .hero-image {
                margin-top: 30px;
            }
            
            .step-connector {
                display: none;
            }
        }
        
        @media (max-width: 767px) {
            .hero-section {
                padding: 60px 0;
                text-align: center;
            }
            
            .hero-cta .btn {
                display: block;
                width: 100%;
                margin-bottom: 10px;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .feature-card,
            .package-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header/Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span style="color: var(--yellow);">MZ</span> Income
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">ফিচার্স</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#packages">প্যাকেজ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">কিভাবে কাজ করে</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#testimonials">সাফল্যের গল্প</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="login.php" class="btn btn-outline-light me-2">লগইন</a>
                    <a href="register.php" class="btn btn-yellow">রেজিস্ট্রেশন</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="floating-element float-1"></div>
        <div class="floating-element float-2"></div>
        <div class="floating-element float-3"></div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="hero-title">ঘরে বসে আয় করুন <span style="color: var(--yellow);">MZ Income</span> এর সাথে</h1>
                    <p class="hero-subtitle">রেফারেল সিস্টেমের মাধ্যমে সহজেই আয় করুন। কোন বিনিয়োগ ছাড়াই শুরু করুন এবং প্যাকেজ কিনে বাড়িয়ে নিন আপনার আয়!</p>
                    <div class="hero-cta">
                        <a href="register.php" class="btn btn-yellow me-3 mb-2 mb-md-0">শুরু করুন</a>
                        <a href="#how-it-works" class="btn btn-outline-white">কিভাবে কাজ করে</a>
                    </div>
                </div>
                <div class="col-lg-6 hero-image">
                    <img src="assets/img/hero-image.png" alt="MZ Income Illustration">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-title">
                <h2>আমাদের ফিচার্স</h2>
                <p>MZ Income এর সাথে আয় করা এখন আরও সহজ</p>
            </div>
            <div class="row">
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>রেফারেল সিস্টেম</h3>
                        <p>আপনার রেফারেল লিঙ্ক শেয়ার করে আমন্ত্রণ করুন নতুন ব্যবহারকারীদের এবং আয় করুন কমিশন।</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <h3>ইনস্ট্যান্ট পেমেন্ট</h3>
                        <p>আপনার আয় সরাসরি আপনার ওয়ালেটে জমা হবে এবং যেকোনো সময় উইথড্র করতে পারবেন।</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-crown"></i>
                        </div>
                        <h3>প্রিমিয়াম প্যাকেজ</h3>
                        <p>আমাদের প্রিমিয়াম প্যাকেজ কিনে পান বেশি কমিশন এবং আকর্ষণীয় সুবিধা।</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>মোবাইল ফ্রেন্ডলি</h3>
                        <p>যেকোনো ডিভাইস থেকে আমাদের প্লাটফর্ম ব্যবহার করুন, যেকোনো সময়, যেকোনো জায়গা থেকে।</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Packages Section -->
    <section class="packages-section" id="packages">
        <div class="container">
            <div class="section-title">
                <h2>আমাদের প্যাকেজ</h2>
                <p>আপনার আয় বাড়ানোর জন্য বেছে নিন উপযুক্ত প্যাকেজ</p>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="package-card basic-package">
                        <div class="package-header">
                            <h3 class="package-name">বেসিক</h3>
                            <p class="package-price">৳0</p>
                            <p class="package-duration">ফ্রি রেজিস্ট্রেশন</p>
                        </div>
                        <div class="package-body">
                            <ul class="package-features">
                                <li><i class="fas fa-check-circle"></i> ১৮% রেফারেল কমিশন</li>
                                <li><i class="fas fa-check-circle"></i> ফ্রি রেজিস্ট্রেশন</li>
                                <li><i class="fas fa-check-circle"></i> আনলিমিটেড রেফারেল</li>
                                <li><i class="fas fa-check-circle"></i> ইনস্ট্যান্ট পেমেন্ট</li>
                                <li><i class="fas fa-times-circle text-danger"></i> প্যাকেজ মূল্য ফেরত নেই</li>
                            </ul>
                        </div>
                        <div class="package-footer">
                            <a href="register.php" class="btn btn-green">রেজিস্ট্রেশন করুন</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="package-card gold-package">
                        <div class="package-header">
                            <h3 class="package-name">গোল্ড</h3>
                            <p class="package-price">৳1,999</p>
                            <p class="package-duration">৪০ দিন মেয়াদ</p>
                        </div>
                        <div class="package-body">
                            <ul class="package-features">
                                <li><i class="fas fa-check-circle"></i> ৩০% রেফারেল কমিশন</li>
                                <li><i class="fas fa-check-circle"></i> ৪০ দিন মেয়াদ</li>
                                <li><i class="fas fa-check-circle"></i> আনলিমিটেড রেফারেল</li>
                                <li><i class="fas fa-check-circle"></i> ইনস্ট্যান্ট পেমেন্ট</li>
                                <li><i class="fas fa-check-circle"></i> ১০০% প্যাকেজ মূল্য ফেরত</li>
                            </ul>
                        </div>
                        <div class="package-footer">
                            <a href="register.php" class="btn btn-green">রেজিস্ট্রেশন করুন</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="package-card diamond-package">
                        <div class="package-header">
                            <h3 class="package-name">ডায়মন্ড</h3>
                            <p class="package-price">৳2,999</p>
                            <p class="package-duration">৬০ দিন মেয়াদ</p>
                        </div>
                        <div class="package-body">
                            <ul class="package-features">
                                <li><i class="fas fa-check-circle"></i> ৫০% রেফারেল কমিশন</li>
                                <li><i class="fas fa-check-circle"></i> ৬০ দিন মেয়াদ</li>
                                <li><i class="fas fa-check-circle"></i> আনলিমিটেড রেফারেল</li>
                                <li><i class="fas fa-check-circle"></i> ইনস্ট্যান্ট পেমেন্ট</li>
                                <li><i class="fas fa-check-circle"></i> ১০০% প্যাকেজ মূল্য ফেরত</li>
                            </ul>
                        </div>
                        <div class="package-footer">
                            <a href="register.php" class="btn btn-green">রেজিস্ট্রেশন করুন</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works-section" id="how-it-works">
        <div class="container">
            <div class="section-title">
                <h2>কিভাবে কাজ করে</h2>
                <p>MZ Income দিয়ে আয় করার সহজ পদ্ধতি</p>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h4 class="step-title">রেজিস্ট্রেশন করুন</h4>
                        <p class="step-description">ফ্রি রেজিস্ট্রেশন করুন আমাদের প্লাটফর্মে</p>
                        <div class="step-connector d-none d-md-block"></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h4 class="step-title">রেফারেল লিঙ্ক শেয়ার করুন</h4>
                        <p class="step-description">আপনার রেফারেল লিঙ্ক শেয়ার করুন সোশ্যাল মিডিয়ায়</p>
                        <div class="step-connector d-none d-md-block"></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h4 class="step-title">প্যাকেজ কিনুন</h4>
                        <p class="step-description">বেশি কমিশন পেতে প্যাকেজ কিনুন</p>
                        <div class="step-connector d-none d-md-block"></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <h4 class="step-title">আয় করুন</h4>
                        <p class="step-description">আপনার কমিশন সরাসরি আপনার ওয়ালেটে পান</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section" id="testimonials">
        <div class="container">
            <div class="section-title">
                <h2>সাফল্যের গল্প</h2>
                <p>আমাদের সফল ব্যবহারকারীদের অভিজ্ঞতা</p>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="assets/img/testimonial-1.jpg" alt="Testimonial 1" class="testimonial-image">
                        <p class="testimonial-text">MZ Income এর সাথে আমি মাসে ১০,০০০+ টাকা আয় করছি। সত্যিই অসাধারণ একটি প্লাটফর্ম।</p>
                        <h5 class="testimonial-name">রাকিব হাসান</h5>
                        <p class="testimonial-role">ডায়মন্ড সদস্য</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="assets/img/testimonial-2.jpg" alt="Testimonial 2" class="testimonial-image">
                        <p class="testimonial-text">পড়াশোনার পাশাপাশি আয় করার দারুণ একটি উপায়। গোল্ড প্যাকেজ থেকে এখন পর্যন্ত ৫০,০০০+ টাকা আয় করেছি।</p>
                        <h5 class="testimonial-name">সাবরিনা আক্তার</h5>
                        <p class="testimonial-role">গোল্ড সদস্য</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="assets/img/testimonial-3.jpg" alt="Testimonial 3" class="testimonial-image">
                        <p class="testimonial-text">বিনা ইনভেস্টমেন্টে শুরু করেছিলাম, এখন ডায়মন্ড সদস্য। MZ Income আমার জীবন বদলে দিয়েছে।</p>
                        <h5 class="testimonial-name">জামিল হোসেন</h5>
                        <p class="testimonial-role">ডায়মন্ড সদস্য</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 cta-content">
                    <h2 class="cta-title">আজই শুরু করুন আপনার আয়ের যাত্রা</h2>
                    <p class="cta-subtitle">ফ্রি রেজিস্ট্রেশন করে শুরু করুন। কোন রিস্ক নেই!</p>
                    <a href="register.php" class="btn btn-yellow btn-lg">রেজিস্ট্রেশন করুন</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h3 class="footer-logo">MZ Income</h3>
                    <p class="footer-description">রেফারেল সিস্টেমের মাধ্যমে ঘরে বসে আয় করার অনন্য প্লাটফর্ম।</p>
                    <div class="footer-social">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    <div class="footer-links">
                        <h5>কুইক লিংক</h5>
                        <ul>
                            <li><a href="#features">ফিচার্স</a></li>
                            <li><a href="#packages">প্যাকেজ</a></li>
                            <li><a href="#how-it-works">কিভাবে কাজ করে</a></li>
                            <li><a href="#testimonials">সাফল্যের গল্প</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 mb-4 mb-md-0">
                    <div class="footer-links">
                        <h5>সাপোর্ট</h5>
                        <ul>
                            <li><a href="#">হেল্প সেন্টার</a></li>
                            <li><a href="#">এফএকিউ</a></li>
                            <li><a href="#">টার্মস অফ সার্ভিস</a></li>
                            <li><a href="#">প্রাইভেসি পলিসি</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="footer-links">
                        <h5>যোগাযোগ</h5>
                        <ul>
                            <li><i class="fas fa-map-marker-alt me-2"></i> ঢাকা, বাংলাদেশ</li>
                            <li><i class="fas fa-phone me-2"></i> +880 1712-345678</li>
                            <li><i class="fas fa-envelope me-2"></i> info@mzincome.com</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> MZ Income. সর্বসত্ব সংরক্ষিত।</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Add active class to nav items when scrolling
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section');
            const navLinks = document.querySelectorAll('.navbar-nav a');
            
            let current = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                
                if (window.pageYOffset >= sectionTop - 100) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>