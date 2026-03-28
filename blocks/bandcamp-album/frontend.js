document.addEventListener("DOMContentLoaded", function () {
  const playlists = document.querySelectorAll(".bandcamp-album");

  playlists.forEach((playlist) => {
    const audio = playlist.querySelector("audio");
    const trackLinks = Array.from(playlist.querySelectorAll(".bandcamp-album-tracks .track-link"));
    let current = 0;

	if (trackLinks == null || audio == null ) { return; }
	
    trackLinks.forEach((link, index) => {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        current = index;
        playTrack(link);
      });
    });

    audio.addEventListener("ended", function () {
      current = (current + 1) % trackLinks.length;
      playTrack(trackLinks[current]);
    });

    function playTrack(link) {
      audio.src = link.getAttribute("data-track");
      audio.play();

      // Optional: highlight the active track
      trackLinks.forEach((l) => l.closest("li").classList.remove("active"));
      link.closest("li").classList.add("active");
    }
  });
});