define("mod_slides/nctslides", ["exports", "jquery", "mod_slides/selectors", "mod_slides/slide", "core/fragment", "core/templates", "core/loadingicon", "theme_boost/bootstrap/carousel"], function (_exports, _jquery, _selectors, _slide, Fragment, Templates, LoadingIcon, _carousel) {
  "use strict";

  _exports.__esModule = true;
  _exports.init = _exports.default = void 0;
  _jquery = _interopRequireDefault(_jquery);
  _slide = _interopRequireDefault(_slide);
  Fragment = _interopRequireWildcard(Fragment);
  Templates = _interopRequireWildcard(Templates);
  LoadingIcon = _interopRequireWildcard(LoadingIcon);
  function _interopRequireWildcard(e, t) { if ("function" == typeof WeakMap) var r = new WeakMap(), n = new WeakMap(); return (_interopRequireWildcard = function (e, t) { if (!t && e && e.__esModule) return e; var o, i, f = { __proto__: null, default: e }; if (null === e || "object" != typeof e && "function" != typeof e) return f; if (o = t ? n : r) { if (o.has(e)) return o.get(e); o.set(e, f); } for (const t in e) "default" !== t && {}.hasOwnProperty.call(e, t) && ((i = (o = Object.defineProperty) && Object.getOwnPropertyDescriptor(e, t)) && (i.get || i.set) ? o(f, t, i) : f[t] = e[t]); return f; })(e, t); }
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  const getRoot = () => document.querySelector(_selectors.SELECTORS.root);
  var CurrentAudio = null;
  class NctSlides {
    constructor(contextid, slideid, cmid) {
      this.contextID = contextid, this.slideID = slideid, this.cmid = cmid;
      this.slides = [];
      const slidedata = typeof slideOptions !== 'undefined' ? slideOptions : {};
      this.generaldata = slidedata['general'] ?? {};
      this.carouselElem = getRoot().querySelector(_selectors.SELECTORS.carousel);
      this.scrollToSlides();
      this.initializeSlides();
    }
    initializeSlides() {
      var self = this;
      this.addCarouselEvents();
      const slidedata = typeof slideOptions !== 'undefined' ? slideOptions : {};
      const loadSlideInstances = async () => {
        const module = async (element, index) => {
          const options = slidedata[element.dataset.slideinstanceid];
          if (options != undefined && 'customslidemodule' in options && options.customslidemodule != '') {
            const mod = await import(options.customslidemodule);
            const slide = new mod(element, self, options);
            self.slides[element.dataset.slideinstanceid] = slide;
          } else {
            var slide = new _slide.default(element, this, options || {});
            self.slides[element.dataset.slideinstanceid] = slide;
          }
        };
        await Promise.all(Array.from(document.querySelectorAll(_selectors.SELECTORS.slideItem)).map(module));
        return self.slides;
      };
      loadSlideInstances().then(slides => {
        self.autoFontResize(slides);
        document.body.classList.remove('nctslidesview-onloading');
        console.log(Object.values(slides));
        if (slides.length > 0) {
          if (this.generaldata.containerheight == '') {
            self.autoMinHeight(slides);
          }
          var firstElem = document.querySelector(_selectors.SELECTORS.slideItem);
          if (firstElem != null && typeof firstElem != undefined) {
            firstElem.classList.add('active');
            var firstSlide = slides[firstElem.dataset.slideinstanceid];
            if (firstSlide != null) {
              firstSlide.initContentDisplay();
            }
          }
        }
      });
    }
    scrollToSlides() {
      var docElement = document.documentElement || document.querySelector('#page-wrapper');
      let nav = document.querySelector('nav.navbar');
      let elementTop = (window.scrollY || window.pageYOffset) + getRoot().getBoundingClientRect().top - 10;
      var top = parseInt(elementTop - nav.clientHeight);
      docElement.scrollTop = top != 0 ? top : elementTop;
    }
    updateNextSlide(currentSlide) {
      var self = this;
      const promise = Fragment.loadFragment('mod_slides', 'load_next_slide', this.contextID, {
        currentslide: currentSlide.options.slideinstanceid,
        cmid: this.cmid
      });
      promise.then((html, js) => {
        var fakeDiv = document.createElement('div');
        fakeDiv.innerHTML = html;
        var slideinstanceid = fakeDiv.children[0].dataset.slideinstanceid;
        if (document.querySelector(_selectors.SELECTORS.carouselInner + ' .carousel-item[data-slideinstanceid="' + slideinstanceid + '"]')) {
          return false;
        }
        const element = fakeDiv.children[0];
        Templates.appendNodeContents(document.querySelector(_selectors.SELECTORS.carouselInner), element, '');
        Templates.runTemplateJS(js);
        var options = NextSlideData[slideinstanceid];
        if (options != undefined && 'customslidemodule' in options && options.customslidemodule != '') {
          require([options.customslidemodule], function (customSlide) {
            const slide = new customSlide(element, self, options);
            self.slides[element.dataset.slideinstanceid] = slide;
          });
        } else {
          var slide = new _slide.default(element, self, options || {});
          self.slides[element.dataset.slideinstanceid] = slide;
        }
        this.makeNextArrowActive(slideinstanceid);
        currentSlide.options.notCompleted = false;
        currentSlide.options.completed = true;
      }).catch(Notification.exception);
    }
    updateNextButtons(currentSlide) {
      var self = this;
      const region = document.querySelector(_selectors.SELECTORS.activityRegion);
      if (region !== null) {
        var completionEvent = new CustomEvent('core_course:manualcompletiontoggled', {
          bubbles: true,
          detail: {
            cmid: self.cmid,
            completed: true
          }
        });
        region.dispatchEvent(completionEvent);
      }
      return true;
    }
    static createInstance(contextid, slideid, cmid) {
      console.log('NctSlides instance created');
      return new NctSlides(contextid, slideid, cmid);
    }
    setCurrentAudio(audio) {
      CurrentAudio = audio;
    }
    getCurrentAudio() {
      return CurrentAudio;
    }
    stopAudio() {
      console.log(CurrentAudio);
      if (CurrentAudio !== null) CurrentAudio.pause();
    }
    makeNextArrowActive(slideinstanceid) {
      const arrow = document.querySelector(_selectors.SELECTORS.nextArrow);
      this.forceNextButton(document.querySelector(_selectors.SELECTORS.carousel + ' ' + _selectors.SELECTORS.activeSlide));
      if (arrow !== null) {
        arrow.classList.add(_selectors.SELECTORS.others.nextSlideAvailable);
        arrow.dataset.nextInstance = slideinstanceid;
        arrow.style.pointerEvents = 'auto';
        const indicators = document.querySelector(_selectors.SELECTORS.indicators);
        if (indicators !== null && indicators.querySelector('[data-slideinstanceid="' + slideinstanceid + '"]')) {
          indicators.querySelector('[data-slideinstanceid="' + slideinstanceid + '"]').classList.add('active-item');
        }
      }
    }
    addCarouselEvents() {
      var self = this;
      const carouselElem = getRoot().querySelector(_selectors.SELECTORS.carousel);
      const arrow = document.querySelector(_selectors.SELECTORS.nextArrow);
      (0, _jquery.default)(carouselElem).on('slide.bs.carousel', function (e) {
        if (e.relatedTarget === null) {
          return;
        }
        if (e.relatedTarget.nextElementSibling === null) {
          arrow.style.pointerEvents = 'none';
        } else {
          arrow.style.pointerEvents = 'auto';
        }
        if ('nextInstance' in arrow.dataset && arrow.dataset.nextInstance != '') {
          const nxtSlide = e.relatedTarget;
          if (nxtSlide.dataset.slideinstanceid == arrow.dataset.nextInstance) {
            arrow.classList.remove(_selectors.SELECTORS.others.nextSlideAvailable);
          }
        }
        var currentSlide = e.target.querySelector(_selectors.SELECTORS.activeSlide);
        var list = Array.from(currentSlide.querySelectorAll('audio')).filter(audio => !audio.paused);
        if (currentSlide.dataset.slideinstanceid in self.slides) {
          const slideInstance = self.slides[currentSlide.dataset.slideinstanceid];
          slideInstance.startTime = 0;
        }
        list.forEach(audio => {
          audio.classList.add('paused-not-activeslide');
          audio.pause();
        });
        const stoppedAudios = Array.from(e.relatedTarget.querySelectorAll('audio')).filter(audio => audio.classList.contains('paused-not-activeslide'));
        stoppedAudios.forEach(audio => {
          audio.classList.remove('paused-not-activeslide');
          audio.play();
        });
      });
      (0, _jquery.default)(carouselElem).on('slid.bs.carousel', function (e) {
        if (e.relatedTarget.dataset.slideinstanceid in self.slides) {
          const slideInstance = self.slides[e.relatedTarget.dataset.slideinstanceid];
          if (slideInstance.options.contentDisplayInitiated == false) {
            if (!slideInstance.element.classList.contains(_selectors.SELECTORS.others.autoResized)) {
              slideInstance.element.classList.add('active');
              slideInstance.autoFontResize();
            }
            slideInstance.initContentDisplay();
          }
          slideInstance.initTimeDuration();
          self.forceNextButton(e.relatedTarget);
        }
      });
      document.addEventListener('play', e => {
        var audio = e.target;
        audio.closest(_selectors.SELECTORS.listenItem).classList.add(_selectors.SELECTORS.others.audioPlayed);
      }, true);
      document.addEventListener('pause', e => {
        var audio = e.target;
        audio.closest(_selectors.SELECTORS.listenItem).classList.remove(_selectors.SELECTORS.others.audioPlayed);
      }, true);
      document.addEventListener('ended', e => {
        var audio = e.target;
        audio.closest(_selectors.SELECTORS.listenItem).classList.remove(_selectors.SELECTORS.others.audioPlayed);
      }, true);
    }
    forceNextButton(target) {
      const nextButton = document.querySelector(_selectors.SELECTORS.forceNext);
      const finishButton = document.querySelector(_selectors.SELECTORS.finishButton);
      const availableSlidesCount = getRoot().querySelectorAll('.carousel-item.slide-item')?.length;
      const options = this.slides[target.dataset.slideinstanceid]?.options;
      console.log('forceNextButton', target, availableSlidesCount, this.slides, target.dataset.slideinstanceid);
      const showFinishButton = () => {
        if (nextButton !== null) {
          nextButton.style.display = 'none';
          const finishButton = document.querySelector(_selectors.SELECTORS.finishButton);
          finishButton.style.display = 'block';
        }
      };
      const hideFinishButton = () => {
        if (finishButton !== null) {
          finishButton.style.display = 'none';
        }
      };
      const enableNextBtn = () => {
        if (nextButton !== null && options !== null) {
          nextButton.style.display = 'inherit';
          nextButton.style.pointerEvents = 'auto';
          nextButton.classList.remove('disabled');
          if (options.forcenext == 1) {
            nextButton.style.visibility = 'visible';
          }
        }
        hideFinishButton();
      };
      const disableNextBtn = () => {
        if (nextButton !== null && options !== null) {
          nextButton.style.pointerEvents = 'none';
          nextButton.classList.add('disabled');
          if (options.forcenext == 1) {
            nextButton.style.visibility = 'hidden';
          }
        }
        hideFinishButton();
      };
      const isLastSlide = () => {
        const slides = document.querySelectorAll('.carousel-item.slide-item');
        const activeSlide = document.querySelector('.carousel-item.slide-item.active');
        const lastSlideIndex = slides.length - 1;
        const activeSlideIndex = Array.from(slides).indexOf(activeSlide);
        return activeSlideIndex === lastSlideIndex;
      };
      if (target.nextElementSibling === null && availableSlidesCount < this.generaldata.slidescount) {
        disableNextBtn(options);
        console.log('disableNextBtn');
      } else if (availableSlidesCount >= this.generaldata.slidescount) {
        if (isLastSlide()) {
          showFinishButton();
        } else {
          enableNextBtn(options);
          console.log('enableNextBtn', 'isLastSlide');
        }
        console.log('showFinishButton');
      } else {
        enableNextBtn(options);
        console.log('enableNextBtn');
      }
    }
    autoMinHeight(slides) {
      var startHeight = 0;
      slides.forEach(slide => {
        slide.element.classList.add('active');
        var height = slide.getHeight();
        if (height > startHeight) {
          startHeight = height;
        }
        slide.element.classList.remove('active');
      });
      if (startHeight > 0) {
        document.querySelector(_selectors.SELECTORS.carouselInner).style.minHeight = startHeight + 'px';
      }
    }
    autoFontResize(slides) {
      slides.forEach(slide => {
        if (slide.options.supportsautofontsize) {
          console.log(slide, 'autoFontsize');
          slide.element.classList.add('active');
          slide.autoFontResize();
          slide.element.classList.remove('active');
        }
      });
      var firstElem = document.querySelector(_selectors.SELECTORS.slideItem);
      if (firstElem != null && typeof firstElem != undefined) {
        firstElem.classList.add('active');
        this.forceNextButton(firstElem);
      }
    }
  }
  const init = _exports.init = NctSlides.createInstance;
  var _default = _exports.default = {
    init: NctSlides.createInstance
  };
});