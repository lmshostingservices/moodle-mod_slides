/**
 * Manage each individual video slide.
 */

define('slidetype_video/slide', [
    'jquery',
    'mod_slides/selectors',
    'core/ajax',
    'core/fragment',
    'core/templates',
    'core/loadingicon',
    'mod_slides/slide',
    'media_videojs/video-lazy',
    'core_filters/events'
], function($, Selectors, Ajax, Fragment, Templates, LoadingIcon, BaseSlide, VideoJS, FilterEvents) {

    var BaseSelectors = Selectors.SELECTORS;
    var forceListen = Selectors.forceListen;

    var SELECTORS = Object.assign({}, BaseSelectors, {
        flipCardBlock: '.flip-card-block'
    });

    class Slide extends BaseSlide {

        constructor(element, nctslides, options) {
            super(element, nctslides, options, false);

            this.currentListenItem = null;
            this.timeOut = null;

            console.log(element);
        }

        startViewContent() {
            var self = this;
            var contents = this.element.querySelectorAll(SELECTORS.listenItem);
            this.contentAnimation(contents);

            setTimeout(function() {
                var videoElement = contents[0].querySelector('.video-js');
                var player = VideoJS.getPlayer(videoElement.id);
                if (self.options.notCompleted || !self.options.completed) {
                    player.play();
                }
                self.videoEvents(player);
            }, this.interval);
        }

        videoEvents(player) {
            var self = this;

            console.log(player);

            var currentIndex = 1;

            if (this.options.forcelisten == forceListen.audio) {
                player.on('ended', function() {
                    self.loadListenItem();
                });
            } else if (this.options.forcelisten == forceListen.duration && currentIndex in self.options.listenduration) {
                var timeUpdateEvent = function(e) {
                    if (player.currentTime() >= self.options.listenduration[currentIndex]) {
                        self.loadListenItem();
                        player.off('timeupdate', timeUpdateEvent);
                    }
                };
                player.on('timeupdate', timeUpdateEvent);
            } else {
                self.loadListenItem();
            }

            var audios = this.element.querySelector(SELECTORS.listenItem).querySelectorAll('audio');
            if (audios.length <= 0) {
                return false;
            }

            var audio = audios[0];

            player.on('play', function() {
                if (!audio.ended) {
                    audio.play();
                }
                self.nctSlides.setCurrentAudio(audio);
                self.currentAudio = audio;
            });

            player.on('pause', function() {
                self.nctSlides.setCurrentAudio(null);
                self.currentAudio = null;
                audio.pause();
            });

            player.on('ended', function() {
                self.nctSlides.setCurrentAudio(null);
                self.currentAudio = null;
                audio.pause();
            });
        }

        updateNextItem(completedIndex) {
            this.element.querySelector(SELECTORS.listenItem + '[data-index="' + completedIndex + '"]').dataset.completed = true;
        }
    }

    return Slide;

});
