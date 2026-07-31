/**
 * Manage each individual slide.
 */

// import $ from 'jquery';
import {forceListen} from "mod_slides/selectors";
import {SELECTORS as BaseSelectors} from "mod_slides/selectors";
/* import Ajax from 'core/ajax';
import * as Fragment from "core/fragment";
import * as Templates from 'core/templates';
import * as LoadingIcon from 'core/loadingicon'; */
import BaseSlide from 'mod_slides/slide';

// const getRoot = document.querySelector(BaseSelectors.root);

const SELECTORS = {...BaseSelectors, ...{
    matchingCardBlock: '.matching-card-block'
}};


export default class Slide extends BaseSlide {

    constructor (element, nctslides, options) {
        super(element, nctslides, options);

        this.currentListenItem = null;
        this.timeOut = null;
    }

/*     addEventListeners() {

        this.startViewContent();


        // Disable the clicks on course index during the user listen.
        document.querySelector(SELECTORS.courseIndex).addEventListener('click', function (e) {
            if (self.disableMenu) {
                e.preventDefault();
            }
        });
    } */

    startViewContent() {


        var contents = this.element.querySelectorAll(SELECTORS.listenItem);
        this.contentAnimation(contents);

        // Its already completed
        if (this.element.dataset.slidecompletion != "true") {
            this.forceListen();
        }

    }

    updateNextItem(completedIndex) {
        return false;
        // console.log(SELECTORS.listenItem + '[data-index="' + completedIndex + '"]');
        // this.element.querySelector(SELECTORS.listenItem +'[data-index="'+completedIndex+'"]').dataset.completed = true;
    }

    forceListen() {

        var self = this;

        const enableNextButton = () => {
            // Remove the feedback listen class from response form to continue the form submissions.
            // element.classList.remove(SELECTORS.other.forceListen);
            self.loadListenItem(1);
            // Enable the course index.
            this.enableCourseIndexMenu(false);
            self.nctSlides.setCurrentAudio(null);
        };

        this.element.addEventListener('slidesMatchingCompleted', function(e) {
            enableNextButton();
        });
    }
}

// Slide;
