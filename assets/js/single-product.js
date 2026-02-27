/**
 * Single Product Page
 *   — Gallery (arrows + thumbnails)
 *   — Tab switching
 *   — Scroll-reveal sections
 */
(function () {
	'use strict';

	/* ==================================================================
	   Gallery
	   ================================================================== */

	function initGallery() {
		var gallery = document.querySelector('.apf-sp-gallery');
		if (!gallery) return;

		var slides  = gallery.querySelectorAll('.apf-sp-slide');
		var thumbs  = gallery.querySelectorAll('.apf-sp-thumb');
		var prev    = gallery.querySelector('.apf-sp-arrow-prev');
		var next    = gallery.querySelector('.apf-sp-arrow-next');
		var idx     = 0;
		var total   = slides.length;

		if (total < 2) return; // nothing to navigate

		function go(n) {
			if (n < 0) n = total - 1;
			if (n >= total) n = 0;
			idx = n;

			for (var i = 0; i < total; i++) {
				slides[i].classList.toggle('active', i === idx);
				if (thumbs[i]) thumbs[i].classList.toggle('active', i === idx);
			}

			if (thumbs[idx]) {
				thumbs[idx].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
			}
		}

		if (prev) prev.addEventListener('click', function () { go(idx - 1); });
		if (next) next.addEventListener('click', function () { go(idx + 1); });

		for (var t = 0; t < thumbs.length; t++) {
			(function (thumb) {
				thumb.addEventListener('click', function () {
					go(parseInt(this.getAttribute('data-index'), 10));
				});
			})(thumbs[t]);
		}

		// Keyboard (skip form fields)
		document.addEventListener('keydown', function (e) {
			var tag = document.activeElement && document.activeElement.tagName;
			if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
			if (e.key === 'ArrowLeft')  go(idx - 1);
			if (e.key === 'ArrowRight') go(idx + 1);
		});

		// Touch swipe
		var startX = 0;
		var main = gallery.querySelector('.apf-sp-gallery-main');
		if (main) {
			main.addEventListener('touchstart', function (e) {
				startX = e.changedTouches[0].clientX;
			}, { passive: true });

			main.addEventListener('touchend', function (e) {
				var diff = e.changedTouches[0].clientX - startX;
				if (Math.abs(diff) > 40) {
					go(diff > 0 ? idx - 1 : idx + 1);
				}
			}, { passive: true });
		}
	}

	/* ==================================================================
	   Tabs
	   ================================================================== */

	function initTabs() {
		var nav = document.querySelector('.apf-sp-tabs-nav');
		if (!nav) return;

		var btns   = nav.querySelectorAll('.apf-sp-tabs-btn');
		var panels = document.querySelectorAll('.apf-sp-tabs-panel');

		for (var b = 0; b < btns.length; b++) {
			(function (btn) {
				btn.addEventListener('click', function () {
					var target = this.getAttribute('data-tab');

					for (var i = 0; i < btns.length; i++) {
						btns[i].classList.remove('active');
						btns[i].setAttribute('aria-selected', 'false');
					}
					for (var j = 0; j < panels.length; j++) {
						panels[j].classList.remove('active');
						panels[j].setAttribute('hidden', '');
					}

					this.classList.add('active');
					this.setAttribute('aria-selected', 'true');

					var panel = document.getElementById('apf-tp-' + target);
					if (panel) {
						panel.classList.add('active');
						panel.removeAttribute('hidden');
					}
				});
			})(btns[b]);
		}
	}

	/* ==================================================================
	   Scroll Reveal (IntersectionObserver)
	   ================================================================== */

	function initReveal() {
		var els = document.querySelectorAll('.apf-sp-reveal');
		if (!els.length) return;

		if (!('IntersectionObserver' in window)) {
			// Fallback: show immediately
			for (var i = 0; i < els.length; i++) els[i].classList.add('revealed');
			return;
		}

		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('revealed');
					observer.unobserve(entry.target);
				}
			});
		}, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

		for (var j = 0; j < els.length; j++) {
			observer.observe(els[j]);
		}
	}

	/* ==================================================================
	   Quote Popup
	   ================================================================== */

	function initQuotePopup() {
		var popupId = window.apfSingleProduct && window.apfSingleProduct.popupId;
		if (!popupId) return;

		document.addEventListener('click', function (e) {
			var btn = e.target.closest('.apf-quote-btn');
			if (!btn) return;

			if (typeof elementorProFrontend !== 'undefined') {
				e.preventDefault();
				elementorProFrontend.modules.popup.showPopup({ id: popupId });
			}
		});
	}

	/* ==================================================================
	   Init
	   ================================================================== */

	initGallery();
	initTabs();
	initReveal();
	initQuotePopup();

})();
