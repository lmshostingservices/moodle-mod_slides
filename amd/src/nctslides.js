
import $ from 'jquery';
import {SELECTORS, forceListen} from "mod_slides/selectors";
import Slide from "mod_slides/slide";
import * as Fragment from "core/fragment";
import * as Templates from 'core/templates';
import * as LoadingIcon from 'core/loadingicon';
import {Carousel} from 'theme_boost/bootstrap/carousel';

const getRoot = () => document.querySelector(SELECTORS.root);

var CurrentAudio = null;

/**
 * NCT slides.
 */
class NctSlides {

    constructor(contextid, slideid, cmid) {
        this.contextID = contextid,
        this.slideID = slideid,
        this.cmid = cmid;
        this.slides = [];
        const slidedata = typeof slideOptions !== 'undefined' ? slideOptions : {};
        this.generaldata = slidedata['general'] ?? {};

        this.carouselElem = getRoot().querySelector(SELECTORS.carousel);
        this.scrollToSlides();
        this.initializeSlides();
    }

    initializeSlides() {
        var self = this;

        this.addCarouselEvents();

        // console.log(document.querySelectorAll(SELECTORS.slideItem));
        const slidedata = typeof slideOptions !== 'undefined' ? slideOptions : {};

        const loadSlideInstances = async () => {

            const module = async (element, index) => {
                const options = slidedata[element.dataset.slideinstanceid];
                if (options != undefined && 'customslidemodule' in options && options.customslidemodule != '') {
                    const mod = await new Promise((resolve, reject) => require([options.customslidemodule], resolve, reject));
                    const SlideClass = (mod && mod.default) ? mod.default : mod;
                    const slide = new SlideClass(element, self, options);
                    self.slides[element.dataset.slideinstanceid] = slide;
                } else {
                    var slide = new Slide(element, this, options || {});
                    self.slides[element.dataset.slideinstanceid] = slide;
                }

            }

            // Assuming SELECTORS.slide contains the selector for your slide elements.
            await Promise.all(Array.from(document.querySelectorAll(SELECTORS.slideItem)).map(module));

            return self.slides;
        };

        loadSlideInstances().then((slides) => {

            // Initiate the auto font resize.
            self.autoFontResize(slides);



            // Remove the onloading class from body, and make the content visible to users.
            document.body.classList.remove('nctslidesview-onloading');

            // Start the first slide.
            console.log(Object.values(slides));

            if (slides.length > 0) {
                // Object.values(slides)[0]?.initContentDisplay();
                if (this.generaldata.containerheight == '') {
                    self.autoMinHeight(slides);
                }

                var firstElem = document.querySelector(SELECTORS.slideItem);
                if (firstElem != null && typeof firstElem != undefined) {
                    firstElem.classList.add('active');
                    var firstSlide = slides[firstElem.dataset.slideinstanceid];
                    // this.forceNextButton(firstElem);
                    if (firstSlide != null) {

                        firstSlide.initContentDisplay();
                    }
                }
            }

        })

        // TODO: Disable the audio playing in the slide when move to next slide.
    }

    scrollToSlides() {
        // Scroll the content into view.
        var docElement = document.documentElement || document.querySelector('#page-wrapper');
        let nav = document.querySelector('nav.navbar');
        let elementTop = (window.scrollY || window.pageYOffset) + getRoot().getBoundingClientRect().top - 10
        var top = parseInt(elementTop - nav.clientHeight);
        // If the nav is not loadded then move the top of root.
        docElement.scrollTop = top != 0 ? top : elementTop;
    }

