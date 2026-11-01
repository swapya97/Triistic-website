(function ($) {
    "use strict";

    // Initiate the wowjs
    new WOW().init();


    // Header: transparent over the hero, solid once the page scrolls or the mobile menu opens
    var header = document.getElementById('siteHeader');
    var menu = document.getElementById('navbarCollapse');
    if (header) {
        var setHeader = function () {
            header.classList.toggle('is-scrolled', window.pageYOffset > 40);
        };
        setHeader();
        window.addEventListener('scroll', setHeader, { passive: true });
        if (menu) {
            menu.addEventListener('show.bs.collapse', function () { header.classList.add('menu-open'); });
            menu.addEventListener('hidden.bs.collapse', function () { header.classList.remove('menu-open'); });
        }
    }


    // Free trial form: one step at a time (without JavaScript all steps show at once)
    var trial = document.getElementById('trialForm');
    if (trial) {
        var steps = [].slice.call(trial.querySelectorAll('.trial-step'));
        var bars = [].slice.call(document.querySelectorAll('.trial-progress li'));
        var current = 0;
        var showStep = function (i, focus) {
            current = i;
            steps.forEach(function (step, n) { step.hidden = n !== i; });
            bars.forEach(function (bar, n) {
                bar.classList.toggle('is-active', n === i);
                bar.classList.toggle('is-done', n < i);
            });
            if (focus) {
                var first = steps[i].querySelector('input:not([type=hidden]), select');
                if (first) { first.focus(); }
            }
        };
        var stepIsValid = function (step) {
            var group = step.querySelector('[data-required-group]');
            if (group) {
                var picked = group.querySelector('input:checked');
                var error = step.querySelector('.step-error');
                if (error) { error.classList.toggle('is-shown', !picked); }
                if (!picked) { return false; }
            }
            var fields = step.querySelectorAll('input[required], select[required]');
            for (var f = 0; f < fields.length; f++) {
                if (!fields[f].checkValidity()) { fields[f].reportValidity(); return false; }
            }
            return true;
        };
        trial.classList.add('is-stepped');
        showStep(0, false);
        trial.addEventListener('click', function (e) {
            if (e.target.closest('[data-step-next]')) {
                e.preventDefault();
                if (stepIsValid(steps[current]) && current < steps.length - 1) { showStep(current + 1, true); }
            } else if (e.target.closest('[data-step-back]')) {
                e.preventDefault();
                if (current > 0) { showStep(current - 1, true); }
            }
        });
        trial.addEventListener('submit', function (e) {
            if (current < steps.length - 1) {
                // Enter on an earlier step moves forward instead of submitting
                e.preventDefault();
                if (stepIsValid(steps[current])) { showStep(current + 1, true); }
            } else if (!stepIsValid(steps[current])) {
                e.preventDefault();
            }
        });
    }


    // Back to top button
    $(window).scroll(function () {
        $('.back-to-top').toggleClass('is-visible', $(this).scrollTop() > 300);
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });


    // Testimonial carousel
    $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1000,
        items: 1,
        loop: true,
        dots: false,
        nav: true,
        navText : [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ]
    });
    
})(jQuery);

