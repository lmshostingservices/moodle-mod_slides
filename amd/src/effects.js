/**
 * Slides UX effects — synthesized sounds + confetti.
 *
 * Self-contained: no audio files, no external libraries. Sounds are generated with
 * the Web Audio API and confetti is drawn on a throwaway canvas, so the whole thing
 * ships inside the AMD build and works offline.
 *
 * A single instance is exposed on window.NctSlidesFX so any slide-type module
 * (including the drag-drop subplugin) can trigger effects without cross-plugin AMD
 * wiring: window.NctSlidesFX && window.NctSlidesFX.play('pickup').
 *
 * @module     mod_slides/effects
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

let audioCtx = null;
let muted = false;

/**
 * Lazily create (and resume) the shared AudioContext. Browsers only allow this
 * after a user gesture; every caller here is a click/drag, so it resumes fine.
 *
 * @return {AudioContext|null}
 */
const getCtx = () => {
    if (muted) {
        return null;
    }
    try {
        if (audioCtx === null) {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) {
                return null;
            }
            audioCtx = new Ctx();
        }
        if (audioCtx.state === 'suspended' && audioCtx.resume) {
            audioCtx.resume();
        }
        return audioCtx;
    } catch (e) {
        return null;
    }
};

/**
 * Play a single shaped tone.
 *
 * @param {AudioContext} ctx
 * @param {number} freq start frequency (Hz)
 * @param {number} start delay before the note (s)
 * @param {number} dur note length (s)
 * @param {object} opts {type, gain, endFreq}
 */
const tone = (ctx, freq, start, dur, opts) => {
    opts = opts || {};
    const t0 = ctx.currentTime + start;
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = opts.type || 'sine';
    osc.frequency.setValueAtTime(freq, t0);
    if (opts.endFreq) {
        osc.frequency.exponentialRampToValueAtTime(opts.endFreq, t0 + dur);
    }
    const peak = opts.gain === undefined ? 0.18 : opts.gain;
    // Quick attack, smooth exponential release — feels tactile, never clicky.
    gain.gain.setValueAtTime(0.0001, t0);
    gain.gain.exponentialRampToValueAtTime(peak, t0 + 0.012);
    gain.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start(t0);
    osc.stop(t0 + dur + 0.02);
};

// Note frequencies (equal temperament) used by the cues.
const N = {
    C4: 261.63, D4: 293.66, E4: 329.63, F4: 349.23, G4: 392.00, A4: 440.00,
    C5: 523.25, D5: 587.33, E5: 659.25, G5: 783.99, C6: 1046.50
};

/**
 * The sound library. Each entry is a short, satisfying UI cue.
 */
const CUES = {
    // Grab a draggable item.
    pickup: (ctx) => tone(ctx, N.A4, 0, 0.09, {type: 'triangle', gain: 0.12, endFreq: N.C5}),
    // Put an item down (soft).
    drop: (ctx) => tone(ctx, N.E4, 0, 0.12, {type: 'sine', gain: 0.14, endFreq: N.C4}),
    // A choice/selection click.
    select: (ctx) => tone(ctx, N.D5, 0, 0.07, {type: 'square', gain: 0.06}),
    // Flip a card.
    flip: (ctx) => tone(ctx, N.G4, 0, 0.16, {type: 'sine', gain: 0.10, endFreq: N.G5}),
    // A correct / matched answer — bright two-note lift.
    correct: (ctx) => {
        tone(ctx, N.E5, 0, 0.11, {type: 'triangle', gain: 0.14});
        tone(ctx, N.G5, 0.09, 0.16, {type: 'triangle', gain: 0.14});
    },
    // Wrong / rejected — gentle low blip (not harsh).
    wrong: (ctx) => tone(ctx, N.C4, 0, 0.16, {type: 'sine', gain: 0.10, endFreq: 174.61}),
    // Activity complete — triumphant ascending arpeggio.
    complete: (ctx) => {
        const seq = [N.C5, N.E5, N.G5, N.C6];
        seq.forEach((f, i) => tone(ctx, f, i * 0.12, 0.34, {type: 'triangle', gain: 0.16}));
        // A shimmer on top.
        tone(ctx, N.G5, 0.48, 0.5, {type: 'sine', gain: 0.08, endFreq: N.C6});
    }
};

/**
 * Play a named sound cue. Silently no-ops if audio is unavailable.
 *
 * @param {string} name one of the CUES keys
 */
export const play = (name) => {
    const cue = CUES[name];
    if (!cue) {
        return;
    }
    const ctx = getCtx();
    if (!ctx) {
        return;
    }
    try {
        cue(ctx);
    } catch (e) {
        // Never let a sound break the activity.
    }
};

/**
 * Fire a celebratory confetti burst over the viewport.
 *
 * @param {object} options {particles}
 */
export const confetti = (options) => {
    options = options || {};
    if (typeof document === 'undefined' || !window.requestAnimationFrame) {
        return;
    }
    // Respect reduced-motion preferences.
    try {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
    } catch (e) {
        // Ignore.
    }

    const canvas = document.createElement('canvas');
    canvas.className = 'nctslides-confetti-canvas';
    canvas.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:2147483000;';
    const dpr = window.devicePixelRatio || 1;
    const w = window.innerWidth;
    const h = window.innerHeight;
    canvas.width = w * dpr;
    canvas.height = h * dpr;
    document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);

    const colors = ['#0f6cbf', '#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#a855f7', '#ec4899', '#14b8a6'];
    const count = options.particles || 160;
    const parts = [];
    for (let i = 0; i < count; i++) {
        parts.push({
            x: w / 2 + (i % 2 ? 1 : -1) * (Math.sqrt(i) * 6),
            y: h * 0.32,
            vx: (((i * 73) % 100) / 100 - 0.5) * 14,
            vy: -8 - ((i * 37) % 100) / 100 * 9,
            g: 0.28 + ((i * 17) % 100) / 100 * 0.18,
            size: 6 + ((i * 29) % 100) / 100 * 6,
            rot: ((i * 53) % 360) * Math.PI / 180,
            vr: (((i * 41) % 100) / 100 - 0.5) * 0.4,
            color: colors[i % colors.length],
            life: 1
        });
    }

    let frame = 0;
    const maxFrames = 180;
    const tick = () => {
        frame++;
        ctx.clearRect(0, 0, w, h);
        parts.forEach((p) => {
            p.vy += p.g;
            p.vx *= 0.99;
            p.x += p.vx;
            p.y += p.vy;
            p.rot += p.vr;
            if (frame > maxFrames - 60) {
                p.life -= 1 / 60;
            }
            ctx.save();
            ctx.globalAlpha = Math.max(0, p.life);
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rot);
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6);
            ctx.restore();
        });
        if (frame < maxFrames) {
            window.requestAnimationFrame(tick);
        } else if (canvas.parentNode) {
            canvas.parentNode.removeChild(canvas);
        }
    };
    window.requestAnimationFrame(tick);
};

/**
 * Big finish: confetti + the completion fanfare together.
 */
export const celebrate = () => {
    confetti();
    play('complete');
};

/**
 * Turn all sound on/off (confetti is unaffected).
 *
 * @param {boolean} value
 */
export const setMuted = (value) => {
    muted = !!value;
};

/**
 * Expose a global handle so slide-type modules (and the drag-drop subplugin) can
 * trigger effects without importing this module across plugin boundaries.
 */
export const init = () => {
    if (!window.NctSlidesFX) {
        window.NctSlidesFX = {play, confetti, celebrate, setMuted};
    }
    return window.NctSlidesFX;
};

export default {play, confetti, celebrate, setMuted, init};
