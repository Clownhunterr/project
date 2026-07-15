let currentTrailer = "REPLACE_WITH_YOUTUBE_ID_SPIDERMAN";

function openTrailer() {
    const overlay = document.getElementById('trailerOverlay');
    const iframe = document.getElementById('trailerVideo');
    iframe.src = `https://www.youtube.com/embed/${currentTrailer}?autoplay=1&mute=1&rel=0&modestbranding=1`;
    overlay.classList.add('active');
}

function closeTrailer() {
    const overlay = document.getElementById('trailerOverlay');
    const iframe = document.getElementById('trailerVideo');
    iframe.src = "";
    overlay.classList.remove('active');
}

function updateMovie(item) {
    const banner = document.getElementById('banner');

    banner.style.backgroundImage = `url("${item.dataset.bg}")`;
    banner.style.backgroundSize = 'cover';
    banner.style.backgroundPosition = 'center';

    document.getElementById('movieTitleImg').src = item.dataset.titleImg;
    document.getElementById('movieYear').textContent = item.dataset.year;
    document.getElementById('movieRating').textContent = item.dataset.rating;
    document.getElementById('movieDuration').textContent = item.dataset.duration;
    document.getElementById('movieGenre').textContent = item.dataset.genre;
    document.getElementById('movieDesc').textContent = item.dataset.desc;

    currentTrailer = item.dataset.trailer;
}

document.addEventListener('DOMContentLoaded', function () {
    const carouselItems = document.querySelectorAll('.carousel-item');

    carouselItems.forEach(item => {
        item.addEventListener('click', function () {
            updateMovie(item);
        });
    });

    $('.carousel').carousel({
        onCycleTo: function (item) {
            updateMovie(item[0]);
        }
    });

    if (carouselItems.length > 0) {
        updateMovie(carouselItems[0]);
    }

    const carouselBox = document.getElementById('carouselBox');
    let scrollTimeout;
    carouselBox.addEventListener('wheel', function (e) {
        e.preventDefault();
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            if (e.deltaY > 0) {
                $('.carousel').carousel('next');
            } else {
                $('.carousel').carousel('prev');
            }
        }, 50);
    });

    const trailerOverlay = document.getElementById('trailerOverlay');
    trailerOverlay.addEventListener('click', function (e) {
        if (e.target === trailerOverlay) {
            closeTrailer();
        }
    });
});