    /**
     * Load the next slide.
     *
     * @param {Slide} currentSlide
     */
    updateNextSlide(currentSlide) {
        var self = this;

        // Load next slide
        const promise = Fragment.loadFragment('mod_slides', 'load_next_slide', this.contextID, {
            currentslide: currentSlide.options.slideinstanceid, cmid: this.cmid});

        promise.then((html, js) => {
            var fakeDiv = document.createElement('div');
            fakeDiv.innerHTML = html;
            var slideinstanceid = fakeDiv.children[0].dataset.slideinstanceid;
            if (document.querySelector(SELECTORS.carouselInner + ' .carousel-item[data-slideinstanceid="'+ slideinstanceid +'"]' )) {
                return false;
            }

            const element = fakeDiv.children[0];
            Templates.appendNodeContents(document.querySelector(SELECTORS.carouselInner), element, '');
            Templates.runTemplateJS(js);

            var options = NextSlideData[slideinstanceid];
            if (options != undefined && 'customslidemodule' in options && options.customslidemodule != '') {
                require([options.customslidemodule], function (customSlide) {
                    const slide = new customSlide(element, self, options);
                    self.slides[element.dataset.slideinstanceid] = slide;
                });
            } else {
                var slide = new Slide(element, self, options || {});
                self.slides[element.dataset.slideinstanceid] = slide;
            }

            this.makeNextArrowActive(slideinstanceid);
            currentSlide.options.notCompleted = false;
            currentSlide.options.completed = true;
        }).catch(Notification.exception);
    }

    /**
     *  Load the next button.
     * @param {Slide} currentSlide
     */
    updateNextButtons(currentSlide) {

        var self = this;
        // Update the courseindex.
        const region = document.querySelector(SELECTORS.activityRegion);

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

        if (CurrentAudio !== null)
            CurrentAudio.pause();
    }

    makeNextArrowActive(slideinstanceid) {
        const arrow = document.querySelector(SELECTORS.nextArrow);

        this.forceNextButton(document.querySelector(SELECTORS.carousel + ' ' + SELECTORS.activeSlide));

        if (arrow !== null) {
            arrow.classList.add(SELECTORS.others.nextSlideAvailable);
            arrow.dataset.nextInstance = slideinstanceid;
            arrow.style.pointerEvents = 'auto';

            const indicators = document.querySelector(SELECTORS.indicators);
            if (indicators !== null && indicators.querySelector('[data-slideinstanceid="' + slideinstanceid + '"]')) {
                indicators.querySelector('[data-slideinstanceid="' + slideinstanceid + '"]').classList.add('active-item');
            }
        }
    }

