document.addEventListener("DOMContentLoaded", function () {
	var players = document.querySelectorAll(".bandcamp-miniplayer");

	players.forEach(function (player) {
		var audio = player.querySelector("audio");
		var button = player.querySelector(".bandcamp-miniplayer-play");
		var playIcon = button.querySelector("path.play");
		var pauseIcon = button.querySelector("path.pause");
		var progress = player.querySelector(".bandcamp-miniplayer-progress");
		var bufferBar = player.querySelector(".bandcamp-miniplayer-progress-buffer");
		var progressWrap = player.querySelector(".bandcamp-miniplayer-progress-wrap");
		var playLabel = button.getAttribute("data-play-label") || "Play Track";
		var pauseLabel = button.getAttribute("data-pause-label") || "Pause Track";
		var progressTimeout;

		/**
		 * Format a time value into MM:SS.
		 *
		 * @param {number} seconds The time value in seconds.
		 * @return {string} The formatted time string.
		 */
		function formatTime(seconds) {
			if (!isFinite(seconds)) {
				return "00:00";
			}

			var mins = Math.floor(seconds / 60);
			var secs = Math.floor(seconds % 60);

			return String(mins).padStart(2, "0") + ":" + String(secs).padStart(2, "0");
		}

		/**
		 * Keep the visual progress bar and ARIA state in sync.
		 */
		function updateProgress() {
			if (!audio.duration) {
				progressWrap.setAttribute("aria-valuenow", "0");
				progressWrap.setAttribute("aria-valuetext", formatTime(0) + " / " + formatTime(audio.duration));
				return;
			}

			var pct = (audio.currentTime / audio.duration) * 100;

			progress.style.width = pct + "%";
			progressWrap.setAttribute("aria-valuenow", String(Math.round(pct)));
			progressWrap.setAttribute("aria-valuetext", formatTime(audio.currentTime) + " / " + formatTime(audio.duration));

			if (!audio.paused && !audio.ended) {
				progressTimeout = setTimeout(updateProgress, 250);
				player._progressTimeout = progressTimeout;
			}
		}

		playIcon.style.display = "";
		pauseIcon.style.display = "none";
		progress.style.width = "0%";
		bufferBar.style.width = "0%";
		progressWrap.setAttribute("aria-valuenow", "0");
		progressWrap.setAttribute("aria-valuetext", "00:00 / 00:00");

		audio.addEventListener("play", function () {
			player.classList.add("active");
			playIcon.style.display = "none";
			pauseIcon.style.display = "";
			button.setAttribute("aria-pressed", "true");
			button.setAttribute("aria-label", pauseLabel);
		});

		audio.addEventListener("pause", function () {
			if (audio.ended) {
				return;
			}

			player.classList.remove("active");
			playIcon.style.display = "";
			pauseIcon.style.display = "none";
			button.setAttribute("aria-pressed", "false");
			button.setAttribute("aria-label", playLabel);
		});

		audio.addEventListener("loadedmetadata", function () {
			progressWrap.setAttribute("aria-valuemax", "100");
			updateProgress();
		});

		audio.addEventListener("progress", function () {
			if (!audio.duration || !audio.buffered.length) {
				return;
			}

			var bufferedEnd = audio.buffered.end(audio.buffered.length - 1);
			var pct = (bufferedEnd / audio.duration) * 100;

			bufferBar.style.width = pct + "%";
		});

		audio.addEventListener("timeupdate", function () {
			updateProgress();
		});

		audio.addEventListener("ended", function () {
			player.classList.remove("active");
			playIcon.style.display = "";
			pauseIcon.style.display = "none";
			button.setAttribute("aria-pressed", "false");
			button.setAttribute("aria-label", playLabel);
			clearTimeout(progressTimeout);
			progress.style.width = "0%";
			progressWrap.setAttribute("aria-valuenow", "0");
			progressWrap.setAttribute("aria-valuetext", "00:00 / 00:00");
		});

		button.addEventListener("click", function () {
			if (audio.paused) {
				players.forEach(function (other) {
					if (other !== player) {
						var oa = other.querySelector("audio");
						var obtn = other.querySelector(".bandcamp-miniplayer-play");
						var oPlay = obtn.querySelector("path.play");
						var oPause = obtn.querySelector("path.pause");
						var oProg = other.querySelector(".bandcamp-miniplayer-progress");
						var oBuffer = other.querySelector(".bandcamp-miniplayer-progress-buffer");
						var oWrap = other.querySelector(".bandcamp-miniplayer-progress-wrap");

						oa.pause();
						oa.currentTime = 0;
						other.classList.remove("active");
						oPlay.style.display = "";
						oPause.style.display = "none";
						obtn.setAttribute("aria-pressed", "false");
						obtn.setAttribute("aria-label", playLabel);
						clearTimeout(other._progressTimeout);
						oProg.style.width = "0%";
						if (oBuffer) {
							oBuffer.style.width = "0%";
						}
						if (oWrap) {
							oWrap.setAttribute("aria-valuenow", "0");
							oWrap.setAttribute("aria-valuetext", "00:00 / 00:00");
						}
					}
				});

				audio.play();
				updateProgress();
			} else {
				audio.pause();
				clearTimeout(progressTimeout);
			}
		});

		progressWrap.addEventListener("click", function (e) {
			if (!audio.duration) {
				return;
			}

			var rect = progressWrap.getBoundingClientRect();
			var clickX = e.clientX - rect.left;
			var pct = clickX / rect.width;

			audio.currentTime = Math.max(0, Math.min(audio.duration, pct * audio.duration));
			updateProgress();
		});

		progressWrap.addEventListener("keydown", function (e) {
			if (!audio.duration) {
				return;
			}

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

		player._progressTimeout = progressTimeout;
	});
});
