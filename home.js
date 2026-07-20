let currentTrailer = "";

function openTrailer() {
    if (!currentTrailer) return;
    const overlay = document.getElementById('trailerOverlay');
    const video = document.getElementById('trailerVideo');
    video.src = currentTrailer;
    overlay.classList.add('active');
    video.play();
}

function closeTrailer() {
    const overlay = document.getElementById('trailerOverlay');
    const video = document.getElementById('trailerVideo');
    video.pause();
    video.currentTime = 0;
    video.removeAttribute('src');
    video.load();
    overlay.classList.remove('active');
}

function updateMovie(item) {
    if (!item || !item.dataset) return;

    const banner = document.getElementById('banner');
    if (banner) {
        banner.style.backgroundImage = `url("${item.dataset.bg}")`;
        banner.style.backgroundSize = 'cover';
        banner.style.backgroundPosition = 'center';
    }

    const titleImg = document.getElementById('movieTitleImg');
    const yearEl = document.getElementById('movieYear');
    const ratingEl = document.getElementById('movieRating');
    const durationEl = document.getElementById('movieDuration');
    const genreEl = document.getElementById('movieGenre');
    const descEl = document.getElementById('movieDesc');

    if (titleImg) titleImg.src = item.dataset.titleImg;
    if (yearEl) yearEl.textContent = item.dataset.year;
    if (ratingEl) ratingEl.textContent = item.dataset.rating;
    if (durationEl) durationEl.textContent = item.dataset.duration;
    if (genreEl) genreEl.textContent = item.dataset.genre;
    if (descEl) descEl.textContent = item.dataset.desc;

    currentTrailer = item.dataset.trailer || "";

    // Wire the "Book Ticket" banner button to this movie
    const bookBtn = document.getElementById('bannerBookBtn');
    if (bookBtn) {
        const mid = item.dataset.movieId || '';
        const isComingSoon = (item.dataset.status || '') === 'coming_soon';
        if (mid && !isComingSoon) {
            bookBtn.href = 'booking/booking.php?id=' + mid;
            bookBtn.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i>Book Ticket';
        } else {
            bookBtn.href = '#';
            bookBtn.innerHTML = '<i class="fa-solid fa-bell" aria-hidden="true"></i>Notify Me';
        }
    }
}

function toggleWishlist(button) {
    if (typeof CINEBOOKING_LOGGED_IN !== 'undefined' && !CINEBOOKING_LOGGED_IN) {
        window.location.href = 'login/login.php';
        return;
    }

    const movieId = button.dataset.movieId;
    const icon = button.querySelector('i');

    fetch('wishlist_toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `movie_id=${encodeURIComponent(movieId)}`
    })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                if (data.message) alert(data.message);
                return;
            }
            button.classList.toggle('active', data.inWishlist);
            icon.classList.toggle('fa-solid', data.inWishlist);
            icon.classList.toggle('fa-regular', !data.inWishlist);
            button.classList.add('pulse');
            setTimeout(() => button.classList.remove('pulse'), 300);
        })
        .catch(() => {
            alert('Something went wrong updating your wishlist. Please try again.');
        });
}

document.addEventListener('DOMContentLoaded', function () {
    const carouselItems = document.querySelectorAll('.carousel-item');

    carouselItems.forEach(item => {
        item.addEventListener('click', function () {
            updateMovie(item);
        });
    });

    if (carouselItems.length > 0 && typeof $ !== 'undefined' && $.fn.carousel) {
        $('.carousel').carousel({
            onCycleTo: function (item) {
                const element = item instanceof HTMLElement ? item : item[0];
                updateMovie(element);
            }
        });

        updateMovie(carouselItems[0]);

        const carouselBox = document.getElementById('carouselBox');
        if (carouselBox) {
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
        }
    }

    // Click on the dark backdrop (anywhere outside the video itself) closes the trailer.
    // stopPropagation on the wrapper is a second safety net so clicks on the video
    // controls/letterboxing never bubble up and accidentally trigger a close.
    const trailerOverlay = document.getElementById('trailerOverlay');
    const videoWrapper = document.querySelector('.video-wrapper');

    if (videoWrapper) {
        videoWrapper.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    if (trailerOverlay) {
        trailerOverlay.addEventListener('click', function () {
            closeTrailer();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && trailerOverlay.classList.contains('active')) {
                closeTrailer();
            }
        });
    }
});
