document.addEventListener("DOMContentLoaded", function () {
	const playlists = document.querySelectorAll(".bandcamp-album");

	playlists.forEach((playlist) => {
		const audioPlayer = playlist.querySelector("audio");
		const trackLinks = Array.from(playlist.querySelectorAll(".bandcamp-album-tracks .track-link"));
		let current = 0;

		if (trackLinks == null || audioPlayer == null ) { return; }
		
		trackLinks.forEach((link, index) => {
			link.addEventListener("click", function (e) {
				e.preventDefault();
				current = index;
				playTrack(link);
			});
		});

		audioPlayer.addEventListener("ended", function () {
			current = (current + 1) % trackLinks.length;
			playTrack(trackLinks[current]);
		});

		/**
		 * Load the selected track into the player and mark it as current.
		 *
		 * @param {HTMLElement} link The track control that was activated.
		 */
		function playTrack(link) {
			audioPlayer.src = link.getAttribute("data-track");
			audioPlayer.play();
			trackLinks.forEach((l) => {
				l.closest("li").classList.remove("active");
				l.setAttribute("aria-current", "false");
			});
			link.closest("li").classList.add("active");
			link.setAttribute("aria-current", "true");
		}
	});


	const players = document.querySelectorAll(".bandcamp-player");

	players.forEach((player) => {
		const audio      = player.querySelector("audio");
		const button     = player.querySelector(".bandcamp-player-play");
		const playIcon   = button.querySelector("path.play");
		const pauseIcon  = button.querySelector("path.pause");
		const progress   = player.querySelector(".bandcamp-player-progress");
		const bufferBar  = player.querySelector(".bandcamp-player-progress-buffer");
		const progressWrap = player.querySelector(".bandcamp-player-progress-wrap");
		const timeReadout = player.querySelector(".bandcamp-player-time");
		const playLabel = button.getAttribute("data-play-label") || "Play sample";
		const pauseLabel = button.getAttribute("data-pause-label") || "Pause sample";

		let progressTimeout;

		/**
		 * Format a time value into MM:SS.
		 *
		 * @param {number} seconds The time value in seconds.
		 * @return {string} The formatted time string.
		 */
		function formatTime(seconds) {
			if (!isFinite(seconds)) return "00:00";

			const mins = Math.floor(seconds / 60);
			const secs = Math.floor(seconds % 60);

			return String(mins).padStart(2, "0") + ":" + String(secs).padStart(2, "0");
		}

		/**
		 * Update the visible and screen-reader time state.
		 */
		function updateTime() {
			var currentTime = formatTime(audio.currentTime);
			var durationTime = formatTime(audio.duration);
			var valueText = currentTime + " / " + durationTime;

			timeReadout.textContent = valueText;
			progressWrap.setAttribute("aria-valuetext", valueText);
		}

		/**
		 * Update the buffered progress bar.
		 */
		function updateBuffer() {
			if (!audio.duration || !audio.buffered.length) return;

			const bufferedEnd = audio.buffered.end(audio.buffered.length - 1);
			const pct = (bufferedEnd / audio.duration) * 100;
			bufferBar.style.width = pct + "%";
		}

		// INITIAL STATE
		playIcon.style.display   = "";
		pauseIcon.style.display  = "none";
		progress.style.width     = "0%";
		bufferBar.style.width    = "0%";
		updateTime();
		progressWrap.setAttribute("aria-valuenow", "0");

		/**
		 * Keep the visual bar and ARIA values in sync with playback.
		 */
		// updates the bar, then re-schedules itself via setTimeout
		function updateProgress() {
			if (!audio.duration) {
				updateTime();
				return;
			}

			const pct = (audio.currentTime / audio.duration) * 100;
			progress.style.width = pct + "%";
			progressWrap.setAttribute("aria-valuenow", String(Math.round(pct)));
			updateTime();
			updateBuffer();

			if (!audio.paused && !audio.ended) {
				progressTimeout = setTimeout(updateProgress, 250);
				player._progressTimeout = progressTimeout;
			}
		}

		audio.addEventListener("play", function () {
			player.classList.add("active");
			playIcon.style.display  = "none";
			pauseIcon.style.display = "";
			button.setAttribute("aria-pressed", "true");
			button.setAttribute("aria-label", pauseLabel);
		});

		audio.addEventListener("pause", function () {
			if (audio.ended) return;

			player.classList.remove("active");
			playIcon.style.display  = "";
			pauseIcon.style.display = "none";
			button.setAttribute("aria-pressed", "false");
			button.setAttribute("aria-label", playLabel);
		});

		button.addEventListener("click", function () {
			if (audio.paused) {
				// pause/reset all the others
				players.forEach((other) => {
					if (other !== player) {
						const oa      = other.querySelector("audio");
						const obtn    = other.querySelector(".bandcamp-player-play");
						const oPlay   = obtn.querySelector("path.play");
						const oPause  = obtn.querySelector("path.pause");
						const oProg   = other.querySelector(".bandcamp-player-progress");
						const oBuffer = other.querySelector(".bandcamp-player-progress-buffer");
						const oTime   = other.querySelector(".bandcamp-player-time");
						const oWrap   = other.querySelector(".bandcamp-player-progress-wrap");

						oa.pause();
						oa.currentTime = 0;
						other.classList.remove("active");
						oPlay.style.display  = "";
						oPause.style.display = "none";
						obtn.setAttribute("aria-pressed", "false");
						obtn.setAttribute("aria-label", playLabel);
						clearTimeout(other._progressTimeout);
						oProg.style.width    = "0%";
						if (oWrap) {
							oWrap.setAttribute("aria-valuenow", "0");
							oWrap.setAttribute("aria-valuetext", formatTime(0) + " / " + formatTime(oa.duration));
						}
						if (oBuffer) oBuffer.style.width = "0%";
						if (oTime) oTime.textContent = formatTime(0) + " / " + formatTime(oa.duration);
					}
				});

				// play this one
				audio.play();
				updateProgress();

			} else {

				// pause this one
				audio.pause();
				clearTimeout(progressTimeout);

			}
		});

		progressWrap.addEventListener("click", function (e) {
			if (!audio.duration) return;

			const rect = progressWrap.getBoundingClientRect();
			const clickX = e.clientX - rect.left;
			const pct = clickX / rect.width;

			audio.currentTime = Math.max(0, Math.min(audio.duration, pct * audio.duration));
			updateProgress();
		});

		progressWrap.addEventListener("keydown", function (e) {
			if (!audio.duration) return;

			var step = audio.duration / 20;
			var handled = false;

			if (e.key === "ArrowLeft" || e.key === "ArrowDown") {
				audio.currentTime = Math.max(0, audio.currentTime - step);
				handled = true;
			}

			if (e.key === "ArrowRight" || e.key === "ArrowUp") {
				audio.currentTime = Math.min(audio.duration, audio.currentTime + step);
				handled = true;
			}

			if (e.key === "Home") {
				audio.currentTime = 0;
				handled = true;
			}

			if (e.key === "End") {
				audio.currentTime = audio.duration;
				handled = true;
			}

			if (handled) {
				e.preventDefault();
				updateProgress();
			}
		});

		audio.addEventListener("loadedmetadata", function () {
			updateTime();
			updateBuffer();
			progressWrap.setAttribute("aria-valuemax", "100");
			progressWrap.setAttribute("aria-valuetext", formatTime(audio.currentTime) + " / " + formatTime(audio.duration));
		});

		audio.addEventListener("progress", function () {
			updateBuffer();
		});

		audio.addEventListener("timeupdate", function () {
			updateTime();
			if (audio.duration) {
				const pct = (audio.currentTime / audio.duration) * 100;
				progress.style.width = pct + "%";
				progressWrap.setAttribute("aria-valuenow", String(Math.round(pct)));
			}
		});

		audio.addEventListener("ended", function () {
			player.classList.remove("active");
			playIcon.style.display  = "";
			pauseIcon.style.display = "none";
			button.setAttribute("aria-pressed", "false");
			button.setAttribute("aria-label", playLabel);
			clearTimeout(progressTimeout);
			progress.style.width    = "0%";
			progressWrap.setAttribute("aria-valuenow", "0");
			updateTime();
		});

		// store so other loops can clear it
		player._progressTimeout = progressTimeout;
	});



});
