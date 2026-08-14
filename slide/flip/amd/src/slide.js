/**
 * Manage each individual slide.
 */

import $ from 'jquery';
import {forceListen} from "mod_slides/selectors";
import {SELECTORS as BaseSelectors} from "mod_slides/selectors";
import Ajax from 'core/ajax';
import * as Fragment from "core/fragment";
import * as Templates from 'core/templates';
import * as LoadingIcon from 'core/loadingicon';
import BaseSlide from 'mod_slides/slide';

const getRoot = document.querySelector(BaseSelectors.root);

const SELECTORS = {...BaseSelectors, ...{
    flipCardBlock: '.flip-card-block',
    flipFeedback: '.flip-feedback-side',
    flipCompleted: 'flip-completed'
}};


export default class Slide extends BaseSlide {

    constructor (element, nctslides, options) {
        super(element, nctslides, options);

        this.currentListenItem = null;
        this.timeOut = null;
    }

    initContentDisplay() {
        this.updateBoxHeight();
        super.initContentDisplay();
        // Fit every answer to its card once layout has settled, and again on window resize,
        // so long answers shrink to stay inside the card (above the flip hint) instead of
        // overflowing into a scrollbox or covering the "Tap to flip back" text.
        var self = this;
        this.scheduleFitFaces();
        if (!this._fitResizeBound) {
            this._fitResizeBound = function() { self.scheduleFitFaces(); };
            window.addEventListener('resize', this._fitResizeBound);
        }
    }

    scheduleFitFaces() {
        var self = this;
        // Two rAFs: let the grid size its rows before we measure available height.
        window.requestAnimationFrame(function() {
            window.requestAnimationFrame(function() {
                self.fitFaces();
            });
        });
    }

