document.addEventListener('DOMContentLoaded', function() {

    /* ============ MOBILE NAV ============ */

    var hamburger = document.getElementById('hamburger');
    var mobileNav = document.getElementById('mobileNav');

    if (hamburger && mobileNav) {
        hamburger.addEventListener('click', function() {
            var isOpen = mobileNav.classList.toggle('is-open');
            hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    /* ============ DROPDOWNS ============ */
    document.querySelectorAll('.has-dropdown > .dropdown-toggle').forEach(function(button) {
        button.addEventListener('click', function() {
            var parent = button.closest('.has-dropdown');
            var isOpen = parent.classList.toggle('is-open');
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            var dropdown = parent.querySelector('.dropdown');
            if (dropdown) {
                dropdown.style.opacity = isOpen ? '1' : '';
                dropdown.style.visibility = isOpen ? 'visible' : '';
                dropdown.style.transform = isOpen ? 'translateY(0)' : '';
            }
        });
    });

    /* =========================================================
       COMMITTEE CAROUSEL
       ========================================================= */
    var carousel = document.querySelector('.carousel');

    if (carousel) {
        var committees = [];
        try {
            committees = JSON.parse(carousel.dataset.committees || '[]');
        } catch (error) {
            committees = [];
        }

        var track = carousel.querySelector('.carousel-track');
        var prevBtn = carousel.querySelector('.carousel-arrow--prev');
        var nextBtn = carousel.querySelector('.carousel-arrow--next');
        var titleEl = document.getElementById('carouselTitle');
        var descEl = document.getElementById('carouselDesc');

        var activeIndex = 0;
        var isAnimating = false;
        var animationTime = 400;

        /* =====================================================
           FORCED CAROUSEL STRUCTURAL CSS
           ===================================================== */
        var style = document.createElement('style');
        style.textContent = `
            .carousel {
                overflow: hidden !important;
                position: relative !important;
                width: 100% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 18px !important;
                touch-action: pan-y;
            }

            .carousel-track {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                position: relative !important;
                width: 100% !important;
                transform: translateX(0px);
                transition: transform ${animationTime}ms cubic-bezier(.22,.61,.36,1);
            }

            .carousel-track [data-role] {
                transition: opacity ${animationTime}ms ease, transform ${animationTime}ms ease, filter ${animationTime}ms ease;
                will-change: transform, opacity;
                flex-shrink: 0 !important;
            }

            .carousel-track [data-role="prev"],
            .carousel-track [data-role="next"] {
                opacity: 0.4;
                transform: scale(0.85);
                cursor: pointer;
            }

            .carousel-track [data-role="center"] {
                opacity: 1;
                transform: scale(1);
                z-index: 2;
            }

            .carousel-arrow {
                cursor: pointer;
                transition: transform 0.2s ease, opacity 0.2s ease;
                z-index: 10;
            }
            .carousel-arrow:hover { transform: scale(1.08); }
            .carousel-arrow:active { transform: scale(0.92); }
            .carousel-track.is-dragging { transition: none !important; }
        `;
        document.head.appendChild(style);

        function getIndex(index) {
            if (!committees.length) return 0;
            return (index + committees.length) % committees.length;
        }

        function getSlide(role) {
            if (!track) return null;
            return track.querySelector('[data-role="' + role + '"]');
        }

        function updateSlide(role, index) {
            var slide = getSlide(role);
            if (!slide || !committees.length) return;
            var item = committees[getIndex(index)];
            var image = slide.querySelector('img');
            var label = slide.querySelector('.carousel-slide-label');

            if (image) {
                image.src = item.image;
                image.alt = item.title;
            }
            if (label) {
                label.textContent = item.title;
            }
            slide.dataset.categoryKey = item.key;
        }

        function updateText() {
            if (!committees.length) return;
            var current = committees[activeIndex];
            if (titleEl) titleEl.textContent = current.title;
            if (descEl) descEl.textContent = current.desc;
        }

        function renderCarousel() {
            if (!committees.length || !track) return;
            updateSlide('prev', activeIndex - 1);
            updateSlide('center', activeIndex);
            updateSlide('next', activeIndex + 1);
            updateText();
        }

        function goNext() {
            if (isAnimating || committees.length <= 1) return;
            isAnimating = true;

            // Smooth track translation shift simulation left
            track.style.transform = 'translateX(-100px)';

            setTimeout(function() {
                activeIndex = getIndex(activeIndex + 1);
                track.style.transition = 'none';
                track.style.transform = 'translateX(0px)';

                updateSlide('prev', activeIndex - 1);
                updateSlide('center', activeIndex);
                updateSlide('next', activeIndex + 1);
                updateText();

                void track.offsetWidth;
                track.style.transition = 'transform ' + animationTime + 'ms cubic-bezier(.22,.61,.36,1)';
                isAnimating = false;
            }, animationTime);
        }

        function goPrev() {
            if (isAnimating || committees.length <= 1) return;
            isAnimating = true;

            // Smooth track translation shift simulation right
            track.style.transform = 'translateX(100px)';

            setTimeout(function() {
                activeIndex = getIndex(activeIndex - 1);
                track.style.transition = 'none';
                track.style.transform = 'translateX(0px)';

                updateSlide('prev', activeIndex - 1);
                updateSlide('center', activeIndex);
                updateSlide('next', activeIndex + 1);
                updateText();

                void track.offsetWidth;
                track.style.transition = 'transform ' + animationTime + 'ms cubic-bezier(.22,.61,.36,1)';
                isAnimating = false;
            }, animationTime);
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                goPrev();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                goNext();
            });
        }

        var previousSlide = getSlide('prev');
        var followingSlide = getSlide('next');

        if (previousSlide) {
            previousSlide.addEventListener('click', function(event) {
                event.preventDefault();
                if (!isAnimating) goPrev();
            });
        }

        if (followingSlide) {
            followingSlide.addEventListener('click', function(event) {
                event.preventDefault();
                if (!isAnimating) goNext();
            });
        }

        // Initialize
        renderCarousel();
    }

    /* =========================================================
       FILTER POSTINGS & UTILITIES
       ========================================================= */
    function filterPostingsByCategory(categoryKey) {
        document.querySelectorAll('.posting-card').forEach(function(card) {
            card.style.display = card.dataset.category === categoryKey ? '' : 'none';
        });
    }

    document.querySelectorAll('[data-category-link]').forEach(function(link) {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            var key = link.dataset.categoryLink;
            filterPostingsByCategory(key);
            var section = document.getElementById('committees');
            if (section) {
                section.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

});