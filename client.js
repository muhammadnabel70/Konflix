// FINAL PROTECTED CANVAS PLAYER
// Features:
// ✔ HiDPI canvas (tajam)
// ✔ Adaptive watermark (opacity & warna menyesuaikan scene)
// ✔ Blur on hidden tab
// ✔ Anti right-click
// ✔ Watermark halus + tetap terbaca

const video = document.getElementById('sourceVideo');
const canvas = document.getElementById('canvasPlayer');
const ctx = canvas.getContext('2d', { alpha: false });

const playBtn = document.getElementById('playBtn');
const pauseBtn = document.getElementById('pauseBtn');
const usernameInput = document.getElementById('username');

// Device Pixel Ratio
let dpr = window.devicePixelRatio || 1;
let raf = null;

// Watermark movement
let watermark = { x: 30, y: 50, vx: 0.5, vy: 0.25 };

// ==========================
// Setup HiDPI canvas
// ==========================
function setupCanvas() {
  const w = video.videoWidth || 1280;
  const h = video.videoHeight || 720;

  canvas.width = w * dpr;
  canvas.height = h * dpr;

  canvas.style.width = w + 'px';
  canvas.style.height = h + 'px';

  ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
}

video.addEventListener("loadedmetadata", () => {
  setupCanvas();
  video.style.display = 'none';
});


// ==========================
// Adaptive Luminance Sampler
// ==========================
function averageLuminance(x, y, w, h) {
  try {
    const img = ctx.getImageData(
      Math.max(0, Math.round(x)),
      Math.max(0, Math.round(y - h)),
      Math.round(w),
      Math.round(h)
    );

    let sum = 0, count = 0;
    for (let i = 0; i < img.data.length; i += 16) {
      const r = img.data[i], g = img.data[i+1], b = img.data[i+2];
      sum += (0.299*r + 0.587*g + 0.114*b);
      count++;
    }
    return sum / count;
  } catch {
    return 128;
  }
}


// ==========================
// Adaptive Watermark Drawing
// ==========================
function drawAdaptiveWatermark(text, x, y) {
  const metrics = ctx.measureText(text);
  const textW = Math.max(60, metrics.width);
  const textH = parseInt(ctx.font) || 18;

  const lum = averageLuminance(x, y, textW, textH);
  const norm = lum / 255;

  let alpha;
  if (norm > 0.7) alpha = 0.16;
  else if (norm > 0.5) alpha = 0.12;
  else if (norm > 0.3) alpha = 0.08;
  else alpha = 0.05;  // sangat gelap → sangat halus

  let fill, stroke;
  if (norm > 0.4) {
    fill = `rgba(255,255,255,${alpha})`;
    stroke = `rgba(0,0,0,${alpha + 0.05})`;
  } else {
    fill = `rgba(0,0,0,${alpha})`;
    stroke = `rgba(255,255,255,${alpha - 0.02})`;
  }

  ctx.save();
  ctx.lineWidth = 2;
  ctx.strokeStyle = stroke;
  ctx.fillStyle = fill;
  ctx.strokeText(text, x, y);
  ctx.fillText(text, x, y);
  ctx.restore();
}


// ==========================
// Main Draw Loop
// ==========================
function drawLoop() {
  if (video.paused || video.ended) {
    cancelAnimationFrame(raf);
    raf = null;
    return;
  }

  // Draw video frame
  ctx.drawImage(video, 0, 0, canvas.width / dpr, canvas.height / dpr);

  // Set watermark font size
  const cssW = canvas.width / dpr;
  const fontSize = Math.max(10, Math.round(cssW / 70));
  ctx.font = `${fontSize}px Arial`;

  // Move watermark
  watermark.x += watermark.vx;
  watermark.y += watermark.vy;

  const maxX = (canvas.width / dpr) - 240;
  const maxY = (canvas.height / dpr) - 30;

  if (watermark.x < 10 || watermark.x > maxX) watermark.vx *= -1;
  if (watermark.y < 20 || watermark.y > maxY) watermark.vy *= -1;

  // Draw adaptive watermark
  const text = `${usernameInput.value || 'anonymous'} • ${new Date().toLocaleString()}`;
  drawAdaptiveWatermark(text, watermark.x, watermark.y);

  // Micro anti-static
  if (Math.random() < 0.002) {
    ctx.fillStyle = "rgba(255,255,255,0.01)";
    ctx.fillRect(
      Math.random() * (canvas.width / dpr),
      Math.random() * (canvas.height / dpr),
      6,
      4
    );
  }

  raf = requestAnimationFrame(drawLoop);
}


// ==========================
// Controls
// ==========================
playBtn.addEventListener("click", async () => {
  await video.play();
  if (!raf) raf = requestAnimationFrame(drawLoop);
});

pauseBtn.addEventListener("click", () => {
  video.pause();
  cancelAnimationFrame(raf);
  raf = null;
});


// ==========================
// Blur on tab-change
// ==========================
document.addEventListener("visibilitychange", () => {
  if (document.hidden) {
    ctx.filter = "blur(22px) brightness(0.3)";
    ctx.drawImage(video, 0, 0, canvas.width / dpr, canvas.height / dpr);
    ctx.filter = "none";
  } else {
    if (!video.paused && !raf) raf = requestAnimationFrame(drawLoop);
  }
});


// ==========================
// Block right click
// ==========================
window.addEventListener("contextmenu", e => {
  if (e.target === canvas) e.preventDefault();
});
