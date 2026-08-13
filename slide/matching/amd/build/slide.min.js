/**
 * Manage each individual matching slide.
 */

define('slidetype_matching/slide', [
    'mod_slides/selectors',
    'mod_slides/slide'
], function(Selectors, BaseSlide) {

    var BaseSelectors = Selectors.SELECTORS;
    var forceListen = Selectors.forceListen;

    var SELECTORS = Object.assign({}, BaseSelectors, {
        matchingCardBlock: '.matching-card-block'
    });

    class Slide extends BaseSlide {

        constructor(element, nctslides, options) {
            super(element, nctslides, options);

            this.currentListenItem = null;
            this.timeOut = null;
        }

        startViewContent() {
            var contents = this.element.querySelectorAll(SELECTORS.listenItem);
            this.contentAnimation(contents);

            if (this.element.dataset.slidecompletion != 'true') {
                this.forceListen();
            }
        }

        updateNextItem(completedIndex) {
            return false;
        }

        forceListen() {
            var self = this;

            var enableNextButton = function() {
                self.loadListenItem(1);
                self.enableCourseIndexMenu(false);
                self.nctSlides.setCurrentAudio(null);
            };

            this.element.addEventListener('slidesMatchingCompleted', function(e) {
                enableNextButton();
            });
        }
    }

    return Slide;

});
