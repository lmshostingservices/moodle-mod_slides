define("slidetype_matching/ddmatching", [], function () {
  "use strict";

  /**
   * JavaScript to make drag-drop into text questions work.
   *
   * Some vocabulary to help understand this code:
   *
   * The question text contains 'drops' - blanks into which the 'drags', the missing
   * words, can be put.
   *
   * The thing that can be moved into the drops are called 'drags'. There may be
   * multiple copies of the 'same' drag which does not really cause problems.
   * Each drag has a 'choice' number which is the value set on the drop's hidden
   * input when this drag is placed in a drop.
   *
   * These may be in separate 'groups', distinguished by colour.
   * Things can only interact with other things in the same group.
   * The groups are numbered from 1.
   *
   * The place where a given drag started from is called its 'home'.
   *
   * @module     qtype_ddwtos/ddwtos
   * @copyright  2018 The Open University
   * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
   * @since      3.6
   */
  define(['jquery', 'core/dragdrop', 'core/key_codes', 'core_form/changechecker'], function ($, dragDrop, keys, FormChangeChecker) {
    "use strict";
    function DragDropToTextQuestion(containerId, readOnly, response) {
      this.containerId = containerId;
      this.questionAnswer = {};
      if (readOnly) {
        this.getRoot().addClass('slide_matching-readonly');
      }
      this.resizeAllDragsAndDrops();
      this.cloneDrags();
      this.positionDrags();
      this.response = response;
    }
    DragDropToTextQuestion.prototype.resizeAllDragsAndDrops = function () {
      var thisQ = this;
    };
    DragDropToTextQuestion.prototype.resizeAllDragsAndDropsInGroup = function (group) {
      console.log(group);
      var thisQ = this,
        dragHomes = this.getRoot().find('.draggrouphomes span.draghome'),
        maxWidth = 0,
        maxHeight = 0;
      dragHomes.each(function (i, drag) {
        console.log(drag);
        maxWidth = Math.max(maxWidth, Math.ceil(drag.offsetWidth));
        maxHeight = Math.max(maxHeight, Math.ceil(0 + drag.offsetHeight));
      });
      maxWidth += 8;
      maxHeight += 2;
      dragHomes.each(function (i, drag) {
        thisQ.setElementSize(drag, maxWidth, maxHeight);
      });
      this.getRoot().find('span.drop.group' + group).each(function (i, drop) {
        thisQ.setElementSize(drop, maxWidth, maxHeight);
      });
    };
    DragDropToTextQuestion.prototype.setElementSize = function (element, width, height) {
      $(element).width(width).height(height).css('lineHeight', height + 'px');
    };
    DragDropToTextQuestion.prototype.cloneDrags = function () {
      var thisQ = this;
      thisQ.getRoot().find('span.draghome').each(function (index, draghome) {
        var drag = $(draghome);
        var placeHolder = drag.clone();
        placeHolder.removeClass();
        placeHolder.addClass('draghome choice' + thisQ.getChoice(drag) + ' group' + thisQ.getGroup(drag) + ' dragplaceholder');
        drag.before(placeHolder);
      });
    };
    DragDropToTextQuestion.prototype.positionDrags = function () {
      var thisQ = this,
        root = this.getRoot();
      root.find('span.draghome').not('.dragplaceholder').each(function (i, dragNode) {
        var drag = $(dragNode),
          currentPlace = thisQ.getClassnameNumericSuffix(drag, 'inplace');
        drag.addClass('unplaced').removeClass('placed');
        drag.removeAttr('tabindex');
        if (currentPlace !== null) {
          drag.removeClass('inplace' + currentPlace);
        }
      });
      root.find('input.placeinput').each(function (i, inputNode) {
        var input = $(inputNode),
          choice = input.val(),
          place = thisQ.getPlace(input);
        var drop = root.find('.drop.place' + place),
          dropPosition = drop.offset();
        drop.data('prev-top', dropPosition.top).data('prev-left', dropPosition.left);
        if (choice === '0') {
          return;
        }
        var unplacedDrag = thisQ.getUnplacedChoice(thisQ.getGroup(input), choice);
        var hiddenDrag = thisQ.getDragClone(unplacedDrag);
        if (hiddenDrag.length) {
          if (unplacedDrag.hasClass('infinite')) {
            var noOfDrags = thisQ.noOfDropsInGroup(thisQ.getGroup(unplacedDrag));
            var cloneDrags = thisQ.getInfiniteDragClones(unplacedDrag, false);
            if (cloneDrags.length < noOfDrags) {
              var cloneDrag = unplacedDrag.clone();
              hiddenDrag.after(cloneDrag);
              questionManager.addEventHandlersToDrag(cloneDrag);
            } else {
              hiddenDrag.addClass('active');
            }
          } else {
            hiddenDrag.addClass('active');
          }
        }
        thisQ.sendDragToDrop(thisQ.getUnplacedChoice(thisQ.getGroup(input), choice), drop);
      });
      thisQ.questionAnswer = thisQ.getQuestionAnsweredValues();
    };
    DragDropToTextQuestion.prototype.getQuestionAnsweredValues = function () {
      let result = {};
      this.getRoot().find('input.placeinput').each((i, inputNode) => {
        result[inputNode.id] = inputNode.value;
      });
      return result;
    };
    DragDropToTextQuestion.prototype.isQuestionInteracted = function () {
      const oldAnswer = this.questionAnswer;
      const newAnswer = this.getQuestionAnsweredValues();
      let isInteracted = false;
      if (JSON.stringify(newAnswer) !== JSON.stringify(oldAnswer)) {
        isInteracted = true;
        return isInteracted;
      }
      Object.keys(newAnswer).forEach(key => {
        if (newAnswer[key] !== oldAnswer[key]) {
          isInteracted = true;
        }
      });
      return isInteracted;
    };
    DragDropToTextQuestion.prototype.handleDragStart = function (e) {
      var thisQ = this,
        drag = $(e.target).closest('.draghome');
      var info = dragDrop.prepare(e);
      if (!info.start || drag.hasClass('beingdragged')) {
        return;
      }
      drag.addClass('beingdragged');
      var currentPlace = this.getClassnameNumericSuffix(drag, 'inplace');
      if (currentPlace !== null) {
        this.setInputValue(currentPlace, 0);
        drag.removeClass('inplace' + currentPlace);
        var hiddenDrop = thisQ.getDrop(drag, currentPlace);
        if (hiddenDrop.length) {
          hiddenDrop.addClass('active');
          drag.offset(hiddenDrop.offset());
        }
      } else {
        var hiddenDrag = thisQ.getDragClone(drag);
        if (hiddenDrag.length) {
          if (drag.hasClass('infinite')) {
            var noOfDrags = this.noOfDropsInGroup(this.getGroup(drag));
            var cloneDrags = this.getInfiniteDragClones(drag, false);
            if (cloneDrags.length < noOfDrags) {
              var cloneDrag = drag.clone();
              cloneDrag.removeClass('beingdragged');
              hiddenDrag.after(cloneDrag);
              questionManager.addEventHandlersToDrag(cloneDrag);
              drag.offset(cloneDrag.offset());
            } else {
              hiddenDrag.addClass('active');
              drag.offset(hiddenDrag.offset());
            }
          } else {
            hiddenDrag.addClass('active');
            drag.offset(hiddenDrag.offset());
          }
        }
      }
      dragDrop.start(e, drag, function (x, y, drag) {
        thisQ.dragMove(x, y, drag);
      }, function (x, y, drag) {
        thisQ.dragEnd(x, y, drag);
      });
    };
    DragDropToTextQuestion.prototype.dragMove = function (pageX, pageY, drag) {
      var thisQ = this;
      this.getRoot().find('span.group' + this.getGroup(drag)).not('.beingdragged').each(function (i, dropNode) {
        var drop = $(dropNode);
        if (thisQ.isPointInDrop(pageX, pageY, drop)) {
          drop.addClass('valid-drag-over-drop');
        } else {
          drop.removeClass('valid-drag-over-drop');
        }
      });
    };
    DragDropToTextQuestion.prototype.dragEnd = function (pageX, pageY, drag) {
      var thisQ = this,
        root = this.getRoot(),
        placed = false;
      root.find('span.group' + this.getGroup(drag)).not('.beingdragged').each(function (i, dropNode) {
        if (placed) {
          return false;
        }
        const dropZone = $(dropNode);
        if (!thisQ.isPointInDrop(pageX, pageY, dropZone)) {
          return true;
        }
        let drop = null;
        if (dropZone.hasClass('placed')) {
          dropZone.removeClass('valid-drag-over-drop');
          drop = thisQ.getDrop(drag, thisQ.getClassnameNumericSuffix(dropZone, 'inplace'));
        } else {
          drop = dropZone;
        }
        drop.removeClass('valid-drag-over-drop');
        thisQ.sendDragToDrop(drag, drop);
        placed = true;
        return false;
      });
      if (!placed) {
        this.sendDragHome(drag);
      }
    };
    DragDropToTextQuestion.prototype.sendDragToDrop = function (drag, drop) {
      if (this.getPlace(drop) === null) {
        this.sendDragHome(drag);
        return;
      }
      var oldDrag = this.getCurrentDragInPlace(this.getPlace(drop));
      if (oldDrag.length !== 0) {
        var currentPlace = this.getClassnameNumericSuffix(oldDrag, 'inplace');
        if (this.hasDropSameDrag(currentPlace, drop, oldDrag, drag)) {
          this.sendDragHome(drag);
          return;
        }
        var hiddenDrop = this.getDrop(oldDrag, currentPlace);
        hiddenDrop.addClass('active');
        oldDrag.addClass('beingdragged');
        oldDrag.offset(hiddenDrop.offset());
        this.sendDragHome(oldDrag);
      }
      if (drag.length === 0) {
        this.setInputValue(this.getPlace(drop), 0);
        if (drop.data('isfocus')) {
          drop.focus();
        }
      } else {
        if (this.getClassnameNumericSuffix(drag, 'inplace')) {
          return;
        }
        this.setInputValue(this.getPlace(drop), drag.data('answercontentid'));
        var place = this.getPlace(drop);
        this.questionAnswer = this.getQuestionAnsweredValues();
        if (this.response[place] !== this.questionAnswer["place-" + place]) {
          this.sendDragHome(drag);
          return false;
        } else {
          this.verifyCompletion();
        }
        drag.removeClass('unplaced').addClass('placed inplace' + this.getPlace(drop));
        drag.attr('tabindex', 0);
        this.animateTo(drag, drop);
      }
    };
    DragDropToTextQuestion.prototype.hasDropSameDrag = function (currentPlace, drop, oldDrag, drag) {
      if (drag.hasClass('infinite')) {
        return drop.hasClass('place' + currentPlace) && this.getGroup(drag) === this.getGroup(drop) && this.getChoice(drag) === this.getChoice(oldDrag) && this.getGroup(drag) === this.getGroup(oldDrag);
      }
      return false;
    };
    DragDropToTextQuestion.prototype.sendDragHome = function (drag) {
      var currentPlace = this.getClassnameNumericSuffix(drag, 'inplace');
      if (currentPlace !== null) {
        drag.removeClass('inplace' + currentPlace);
      }
      drag.data('unplaced', true);
      this.verifyCompletion();
      this.animateTo(drag, this.getDragHome(this.getGroup(drag), this.getChoice(drag)));
    };
    DragDropToTextQuestion.prototype.handleKeyPress = function (e) {
      var drop = $(e.target).closest('.drop');
      if (drop.length === 0) {
        var placedDrag = $(e.target);
        var currentPlace = this.getClassnameNumericSuffix(placedDrag, 'inplace');
        if (currentPlace !== null) {
          drop = this.getDrop(placedDrag, currentPlace);
        }
      }
      var currentDrag = this.getCurrentDragInPlace(this.getPlace(drop)),
        nextDrag = $();
      switch (e.keyCode) {
        case keys.space:
        case keys.arrowRight:
        case keys.arrowDown:
          nextDrag = this.getNextDrag(this.getGroup(drop), currentDrag);
          break;
        case keys.arrowLeft:
        case keys.arrowUp:
          nextDrag = this.getPreviousDrag(this.getGroup(drop), currentDrag);
          break;
        case keys.escape:
          break;
        default:
          questionManager.isKeyboardNavigation = false;
          return;
      }
      if (nextDrag.length) {
        nextDrag.data('isfocus', true);
        nextDrag.addClass('beingdragged');
        var hiddenDrag = this.getDragClone(nextDrag);
        if (hiddenDrag.length) {
          if (nextDrag.hasClass('infinite')) {
            var noOfDrags = this.noOfDropsInGroup(this.getGroup(nextDrag));
            var cloneDrags = this.getInfiniteDragClones(nextDrag, false);
            if (cloneDrags.length < noOfDrags) {
              var cloneDrag = nextDrag.clone();
              cloneDrag.removeClass('beingdragged');
              cloneDrag.removeAttr('tabindex');
              hiddenDrag.after(cloneDrag);
              questionManager.addEventHandlersToDrag(cloneDrag);
              nextDrag.offset(cloneDrag.offset());
            } else {
              hiddenDrag.addClass('active');
              nextDrag.offset(hiddenDrag.offset());
            }
          } else {
            hiddenDrag.addClass('active');
            nextDrag.offset(hiddenDrag.offset());
          }
        }
      } else {
        drop.data('isfocus', true);
      }
      e.preventDefault();
      this.sendDragToDrop(nextDrag, drop);
    };
    DragDropToTextQuestion.prototype.getNextDrag = function (group, drag) {
      var choice,
        numChoices = this.noOfChoicesInGroup(group);
      if (drag.length === 0) {
        choice = 1;
      } else {
        choice = this.getChoice(drag) + 1;
      }
      var next = this.getUnplacedChoice(group, choice);
      while (next.length === 0 && choice < numChoices) {
        choice++;
        next = this.getUnplacedChoice(group, choice);
      }
      return next;
    };
    DragDropToTextQuestion.prototype.getPreviousDrag = function (group, drag) {
      var choice;
      if (drag.length === 0) {
        choice = this.noOfChoicesInGroup(group);
      } else {
        choice = this.getChoice(drag) - 1;
      }
      var previous = this.getUnplacedChoice(group, choice);
      while (previous.length === 0 && choice > 1) {
        choice--;
        previous = this.getUnplacedChoice(group, choice);
      }
      return previous;
    };
    DragDropToTextQuestion.prototype.animateTo = function (drag, target) {
      var currentPos = drag.offset(),
        targetPos = target.offset(),
        thisQ = this;
      M.util.js_pending('slidetype_matching-animate-' + thisQ.containerId);
      drag.animate({
        left: parseInt(drag.css('left')) + targetPos.left - currentPos.left,
        top: parseInt(drag.css('top')) + targetPos.top - currentPos.top
      }, {
        duration: 'fast',
        done: function () {
          $('body').trigger('slidetype_matching-dragmoved', [drag, target, thisQ]);
          M.util.js_complete('slidetype_matching-animate-' + thisQ.containerId);
        }
      });
    };
    DragDropToTextQuestion.prototype.isPointInDrop = function (pageX, pageY, drop) {
      var position = drop.offset();
      return pageX >= position.left && pageX < position.left + drop.width() && pageY >= position.top && pageY < position.top + drop.height();
    };
    DragDropToTextQuestion.prototype.setInputValue = function (place, choice) {
      this.getRoot().find('input.placeinput.place' + place).val(choice);
    };
    DragDropToTextQuestion.prototype.getRoot = function () {
      return $(document.getElementById(this.containerId));
    };
    DragDropToTextQuestion.prototype.getDragHome = function (group, choice) {
      if (!this.getRoot().find('.draghome.dragplaceholder.group' + group + '.choice' + choice).is(':visible')) {
        return this.getRoot().find('.draggrouphomes' + group + ' span.draghome' + '.choice' + choice + '.group' + group);
      }
      return this.getRoot().find('.draghome.dragplaceholder.group' + group + '.choice' + choice);
    };
    DragDropToTextQuestion.prototype.getUnplacedChoice = function (group, choice) {
      return this.getRoot().find('.draghome.group' + group + '.choice' + choice + '.unplaced').slice(0, 1);
    };
    DragDropToTextQuestion.prototype.getCurrentDragInPlace = function (place) {
      return this.getRoot().find('span.draghome.inplace' + place);
    };
    DragDropToTextQuestion.prototype.noOfDropsInGroup = function (group) {
      return this.getRoot().find('.drop.group' + group).length;
    };
    DragDropToTextQuestion.prototype.noOfChoicesInGroup = function (group) {
      return this.getRoot().find('.draghome.group' + group).length;
    };
    DragDropToTextQuestion.prototype.getClassnameNumericSuffix = function (node, prefix) {
      var classes = node.attr('class');
      if (classes !== undefined && classes !== '') {
        var classesArr = classes.split(' ');
        for (var index = 0; index < classesArr.length; index++) {
          var patt1 = new RegExp('^' + prefix + '([0-9])+$');
          if (patt1.test(classesArr[index])) {
            var patt2 = new RegExp('([0-9])+$');
            var match = patt2.exec(classesArr[index]);
            return Number(match[0]);
          }
        }
      }
      return null;
    };
    DragDropToTextQuestion.prototype.getChoice = function (drag) {
      return this.getClassnameNumericSuffix(drag, 'choice');
    };
    DragDropToTextQuestion.prototype.getGroup = function (node) {
      return this.getClassnameNumericSuffix(node, 'group');
    };
    DragDropToTextQuestion.prototype.getPlace = function (node) {
      return this.getClassnameNumericSuffix(node, 'place');
    };
    DragDropToTextQuestion.prototype.getDragClone = function (drag) {
      return this.getRoot().find('.draggrouphomes' + this.getGroup(drag) + ' span.draghome' + '.choice' + this.getChoice(drag) + '.group' + this.getGroup(drag) + '.dragplaceholder');
    };
    DragDropToTextQuestion.prototype.getInfiniteDragClones = function (drag, inHome) {
      if (inHome) {
        return this.getRoot().find('.draggrouphomes' + this.getGroup(drag) + ' span.draghome' + '.choice' + this.getChoice(drag) + '.group' + this.getGroup(drag) + '.infinite').not('.dragplaceholder');
      }
      return this.getRoot().find('span.draghome' + '.choice' + this.getChoice(drag) + '.group' + this.getGroup(drag) + '.infinite').not('.dragplaceholder');
    };
    DragDropToTextQuestion.prototype.getDrop = function (drag, currentPlace) {
      return this.getRoot().find('.drop.group' + this.getGroup(drag) + '.place' + currentPlace);
    };
    DragDropToTextQuestion.prototype.verifyCompletion = function () {
      const answers = this.getQuestionAnsweredValues();
      var matchs = [];
      Object.entries(this.response).forEach(v => {
        var i = v[0];
        if (answers['place-' + i] == v[1]) {
          matchs.push('place-' + i);
          this.getRoot()[0].querySelector('.slides-drop-block.place' + i + ' .slides-option').classList.add('matched');
        } else {
          this.getRoot()[0].querySelector('.slides-drop-block.place' + i + ' .slides-option').classList.remove('matched');
        }
      });
      if (matchs.length == Object.entries(answers).length) {
        this.getRoot()[0].dispatchEvent(new CustomEvent('slidesMatchingCompleted'));
      }
    };
    var questionManager = {
      eventHandlersInitialised: false,
      dragEventHandlersInitialised: {},
      isKeyboardNavigation: false,
      questions: {},
      init: function (containerId, readOnly, response) {
        questionManager.questions[containerId] = new DragDropToTextQuestion(containerId, readOnly, response);
        if (!questionManager.eventHandlersInitialised) {
          questionManager.setupEventHandlers();
          questionManager.eventHandlersInitialised = true;
        }
        if (!questionManager.dragEventHandlersInitialised.hasOwnProperty(containerId)) {
          questionManager.dragEventHandlersInitialised[containerId] = true;
          var questionContainer = document.getElementById(containerId);
          if (questionContainer.classList.contains('slide-item') && !questionContainer.classList.contains('qtype_ddwtos-readonly')) {
            questionManager.addEventHandlersToDrag($(questionContainer).find('span.draghome'));
          }
        }
      },
      setupEventHandlers: function () {
        $('body').on('keydown', '.slide-item[data-slidetype="matching"]:not(.qtype_ddwtos-readonly) span.drop', questionManager.handleKeyPress).on('keydown', '.slide-item[data-slidetype="matching"]:not(.qtype_ddwtos-readonly) span.draghome.placed:not(.beingdragged)', questionManager.handleKeyPress).on('slidetype_matching-dragmoved', questionManager.handleDragMoved);
      },
      addEventHandlersToDrag: function (element) {
        element.unbind('mousedown touchstart');
        element.on('mousedown touchstart', questionManager.handleDragStart);
      },
      handleDragStart: function (e) {
        e.preventDefault();
        var question = questionManager.getQuestionForEvent(e);
        if (question) {
          question.handleDragStart(e);
        }
      },
      handleKeyPress: function (e) {
        if (questionManager.isKeyboardNavigation) {
          return;
        }
        questionManager.isKeyboardNavigation = true;
        var question = questionManager.getQuestionForEvent(e);
        if (question) {
          question.handleKeyPress(e);
        }
      },
      getQuestionForEvent: function (e) {
        var containerId = $(e.currentTarget).closest('.slide-item[data-slidetype="matching"]').attr('id');
        return questionManager.questions[containerId];
      },
      handleDragMoved: function (e, drag, target, thisQ) {
        drag.removeClass('beingdragged');
        drag.css('top', '').css('left', '');
        target.after(drag);
        target.removeClass('active');
        if (typeof drag.data('unplaced') !== 'undefined' && drag.data('unplaced') === true) {
          drag.removeClass('placed').addClass('unplaced');
          drag.removeAttr('tabindex');
          drag.removeData('unplaced');
          if (drag.hasClass('infinite') && thisQ.getInfiniteDragClones(drag, true).length > 1) {
            thisQ.getInfiniteDragClones(drag, true).first().remove();
          }
        }
        if (typeof drag.data('isfocus') !== 'undefined' && drag.data('isfocus') === true) {
          drag.focus();
          drag.removeData('isfocus');
        }
        if (typeof target.data('isfocus') !== 'undefined' && target.data('isfocus') === true) {
          target.removeData('isfocus');
        }
        if (questionManager.isKeyboardNavigation) {
          questionManager.isKeyboardNavigation = false;
        }
        if (thisQ.isQuestionInteracted()) {
          thisQ.questionAnswer = thisQ.getQuestionAnsweredValues();
        }
      }
    };
    return {
      init: questionManager.init
    };
  });
});