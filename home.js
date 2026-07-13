let currentTrailer = "video/Gadar2 Official Trailer - 11th August - Sunny Deol - Ameesha Patel - Anil Sharma - Zee Studios.mp4";

function openTrailer() {
    const trailer = document.querySelector('.trailer');
    const video = document.getElementById('trailerVideo');
    video.src = currentTrailer;
    trailer.classList.add('active');
    video.play();
}

function closeTrailer() {
    const trailer = document.querySelector('.trailer');
    const video = document.getElementById('trailerVideo');
    video.pause();
    video.currentTime = 0;
    video.removeAttribute('src');
    video.load();
    trailer.classList.remove('active');
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

    $('.carousel').carousel('set', 2);

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
});