/**
 * Manage each individual slide.
 */

import $ from 'jquery';
import { SELECTORS, forceListen } from "mod_slides/selectors";
import AJAX from 'core/ajax';
import * as Fragment from "core/fragment";
import * as Templates from 'core/templates';
import * as LoadingIcon from 'core/loadingicon';
import * as FilterEvents from "core_filters/events";
import selectors from './selectors';


const getRoot = document.querySelector(SELECTORS.root);


export default class Slide {

    constructor(element, nctSlides, options, filter=true) {

        // Only assign elements and event listners here.
        this.element = element;
        this.root = element;
        this.clickToView = this.root.querySelector(SELECTORS.clickToView);

        this.interval = 0;
        this.imgInterval = 0;

        this.nctSlides = nctSlides;
        this.contextID = nctSlides.contextID;
        this.cmid = nctSlides.cmid;

        this.options = options;

        this.disableMenu = false;

        this.listen = 0;
        this.currentAudio = null;

        this.options.contentDisplayInitiated = false;

        // Notify the filter events to update the content.
        if (filter) {
            FilterEvents.notifyFilterContentUpdated(this.element);
        }

    }

    initContentDisplay() {

        var self = this;

        var contentImages = this.element.querySelectorAll(SELECTORS.listenContentImage);
        contentImages.forEach((image) => {
            self.updateAnimation(image, this.interval);
            this.interval += 300; // Increate the interval for next animation contnets.
        });
        // Additional setup for individual slides can be done here.
        this.addEventListeners();
        console.log('eventlisteneradded', this.options);

        this.options.contentDisplayInitiated = true;

    }

    addEventListeners() {
        var self = this;

        if (this.element.querySelectorAll(SELECTORS.content + '.viewed') !== null) {
            this.contentAnimation(this.element.querySelectorAll(SELECTORS.listenItem + '.viewed'));
        }

        // Listen on click to view box to display the next contents.
        if (this.clickToView !== undefined && this.clickToView !== null) {
            // Update the animation.
            this.updateAnimation(this.clickToView, this.interval);

            this.clickToView.onclick = () => {
                this.element.querySelector('.' + SELECTORS.others.clickToViewAvailable)?.classList.remove(SELECTORS.others.clickToViewAvailable);
                this.clickToView.classList.add('animate__fadeOut');
                this.clickToView.remove();
                this.startViewContent();
            };

        } else {
            this.startViewContent();
        }

        // Disable the clicks on course index during the user listen.
        document.querySelector(SELECTORS.courseIndex).addEventListener('click', function (e) {
            if (self.disableMenu) {
                e.preventDefault();
            }
        });

        // Visibility changed.
        // User switched to different browser. Stop the timespent and playing audio.
        var stopped = false;
        document.addEventListener('visibilitychange', () => {

            if (document.hidden) {

                if (self.options.forcelisten == forceListen.duration) {
                    self.listen = self.getListenDuration();
                    self.startTime = 0;
                }

                if (self.options.forcelisten == forceListen.audio && self.currentAudio) {
                    self.currentAudio.pause();
                    stopped = true;
                }
            } else {
                self.startTime = Math.round(Date.now() / 1000);
                if (stopped && self.currentAudio !== null) {
                    self.currentAudio.play();
                    stopped = false; // Reset the flag to false, start played the paused audio.
                }
            }
        });
    }

    updateAnimation(card, interval = 0) {
        setTimeout(() => {
            var animateElem = card;
            animateElem.classList.add(animateElem.dataset.animation || 'no-animation');
            // animateElem.style.display = 'block';
            animateElem.classList.remove('hide');
            animateElem.style.visibility = 'visible';

            // Remove the empty p tags.
            animateElem.querySelectorAll('p').forEach((e) => e.textContent.trim() == '' ? e.remove() : '');
        }, interval);
    }

    startViewContent() {
        var contents = this.element.querySelectorAll(SELECTORS.listenItem);
        this.contentAnimation(contents);
        this.forceListen(true);
    }

    contentAnimation(contents) {
        var self = this;
        Array.from(contents).map((i) => {
            self.updateAnimation(i, this.interval);
            this.interval += 300;
        });
    }

    updateAnimation(card, interval = 0) {

        setTimeout(() => {

            var animateElem = card;
            animateElem.classList.add(animateElem.dataset.animation || 'no-animation');
            // animateElem.style.display = 'block';
            animateElem.classList.remove('hide');
            animateElem.style.visibility = 'visible';

            // Remove the empty p tags.
            animateElem.querySelectorAll('p').forEach((e) => e.textContent.trim() == '' ? e.remove() : '');

        }, interval);
    }

