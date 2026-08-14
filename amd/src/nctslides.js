
import $ from 'jquery';
import {SELECTORS, forceListen} from "mod_slides/selectors";
import Slide from "mod_slides/slide";
import * as Fragment from "core/fragment";
import * as Templates from 'core/templates';
import * as LoadingIcon from 'core/loadingicon';
import Notification from 'core/notification';
import * as Effects from 'mod_slides/effects';

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
        this.celebrated = false;
        Effects.init();
        this.scrollToSlides();
        this.initializeSlides();
    }

    /**
     * Fire the celebration (confetti + achievement chime) once, when the learner
     * reaches the final slide of the activity.
     *
     * @param {Element} activeEl the slide that just became active
     */
    maybeCelebrate(activeEl) {
        if (this.celebrated || !activeEl) {
            return;
        }
        const items = getRoot().querySelectorAll('.carousel-item.slide-item');
        const isLast = items.length > 0 && items[items.length - 1] === activeEl;
        const allAvailable = items.length >= (this.generaldata.slidescount || items.length);
        if (isLast && allAvailable) {
            this.celebrated = true;
            Effects.celebrate();
        }
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
                    // Slide-type modules are ES modules compiled to AMD, so the class is
                    // the module's default export. Unwrap it before constructing (matches
                    // the initial loadSlideInstances path) — otherwise `new customSlide`
                    // throws "e is not a constructor" and the slide after the video never
                    // renders. This on-demand path is used for every progressively-revealed
                    // slide (and for teacher preview, which now runs the full reveal flow).
                    const SlideClass = (customSlide && customSlide.default) ? customSlide.default : customSlide;
                    const slide = new SlideClass(element, self, options);
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

            // Guard: Moodle's core completion reactive component can throw
            // "Reactive components needs a main DOM element" if the completion UI isn't on
            // the page (e.g. some teacher/preview states). That must never abort our slide
            // flow or the whole activity renders blank.
            try {
                region.dispatchEvent(completionEvent);
            } catch (e) {
                window.console && window.console.warn && window.console.warn('mod_slides: completion toggle skipped', e);
            }
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

        // Self-contained carousel navigation (does NOT use Bootstrap's carousel JS).
        //
        // The controls in the template use Bootstrap 4 data-api attributes
        // (data-slide / data-target / href), but modern Moodle ships Bootstrap 5.
        // On the activity view no carousel click data-api is registered (neither
        // the BS4 jQuery one nor the BS5 native one), so the arrows never moved the
        // carousel. Worse, driving Bootstrap's own carousel API on this markup is
        // unreliable: after one transition the `slid` event often never fires, so
        // Bootstrap's internal `_isSliding` flag sticks and blocks all further
        // navigation (arrows dead after one move, cannot go back), AND the incoming
        // slide's content is never initialised (blank card). That single failure
        // produced every reported symptom.
        //
        // We therefore perform the transition ourselves and ALWAYS fire
        // slide/slid.bs.carousel so the existing handlers below still run (arrow
        // state, audio, and — critically — initContentDisplay on the incoming
        // slide). A guard plus a safety-net timeout guarantees the transition can
        // never get stuck. Event delegation preserves the existing gating: a
        // control disabled via pointer-events:none receives no click. The
        // .carousel-control-next / .carousel-control-prev classes cover both the
        // arrows and the forcenext/forceprev buttons; the finish button uses
        // .carousel-control-finish and keeps its default href to the next activity.
        var navBusy = false;

        const slideItems = () => Array.from(
            carouselElem.querySelectorAll('.carousel-item.slide-item'));

        const goToIndex = (targetIdx) => {
            if (navBusy) {
                return;
            }
            const items = slideItems();
            const current = items.findIndex((el) => el.classList.contains('active'));
            if (current < 0 || targetIdx === current || targetIdx < 0 || targetIdx >= items.length) {
                return;
            }

            // Force-watch gate: block FORWARD navigation (Next arrow OR pagination dots) while
            // the currently-active slide still requires watching — the min time / full video
            // set by the teacher has not yet elapsed. The lock flag lives on the slide element
            // (set/cleared by the video slide), so backward navigation is always allowed and the
            // gate follows the correct slide as the learner moves around.
            if (targetIdx > current && items[current] && items[current].dataset.forcelocked === '1') {
                return;
            }

            const dirNext = targetIdx > current;
            const fromEl = items[current];
            const toEl = items[targetIdx];

            // Cancellable slide event, so existing logic can veto/react.
            const slideEvent = $.Event('slide.bs.carousel', {
                relatedTarget: toEl,
                direction: dirNext ? 'left' : 'right',
                from: current,
                to: targetIdx
            });
            $(carouselElem).trigger(slideEvent);
            if (slideEvent.isDefaultPrevented()) {
                return;
            }

            navBusy = true;

            // Mirror Bootstrap 5's transition classes so the site's carousel CSS
            // animates the move (position the incoming slide, reflow, then slide).
            const startClass = dirNext ? 'carousel-item-next' : 'carousel-item-prev';
            const moveClass = dirNext ? 'carousel-item-start' : 'carousel-item-end';

            toEl.classList.add(startClass);
            void toEl.offsetHeight; // Force reflow so the animation runs.
            fromEl.classList.add(moveClass);
            toEl.classList.add(moveClass);

            var finished = false;
            const finish = () => {
                if (finished) {
                    return;
                }
                finished = true;
                fromEl.classList.remove('active', moveClass, startClass);
                toEl.classList.remove(startClass, moveClass);
                toEl.classList.add('active');
                navBusy = false;
                // Keep the disabled-nav styling in sync with the newly-active slide's lock state.
                var lockRoot = document.getElementById('mod-nct-slides');
                if (lockRoot) {
                    lockRoot.classList.toggle('slides-forcelocked', toEl.dataset.forcelocked === '1');
                }
                // Move the "current" highlight on the pagination dots to match.
                const dots = getRoot().querySelectorAll('.slides-pagination-dots .slides-dot');
                dots.forEach((dot, di) => {
                    dot.classList.toggle('current', di === targetIdx);
                    if (di <= targetIdx) {
                        dot.classList.add('available');
                    }
                });
                $(carouselElem).trigger($.Event('slid.bs.carousel', {
                    relatedTarget: toEl,
                    direction: dirNext ? 'left' : 'right',
                    from: current,
                    to: targetIdx
                }));
            };

            const onTransitionEnd = (e) => {
                if (e.target === toEl) {
                    toEl.removeEventListener('transitionend', onTransitionEnd);
                    finish();
                }
            };
            toEl.addEventListener('transitionend', onTransitionEnd);
            // Safety net: complete even if transitionend never fires (idempotent).
            setTimeout(finish, 700);
        };

        const activeIndex = () => slideItems().findIndex((el) => el.classList.contains('active'));

        $(carouselElem).on('click.nctslidesnav', '.carousel-control-next', function (e) {
            e.preventDefault();
            goToIndex(activeIndex() + 1);
        });

        $(carouselElem).on('click.nctslidesnav', '.carousel-control-prev', function (e) {
            e.preventDefault();
            goToIndex(activeIndex() - 1);
        });

        // Indicator dots: jump straight to the chosen slide.
        $(carouselElem).on('click.nctslidesnav',
            SELECTORS.indicators + ' [data-slide-to], ' + SELECTORS.indicators + ' [data-bs-slide-to], ' + SELECTORS.indicators + ' li',
            function (e) {
                e.preventDefault();
                var raw = this.getAttribute('data-slide-to');
                if (raw === null) {
                    raw = this.getAttribute('data-bs-slide-to');
                }
                var idx = raw !== null ? parseInt(raw, 10) : Array.prototype.indexOf.call(this.parentNode.children, this);
                if (!isNaN(idx)) {
                    goToIndex(idx);
                }
            });

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
            if (!currentSlide) {
                return;
            }
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

            // Celebrate reaching the final slide (confetti + achievement chime).
            self.maybeCelebrate(e.relatedTarget);
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

        // Guard: on the "Activity Finished" screen there may be no active slide-item,
        // so the caller can pass null. Reading target.dataset then threw
        // "Cannot read properties of undefined (reading 'dataset')".
        if (!target || !target.dataset) {
            return;
        }

        const nextButton = document.querySelector(SELECTORS.forceNext);
        const finishButton = document.querySelector(SELECTORS.finishButton);
        // List of available slides.
        const availableSlidesCount = getRoot().querySelectorAll('.carousel-item.slide-item')?.length;
        const options = this.slides[target.dataset.slideinstanceid]?.options; // Current active slide options.


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
