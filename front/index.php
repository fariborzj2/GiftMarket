<?php
$dataJson = file_get_contents('js/data.json');
$appData = json_decode($dataJson, true);

$pricingData = $appData['pricingData'];
$countryNames = $appData['countryNames'];
$exchangeRates = $appData['exchangeRates'];
$faqs = $appData['faqs'];
$testimonials = $appData['testimonials'];

// Default view for SSR
$defaultBrand = 'apple';
$defaultCountry = 'uae';
$defaultPackSize = 100;
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UAE.GIFT | Official Gift Card Distributor in Dubai</title>
    <meta name="description" content="Official Gift Card Distributor in Dubai. Buy authentic digital gift cards for Apple, PlayStation, Xbox, Google Play and more. Wholesale and retail with instant delivery in UAE.">
    <meta name="keywords" content="gift card Dubai, buy gift cards UAE, iTunes gift card Dubai, PlayStation gift card UAE, wholesale gift cards Dubai, digital gift cards instant delivery">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://uae.gift/">
    <meta property="og:title" content="UAE.GIFT | Official Gift Card Distributor in Dubai">
    <meta property="og:description" content="Authentic digital gift cards for Apple, PSN, Xbox, Google Play and more. Wholesale & retail with instant delivery.">
    <meta property="og:image" content="images/hero.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://uae.gift/">
    <meta property="twitter:title" content="UAE.GIFT | Official Gift Card Distributor in Dubai">
    <meta property="twitter:description" content="Authentic digital gift cards for Apple, PSN, Xbox, Google Play and more. Wholesale & retail with instant delivery.">
    <meta property="twitter:image" content="images/hero.png">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/grid.css">
    <link rel="stylesheet" href="css/swiper-bundle.min.css"/>
    <link rel="preload" href="fonts/icon/icon.woff2" as="font" type="font/woff2" crossorigin="anonymous" />
