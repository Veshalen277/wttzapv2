<?php 
include 'header.php';
?>

<style>
    @import url('https://fonts.googleapis.com/css?family=Dosis:300,400,500');

    /* Keyframe animations omitted for brevity */

    .spin-earth-on-hover {
        transition: ease 200s !important;
        transform: rotate(-3600deg) !important;
    }

    .bg-purple {
        background: url(http://salehriaz.com/404Page/img/bg_purple.png);
        background-repeat: repeat-x;
        background-size: cover;
        background-position: left top;
        height: 100%;
        overflow: hidden;
    }

    .btn-request {
        padding: 10px 25px;
        border: 1px solid #FFCB39;
        border-radius: 100px;
        font-weight: 400;
    }

    .central-body {
        padding: 17% 5% 10% 5%;
        text-align: center;
    }

    .objects img {
        z-index: 90;
        pointer-events: none;
    }

    .object_rocket {
        z-index: 95;
        position: absolute;
        transform: translateX(-50px);
        top: 75%;
        pointer-events: none;
        animation: rocket-movement 200s linear infinite both running;
    }

    .object_earth {
        position: absolute;
        top: 20%;
        left: 15%;
        z-index: 90;
    }

    .object_moon {
        position: absolute;
        top: 12%;
        left: 25%;
    }

    .earth-moon {}

    .object_astronaut {
        animation: rotate-astronaut 200s infinite linear both alternate;
    }

    .box_astronaut {
        z-index: 110 !important;
        position: absolute;
        top: 60%;
        right: 20%;
        will-change: transform;
        animation: move-astronaut 70s infinite linear both alternate;
    }

    .image-404-container {
        position: relative;
        display: inline-block;
    }

    .image-404 {
        width: 300px;
        transition: opacity 0.3s ease-in-out;
    }

    .image-404-container:hover .image-404 {
        opacity: 0;
    }

    .image-404-container:hover .image-404-hover {
        opacity: 1;
    }

    .image-404-hover {
        position: absolute;
        top: 0;
        left: 0;
        width: 300px;
        height: auto;
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }

    .stars {
        background: url(http://salehriaz.com/404Page/img/overlay_stars.svg);
        background-repeat: repeat;
        background-size: contain;
        background-position: left top;
    }

    .glowing_stars .star {
        position: absolute;
        border-radius: 100%;
        background-color: #fff;
        width: 3px;
        height: 3px;
        opacity: 0.3;
        will-change: opacity;
    }

    .glowing_stars .star:nth-child(1) {
        top: 80%;
        left: 25%;
        animation: glow-star 2s infinite ease-in-out alternate 1s;
    }
    .glowing_stars .star:nth-child(2) {
        top: 20%;
        left: 40%;
        animation: glow-star 2s infinite ease-in-out alternate 3s;
    }
    .glowing_stars .star:nth-child(3) {
        top: 25%;
        left: 25%;
        animation: glow-star 2s infinite ease-in-out alternate 5s;
    }
    .glowing_stars .star:nth-child(4) {
        top: 75%;
        left: 80%;
        animation: glow-star 2s infinite ease-in-out alternate 7s;
    }
    .glowing_stars .star:nth-child(5) {
        top: 90%;
        left: 50%;
        animation: glow-star 2s infinite ease-in-out alternate 9s;
    }

    @media only screen and (max-width: 600px) {
        .box_astronaut {
            top: 70%;
        }

        .central-body {
            padding-top: 25%;
        }
    }
</style>

<div class="bg-purple">
    <div class="stars">
        <div class="central-body">
            <div class="image-404-container">
                <a href="/dashboard/index.php">
                    <img class="image-404" src="https://www.safetysignonline.co.za/cdn/shop/files/MI4-Restrictedarea-Noentry_1200x1200.png?v=1683575859" alt="404 Image">
                    <img class="image-404-hover" src="https://cdn1.iconfinder.com/data/icons/ui-6/502/left-512.png" alt="404 Hover Image">
                </a>
            </div>
        </div>
        <div class="objects">
            <img class="object_rocket" src="http://salehriaz.com/404Page/img/rocket.svg" width="40px">
            <div class="earth-moon">
                <img class="object_earth" src="http://salehriaz.com/404Page/img/earth.svg" width="100px">
                <img class="object_moon" src="http://salehriaz.com/404Page/img/moon.svg" width="80px">
            </div>
            <div class="box_astronaut">
                <img class="object_astronaut" src="http://salehriaz.com/404Page/img/astronaut.svg" width="140px">
            </div>
        </div>
        <div class="glowing_stars">
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
        </div>
    </div>
</div>

<?php 
include 'footer.php';
?>