    addCarouselEvents() {

        var self = this;

        const carouselElem = getRoot().querySelector(SELECTORS.carousel);
        const arrow = document.querySelector(SELECTORS.nextArrow);

        $(carouselElem).on('slide.bs.carousel', function(e) {

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
                    arrow.classList.remove(SELECTORS.others.nextSlideAvailable)
                }
            }

            // Handle the audio play and pause on during moving the slides.
            var currentSlide = e.target.querySelector(SELECTORS.activeSlide);
            var list = Array.from(currentSlide.querySelectorAll('audio')).filter((audio) => !audio.paused);

            if (currentSlide.dataset.slideinstanceid in self.slides) {
                const slideInstance = self.slides[currentSlide.dataset.slideinstanceid];
                slideInstance.startTime = 0;
            }
            list.forEach((audio) => {
                audio.classList.add('paused-not-activeslide');
                audio.pause();
            });

            const stoppedAudios = Array.from(e.relatedTarget.querySelectorAll('audio')).filter(audio => audio.classList.contains('paused-not-activeslide'));
            stoppedAudios.forEach(audio => {
                audio.classList.remove('paused-not-activeslide');
                audio.play();
            });

        });

        $(carouselElem).on('slid.bs.carousel', function (e) {
            if (e.relatedTarget.dataset.slideinstanceid in self.slides) {
                const slideInstance = self.slides[e.relatedTarget.dataset.slideinstanceid];

                if (slideInstance.options.contentDisplayInitiated == false) {
                    if (!slideInstance.element.classList.contains(SELECTORS.others.autoResized)) {
                        slideInstance.element.classList.add('active');
                        slideInstance.autoFontResize();
                    }
                    slideInstance.initContentDisplay();
                }
                slideInstance.initTimeDuration();

                self.forceNextButton(e.relatedTarget);
            }

        });

        // Add the highlight on content during playing audio.
        document.addEventListener('play', (e) => {
            var audio = e.target;
            audio.closest(SELECTORS.listenItem).classList.add(SELECTORS.others.audioPlayed);
        }, true);

        // Remove the highlight on content when audio paused.
        document.addEventListener('pause', (e) => {
            var audio = e.target;
            audio.closest(SELECTORS.listenItem).classList.remove(SELECTORS.others.audioPlayed);
        }, true);

        // Remove the highlight on content when audio ended.
        document.addEventListener('ended', (e) => {
            var audio = e.target;
            audio.closest(SELECTORS.listenItem).classList.remove(SELECTORS.others.audioPlayed);
        }, true);

    }

    forceNextButton(target) {

        const nextButton = document.querySelector(SELECTORS.forceNext);
        const finishButton = document.querySelector(SELECTORS.finishButton);
        // List of available slides.
        const availableSlidesCount = getRoot().querySelectorAll('.carousel-item.slide-item')?.length;
        const options = this.slides[target.dataset.slideinstanceid]?.options; // Current active slide options.

        console.log('forceNextButton', target, availableSlidesCount, this.slides, target.dataset.slideinstanceid);


        const showFinishButton = () => {
            if (nextButton !== null) {
                nextButton.style.display = 'none';
                const finishButton = document.querySelector(SELECTORS.finishButton);
                finishButton.style.display = 'block';
            }
        }

        const hideFinishButton = () => {
            if (finishButton !== null) {
                finishButton.style.display = 'none';
            }
        }

        const enableNextBtn = () => {

            if (nextButton !== null && options !== null) {
                nextButton.style.display = 'inherit';
                nextButton.style.pointerEvents = 'auto';
                // Disable the next button.
                nextButton.classList.remove('disabled');
                if (options.forcenext == 1) { // Show
                    nextButton.style.visibility = 'visible';
                }
            }

            hideFinishButton();
        }

        const disableNextBtn = () => {

            if (nextButton !== null && options !== null) {
                nextButton.style.pointerEvents = 'none';
                // Disable the next button.
                nextButton.classList.add('disabled');
                if (options.forcenext == 1) { // Hide.
                    nextButton.style.visibility = 'hidden';
                }
            }
            hideFinishButton();
        }

        const isLastSlide = () => {
            const slides = document.querySelectorAll('.carousel-item.slide-item');
            const activeSlide = document.querySelector('.carousel-item.slide-item.active');
            const lastSlideIndex = slides.length - 1;
            const activeSlideIndex = Array.from(slides).indexOf(activeSlide);

            return activeSlideIndex === lastSlideIndex;
        }


        // Check if the next slide is available or not and still there are some slides not available to user.
        if (target.nextElementSibling === null && availableSlidesCount < this.generaldata.slidescount) {
            disableNextBtn(options);
            console.log('disableNextBtn');

        } else if (availableSlidesCount >= this.generaldata.slidescount) { // If all slides are available to user.
            // Show finish button.
            if (isLastSlide()) {
                showFinishButton();
            } else {
                enableNextBtn(options);
                console.log('enableNextBtn', 'isLastSlide');

            }
            console.log('showFinishButton');

        } else {
            // Enable the next button.
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
            document.querySelector(SELECTORS.carouselInner).style.minHeight = startHeight + 'px';
            // slides.forEach(slide => { slide.element.style.height = startHeight + 'px'; });
        }
    }

    /**
     * Auto font resize.
     */
    autoFontResize(slides) {

        slides.forEach(slide => {

            if (slide.options.supportsautofontsize) {
                console.log(slide, 'autoFontsize');
                slide.element.classList.add('active');
                slide.autoFontResize();
                slide.element.classList.remove('active');
            }
        });

        var firstElem = document.querySelector(SELECTORS.slideItem);
        if (firstElem != null && typeof firstElem != undefined) {
            firstElem.classList.add('active');
            this.forceNextButton(firstElem);
        }
    }
}

export const init = NctSlides.createInstance;

export default {
    init: NctSlides.createInstance,
}
