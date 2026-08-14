/**
 * Manage each individual slide.
 */

import $ from 'jquery';
import { forceListen } from "mod_slides/selectors";
import { SELECTORS as BaseSelectors } from "mod_slides/selectors";
import Ajax from 'core/ajax';
import * as Fragment from "core/fragment";
import * as Templates from 'core/templates';
import * as LoadingIcon from 'core/loadingicon';
import BaseSlide from 'mod_slides/slide';
import * as VideoJS from 'media_videojs/video-lazy';
import * as FilterEvents from 'core_filters/events';


const getRoot = document.querySelector(BaseSelectors.root);

const SELECTORS = {
    ...BaseSelectors, ...{
        flipCardBlock: '.flip-card-block'
    }
};


export default class Slide extends BaseSlide {

    constructor(element, nctslides, options) {
        super(element, nctslides, options, false);

        this.currentListenItem = null;
        this.timeOut = null;

        // A video should display on screen immediately — never behind a
        // "Click to display and read text" gate. Drop any click-to-view overlay
        // so the video player shows straight away (the learner can still press
        // play on the player itself if the browser blocks autoplay).
        if (this.clickToView) {
            this.clickToView.remove();
            this.clickToView = null;
        }
    }

    startViewContent() {

        var contents = this.element.querySelectorAll(SELECTORS.listenItem);
        this.contentAnimation(contents);

        // Play the video after animation.
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

            // Gate forward navigation while a watch requirement is active and not yet met, so
            // the learner cannot skip ahead (via Next or the pagination dots) before the min
            // time / full video has elapsed. Cleared in videoEvents when the requirement is
            // satisfied. Backward navigation stays available.
            var needsWatch = (this.options.forcelisten == forceListen.audio
                              || this.options.forcelisten == forceListen.duration);
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

        const currentIndex = 1; // Only one content itme available.

        // Forcelisten audio means for the video slide it represents the completion of the entire video.
        if (this.options.forcelisten == forceListen.audio) {
            // "Full video" requirement — unlock navigation once the video has ended.
            player.on('ended', function () {
                self.loadListenItem();
                self.unlockNav();
            });
        } else if (this.options.forcelisten == forceListen.duration && currentIndex in self.options.listenduration) {

            // "Minimum time" requirement — unlock as soon as the required seconds have played.
            var timeUpdateEvent = function (e) {
                if (player.currentTime() >= self.options.listenduration[currentIndex]) {
                    self.loadListenItem();
                    self.unlockNav();
                    player.off('timeupdate', timeUpdateEvent);
                }
            };

            player.on('timeupdate', timeUpdateEvent);

            // Safety net: if the video is shorter than the required minimum, watching it to
            // the end still satisfies the requirement (and prevents a stuck, never-unlocked slide).
            player.on('ended', function () {
                self.loadListenItem();
                self.unlockNav();
            });
        } else {
            // No watch requirement — navigation is never locked for this slide.
            self.loadListenItem();
        }

        const audios = this.element.querySelector(SELECTORS.listenItem).querySelectorAll('audio');
        if (audios.length <= 0) {
            return false;
        }

        const audio = audios[0];

        player.on('play', function() {
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

// Slide;
