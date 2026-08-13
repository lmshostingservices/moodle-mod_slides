define("mod_slides/slide", ["exports", "jquery", "mod_slides/selectors", "core/ajax", "core/fragment", "core/templates", "core/loadingicon", "core_filters/events"], function (_exports, _jquery, _selectors, _ajax, Fragment, Templates, LoadingIcon, FilterEvents) {
  "use strict";

  _exports.__esModule = true;
  _exports.default = void 0;
  _jquery = _interopRequireDefault(_jquery);
  _selectors = _interopRequireWildcard(_selectors);
  _ajax = _interopRequireDefault(_ajax);
  Fragment = _interopRequireWildcard(Fragment);
  Templates = _interopRequireWildcard(Templates);
  LoadingIcon = _interopRequireWildcard(LoadingIcon);
  FilterEvents = _interopRequireWildcard(FilterEvents);
  function _interopRequireWildcard(e, t) { if ("function" == typeof WeakMap) var r = new WeakMap(), n = new WeakMap(); return (_interopRequireWildcard = function (e, t) { if (!t && e && e.__esModule) return e; var o, i, f = { __proto__: null, default: e }; if (null === e || "object" != typeof e && "function" != typeof e) return f; if (o = t ? n : r) { if (o.has(e)) return o.get(e); o.set(e, f); } for (const t in e) "default" !== t && {}.hasOwnProperty.call(e, t) && ((i = (o = Object.defineProperty) && Object.getOwnPropertyDescriptor(e, t)) && (i.get || i.set) ? o(f, t, i) : f[t] = e[t]); return f; })(e, t); }
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  const getRoot = document.querySelector(_selectors.SELECTORS.root);
  class Slide {
    constructor(element, nctSlides, options, filter = true) {
      this.element = element;
      this.root = element;
      this.clickToView = this.root.querySelector(_selectors.SELECTORS.clickToView);
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
      if (filter) {
        FilterEvents.notifyFilterContentUpdated(this.element);
      }
    }
    initContentDisplay() {
      var self = this;
      var contentImages = this.element.querySelectorAll(_selectors.SELECTORS.listenContentImage);
      contentImages.forEach(image => {
        self.updateAnimation(image, this.interval);
        this.interval += 300;
      });
      this.addEventListeners();
      console.log('eventlisteneradded', this.options);
      this.options.contentDisplayInitiated = true;
    }
    addEventListeners() {
      var self = this;
      if (this.element.querySelectorAll(_selectors.SELECTORS.content + '.viewed') !== null) {
        this.contentAnimation(this.element.querySelectorAll(_selectors.SELECTORS.listenItem + '.viewed'));
      }
      if (this.clickToView !== undefined && this.clickToView !== null) {
        this.updateAnimation(this.clickToView, this.interval);
        this.clickToView.onclick = () => {
          this.element.querySelector('.' + _selectors.SELECTORS.others.clickToViewAvailable)?.classList.remove(_selectors.SELECTORS.others.clickToViewAvailable);
          this.clickToView.classList.add('animate__fadeOut');
          this.clickToView.remove();
          this.startViewContent();
        };
      } else {
        this.startViewContent();
      }
      document.querySelector(_selectors.SELECTORS.courseIndex).addEventListener('click', function (e) {
        if (self.disableMenu) {
          e.preventDefault();
        }
      });
      var stopped = false;
      document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
          if (self.options.forcelisten == _selectors.forceListen.duration) {
            self.listen = self.getListenDuration();
            self.startTime = 0;
          }
          if (self.options.forcelisten == _selectors.forceListen.audio && self.currentAudio) {
            self.currentAudio.pause();
            stopped = true;
          }
        } else {
          self.startTime = Math.round(Date.now() / 1000);
          if (stopped && self.currentAudio !== null) {
            self.currentAudio.play();
            stopped = false;
          }
        }
      });
    }
    updateAnimation(card, interval = 0) {
      setTimeout(() => {
        var animateElem = card;
        animateElem.classList.add(animateElem.dataset.animation || 'no-animation');
        animateElem.classList.remove('hide');
        animateElem.style.visibility = 'visible';
        animateElem.querySelectorAll('p').forEach(e => e.textContent.trim() == '' ? e.remove() : '');
      }, interval);
    }
    startViewContent() {
      var contents = this.element.querySelectorAll(_selectors.SELECTORS.listenItem);
      this.contentAnimation(contents);
      this.forceListen(true);
    }
    contentAnimation(contents) {
      var self = this;
      Array.from(contents).map(i => {
        self.updateAnimation(i, this.interval);
        this.interval += 300;
      });
    }
    updateAnimation(card, interval = 0) {
      setTimeout(() => {
        var animateElem = card;
        animateElem.classList.add(animateElem.dataset.animation || 'no-animation');
        animateElem.classList.remove('hide');
        animateElem.style.visibility = 'visible';
        animateElem.querySelectorAll('p').forEach(e => e.textContent.trim() == '' ? e.remove() : '');
      }, interval);
    }
    enableCourseIndexMenu() {
      this.disableMenu = false;
    }
    disableCourseIndexMenu() {
      this.disableMenu = true;
    }
    initTimeDuration() {
      this.startTime = Math.round(Date.now() / 1000);
    }
    getListenDuration() {
      var now = Math.round(Date.now() / 1000);
      return this.startTime ? this.listen + Math.round(now - this.startTime) : this.listen;
    }
    loadListenItem(contentIndex = 0) {
      const self = this;
      if (parseInt(this.element.dataset.viewed)) {
        return true;
      }
      const contentCount = contentIndex || this.element.querySelectorAll(_selectors.SELECTORS.listenItem).length;
      const completiondata = {
        cmid: this.cmid,
        slideinstanceid: this.options.slideinstanceid,
        slidetype: this.options.slidetype,
        listenitem: contentCount
      };
      var promises = _ajax.default.call([{
        methodname: 'mod_slides_update_slidecompletion',
        args: completiondata
      }]);
      promises[0].then(result => {
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
        return true;
      });
    }
    updateNextItem(currentIndex) {
      var self = this;
      var spinnerParent = getRoot.querySelector(_selectors.SELECTORS.spinnerParent) || self.element;
      var spinner = LoadingIcon.addIconToContainerWithPromise(spinnerParent);
      Fragment.loadFragment('mod_slides', 'load_next_listeitem', self.contextID, {
        slideinstanceid: this.options.slideinstanceid,
        index: currentIndex || this.options.currentIndex,
        sesskey: M.cfg.sesskey,
        cmid: this.cmid
      }).then((html, js) => {
        spinner.resolve();
        if (html == '' || html === null) {
          return true;
        }
        var jsonHtml = new DOMParser().parseFromString(html, 'text/html').body.firstElementChild;
        var newcontent = document.createElement('div');
        (0, _jquery.default)(jsonHtml).find(_selectors.SELECTORS.listenItem).each((key, content) => {
          var index = content.dataset.index;
          if (this.element.querySelector(_selectors.SELECTORS.listenItem + '[data-index="' + index + '"]') !== null) {
            content.dataset.animation = 'no-animation';
          } else {
            newcontent.append(content);
          }
        });
        self.element.querySelectorAll(_selectors.SELECTORS.currentItem).forEach(elem => {
          elem.dataset.currentitem = false;
        });
        Templates.appendNodeContents(this.element.querySelector(_selectors.SELECTORS.listenItems), newcontent.innerHTML, js);
        self.options = CurrentSlideData[this.options.slideinstanceid];
        let contents = this.element.querySelectorAll(_selectors.SELECTORS.listenItem);
        if (contents !== null) {
          Array.from(contents).map(i => self.updateAnimation(i));
        }
        self.startTime = Math.round(Date.now() / 1000);
        self.forceListen(true);
      }).catch(Notification.exception);
    }
    forceListen(continuePlay = false) {
      const element = this.element;
      const self = this;
      console.log('viewed');
      if (element.classList.contains('viewed')) {
        return self.loadListenItem();
      }
      this.disableCourseIndexMenu(true);
      const enableNextButton = () => {
        if (self.currentAudio !== null) {
          self.currentAudio.pause();
        }
        self.loadListenItem();
        this.enableCourseIndexMenu(false);
        self.nctSlides.setCurrentAudio(null);
        self.currentAudio = null;
      };
      const currentItem = element.querySelector(_selectors.SELECTORS.currentItem);
      console.log(currentItem);
      if (currentItem === null) {
        enableNextButton();
        return false;
      }
      const currentIndex = currentItem.dataset.index;
      const audios = element.querySelector(_selectors.SELECTORS.currentItem).querySelectorAll('audio');
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
              audio.closest(_selectors.SELECTORS.listenItem).classList.remove(_selectors.SELECTORS.audioplayed);
              if (confirmAudioCompletes()) {
                if (self.options.forcelisten == _selectors.forceListen.audio) {
                  enableNextButton();
                }
              } else {
                currentAudioIndex++;
                playNextAudio();
              }
            };
            if (!i) {
              if (continuePlay) {
                playNextAudio();
              } else {
                document.addEventListener('click', playNextAudio, false);
              }
            }
            i++;
          });
        } else {
          if (self.options.forcelisten === _selectors.forceListen.audio) {
            enableNextButton();
          }
        }
      }
      if (self.options.forcelisten === _selectors.forceListen.duration && currentIndex in self.options.listenduration) {
        console.log('timespaen', self.options.listenduration[currentIndex]);
        self.startTime = Math.round(Date.now() / 1000);
        setTimeout(verifyTimeSpent, Math.floor(parseInt(self.options.listenduration[currentIndex]) * 1000));
      }
      if (self.options.forcelisten == _selectors.forceListen.none) {
        console.log(_selectors.forceListen, 'none');
        enableNextButton();
      }
    }
    getHeight() {
      const self = this;
      self.element.classList.add('font-auto-resizing');
      var hiddenElements = self.element.querySelectorAll('.hide');
      hiddenElements.forEach(e => e.classList.remove('hide'));
      var clickToView = self.element.querySelector(_selectors.SELECTORS.clickToView);
      if (clickToView !== undefined && clickToView !== null) {
        clickToView.classList.add('hide');
      }
      var height = self.element.parentNode.clientHeight;
      console.log(self.element.parentNode, 'parentNode');
      console.log(height);
      hiddenElements.forEach(e => e.classList.add('hide'));
      if (clickToView !== undefined && clickToView !== null) {
        clickToView.classList.remove('hide');
      }
      self.element.classList.remove('font-auto-resizing');
      return height;
    }
    resizeAdditionalContent() {}
    autoFontResize() {
      const self = this;
      if (this.nctSlides.generaldata.autotextsize != true) {
        return null;
      }
      const resizeTextToFit = () => {
        const maxHeight = () => {
          const textElement = self.element.parentNode;
          var mt = window.getComputedStyle(textElement).paddingTop;
          var mb = window.getComputedStyle(textElement).paddingBottom;
          return textElement.clientHeight - parseInt(mt) - parseInt(mb);
        };
        const fontSize = () => self.element ? parseFloat(window.getComputedStyle(self.element).fontSize) : 15;
        const elementHeight = () => self.element.clientHeight;
        self.element.classList.add('font-auto-resizing');
        var hiddenElements = self.element.querySelectorAll('.hide');
        hiddenElements.forEach(e => e.classList.remove('hide'));
        var clickToView = self.element.querySelector(_selectors.SELECTORS.clickToView);
        if (clickToView !== undefined && clickToView !== null) {
          clickToView.classList.add('hide');
        }
        var i = 1;
        var listenElements = self.element.querySelectorAll(_selectors.SELECTORS.listenItem);
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
            if (fontsize <= 10) {
              break;
            }
            self.element.style.fontSize = fontsize + 'px';
            console.log(fontsize, 'fontsize');
            console.log(elementHeight(), 'elementHeight-de');
            console.log(maxHeight(), 'maxHeight-de');
            listenElements.forEach(e => e.style.fontSize = fontsize + 'px');
            if (elementHeight() < maxHeight()) {
              fontsize = parseFloat(fontsize) - 0.1;
              self.element.style.fontSize = fontsize + 'px';
              listenElements.forEach(e => e.style.fontSize = fontsize + 'px');
              break;
            }
          }
        }
        hiddenElements.forEach(e => e.classList.add('hide'));
        if (clickToView !== undefined && clickToView !== null) {
          clickToView.classList.remove('hide');
        }
        var fontsize = parseFloat(fontSize());
        this.addFontSizeStyle(fontsize);
        this.resizeAdditionalContent();
        return parseFloat(fontSize());
      };
      const removeHiddenContents = () => {
        self.element.querySelectorAll(_selectors.SELECTORS.autoFontRemoveContent)?.forEach(e => e.remove());
      };
      resizeTextToFit();
      removeHiddenContents();
      self.element.classList.remove('font-auto-resizing');
      this.element.classList.add(_selectors.SELECTORS.others.autoResized);
    }
    addFontSizeStyle = fontsize => {
      var style = document.createElement('style');
      style.innerHTML = `#${this.element.id}.${_selectors.SELECTORS.others.autoResized} ${_selectors.SELECTORS.listenItem} {font-size: ${fontsize}px !important;}`;
      document.head.appendChild(style);
    };
  }
  _exports.default = Slide;
});