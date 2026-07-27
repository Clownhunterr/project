let currentTrailer = "";

function openTrailer() {
    if (!currentTrailer) return;
    
    const overlay = document.getElementById('trailerOverlay');
    const video = document.getElementById('trailerVideo');
    const iframe = document.getElementById('trailerIframe');
    
    // Check if it's a YouTube URL
    if (currentTrailer.includes('youtube.com') || currentTrailer.includes('youtu.be')) {
        let videoId = "";
        if (currentTrailer.includes('youtube.com/watch?v=')) {
            videoId = currentTrailer.split('v=')[1].split('&')[0];
        } else if (currentTrailer.includes('youtu.be/')) {
            videoId = currentTrailer.split('youtu.be/')[1].split('?')[0];
        }
        
        if (videoId && iframe && overlay) {
            iframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
            iframe.style.display = 'block';
            if (video) video.style.display = 'none';
            overlay.classList.add('active');
            return;
        }
    }
    
    // Check if it's an external URL (http/https) that isn't YouTube
    if (currentTrailer.startsWith('http://') || currentTrailer.startsWith('https://')) {
        // Just open it in a new tab if it's not a direct video link
        if (!currentTrailer.endsWith('.mp4') && !currentTrailer.endsWith('.webm')) {
            window.open(currentTrailer, '_blank');
            return;
        }
    }
    
    if (video && overlay) {
        if (iframe) iframe.style.display = 'none';
        video.style.display = 'block';
        video.src = currentTrailer;
        overlay.classList.add('active');
        video.play();
    }
}

function closeTrailer() {
    const overlay = document.getElementById('trailerOverlay');
    const video = document.getElementById('trailerVideo');
    const iframe = document.getElementById('trailerIframe');
    
    if (iframe) {
        iframe.src = '';
        iframe.style.display = 'none';
    }
    
    if (video && overlay) {
        video.pause();
        video.currentTime = 0;
        video.removeAttribute('src');
        video.load();
        video.style.display = 'none';
    }
    if (overlay) overlay.classList.remove('active');
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

function toggleNotify(button) {
    if (typeof CINEBOOKING_LOGGED_IN !== 'undefined' && !CINEBOOKING_LOGGED_IN) {
        window.location.href = 'login/login.php';
        return;
    }

    const movieId = button.dataset.movieId;

    fetch('notify_toggle.php', {
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
            button.classList.toggle('notified', data.inWishlist);
            button.textContent = data.inWishlist ? 'Notified ✓' : 'Notify Me';
        })
        .catch(() => {
            alert('Something went wrong. Please try again.');
        });
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