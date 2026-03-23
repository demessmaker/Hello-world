/**
 * WICM Developer Theme JavaScript
 * Optimized: passive scroll listeners, requestAnimationFrame for scroll handlers
 */
(function () {
  'use strict';

  // Sticky header shadow — using passive listener + rAF for performance
  var header = document.getElementById('site-header');
  if (header) {
    var lastScrolled = false;
    window.addEventListener('scroll', function () {
      if (!lastScrolled) {
        lastScrolled = true;
        requestAnimationFrame(function () {
          header.classList.toggle('scrolled', window.scrollY > 10);
          lastScrolled = false;
        });
      }
    }, { passive: true });
  }

  // Mobile menu toggle
  var menuBtn = document.getElementById('mobile-menu-btn');
  var mobileMenu = document.getElementById('mobile-menu');
  if (menuBtn && mobileMenu) {
    menuBtn.addEventListener('click', function () {
      var isOpen = mobileMenu.classList.toggle('open');
      menuBtn.setAttribute('aria-expanded', isOpen);
    });

    // Close on link click
    mobileMenu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        mobileMenu.classList.remove('open');
        menuBtn.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      var href = this.getAttribute('href');
      if (href === '#') return;
      var target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        var headerHeight = header ? header.offsetHeight : 0;
        var top = target.getBoundingClientRect().top + window.scrollY - headerHeight;
        window.scrollTo({ top: top, behavior: 'smooth' });
      }
    });
  });

  // Back to top button — passive scroll listener + rAF
  var backToTop = document.getElementById('back-to-top');
  if (backToTop) {
    var lastBackToTop = false;
    window.addEventListener('scroll', function () {
      if (!lastBackToTop) {
        lastBackToTop = true;
        requestAnimationFrame(function () {
          backToTop.classList.toggle('visible', window.scrollY > 400);
          lastBackToTop = false;
        });
      }
    }, { passive: true });
    backToTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // Scroll-triggered fade-in animations
  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.1, rootMargin: '0px 0px -50px 0px' }
    );

    document.querySelectorAll('.fade-in').forEach(function (el) {
      observer.observe(el);
    });
  }
})();
