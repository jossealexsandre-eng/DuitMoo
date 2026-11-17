import Chart from 'chart.js/auto';
window.Chart = Chart;

// Register DuitMoo Service Worker safely
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Silent fallback if browser restricts SW (e.g. non-HTTPS local IP)
        });
    });
}

// Capture beforeinstallprompt for custom Install PWA button
window.duitmooInstallPrompt = null;
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    window.duitmooInstallPrompt = event;
    window.dispatchEvent(new CustomEvent('duitmoo-pwa-installable'));
});

// Synthesized Web Audio & Haptic Feedback Engine (Retro Coin & Tactile Pop)
window.duitmooFeedback = function (type = 'tap') {
    const soundEnabled = localStorage.getItem('duitmoo_sound') !== 'off';
    const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!prefersReducedMotion && navigator.vibrate) {
        try {
            if (type === 'coin') {
                navigator.vibrate([25, 40, 35]);
            } else {
                navigator.vibrate(12);
            }
        } catch (_) {}
    }

    if (!soundEnabled) return;

    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;

        if (!window._duitmooAudioCtx) {
            window._duitmooAudioCtx = new AudioCtx();
        }
        const ctx = window._duitmooAudioCtx;
        if (ctx.state === 'suspended') {
            ctx.resume();
        }

        const now = ctx.currentTime;

        if (type === 'coin') {
            // Two-note retro coin chime (B5 -> E6)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(987.77, now);
            gain1.gain.setValueAtTime(0.14, now);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.09);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(now);
            osc1.stop(now + 0.09);

            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'triangle';
            osc2.frequency.setValueAtTime(1318.51, now + 0.085);
            gain2.gain.setValueAtTime(0.16, now + 0.085);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.36);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(now + 0.085);
            osc2.stop(now + 0.36);
        } else {
            // Soft tactile click
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(520, now);
            osc.frequency.exponentialRampToValueAtTime(260, now + 0.045);
            gain.gain.setValueAtTime(0.08, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.045);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(now);
            osc.stop(now + 0.045);
        }
    } catch (_) {}
};
