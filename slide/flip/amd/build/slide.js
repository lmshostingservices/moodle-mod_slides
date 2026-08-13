define("slidetype_flip/slide", ["exports", "jquery", "mod_slides/selectors", "core/ajax", "core/fragment", "core/templates", "core/loadingicon", "mod_slides/slide"], function (_exports, _jquery, _selectors, _ajax, Fragment, Templates, LoadingIcon, _slide) {
  "use strict";

  _exports.__esModule = true;
  _exports.default = void 0;
  _jquery = _interopRequireDefault(_jquery);
  _ajax = _interopRequireDefault(_ajax);
  Fragment = _interopRequireWildcard(Fragment);
  Templates = _interopRequireWildcard(Templates);
  LoadingIcon = _interopRequireWildcard(LoadingIcon);
  _slide = _interopRequireDefault(_slide);
  function _interopRequireWildcard(e, t) { if ("function" == typeof WeakMap) var r = new WeakMap(), n = new WeakMap(); return (_interopRequireWildcard = function (e, t) { if (!t && e && e.__esModule) return e; var o, i, f = { __proto__: null, default: e }; if (null === e || "object" != typeof e && "function" != typeof e) return f; if (o = t ? n : r) { if (o.has(e)) return o.get(e); o.set(e, f); } for (const t in e) "default" !== t && {}.hasOwnProperty.call(e, t) && ((i = (o = Object.defineProperty) && Object.getOwnPropertyDescriptor(e, t)) && (i.get || i.set) ? o(f, t, i) : f[t] = e[t]); return f; })(e, t); }
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  const getRoot = document.querySelector(_selectors.SELECTORS.root);
  const SELECTORS = {
    ..._selectors.SELECTORS,
    ...{
      flipCardBlock: '.flip-card-block',
      flipFeedback: '.flip-feedback-side',
      flipCompleted: 'flip-completed'
    }
  };
  class Slide extends _slide.default {
    constructor(element, nctslides, options) {
      super(element, nctslides, options);
      this.currentListenItem = null;
      this.timeOut = null;
    }
    initContentDisplay() {
      this.updateBoxHeight();
      super.initContentDisplay();
    }
    updateBoxHeight() {
      const items = this.element.querySelectorAll(SELECTORS.listenItem);
      let maxHeight = 0;
      var hiddenElements = this.element.querySelectorAll('.hide');
      hiddenElements.forEach(e => {
        e.classList.remove('hide');
        e.style.visibility = 'hidden';
      });
      items.forEach(item => {
        var flip = item.querySelector(SELECTORS.flipCardBlock);
        var itemHeight = item.clientHeight;
        if (itemHeight > maxHeight) {
          maxHeight = itemHeight;
        }
        flip.parentNode.classList.add('child-flipped');
        flip.dataset.flipped = 'true';
        flip.classList.add('flipped');
        var itemHeight = item.clientHeight;
        if (itemHeight > maxHeight) {
          maxHeight = itemHeight;
        }
        flip.parentNode.classList.remove('child-flipped');
        flip.classList.remove('flipped');
        flip.dataset.flipped = 'false';
      });
      items.forEach(item => {
        item.style.height = `${maxHeight}px`;
      });
      hiddenElements.forEach(e => {
        e.classList.add('hide');
        e.style.visibility = 'visible';
      });
    }
    startViewContent() {
      var contents = this.element.querySelectorAll(SELECTORS.listenItem);
      this.contentAnimation(contents);
      var self = this;
      Array.from(contents).forEach(content => {
        content.addEventListener('click', e => this.doFlip(e, self));
      });
      if (this.options.completed) {
        var result = true;
        contents.forEach(e => {
          if (e.dataset.completed != "true") {
            result = false;
            return;
          }
        });
        if (result) {
          this.loadListenItem();
        }
      }
    }
    doFlip(e, self) {
      const target = e.target.closest(SELECTORS.listenItem);
      var cardBlock = target.querySelector(SELECTORS.flipCardBlock);
      const anim = cardBlock.getAnimations()?.[0];
      if (cardBlock.dataset.flipped == 'true') {
        anim.play();
        cardBlock.classList.remove('flipped');
        cardBlock.parentNode.classList.remove('child-flipped');
        cardBlock.dataset.flipped = false;
      } else {
        if (anim !== undefined) {
          anim.play();
        } else {
          cardBlock.classList.add('animate__animated');
          cardBlock.classList.add('animate__flipInX');
        }
        cardBlock.classList.add('flipped');
        cardBlock.parentNode.classList.add('child-flipped');
        cardBlock.dataset.flipped = true;
      }
      self.listentime = 0;
      self.startTime = 0;
      self.nctSlides.stopAudio();
      if (target.dataset.completed != "true") {
        self.forceListen(target, true);
      }
      if (self.element.querySelector('.flipped') !== null) {
        self.element.classList.add('content-flipped');
      } else {
        self.element.classList.remove('content-flipped');
      }
    }
    updateNextItem(completedIndex) {
      this.element.querySelector(SELECTORS.listenItem + '[data-index="' + completedIndex + '"]').dataset.completed = true;
      this.element.querySelector(SELECTORS.listenItem + '[data-index="' + completedIndex + '"] ' + SELECTORS.flipCardBlock).classList.add(SELECTORS.flipCompleted);
    }
    forceListen(listenElement, continuePlay) {
      var self = this;
      this.startTime = Math.round(Date.now() / 1000);
      const currentIndex = listenElement.dataset.index || 0;
      this.currentListenItem = currentIndex;
      this.timeOut == 0 || clearTimeout(this.timeOut);
      const enableNextButton = () => {
        self.loadListenItem(this.currentListenItem);
        this.enableCourseIndexMenu(false);
        this.element.querySelector(SELECTORS.listenItem + '[data-index="' + currentIndex + '"]').dataset.completed = true;
        this.element.querySelector(SELECTORS.listenItem + '[data-index="' + currentIndex + '"] ' + SELECTORS.flipCardBlock).classList.add(SELECTORS.flipCompleted);
        self.nctSlides.setCurrentAudio(null);
      };
      if (self.options.forcelisten == _selectors.forceListen.none) {
        enableNextButton();
      }
      const audios = listenElement.querySelectorAll('audio');
      let currentAudioIndex = 0;
      const confirmAudioCompletes = () => {
        var pendingAudio = 0;
        audios.forEach(audio => audio.ended || pendingAudio++);
        return pendingAudio == 0;
      };
      const playNextAudio = () => {
        if (currentAudioIndex < audios.length) {
          self.nctSlides.setCurrentAudio(audios[currentAudioIndex]);
          audios[currentAudioIndex].play();
          document.removeEventListener('click', playNextAudio, false);
        }
      };
      const verifyTimeSpent = () => {
        var forceDuration = parseInt(self.options.listenduration[currentIndex]);
        if (self.getListenDuration() >= forceDuration) {
          enableNextButton();
        } else {
          this.timeOut = setTimeout(verifyTimeSpent, parseInt(forceDuration - self.getListenDuration()) * 1000);
        }
      };
      if (audios.length > 0) {
        var i = 0;
        audios.forEach(audio => {
          audio.onended = () => {
            console.log('audio-ended');
            console.log('confirmAudioCompletes', confirmAudioCompletes());
            audio.closest(SELECTORS.listenItem).classList.remove(SELECTORS.audioplayed);
            if (confirmAudioCompletes()) {
              if (self.options.forcelisten == _selectors.forceListen.audio) {
                enableNextButton();
              }
            } else {
              currentAudioIndex++;
              playNextAudio();
            }
          };
          if (!i) {
            playNextAudio();
          }
          i++;
        });
      } else {
        if (self.options.forcelisten === _selectors.forceListen.audio) {
          enableNextButton();
        }
      }
      if (self.options.forcelisten === _selectors.forceListen.duration && currentIndex in self.options.listenduration) {
        this.timeOut = setTimeout(verifyTimeSpent, Math.floor(parseInt(self.options.listenduration[currentIndex]) * 1000));
      }
    }
    resizeAdditionalContent() {
      super.resizeAdditionalContent();
      this.element.querySelectorAll(SELECTORS.flipCardBlock).forEach(cardBlock => {
        var cardHeight = cardBlock.clientHeight;
        cardBlock.dataset.flipped = 'true';
        cardBlock.classList.add('flipped');
        cardBlock.style.maxHeight = cardHeight + 'px';
        const feedbackSide = cardBlock.querySelector(SELECTORS.flipFeedback);
        if (feedbackSide) {
          let fontSize = parseFloat(window.getComputedStyle(feedbackSide).fontSize);
          feedbackSide.style.fontSize = `${fontSize}px`;
          while (feedbackSide.scrollHeight <= cardHeight && fontSize < cardHeight && fontSize < 35) {
            fontSize++;
            feedbackSide.style.fontSize = `${fontSize}px`;
          }
          while (feedbackSide.scrollHeight > cardHeight && fontSize > 9) {
            fontSize--;
            feedbackSide.style.fontSize = `${fontSize}px`;
          }
        }
        cardBlock.classList.remove('flipped');
        cardBlock.dataset.flipped = 'false';
      });
    }
  }
  _exports.default = Slide;
});