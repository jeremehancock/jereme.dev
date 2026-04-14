(function () {
  'use strict';

  // --- State ---
  var lightbox      = null;
  var lbImage       = null;
  var lbPrev        = null;
  var lbNext        = null;
  var lbClose       = null;
  var lbCounter     = null;

  var currentGroup  = [];
  var currentIndex  = 0;

  // Touch tracking
  var touchStartX   = 0;
  var touchEndX     = 0;

  // --- Build lightbox DOM (once) ---
  function buildLightbox() {
    lightbox = document.createElement('div');
    lightbox.className = 'bludit-lightbox';

    lbImage = document.createElement('img');
    lbImage.className = 'bludit-lightbox-image';

    lbPrev = document.createElement('button');
    lbPrev.className = 'bludit-lightbox-prev';
    lbPrev.setAttribute('aria-label', 'Previous image');
    // Arrow glyph rendered via CSS ::before pseudo-element

    lbNext = document.createElement('button');
    lbNext.className = 'bludit-lightbox-next';
    lbNext.setAttribute('aria-label', 'Next image');
    // Arrow glyph rendered via CSS ::before pseudo-element

    lbClose = document.createElement('button');
    lbClose.className = 'bludit-lightbox-close';
    lbClose.setAttribute('aria-label', 'Close lightbox');
    // X glyph rendered via CSS ::before/::after pseudo-elements

    lbCounter = document.createElement('span');
    lbCounter.className = 'bludit-lightbox-counter';

    lightbox.appendChild(lbImage);
    lightbox.appendChild(lbPrev);
    lightbox.appendChild(lbNext);
    lightbox.appendChild(lbClose);
    lightbox.appendChild(lbCounter);

    document.body.appendChild(lightbox);

    // --- Lightbox-internal events ---

    lbClose.addEventListener('click', function (e) {
      e.stopPropagation();
      closeLightbox();
    });

    lbPrev.addEventListener('click', function (e) {
      e.stopPropagation();
      navigate(-1);
    });

    lbNext.addEventListener('click', function (e) {
      e.stopPropagation();
      navigate(1);
    });

    // Close when clicking on the backdrop (not on the image or buttons)
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) {
        closeLightbox();
      }
    });

    // Touch swipe on the image
    lbImage.addEventListener('touchstart', function (e) {
      touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    lbImage.addEventListener('touchend', function (e) {
      touchEndX = e.changedTouches[0].screenX;
      var delta = touchStartX - touchEndX;
      if (Math.abs(delta) >= 50) {
        navigate(delta > 0 ? 1 : -1);
      }
    }, { passive: true });
  }

  // --- Open lightbox ---
  function openLightbox(items, startIndex) {
    if (!lightbox) {
      buildLightbox();
    }

    currentGroup = items;
    currentIndex = startIndex;

    showImage(currentIndex);

    // Show overlay (requestAnimationFrame ensures the class triggers a CSS transition)
    lightbox.style.display = 'flex';
    requestAnimationFrame(function () {
      lightbox.classList.add('bludit-lightbox-active');
    });

    document.body.style.overflow = 'hidden';
    document.addEventListener('keydown', onKeyDown);
  }

  // --- Close lightbox ---
  function closeLightbox() {
    lightbox.classList.remove('bludit-lightbox-active');

    // Wait for fade-out transition before hiding
    lightbox.addEventListener('transitionend', function onEnd() {
      lightbox.removeEventListener('transitionend', onEnd);
      lightbox.style.display = 'none';
      lbImage.src = '';
    });

    document.body.style.overflow = '';
    document.removeEventListener('keydown', onKeyDown);
  }

  // --- Show image at index ---
  function showImage(index) {
    var item = currentGroup[index];
    var href = item.getAttribute('href');
    var alt  = (item.querySelector('img') || {}).alt || '';

    lbImage.src = href;
    lbImage.alt = alt;

    // Counter
    lbCounter.textContent = (index + 1) + ' / ' + currentGroup.length;

    // Button visibility
    lbPrev.style.visibility = currentGroup.length > 1 ? 'visible' : 'hidden';
    lbNext.style.visibility = currentGroup.length > 1 ? 'visible' : 'hidden';

    preloadAdjacent(index);
  }

  // --- Navigate prev / next ---
  function navigate(direction) {
    var len = currentGroup.length;
    currentIndex = (currentIndex + direction + len) % len;
    showImage(currentIndex);
  }

  // --- Preload adjacent images ---
  function preloadAdjacent(index) {
    var len = currentGroup.length;
    [-1, 1].forEach(function (offset) {
      var adjIndex = (index + offset + len) % len;
      var adjItem  = currentGroup[adjIndex];
      var src      = adjItem && adjItem.getAttribute('href');
      if (src) {
        var img = new Image();
        img.src = src;
      }
    });
  }

  // --- Keyboard handler ---
  function onKeyDown(e) {
    switch (e.key) {
      case 'ArrowLeft':
        navigate(-1);
        break;
      case 'ArrowRight':
        navigate(1);
        break;
      case 'Escape':
        closeLightbox();
        break;
    }
  }

  // --- Collect all items that share the same data-gallery-group ---
  function collectGroup(groupName) {
    return Array.prototype.slice.call(
      document.querySelectorAll(
        '.bludit-gallery-item[data-gallery-group="' + groupName + '"]'
      )
    );
  }

  // --- Event delegation on document ---
  document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
      var item = e.target.closest('.bludit-gallery-item');
      if (!item) {
        return;
      }

      e.preventDefault();

      var groupName = item.getAttribute('data-gallery-group');
      var items     = collectGroup(groupName);
      var index     = items.indexOf(item);

      if (index === -1) {
        index = 0;
      }

      openLightbox(items, index);
    });
  });

}());