    enableCourseIndexMenu() {
        this.disableMenu = false;
    }

    disableCourseIndexMenu() {
        this.disableMenu = true;
    }

/*     disableSlideArrows() {
        const arrow = document.querySelector(SELECTORS.nextArrow);
        arrow.style.pointerEvents = 'none';
    }

    enableSlideArrows() {
        const arrow = document.querySelector(SELECTORS.nextArrow);
        arrow.style.pointerEvents = 'auto';
    } */

    initTimeDuration() {
        this.startTime = Math.round(Date.now() / 1000);
    }

    getListenDuration() {
        var now = Math.round(Date.now() / 1000);
        return this.startTime ? this.listen + Math.round(now - this.startTime) : this.listen;
    }

    /**
     * Update the listen item completion.
     *
     * Load the next item of the current slide if available, otherwise load the next slide.
     * If this is the last slide, then load next btn.
     *
     * @param {*} contentIndex
     * @returns
     */
    loadListenItem(contentIndex = 0) {
        const self = this;

        // No need to load the viewed slide data content.
        if (parseInt(this.element.dataset.viewed)) {
            return true;
        }

        // Disable the slides arrows.
        // this.disableSlideArrows();

        const contentCount = contentIndex || this.element.querySelectorAll(SELECTORS.listenItem).length;

        const completiondata = {
            cmid: this.cmid,
            slideinstanceid: this.options.slideinstanceid,
            slidetype: this.options.slidetype,
            listenitem: contentCount
        };

        var promises = AJAX.call([{
            methodname: 'mod_slides_update_slidecompletion',
            args: completiondata
        }]);

        promises[0].then((result) => {

            if (!result.status) {
                return false;
            }

            if (result.updateitem) {
                self.updateNextItem(contentCount);
            } else if (result.updatenextslide) {
                this.nctSlides.updateNextSlide(self);
            } else if (result.updatenextbuttons) {
                this.nctSlides.updateNextButtons(self);
            }

            // Enable the slides arrows.
            // self.enableSlideArrows();

            return true;
        })
    }

    updateNextItem(currentIndex) {

        var self = this;

        // this.disableSlideArrows();

        // Loading icon.
        var spinnerParent = getRoot.querySelector(SELECTORS.spinnerParent) || self.element;
        var spinner = LoadingIcon.addIconToContainerWithPromise(spinnerParent);

        Fragment.loadFragment('mod_slides', 'load_next_listeitem', self.contextID, {

            slideinstanceid: this.options.slideinstanceid,
            index: currentIndex || this.options.currentIndex,
            sesskey: M.cfg.sesskey,
            cmid: this.cmid,

        }).then((html, js) => {

            spinner.resolve();

            if (html == '' || html === null) {
                return true;
            }

            // this.enableSlideArrows();

            var jsonHtml = new DOMParser().parseFromString(html, 'text/html').body.firstElementChild;
            // Templates.runTemplateJS(js);
            var newcontent = document.createElement('div');

            // /'[data-slideinstanceid="' + self.options.slideinstanceid + '"] ' +
            $(jsonHtml).find(SELECTORS.listenItem).each((key, content) => {
                var index = content.dataset.index;
                // Remove the animation from already shown content.
                if (this.element.querySelector(SELECTORS.listenItem + '[data-index="' + index + '"]') !== null) {
                    content.dataset.animation = 'no-animation';
                } else {
                    newcontent.append(content);
                }
            });

            self.element.querySelectorAll(SELECTORS.currentItem).forEach((elem) => {
                elem.dataset.currentitem = false;
            });

            // Append the ajax content to the page.
            Templates.appendNodeContents(this.element.querySelector(SELECTORS.listenItems), newcontent.innerHTML, js);

            self.options = CurrentSlideData[this.options.slideinstanceid];
            // TODO: Update tabs navigation.
            // self.updateIndex();
            // options.currentIndex = self.options.currentIndex;
            // self.options.viewed++;

            let contents = this.element.querySelectorAll(SELECTORS.listenItem);

            if (contents !== null) {
                Array.from(contents).map((i) => self.updateAnimation(i));
            }

            self.startTime = Math.round(Date.now() / 1000);
            // self.listen = 0;

            self.forceListen(true);

        }).catch(Notification.exception);
    }