    /**
     * Shrink each flip face's text until it fits the card's content area (above the hint zone).
     * Runs on both faces, including the back face which is normally display:none — it is made
     * measurable (position:absolute so nothing shifts) only for the measurement.
     */
    fitFaces() {
        var MAX_FS = 15.5, MIN_FS = 9;
        var faces = this.element.querySelectorAll('.flip-content-block');
        faces.forEach(function(face) {
            var content = face.querySelector('.content');
            if (!content) {
                return;
            }
            var cs = window.getComputedStyle(face);
            var restore = null;
            if (cs.display === 'none') {
                restore = {display: face.style.display, visibility: face.style.visibility};
                face.style.display = 'flex';
                face.style.visibility = 'hidden';
                cs = window.getComputedStyle(face);
            }
            var padTop = parseFloat(cs.paddingTop) || 20;
            var padBottom = parseFloat(cs.paddingBottom) || 40;
            var avail = face.clientHeight - padTop - padBottom;
            if (avail > 24) {
                var fs = MAX_FS;
                content.style.fontSize = fs + 'px';
                var guard = 0;
                while (content.scrollHeight > avail && fs > MIN_FS && guard < 80) {
                    fs -= 0.5;
                    content.style.fontSize = fs + 'px';
                    guard++;
                }
            }
            if (restore) {
                face.style.display = restore.display;
                face.style.visibility = restore.visibility;
            }
        });
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
            // Ensure the card is flipped.
            flip.parentNode.classList.add('child-flipped');
            flip.dataset.flipped = 'true';
            flip.classList.add('flipped');

            var itemHeight = item.clientHeight;
            if (itemHeight > maxHeight) {
                maxHeight = itemHeight;
            }
            // Return the flipped card to the original state.
            // Return the flipped card to the original state.
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
        Array.from(contents).forEach((content) => {
            content.addEventListener('click', (e) => this.doFlip(e, self));
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
        var cardBlock = target.querySelector(SELECTORS.flipCardBlock)

        if (window.NctSlidesFX) {
            window.NctSlidesFX.play('flip');
        }

        // Simple class toggle — the stylesheet's `transition: transform` on .flip-card-block
        // performs the smooth 3D rotateY flip. (The old code added Animate.css .flipInX, whose
        // rotateX keyframe + backface-visibility:visible fought the 3D flip, so cards did not
        // flip cleanly on the live site.)
        if (cardBlock.dataset.flipped == 'true') {
            cardBlock.classList.remove('flipped');
            cardBlock.parentNode.classList.remove('child-flipped');
            cardBlock.dataset.flipped = 'false';
        } else {
            cardBlock.classList.add('flipped');
            cardBlock.parentNode.classList.add('child-flipped');
            cardBlock.dataset.flipped = 'true';
        }

        self.listentime = 0;
        self.startTime = 0;
        self.nctSlides.stopAudio();


        // Its already completed
        if (target.dataset.completed != "true") {
            self.forceListen(target, true);
        }

        if (self.element.querySelector('.flipped') !== null) {
            self.element.classList.add('content-flipped');
        } else {
            self.element.classList.remove('content-flipped');
        }

        // Re-fit now the face is on screen (belt-and-braces; init already fits hidden faces).
        self.scheduleFitFaces();
    }

    updateNextItem(completedIndex) {
        this.element
            .querySelector(SELECTORS.listenItem + '[data-index="' + completedIndex + '"]').dataset.completed = true;
        this.element
            .querySelector(SELECTORS.listenItem + '[data-index="' + completedIndex + '"] ' + SELECTORS.flipCardBlock)
            .classList.add(SELECTORS.flipCompleted);
    }

    forceListen(listenElement, continuePlay) {

        var self = this;

        this.startTime = Math.round(Date.now() / 1000);
        const currentIndex = listenElement.dataset.index || 0;

        this.currentListenItem = currentIndex;

        this.timeOut == 0 || clearTimeout(this.timeOut);

        const enableNextButton = () => {
            // Remove the feedback listen class from response form to continue the form submissions.
            self.loadListenItem(this.currentListenItem);
            // Enable the course index.
            this.enableCourseIndexMenu(false);

            this.element.querySelector(SELECTORS.listenItem + '[data-index="' + currentIndex + '"]').dataset.completed = true;
            this.element
                .querySelector(SELECTORS.listenItem + '[data-index="' + currentIndex + '"] ' + SELECTORS.flipCardBlock)
                .classList.add(SELECTORS.flipCompleted);

            self.nctSlides.setCurrentAudio(null);
        };

        // Force listen is none.
        if (self.options.forcelisten == forceListen.none) {
            enableNextButton();
        }

        // If is audio, then force the users to watch all the audio in the responses.
        const audios = listenElement.querySelectorAll('audio');
        let currentAudioIndex = 0;

        const confirmAudioCompletes = () => {
            var pendingAudio = 0;
            audios.forEach(audio => audio.ended || pendingAudio++);
            return pendingAudio == 0;
        };

        const playNextAudio = () => {
            if (currentAudioIndex < audios.length) {
                // TODO: switch to event observer for stop audio.
                // Set the current listen audio to slides module to control the tab swtiches.
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
                    // Remove the class when the audio is finished.
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
                    playNextAudio();
                }

                i++;
            });
        } else {
            if (self.options.forcelisten === forceListen.audio) {
                enableNextButton();
            }
        }

        if (self.options.forcelisten === forceListen.duration && currentIndex in self.options.listenduration) {
            // Feedback Text.
            this.timeOut = setTimeout(verifyTimeSpent,
                Math.floor(parseInt(self.options.listenduration[currentIndex]) * 1000)
            );
        }
    }



    resizeAdditionalContent() {
        super.resizeAdditionalContent();
        // The old routine grew the back-face font up to 35px to fill the card, which is what
        // made long answers overflow and cover the hint. Replaced by fitFaces(), which shrinks
        // each answer to fit the area above the hint zone.
        this.scheduleFitFaces();
    }
}
// Slide;
