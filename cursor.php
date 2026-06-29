<div class="cursor-dot" id="dot"></div>
<div class="cursor-ring" id="ring"></div>

<script>
const dot = document.getElementById('dot');
const ring = document.getElementById('ring');
let mx = 0, my = 0, rx = 0, ry = 0;

document.addEventListener('mousemove', e => {
  mx = e.clientX;
  my = e.clientY;
  dot.style.left = mx + 'px';
  dot.style.top = my + 'px';

  // Footer detect karo
  const footer = document.querySelector('footer');
  if(footer) {
    const rect = footer.getBoundingClientRect();
    if(e.clientY >= rect.top) {
      dot.style.background = '#ffeed9';
      ring.style.borderColor = '#ffeed9';
    } else {
      dot.style.background = '#C84B31';
      ring.style.borderColor = '#C84B31';
    }
  }
});

function animateRing() {
  rx += (mx - rx) * 0.12;
  ry += (my - ry) * 0.12;
  ring.style.left = rx + 'px';
  ring.style.top = ry + 'px';
  requestAnimationFrame(animateRing);
}
animateRing();
</script>