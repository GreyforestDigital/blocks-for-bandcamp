document.addEventListener("DOMContentLoaded", () => {
  const players = document.querySelectorAll(".bandcamp-miniplayer");

  players.forEach((player) => {
    const audio     = player.querySelector("audio");
    const button    = player.querySelector(".bandcamp-miniplayer-play");
    const playIcon  = button.querySelector("path.play");
    const pauseIcon = button.querySelector("path.pause");
    const progress  = player.querySelector(".bandcamp-miniplayer-progress");

    let progressTimeout;

    // INITIAL STATE
    playIcon.style.display  = "";
    pauseIcon.style.display = "none";
    progress.style.width    = "0%";

    // updates the bar, then re-schedules itself via setTimeout
    function updateProgress() {
      if (!audio.duration) return;
      const pct = (audio.currentTime / audio.duration) * 100;
      progress.style.width = pct + "%";

      if (!audio.paused && !audio.ended) {
        progressTimeout = setTimeout(updateProgress, 250);
      }
    }

    button.addEventListener("click", () => {
      if (audio.paused) {
        // pause/reset all the others
        players.forEach((other) => {
          if (other !== player) {
            const oa     = other.querySelector("audio");
            const obtn   = other.querySelector(".bandcamp-miniplayer-play");
            const oPlay  = obtn.querySelector("path.play");
            const oPause = obtn.querySelector("path.pause");
            const oProg  = other.querySelector(".bandcamp-miniplayer-progress");

            oa.pause();
            other.classList.remove("active");
            oPlay.style.display  = "";
            oPause.style.display = "none";
            clearTimeout(other._progressTimeout);
            oProg.style.width    = "0%";
          }
        });

        // play this one
        audio.play();
        player.classList.add("active");
        playIcon.style.display  = "none";
        pauseIcon.style.display = "";
        updateProgress();

      } else {
        // pause this one
        audio.pause();
        player.classList.remove("active");
        playIcon.style.display  = "";
        pauseIcon.style.display = "none";
        clearTimeout(progressTimeout);
      }
    });

    audio.addEventListener("ended", () => {
      player.classList.remove("active");
      playIcon.style.display  = "";
      pauseIcon.style.display = "none";
      clearTimeout(progressTimeout);
      progress.style.width    = "0%";
    });

    // store so other loops can clear it
    player._progressTimeout = progressTimeout;
  });
});
