define("mod_slides/effects", ["exports"], function (_exports) {
  "use strict";

  _exports.__esModule = true;
  _exports.setMuted = _exports.play = _exports.init = _exports.default = _exports.confetti = _exports.celebrate = void 0;
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
    gain.gain.setValueAtTime(0.0001, t0);
    gain.gain.exponentialRampToValueAtTime(peak, t0 + 0.012);
    gain.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start(t0);
    osc.stop(t0 + dur + 0.02);
  };
  const N = {
    C4: 261.63,
    D4: 293.66,
    E4: 329.63,
    F4: 349.23,
    G4: 392.00,
    A4: 440.00,
    C5: 523.25,
    D5: 587.33,
    E5: 659.25,
    G5: 783.99,
    C6: 1046.50
  };
  const CUES = {
    pickup: ctx => tone(ctx, N.A4, 0, 0.09, {
      type: 'triangle',
      gain: 0.12,
      endFreq: N.C5
    }),
    drop: ctx => tone(ctx, N.E4, 0, 0.12, {
      type: 'sine',
      gain: 0.14,
      endFreq: N.C4
    }),
    select: ctx => tone(ctx, N.D5, 0, 0.07, {
      type: 'square',
      gain: 0.06
    }),
    flip: ctx => tone(ctx, N.G4, 0, 0.16, {
      type: 'sine',
      gain: 0.10,
      endFreq: N.G5
    }),
    correct: ctx => {
      tone(ctx, N.E5, 0, 0.11, {
        type: 'triangle',
        gain: 0.14
      });
      tone(ctx, N.G5, 0.09, 0.16, {
        type: 'triangle',
        gain: 0.14
      });
    },
    wrong: ctx => tone(ctx, N.C4, 0, 0.16, {
      type: 'sine',
      gain: 0.10,
      endFreq: 174.61
    }),
    complete: ctx => {
      const seq = [N.C5, N.E5, N.G5, N.C6];
      seq.forEach((f, i) => tone(ctx, f, i * 0.12, 0.34, {
        type: 'triangle',
        gain: 0.16
      }));
      tone(ctx, N.G5, 0.48, 0.5, {
        type: 'sine',
        gain: 0.08,
        endFreq: N.C6
      });
    }
  };
  const play = name => {
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
    } catch (e) {}
  };
  _exports.play = play;
  const confetti = options => {
    options = options || {};
    if (typeof document === 'undefined' || !window.requestAnimationFrame) {
      return;
    }
    try {
      if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
      }
    } catch (e) {}
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
        vx: (i * 73 % 100 / 100 - 0.5) * 14,
        vy: -8 - i * 37 % 100 / 100 * 9,
        g: 0.28 + i * 17 % 100 / 100 * 0.18,
        size: 6 + i * 29 % 100 / 100 * 6,
        rot: i * 53 % 360 * Math.PI / 180,
        vr: (i * 41 % 100 / 100 - 0.5) * 0.4,
        color: colors[i % colors.length],
        life: 1
      });
    }
    let frame = 0;
    const maxFrames = 180;
    const tick = () => {
      frame++;
      ctx.clearRect(0, 0, w, h);
      parts.forEach(p => {
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
  _exports.confetti = confetti;
  const celebrate = () => {
    confetti();
    play('complete');
  };
  _exports.celebrate = celebrate;
  const setMuted = value => {
    muted = !!value;
  };
  _exports.setMuted = setMuted;
  const init = () => {
    if (!window.NctSlidesFX) {
      window.NctSlidesFX = {
        play,
        confetti,
        celebrate,
        setMuted
      };
    }
    return window.NctSlidesFX;
  };
  _exports.init = init;
  var _default = _exports.default = {
    play,
    confetti,
    celebrate,
    setMuted,
    init
  };
});