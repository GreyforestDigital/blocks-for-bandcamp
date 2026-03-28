document.addEventListener('DOMContentLoaded', () => {
	let currentGallery = [];
	let currentIndex = 0;

	// Create lightbox DOM
	const bcLightboxOverlay = document.createElement('div');
	bcLightboxOverlay.id = 'bc-lightbox-overlay';
	bcLightboxOverlay.style.display = 'none';
	bcLightboxOverlay.innerHTML = `
		<div id="bc-lightbox-content">
			<img id="bc-lightbox-image" />
		</div>
		<button id="bc-lightbox-close" aria-label="Close">&times;</button>
		<button id="bc-lightbox-prev" aria-label="Previous">&#10094;</button>
		<button id="bc-lightbox-next" aria-label="Next">&#10095;</button>
	`;
	document.body.appendChild(bcLightboxOverlay);

	const bcLightboxImage = document.getElementById('bc-lightbox-image');
	const bcLightboxPrev = document.getElementById('bc-lightbox-prev');
	const bcLightboxNext = document.getElementById('bc-lightbox-next');
	const bcLightboxClose = document.getElementById('bc-lightbox-close');
	const bcLightboxContent = document.getElementById('bc-lightbox-content');

	// Open lightbox
	function openLightbox(index, gallery) {
		currentIndex = index;
		currentGallery = gallery;

		showImage(currentIndex);
		updateArrows();
		bcLightboxOverlay.style.display = 'flex';
	}

	// Close lightbox
	function closeLightbox() {
		bcLightboxOverlay.style.display = 'none';
		currentGallery = [];
		currentIndex = 0;
	}

	// Show image
	function showImage(index) {
		const item = currentGallery[index];
		if (!item) return;

		bcLightboxImage.src = item.href;
		bcLightboxImage.alt = '';
		currentIndex = index;

		updateArrows(); // <-- CRITICAL
	}

	function updateArrows() {
		const galleryLength = currentGallery.length;

		// Hide both if only one item
		if (galleryLength <= 1) {
			bcLightboxPrev.style.display = 'none';
			bcLightboxNext.style.display = 'none';
			return;
		}

		// Show/hide prev
		bcLightboxPrev.style.display = currentIndex > 0 ? 'block' : 'none';

		// Show/hide next
		bcLightboxNext.style.display = currentIndex < galleryLength - 1 ? 'block' : 'none';
	}

	// Close on overlay click
	bcLightboxOverlay.addEventListener('click', (e) => {
		if (e.target === bcLightboxOverlay || e.target === bcLightboxContent) {
			closeLightbox();
		}
	});

	// Close button
	bcLightboxClose.addEventListener('click', closeLightbox);

	// Navigation buttons
	bcLightboxPrev.addEventListener('click', () => {
		if (currentIndex > 0) {
			currentIndex--;
			showImage(currentIndex);
		}
	});

	bcLightboxNext.addEventListener('click', () => {
		if (currentIndex < currentGallery.length - 1) {
			currentIndex++;
			showImage(currentIndex);
		}
	});

	// Keyboard support
	document.addEventListener('keydown', (e) => {
		if (bcLightboxOverlay.style.display !== 'flex') return;

		if (e.key === 'Escape') {
			closeLightbox();
		} else if (e.key === 'ArrowLeft' && currentIndex > 0) {
			currentIndex--;
			showImage(currentIndex);
		} else if (e.key === 'ArrowRight' && currentIndex < currentGallery.length - 1) {
			currentIndex++;
			showImage(currentIndex);
		}
	});

	// Touch swipe support
	let touchStartX = null;
	bcLightboxOverlay.addEventListener('touchstart', (e) => {
		touchStartX = e.changedTouches[0].screenX;
	}, { passive: true });

	bcLightboxOverlay.addEventListener('touchend', (e) => {
		if (touchStartX === null) return;
		const deltaX = e.changedTouches[0].screenX - touchStartX;
		if (deltaX > 50 && currentIndex > 0) {
			currentIndex--;
			showImage(currentIndex);
		} else if (deltaX < -50 && currentIndex < currentGallery.length - 1) {
			currentIndex++;
			showImage(currentIndex);
		}
		touchStartX = null;
	});

	// Trigger lightbox on click
	document.querySelectorAll('.bandcamp-lightbox').forEach((el) => {
		el.addEventListener('click', (e) => {
			e.preventDefault();

			const galleryName = el.dataset.bandcampGallery || null;

			let galleryItems;

			if (galleryName) {
				// Group all items with same data-bandcamp-gallery
				galleryItems = Array.from(document.querySelectorAll(`.bandcamp-lightbox[data-bandcamp-gallery="${galleryName}"]`));
			} else {
				// Treat as single-item gallery if no group defined
				galleryItems = [el];
			}

			const thisIndex = galleryItems.indexOf(el);
			openLightbox(thisIndex, galleryItems.map(item => ({ href: item.href })));
		});
	});

});