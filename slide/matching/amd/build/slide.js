define("slidetype_matching/slide", ["exports", "mod_slides/selectors", "mod_slides/slide"], function (_exports, _selectors, _slide) {
  "use strict";

  _exports.__esModule = true;
  _exports.default = void 0;
  _slide = _interopRequireDefault(_slide);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  const SELECTORS = {
    ..._selectors.SELECTORS,
    ...{
      matchingCardBlock: '.matching-card-block'
    }
  };
  class Slide extends _slide.default {
    constructor(element, nctslides, options) {
      super(element, nctslides, options);
      this.currentListenItem = null;
      this.timeOut = null;
    }
    startViewContent() {
      var contents = this.element.querySelectorAll(SELECTORS.listenItem);
      this.contentAnimation(contents);
      if (this.element.dataset.slidecompletion != "true") {
        this.forceListen();
      }
    }
    updateNextItem(completedIndex) {
      return false;
    }
    forceListen() {
      var self = this;
      const enableNextButton = () => {
        self.loadListenItem(1);
        this.enableCourseIndexMenu(false);
        self.nctSlides.setCurrentAudio(null);
      };
      this.element.addEventListener('slidesMatchingCompleted', function (e) {
        enableNextButton();
      });
    }
  }
  _exports.default = Slide;
});