    /**
     * Initiate the list options for this slide.
     *
     * Based on the selected listen options, listen to the audio or the time duration.
     *
     * @returns
     */
    forceListen(continuePlay = false) {

        const element = this.element;
        const self = this;

        console.log('viewed');
        if (element.classList.contains('viewed')) {
            return self.loadListenItem();
        }


        // Disable the course index.
        this.disableCourseIndexMenu(true);

        const enableNextButton = () => {

            // Remove the feedback listen class from response form to continue the form submissions.
            // element.classList.remove(SELECTORS.other.forceListen);
            if (self.currentAudio !== null) {
                self.currentAudio.pause();
            }

            self.loadListenItem();
            // console.log('loadlistenitem');

            // Enable the course index.
            this.enableCourseIndexMenu(false);
            self.nctSlides.setCurrentAudio(null);
            self.currentAudio = null;
        };


        const currentItem = element.querySelector(SELECTORS.currentItem);
        console.log(currentItem);
        if (currentItem === null) {
            enableNextButton();
            return false;
        }
        const currentIndex = currentItem.dataset.index;


        // If is audio, then force the users to watch all the audio in the responses.
        const audios = element.querySelector(SELECTORS.currentItem).querySelectorAll('audio');
        let currentAudioIndex = 0;

        const confirmAudioCompletes = () => {
            var pendingAudio = 0;
            audios.forEach(audio => audio.ended || pendingAudio++);
            return pendingAudio == 0;
        };

        const playNextAudio = () => {
            if (currentAudioIndex < audios.length) {
                self.nctSlides.setCurrentAudio(audios[currentAudioIndex]);
                self.currentAudio = audios[currentAudioIndex];
                audios[currentAudioIndex].play();
                document.removeEventListener('click', playNextAudio, false);
            }
        };

        const verifyTimeSpent = () => {
            var forceDuration = parseInt(self.options.listenduration[currentIndex]);
            console.log(self.getListenDuration(), 'duration');

            if (self.getListenDuration() >= forceDuration) {
                enableNextButton();
            } else {
                setTimeout(verifyTimeSpent, parseInt(forceDuration - self.getListenDuration()) * 1000);
            }
        };

        if (self.options.initaudio == true) {

            if (audios.length > 0) {
                var i = 0;
                audios.forEach(audio => {

                    audio.onended = () => {
                        // Remve the class when the audio is finished.
                        audio.closest(SELECTORS.listenItem).classList.remove(SELECTORS.audioplayed);
                        // Verify all the audios are completed.
                        if (confirmAudioCompletes()) {

                            if (self.options.forcelisten == forceListen.audio) {
                                // Enable the next button to proceed with attempt.
                                enableNextButton();
                            }
                        } else {
                            currentAudioIndex++;
                            playNextAudio();
                        }
                    };

                    if (!i) {
                        // Timeout to make the audio skil respond the audio play/pause icons.
                        if (continuePlay) {
                            playNextAudio();
                        } else {
                            document.addEventListener('click', playNextAudio, false);
                        }
                    }
                    i++;
                });
            } else {
                if (self.options.forcelisten === forceListen.audio) {
                    enableNextButton();
                }
            }
        }

        if (self.options.forcelisten === forceListen.duration && currentIndex in self.options.listenduration) {
            // Feedback Text.
            console.log('timespaen', self.options.listenduration[currentIndex]);

            self.startTime = Math.round(Date.now() / 1000);

            setTimeout(verifyTimeSpent,
                Math.floor(parseInt(self.options.listenduration[currentIndex]) * 1000)
            );
            // TODO: add progress bar.
        }

        if (self.options.forcelisten == forceListen.none) {
            console.log(forceListen, 'none');
            enableNextButton();
        }
    }

    getHeight() {

        const self = this;

        // Font auto resizing.
        self.element.classList.add('font-auto-resizing');

        var hiddenElements = self.element.querySelectorAll('.hide');
        hiddenElements.forEach(e => e.classList.remove('hide'));

        // Hide the click to view.
        var clickToView = self.element.querySelector(SELECTORS.clickToView)
        if (clickToView !== undefined && clickToView !== null) {
            clickToView.classList.add('hide');
        }

        var height = self.element.parentNode.clientHeight;

        console.log(self.element.parentNode, 'parentNode');
        console.log(height);
        // throw new Error('Not implemented');
        // Hide the hidden elements.
        hiddenElements.forEach(e => e.classList.add('hide'));
        if (clickToView !== undefined && clickToView !== null) {
            clickToView.classList.remove('hide');
        }
        self.element.classList.remove('font-auto-resizing');

        return height;
    }

    resizeAdditionalContent() {
        // Resize the additional content if you need in subplugins.
    }


