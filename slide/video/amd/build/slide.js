define("slidetype_video/slide", ["exports", "jquery", "mod_slides/selectors", "core/ajax", "core/fragment", "core/templates", "core/loadingicon", "mod_slides/slide", "media_videojs/video-lazy", "core_filters/events"], function (_exports, _jquery, _selectors, _ajax, Fragment, Templates, LoadingIcon, _slide, VideoJS, FilterEvents) {
  "use strict";

  _exports.__esModule = true;
  _exports.default = void 0;
  _jquery = _interopRequireDefault(_jquery);
  _ajax = _interopRequireDefault(_ajax);
  Fragment = _interopRequireWildcard(Fragment);
  Templates = _interopRequireWildcard(Templates);
  LoadingIcon = _interopRequireWildcard(LoadingIcon);
  _slide = _interopRequireDefault(_slide);
  VideoJS = _interopRequireWildcard(VideoJS);
  FilterEvents = _interopRequireWildcard(FilterEvents);
  function _interopRequireWildcard(e, t) { if ("function" == typeof WeakMap) var r = new WeakMap(), n = new WeakMap(); return (_interopRequireWildcard = function (e, t) { if (!t && e && e.__esModule) return e; var o, i, f = { __proto__: null, default: e }; if (null === e || "object" != typeof e && "function" != typeof e) return f; if (o = t ? n : r) { if (o.has(e)) return o.get(e); o.set(e, f); } for (const t in e) "default" !== t && {}.hasOwnProperty.call(e, t) && ((i = (o = Object.defineProperty) && Object.getOwnPropertyDescriptor(e, t)) && (i.get || i.set) ? o(f, t, i) : f[t] = e[t]); return f; })(e, t); }
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  const getRoot = document.querySelector(_selectors.SELECTORS.root);
  const SELECTORS = {
    ..._selectors.SELECTORS,
    ...{
      flipCardBlock: '.flip-card-block'
    }
  };
  class Slide extends _slide.default {
    constructor(element, nctslides, options) {
      super(element, nctslides, options, false);
      this.currentListenItem = null;
      this.timeOut = null;
      if (this.clickToView) {
        this.clickToView.remove();
        this.clickToView = null;
      }
    }
    startViewContent() {
      var contents = this.element.querySelectorAll(SELECTORS.listenItem);
      this.contentAnimation(contents);
      setTimeout(() => {
        if (!contents.length) {
          return;
        }
        const videoElement = contents[0].querySelector('.video-js');
        if (!videoElement) {
          return;
        }
        var player = VideoJS.getPlayer(videoElement.id);
        if (!player) {
          return;
        }
        if (this.options.notCompleted || !this.options.completed) {
          player.play();
        }
        var needsWatch = this.options.forcelisten == _selectors.forceListen.audio || this.options.forcelisten == _selectors.forceListen.duration;
        var alreadyDone = parseInt(this.element.dataset.viewed) || this.options.completed;
        if (needsWatch && !alreadyDone) {
          this.lockNav();
        }
        this.videoEvents(player);
      }, this.interval);
    }
    lockNav() {
      this.element.dataset.forcelocked = '1';
      var rootEl = document.getElementById('mod-nct-slides');
      if (rootEl) {
        rootEl.classList.add('slides-forcelocked');
      }
    }
    unlockNav() {
      if (this.element.dataset.forcelocked) {
        delete this.element.dataset.forcelocked;
      }
      var rootEl = document.getElementById('mod-nct-slides');
      if (rootEl) {
        rootEl.classList.remove('slides-forcelocked');
      }
    }
    videoEvents(player) {
      const self = this;
      console.log(player);
      const currentIndex = 1;
      if (this.options.forcelisten == _selectors.forceListen.audio) {
        player.on('ended', function () {
          self.loadListenItem();
          self.unlockNav();
        });
      } else if (this.options.forcelisten == _selectors.forceListen.duration && currentIndex in self.options.listenduration) {
        var timeUpdateEvent = function (e) {
          if (player.currentTime() >= self.options.listenduration[currentIndex]) {
            self.loadListenItem();
            self.unlockNav();
            player.off('timeupdate', timeUpdateEvent);
          }
        };
        player.on('timeupdate', timeUpdateEvent);
        player.on('ended', function () {
          self.loadListenItem();
          self.unlockNav();
        });
      } else {
        self.loadListenItem();
      }
      const audios = this.element.querySelector(SELECTORS.listenItem).querySelectorAll('audio');
      if (audios.length <= 0) {
        return false;
      }
      const audio = audios[0];
      player.on('play', function () {
        if (!audio.ended) {
          audio.play();
        }
        self.nctSlides.setCurrentAudio(audio);
        self.currentAudio = audio;
      });
      player.on('pause', function () {
        self.nctSlides.setCurrentAudio(null);
        self.currentAudio = null;
        audio.pause();
      });
      player.on('ended', function (e) {
        self.nctSlides.setCurrentAudio(null);
        self.currentAudio = null;
        audio.pause();
      });
    }
    updateNextItem(completedIndex) {
      this.element.querySelector(SELECTORS.listenItem + '[data-index="' + completedIndex + '"]').dataset.completed = true;
    }
  }
  _exports.default = Slide;
});