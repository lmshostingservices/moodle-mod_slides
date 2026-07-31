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

        // Notifiy the video js plugin to update the audios.
        // FilterEvents.notifyFilterContentUpdated(element);
        console.log(element);

    }

    startViewContent() {

        var contents = this.element.querySelectorAll(SELECTORS.listenItem);
        this.contentAnimation(contents);

        // Play the video after animation.
        setTimeout(() => {
            const videoElement = contents[0].querySelector('.video-js');
            var player = VideoJS.getPlayer(videoElement.id);
            if (this.options.notCompleted || !this.options.completed) {
                player.play();
            }

            this.videoEvents(player);

        }, this.interval);

    }

    videoEvents(player) {
        const self = this;

        console.log(player);

        const currentIndex = 1; // Only one content itme available.

        // Forcelisten audio means for the video slide it represents the completion of the entire video.
        if (this.options.forcelisten == forceListen.audio) {
            player.on('ended', function () {
                self.loadListenItem();
            });
        } else if (this.options.forcelisten == forceListen.duration && currentIndex in self.options.listenduration) {

            var timeUpdateEvent = function (e) {
                if (player.currentTime() >= self.options.listenduration[currentIndex]) {
                    self.loadListenItem();
                    player.off('timeupdate', timeUpdateEvent);
                }
            };

            player.on('timeupdate', timeUpdateEvent);
        } else {
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
