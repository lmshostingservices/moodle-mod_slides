/**
 * Manage each individual flip slide.
 */

define('slidetype_flip/slide', [
    'jquery',
    'mod_slides/selectors',
    'core/ajax',
    'core/fragment',
    'core/templates',
    'core/loadingicon',
    'mod_slides/slide'
], function($, Selectors, Ajax, Fragment, Templates, LoadingIcon, BaseSlide) {

    var BaseSelectors = Selectors.SELECTORS;
    var forceListen = Selectors.forceListen;

    var SELECTORS = Object.assign({}, BaseSelectors, {
        flipCardBlock: '.flip-card-block',
        flipFeedback: '.flip-feedback-side',
        flipCompleted: 'flip-completed'
    });

    class Slide extends BaseSlide {

        constructor(element, nctslides, options) {
            super(element, nctslides, options);

            this.currentListenItem = null;
            this.timeOut = null;
        }

        initContentDisplay() {
            this.updateBoxHeight();
            super.initContentDisplay();
        }

        updateBoxHeight() {
            var items = this.element.querySelectorAll(SELECTORS.listenItem);
            var maxHeight = 0;

            var hiddenElements = this.element.querySelectorAll('.hide');
            hiddenElements.forEach(function(e) {
                e.classList.remove('hide');
                e.style.visibility = 'hidden';
            });

            items.forEach(function(item) {
                var flip = item.querySelector(SELECTORS.flipCardBlock);
                var itemHeight = item.clientHeight;
                if (itemHeight > maxHeight) {
                    maxHeight = itemHeight;
                }
                flip.parentNode.classList.add('child-flipped');
                flip.dataset.flipped = 'true';
                flip.classList.add('flipped');

                itemHeight = item.clientHeight;
                if (itemHeight > maxHeight) {
                    maxHeight = itemHeight;
                }
                flip.parentNode.classList.remove('child-flipped');
                flip.classList.remove('flipped');
                flip.dataset.flipped = 'false';
            });

            items.forEach(function(item) {
                item.style.height = maxHeight + 'px';
            });

            hiddenElements.forEach(function(e) {
                e.classList.add('hide');
                e.style.visibility = 'visible';
            });
        }

        startViewContent() {
            var self = this;
            var contents = this.element.querySelectorAll(SELECTORS.listenItem);
            this.contentAnimation(contents);

            Array.from(contents).forEach(function(content) {
                content.addEventListener('click', function(e) {
                    self.doFlip(e, self);
                });
            });

            if (this.options.completed) {
                var result = true;
                contents.forEach(function(e) {
                    if (e.dataset.completed != 'true') {
                        result = false;
                    }
                });
                if (result) {
                    this.loadListenItem();
                }
            }
        }

        doFlip(e, self) {
            var target = e.target.closest(SELECTORS.listenItem);
            var cardBlock = target.querySelector(SELECTORS.flipCardBlock);

            var anim = cardBlock.getAnimations ? cardBlock.getAnimations()[0] : undefined;
            if (cardBlock.dataset.flipped == 'true') {
                if (anim) {
                    anim.play();
                }
                cardBlock.classList.remove('flipped');
                cardBlock.parentNode.classList.remove('child-flipped');
                cardBlock.dataset.flipped = false;
            } else {
                if (anim !== undefined) {
                    anim.play();
                } else {
                    cardBlock.classList.add('animate__animated');
                    cardBlock.classList.add('animate__flipInX');
                }
                cardBlock.classList.add('flipped');
                cardBlock.parentNode.classList.add('child-flipped');
                cardBlock.dataset.flipped = true;
            }

            self.listentime = 0;
            self.startTime = 0;
            self.nctSlides.stopAudio();

            if (target.dataset.completed != 'true') {
                self.forceListen(target, true);
            }

            if (self.element.querySelector('.flipped') !== null) {
                self.element.classList.add('content-flipped');
            } else {
                self.element.classList.remove('content-flipped');
            }
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
            var currentIndex = listenElement.dataset.index || 0;

            this.currentListenItem = currentIndex;

            if (this.timeOut != 0) {
                clearTimeout(this.timeOut);
            }

            var enableNextButton = function() {
                self.loadListenItem(self.currentListenItem);
                self.enableCourseIndexMenu(false);
                self.element.querySelector(SELECTORS.listenItem + '[data-index="' + currentIndex + '"]').dataset.completed = true;
                self.element
                    .querySelector(SELECTORS.listenItem + '[data-index="' + currentIndex + '"] ' + SELECTORS.flipCardBlock)
                    .classList.add(SELECTORS.flipCompleted);
                self.nctSlides.setCurrentAudio(null);
            };

            if (self.options.forcelisten == forceListen.none) {
                enableNextButton();
            }

            var audios = listenElement.querySelectorAll('audio');
            var currentAudioIndex = 0;

            var confirmAudioCompletes = function() {
                var pendingAudio = 0;
                audios.forEach(function(audio) {
                    if (!audio.ended) {
                        pendingAudio++;
                    }
                });
                return pendingAudio == 0;
            };

            var playNextAudio = function() {
                if (currentAudioIndex < audios.length) {
                    self.nctSlides.setCurrentAudio(audios[currentAudioIndex]);
                    audios[currentAudioIndex].play();
                    document.removeEventListener('click', playNextAudio, false);
                }
            };

            var verifyTimeSpent = function() {
                var forceDuration = parseInt(self.options.listenduration[currentIndex]);
                if (self.getListenDuration() >= forceDuration) {
                    enableNextButton();
                } else {
                    self.timeOut = setTimeout(verifyTimeSpent, parseInt(forceDuration - self.getListenDuration()) * 1000);
                }
            };

            if (audios.length > 0) {
                var i = 0;
                audios.forEach(function(audio) {
                    audio.onended = function() {
                        console.log('audio-ended');
                        console.log('confirmAudioCompletes', confirmAudioCompletes());
                        audio.closest(SELECTORS.listenItem).classList.remove(SELECTORS.audioplayed);
                        if (confirmAudioCompletes()) {
                            if (self.options.forcelisten == forceListen.audio) {
                                enableNextButton();
                            }
                        } else {
                            currentAudioIndex++;
                            playNextAudio();
                        }
                    };
                    if (!i) {
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
                self.timeOut = setTimeout(verifyTimeSpent,
                    Math.floor(parseInt(self.options.listenduration[currentIndex]) * 1000)
                );
            }
        }

        resizeAdditionalContent() {
            super.resizeAdditionalContent();

            var self = this;
            this.element.querySelectorAll(SELECTORS.flipCardBlock).forEach(function(cardBlock) {
                var cardHeight = cardBlock.clientHeight;
                cardBlock.dataset.flipped = 'true';
                cardBlock.classList.add('flipped');
                cardBlock.style.maxHeight = cardHeight + 'px';

                var feedbackSide = cardBlock.querySelector(SELECTORS.flipFeedback);
                if (feedbackSide) {
                    var fontSize = parseFloat(window.getComputedStyle(feedbackSide).fontSize);
                    feedbackSide.style.fontSize = fontSize + 'px';

                    while (feedbackSide.scrollHeight <= cardHeight && fontSize < cardHeight && fontSize < 35) {
                        fontSize++;
                        feedbackSide.style.fontSize = fontSize + 'px';
                    }

                    while (feedbackSide.scrollHeight > cardHeight && fontSize > 9) {
                        fontSize--;
                        feedbackSide.style.fontSize = fontSize + 'px';
                    }
                }

                cardBlock.classList.remove('flipped');
                cardBlock.dataset.flipped = 'false';
            });
        }
    }

    return Slide;

});