    autoFontResize() {

        const self = this;

        if (this.nctSlides.generaldata.autotextsize != true) {
            return null;
        }

        /**
         * Resize the text to fit the container height.
         *
         * @param {*} value
         */
        const resizeTextToFit = () => {

            // Completion size. when the size is calculated for completion base size.
            // Hide the audio bars.

            /**
             * Max Height.
             * @returns
             */
            const maxHeight = () => {
                const textElement = self.element.parentNode;
                var mt = window.getComputedStyle(textElement).paddingTop;
                var mb = window.getComputedStyle(textElement).paddingBottom;

                return textElement.clientHeight - parseInt(mt) - parseInt(mb);
            }

            // Start with a small font size.
            const fontSize = () => self.element
                ? parseFloat(window.getComputedStyle(self.element).fontSize) : 15;

            // Element Height.
            const elementHeight = () => self.element.clientHeight;

            // Font auto resizing.
            self.element.classList.add('font-auto-resizing');

            var hiddenElements = self.element.querySelectorAll('.hide');
            hiddenElements.forEach(e => e.classList.remove('hide'));

            // Hide the click to view.
            var clickToView = self.element.querySelector(SELECTORS.clickToView)
            if (clickToView !== undefined && clickToView !== null) {
                clickToView.classList.add('hide');
            }

            var i = 1; // To update the conten size mostly, used 1;
            var listenElements = self.element.querySelectorAll(SELECTORS.listenItem);

            if (maxHeight() - elementHeight() > 5) {
                while (elementHeight() < maxHeight()) {
                    i++;
                    if (i >= 500) {
                        break;
                    }

                    var fontsize = parseFloat(fontSize()) + 0.1;
                    listenElements.forEach(e => e.style.fontSize = fontsize + 'px');

                    if (fontsize > 30) {
                        break;
                    }

                    self.element.style.fontSize = fontsize + 'px';
                    // Increasing the font size moves the text elemtent high, then remove the udpate size.
                    if (elementHeight() > maxHeight()) {
                        fontsize = parseFloat(fontsize) - 0.1;
                        self.element.style.fontSize = fontsize + 'px';
                        listenElements.forEach(e => e.style.fontSize = fontsize + 'px');
                        break;
                    }
                }

            } else if (elementHeight() - maxHeight() > 5) {

                while (elementHeight() > maxHeight() && fontSize() > 10) {

                    i++;
                    if (i >= 500) {
                        break;
                    }
                    var fontsize = parseFloat(fontSize()) - 0.1;
                    // No need to reduce the fontsize more than 10.
                    if (fontsize <= 10) {
                        break;
                    }
                    self.element.style.fontSize = fontsize + 'px';
                    console.log(fontsize, 'fontsize');

                    console.log(elementHeight(), 'elementHeight-de');
                    console.log(maxHeight(), 'maxHeight-de');


                    // Add the auto find text size each listen item in this element.
                    listenElements.forEach(e => e.style.fontSize = fontsize + 'px');
                    // Increasing the font size moves the text elemtent high, then remove the udpate size.
                    if (elementHeight() < maxHeight()) {
                        fontsize = parseFloat(fontsize) - 0.1;
                        self.element.style.fontSize = fontsize + 'px';
                        // Add the auto find text size each listen item in this element.
                        listenElements.forEach(e => e.style.fontSize = fontsize + 'px');
                        break;
                    }
                }
            }

            // Hide the hidden elements.
            hiddenElements.forEach(e => e.classList.add('hide'));
            if (clickToView !== undefined && clickToView !== null) {
                clickToView.classList.remove('hide');
            }

            var fontsize = parseFloat(fontSize());
            this.addFontSizeStyle(fontsize);
            this.resizeAdditionalContent();

            return parseFloat(fontSize());
        };

        // Remove the contents not available to the user.
        const removeHiddenContents = () => {
            self.element.querySelectorAll(SELECTORS.autoFontRemoveContent)?.forEach(e=>e.remove());
        };

        resizeTextToFit();
        // Remove the auto find text size class from tab.
        // removeFindAutoClass();
        removeHiddenContents();
        self.element.classList.remove('font-auto-resizing');
        this.element.classList.add(SELECTORS.others.autoResized);

    }

    addFontSizeStyle = (fontsize) => {
        var style = document.createElement('style');
        style.innerHTML = `#${this.element.id}.${SELECTORS.others.autoResized} ${SELECTORS.listenItem} {font-size: ${fontsize}px !important;}`;
        document.head.appendChild(style);
    }
}