</head>
<body>
    <div class="grid-line-bg" style="max-width: 1200px; top: 40px;"><img src="images/grid-line.svg" alt=""></div>
    <div class="main relative overhide">

        <div class="top-menu">
            <div class="center d-flex just-between align-center">

                <div class="logo"><img src="images/logo.svg" alt=""></div>
                <div class="menu m-hide">
                    <a href="#">Home</a>
                    <a href="#whyus">Why us?</a>
                    <a href="#pricing">Pricing</a>
                    <a href="#contact">Contact</a>
                </div>

                <div class="d-flex align-center gap-10">

                    <div class="btn-sm toggle-theme">
                        <span class="icon theme-light-icon icon-moon-stars icon-size-22"></span>
                        <span class="icon theme-dark-icon icon-sun icon-size-22"></span>
                    </div>

                    <div class="drop-down">
                        <div class="drop-down-btn d-flex align-center gap-10 pointer">
                            <div class="drop-down-img">
                                <img class="selected-img" src="images/flag/uk.svg" alt="">
                            </div>
                            <div class="selected-text m-hide">English</div>
                            <span class="icon icon-arrow-down icon-size-16"></span>
                        </div>

                        <input type="text" class="selected-option" name="lang" value="english" id="" hidden>

                        <div class="drop-down-list">
                            <div class="drop-option d-flex gap-10 align-center active">
                                <div class="drop-option-img" data-option="english"><img src="images/flag/uk.svg" alt=""></div>
                                <span>English</span>
                            </div>

                            <div class="drop-option d-flex gap-10 align-center">
                                <div class="drop-option-img" data-option="arabic"><img src="images/flag/emirates.svg" alt=""></div>
                                <span>Arabic</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <div class="hero-section">
            <div class="center text-center">
                <div class="hero-content">
                    <h1 class="font-size-4 color-title line60 mb-10">Official <span class="color-primary">Gift Card</span><br> Distributor in Dubai</h1>
                    <span class="font-size-1-2">Wholesale & retail digital gift cards with instant delivery.</span>
                </div>

                <div class="hero-img">
                    <div class="img"><img src="images/hero.png" alt=""></div>
                </div>
            </div>
        </div>

        <div id="whyus" class="section">
            <div class="center">
                <div class="d-flex-wrap just-around gap-40">
                    <div class="basis400 grow-1">
                        <h2 class="line60 font-size-3 color-title">Why Trust Our <br>Gift Card Distribution?</h2>
                        <p class="pd-td-30">We provide authentic digital gift cards through verified supply channels, ensuring reliable and instant delivery, with a proven track record of satisfied customers, all operated directly from our Dubai-based distribution center.</p>
                        <div class="d-flex align-center gap-40 text-center">
                            <div class="">
                                <div class="font-size-3 color-primary line60">+8</div>
                                <div class="font-size-1-2"><span>Years</span></div>
                            </div>
                            <div class="">
                                <div class="font-size-3 color-primary line60">+20</div>
                                <div class="font-size-1-2"><span>Brands</span></div>
                            </div>
                            <div class="">
                                <div class="font-size-3 color-primary line60">100%</div>
                                <div class="font-size-1-2"><span>Satisfaction</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="basis400 m-hide"><img src="images/why.png" alt=""></div>
                </div>
            </div>
        </div>

        <!-- product table section -->
        <div id="pricing" class="section">
            <div class="center">

                <div class="text-center mb-20">
                    <h2 class="line60 color-title font-size-3"><span class="color-primary">Gift Card</span> Pricing</h2>
                    <span>Compare denominations, pack sizes, and see prices in multiple currencies for different countries</span>
                </div>

                <div class="fields table-fliters d-flex-wrap align-center mb-20  gap-10">

                    <div class="drop-down grow-1">
                        <div class="drop-down-btn d-flex align-center gap-10 pointer">
                            <div class="drop-down-img">
                                <img class="selected-img" src="images/brand/apple-logo.png" alt="">
                            </div>
                            <div class="selected-text">Apple iTunes</div>
                            <span class="icon icon-arrow-down icon-size-16  lt-auto"></span>
                        </div>

                        <input type="text" class="selected-option" name="brand" value="apple" id="" hidden>

                        <div class="drop-down-list">
                            <div class="drop-option d-flex gap-10 align-center active">
                                <div class="drop-option-img" data-option="apple"><img src="images/brand/apple-logo.png" alt=""></div>
                                <span>Apple iTunes</span>
                            </div>

                            <div class="drop-option d-flex gap-10 align-center">
                                <div class="drop-option-img" data-option="psn"><img src="images/brand/ps-logo.png" alt=""></div>
                                <span>PlayStation Network</span>
                            </div>

                            <div class="drop-option d-flex gap-10 align-center">
                                <div class="drop-option-img" data-option="xbox"><img src="images/brand/xbox-logo.png" alt=""></div>
                                <span>Xbox</span>
                            </div>

                            <div class="drop-option d-flex gap-10 align-center">
                                <div class="drop-option-img" data-option="googleplay"><img src="images/brand/googleplay-logo.png" alt=""></div>
                                <span>GooglePlay</span>
                            </div>
                        </div>
                    </div>

                    <div class="drop-down grow-1">
                        <div class="drop-down-btn d-flex align-center gap-10 pointer">
                            <div class="drop-down-img">
                                <img class="selected-img" src="images/flag/emirates.svg" alt="">
                            </div>
                            <div class="selected-text">United Arab Emirates (AED)</div>
                            <span class="icon icon-arrow-down icon-size-16  lt-auto"></span>
                        </div>

                        <input type="text" class="selected-option" name="country" value="uae" id="" hidden>

                        <div class="drop-down-list">

                            <div class="drop-option d-flex gap-10 align-center active">
                                <div class="drop-option-img" data-option="uae"><img src="images/flag/emirates.svg" alt=""></div>
                                <span>United Arab Emirates (AED)</span>
                            </div>

                            <div class="drop-option d-flex gap-10 align-center">
                                <div class="drop-option-img" data-option="usa"><img src="images/flag/united-states.svg" alt=""></div>
                                <span>United States (USD)</span>
                            </div>

                            <div class="drop-option d-flex gap-10 align-center">
                                <div class="drop-option-img" data-option="uk"><img src="images/flag/uk.svg" alt=""></div>
                                <span>United Kingdom (GBP)</span>
                            </div>

                            <div class="drop-option d-flex gap-10 align-center">
                                <div class="drop-option-img" data-option="turkey"><img src="images/flag/turkey.svg" alt=""></div>
                                <span>Turkey (TRY)</span>
                            </div>
                        </div>
                    </div>

                    <div class="drop-down grow-1">
                        <div class="drop-down-btn d-flex align-center gap-10 pointer">
                            <span class="color-bright">Pack Size:</span>
                            <div class="selected-text">Pack Of 100</div>
                            <span class="icon icon-arrow-down icon-size-16 lt-auto"></span>
                        </div>

                        <input type="text" class="selected-option" name="pack_size" value="100" id="" hidden>

                        <div class="drop-down-list">
                            <div class="drop-option d-flex gap-10 align-center" data-option="10">
                                <span>Pack Of 10</span>
                            </div>

                            <div class="drop-option d-flex gap-10 align-center" data-option="25">
                                <span>Pack Of 25</span>
                            </div>

                            <div class="drop-option d-flex gap-10 align-center" data-option="50">
                                <span>Pack Of 50</span>
                            </div>

                            <div class="drop-option d-flex gap-10 align-center active" data-option="100">
                                <span>Pack Of 100</span>
                            </div>
                        </div>
                    </div>

                    <div class="mode-toggle m-grow-1">
                        <button class="mode-btn active" id="modeDigitalBtn" type="button">Digital</button>
                        <button class="mode-btn" id="modePhysicalBtn" type="button">Physical</button>
                    </div>
                </div>
            
                <!-- tabel products -->
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th class="text-center">Brand</th>
                                <th class="text-left">Denomination</th>
                                <th class="text-left">Country</th>
                                <th class="text-left">Qty</th>
                                <th class="text-left">Price / Card</th>
                                <th class="text-left">Total Price</th>
                                <th class="text-center">Buy</th>
                            </tr>
                        </thead>

                        <tbody id="priceTableBody">
                            <?php
                            $options = $pricingData[$defaultBrand]['options'][$defaultCountry];
                            foreach ($options as $opt):
                                $pricePerCard = $opt['price'];
                                $totalPrice = number_format($pricePerCard * $defaultPackSize, 2, '.', '');
                                $rate = $exchangeRates[$opt['currency']] ?? 1;
                                $priceInAED = number_format($pricePerCard * $rate, 2, '.', '');
                                $totalInAED = number_format($totalPrice * $rate, 2, '.', '');
                                $symbol = ($opt['currency'] === 'USD' ? '$' : ($opt['currency'] === 'GBP' ? '£' : ($opt['currency'] === 'TRY' ? 'TL' : '')));
                            ?>
                            <tr>
                                <td data-label="Brand" class="text-center">
                                    <div class="brand-logo m-auto">
                                        <img src="<?php echo $pricingData[$defaultBrand]['logo']; ?>" alt="">
                                    </div>
                                </td>
                                <td data-label="Denomination">
                                    <span><?php echo $opt['denomination']; ?></span><br>
                                    <span class="color-bright font-size-0-9">Digital · <?php echo $opt['currency']; ?></span>
                                </td>
                                <td data-label="Country"><?php echo $countryNames[$defaultCountry]; ?></td>
                                <td data-label="Qty"><?php echo $defaultPackSize; ?></td>
                                <td data-label="Price / Card">
                                    <span><?php echo $symbol . $pricePerCard; ?></span><br>
                                    <?php if ($opt['currency'] !== 'AED'): ?>
                                    <span class="color-bright font-size-0-9">~ <?php echo $priceInAED; ?> AED</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Total Price">
                                    <span><?php echo $symbol . $totalPrice; ?></span><br>
                                    <?php if ($opt['currency'] !== 'AED'): ?>
                                    <span class="color-bright font-size-0-9">~ <?php echo $totalInAED; ?> AED</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center" data-label="Buy">
                                    <a href="tel:+9710506565129" class="btn">
                                        <span class="icon icon-calling icon-size-18"></span>
                                        Call To Order
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>


            </div>
        </div>

        <div class="section">
            <div class="center">
                <div class="text-center mb-20">
                    <h2 class="line60 color-title font-size-3">Our <span class="color-primary">Advantages</span></h2>
                    <span>Trusted gift card distribution with the best prices for wholesale and retail customers.</span>
                </div>

                <div class="d-flex-wrap gap-30">
                    <div class="basis200 bg-gr-light border pd-20 grow-1 radius-20 overhide relative">
                        <div class="grid-line-bg" style="top: 0; margin: unset; max-width: 200px;"><img src="images/grid-line-3.svg" alt=""></div>
                        <div class="relative">
                            <div class="mb-10"><span class="icon icon-verify icon-size-48 icon-color-primary"></span></div>
                            <h3 class="mb-5 color-title">Authentic Gift Cards</h3>
                            <p class="line20">All cards are original and sourced from verified distributors</p>
                        </div>
                    </div>
                    <div class="basis200 bg-gr-light border pd-20 grow-1 radius-20 overhide relative">
                        <div class="grid-line-bg" style="top: 0; margin: unset; max-width: 200px;"><img src="images/grid-line-3.svg" alt=""></div>
                        <div class="relative">
                            <div class="mb-10"><span class="icon icon-stopwatch icon-size-48 icon-color-primary"></span></div>
                            <h3 class="mb-5 color-title">Instant Delivery</h3>
                            <p class="line20">Receive your codes quickly, whether buying single or bulk packs</p>
                        </div>
                    </div>
                    <div class="basis200 bg-gr-light border pd-20 grow-1 radius-20 overhide relative">
                        <div class="grid-line-bg" style="top: 0; margin: unset; max-width: 200px;"><img src="images/grid-line-3.svg" alt=""></div>
                        <div class="relative">
                            <div class="mb-10"><span class="icon icon-tag-price icon-size-48 icon-color-primary"></span></div>
                            <h3 class="mb-5 color-title">Best Prices</h3>
                            <p class="line20">Competitive pricing for both retail and wholesale purchases</p>
                        </div>
                    </div>
                    <div class="basis200 bg-gr-light border pd-20 grow-1 radius-20 overhide relative">
                        <div class="grid-line-bg" style="top: 0; margin: unset; max-width: 200px;"><img src="images/grid-line-3.svg" alt=""></div>
                        <div class="relative">
                            <div class="mb-10"><span class="icon icon-headphone icon-size-48 icon-color-primary"></span></div>
                            <h3 class="mb-5 color-title">Customer Support</h3>
                            <p class="line20">Our support team is available to assist you before and after your purchase</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

         <!-- comments section -->
        <div class="section overhide">
            <div class="center">
                <div class="d-flex-wrap just-around align-center gap-40 overhide">
                    <div class="grow-1 m-hide">
                        <div class="max-w400">
                            <img src="images/contact-us.png" alt="">
                        </div>
                    </div>
                    <div class="basis400 grow-8 overhide">
                        <div class="mb-20">
                            <h2 class="line60 font-size-3 color-title">What Our <br><span class="color-primary">Customers Say</span></h2>
                            <span>Real feedback from clients who buy gift cards from us in Dubai and the UAE</span>
                        </div>
                        <div id="comments-slider" class="swiper">
                            <div class="swiper-wrapper mb-20" id="testimonialsContainer">
                        <?php foreach ($testimonials as $t): ?>
                        <div class="swiper-slide">
                            <div class="slide-comment">
                                <div class="d-flex align-center just-between gap-20 mb-10">
                                    <div class="d-flex align-center ">
                                        <div class="user-img"><img src="<?php echo $t['image']; ?>" alt=""></div>
                                        <div class="line20">
                                            <div class="color-title font-size-0-9"><?php echo $t['name']; ?></div>
                                            <div class="color-bright font-size-0-8"><?php echo $t['date']; ?></div>
                                        </div>
                                    </div>

                                    <div class="">
                                        <div class="stars"><img src="images/stars.svg" alt=""></div>
                                        <div class="font-size-0-8 color-green"><span class="icon icon-size-16 icon-color-green"></span> Verified</div>
                                    </div>
                                </div>
                                <p class="font-size-0-9"><?php echo $t['text']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex">
                                <div class="btn-sm com-slide-prev pointer mr-10"><span class="icon icon-arrow-left icon-size-18"></span></div>
                                <div class="btn-sm com-slide-next pointer"><span class="icon icon-arrow-right icon-size-18"></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="center text-content">
                <h2>Buy Authentic Gift Cards in Dubai</h2>
                <p>
                    Looking to <strong>buy gift cards in Dubai</strong> with competitive pricing? We supply 
                    <strong>authentic digital gift cards</strong> for top international brands, offering both 
                    <strong>wholesale and retail gift card sales in the UAE</strong>.
                </p>

                <p>
                    As a trusted <strong>gift card distributor in Dubai</strong>, we provide access to gift cards 
                    for multiple countries and regions. Customers can select the correct country version, supported 
                    currency, and denomination based on their needs, whether for personal use or business resale.
                </p>

                <p>
                    Our pricing model is fully transparent. Each gift card listing clearly displays the denomination, 
                    pack size, <strong>unit price per card</strong>, total package cost, and the equivalent value in 
                    <strong>AED (United Arab Emirates Dirham)</strong>, helping buyers easily compare and choose the 
                    best-priced option.
                </p>

                <p>
                    We support both individual and <strong>bulk gift card orders in Dubai</strong>. For businesses 
                    and resellers searching for reliable <strong>gift card suppliers in the UAE</strong>, orders are 
                    processed through direct contact to confirm availability, pricing, and order details before 
                    completion.
                </p>

                <p>
                    All gift cards are 100% original and sourced through verified distribution channels. Our support 
                    team is available before and after purchase, ensuring a secure and reliable experience for 
                    customers buying gift cards in Dubai and across the UAE.
                </p>
            </div>
        </div>

        <div class="section">
            <div class="center">
                <div class="text-center mb-20">
                    <h2 class="line60 color-title font-size-3">Frequently Asked Questions</h2>
                    <span>Clear answers about pricing, availability, supported countries, and wholesale orders</span>
                </div>

                <div class="faq-list border radius-20" id="faqContainer">
                    <?php foreach ($faqs as $index => $faq): ?>
                    <div class="faq-item <?php echo ($index === count($faqs) - 1 ? '' : 'border-b'); ?>">
                        <div class="faq-head d-flex align-center gap-10 pd-20 pointer">
                            <span class="color-primary"><?php echo $faq['id']; ?></span>
                            <h3 class="color-title"><?php echo $faq['question']; ?></h3>
                            <span class="icon icon-add icon-size-22 lt-auto"></span>
                        </div>
                        <div class="faq-content border-t pd-20">
                            <p><?php echo $faq['answer']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="contact" class="section">
            <div class="center">
                <div class="contact-box border bg-gr-light radius-20 overhide relative">
                    <div class="grid-line-bg" style="top: 0; margin: unset; max-width: 300px;"><img src="images/grid-line-3.svg" alt=""></div>
                    <div class="text-left mb-20 relative">
                        <h2 class="line60 color-primary font-size-3">Get in touch</h2>
                        <span>
                            Trusted gift card distribution with the best prices for wholesale and retail customers. 
                        </span>
                    </div>

                    <div class="d-flex-wrap align-center gap-40 relative">

                        <div class="contact-form basis300 grow-1 border radius-20 pd-20">
                            <form action="#" method="POST">
                                <div class="d-flex-wrap gap-20">
                                    <div class="input-item basis200 grow-1">
                                        <div class="input-label">Name</div>
                                        <div class="input">
                                            <input type="text" name="name" placeholder="Your Name" required>
                                            <div class="input-icon"><span class="icon icon-size-22"></span></div>
                                        </div>
                                    </div>

                                    <div class="input-item basis200 grow-1">
                                        <div class="input-label">Mobile</div>
                                        <div class="input">
                                            <input type="tel" name="mobile" placeholder="Your Mobile Number" required>
                                            <div class="input-icon"><span class="icon icon-size-22"></span></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="input-item">
                                    <div class="input-label">Email</div>
                                    <div class="input">
                                        <input type="email" name="email" placeholder="Your Email Address" required>
                                        <div class="input-icon"><span class="icon icon-size-22"></span></div>
                                    </div>
                                </div>

                                <div class="input-item">
                                    <div class="input-label">Message Subject</div>
                                    <div class="input">
                                        <input type="text" name="subject" placeholder="Your Message Subject">
                                        <div class="input-icon"><span class="icon icon-size-22"></span></div>
                                    </div>
                                </div>

                                <div class="input-item">
                                    <div class="input-label">Message text</div>
                                    <textarea name="message" id="message" placeholder="Your message text" rows="3" required></textarea>
                                </div>
                                <div class="d-flex">
                                    <button type="submit" class="btn-primary radius-100">Send message <span class="icon icon-send icon-size-22 icon-color-white"></span></button>
                                </div>
                            </form>
                        </div>

                        <div class="contact-info basis300 grow-1 relative">
                            <div class="grid-line-bg" style="top: 50%;transform: translateY(-50%)scale(1.3);"><img src="images/grid-line-2.svg" alt=""></div>
                            <div class="max-w400 m-auto relative">
                                <div class="mb-20">
                                    <div class="d-flex color-title font-size-1-2"><span class="icon icon-location icon-size-24"></span> <span class="ml-10">Address</span></div>
                                    <p>Flat 1103, 11th Floor, Affini Building, Oud Metha Rd, AI Jaddaf, Dubai, United Arab Emirates</p>
                                </div>
                                <div class="mb-20">
                                    <div class="d-flex color-title font-size-1-2"><span class="icon icon-mail icon-size-24"></span> <span class="ml-10">Email</span></div>
                                    <p>info@uea.gift</p>
                                </div>

                                <div class="d-flex-wrap gap-20 mb-20">
                                    <div class="">
                                        <div class="d-flex color-title font-size-1-2"><span class="icon"></span> <span class="ml-10">Phone</span></div>
                                        <p>+971 050 656 5129</p>
                                    </div>
                                    <div class="">
                                        <div class="d-flex color-title font-size-1-2"><span class="icon"></span> <span class="ml-10">WhatsApp</span></div>
                                        <p>+971 056 380 3107</p>
                                    </div>
                                </div>

                                <div>
                                    <div class="d-flex color-title font-size-1-2 mb-10"><span class="icon"></span> <span class="ml-10">Follow Us</span></div>
                                    <div class="d-flex gap-10">
                                        <a href="" class="social-btn"><span class="icon icon-telegram icon-size-22 icon-color-primary"></span></a>
                                        <a href="" class="social-btn"><span class="icon icon-instagram icon-size-22 icon-color-primary"></span></a>
                                        <a href="" class="social-btn"><span class="icon icon-youtube icon-size-22 icon-color-primary"></span></a>
                                        <a href="" class="social-btn"><span class="icon icon-x icon-size-22 icon-color-primary"></span></a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>

        <div class="footer">
            <div class="center">
                <div class="d-flex-wrap just-between align-center gap-20 pd-td-30 border-b border-t">
                    <div class="logo"><img src="images/logo.svg" alt=""></div>
                    <div class="menu">
                        <a href="#">Home</a>
                        <a href="#whyus">About</a>
                        <a href="#pricing">Pricing</a>
                        <a href="#contact">Contact</a>
                    </div>
                </div>
                <div class="text-center pd-td-20">
                    All rights Reserved
                </div>
            </div>
        </div>

    </div>

    <script src="js/main.js"></script>
    <script src="js/swiper-bundle.min.js"></script>

    <script>
        let commentsSlider;
        function initSwiper() {
            commentsSlider = new Swiper('#comments-slider', {
                loop: true,
                spaceBetween: 20,
                navigation: {
                    nextEl: '.com-slide-next',
                    prevEl: '.com-slide-prev'
                },
                breakpointsBase: 'container',
                breakpoints: {
                    0: { slidesPerView: 1 },
                    500: { slidesPerView: 2 }
                }
            });
        }
    </script>

</body>
</html>
